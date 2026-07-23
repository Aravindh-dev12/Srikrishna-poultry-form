const API_BASE = process.env.VS_API_BASE || "http://localhost/common/Vijayanth";
const WS_URL = process.env.VS_WS_URL || "wss://vinobasolar.scadahub.in:5001";
const STORE_TOKEN = process.env.VS_STORE_TOKEN || "";

const PLANTS = [
  { plant_id: "vijayanth", unit_id: "via-1mw" },
  { plant_id: "krishna", unit_id: "via-3mw" },
];

const unitToPlant = Object.fromEntries(PLANTS.map((p) => [p.unit_id, p.plant_id]));
const knownDevicesByUnit = Object.fromEntries(PLANTS.map((p) => [p.unit_id, new Set()]));
const liveWriteSeen = new Map();

function num(value) {
  const n = parseFloat(value);
  return Number.isFinite(n) ? n : 0;
}

function powerKw(value) {
  const n = num(value);
  return Math.abs(n) > 10000 ? n / 1000 : n;
}

function sourceTime(message) {
  return message.time || message.timestamp || message.ts || message.recorded_at || message.created_at || "";
}

function todayLocal() {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${y}-${m}-${day}`;
}

function canonicalDeviceName(name, fallback = "Device") {
  return (name || fallback).toString();
}

function canonicalInverterName(name) {
  const match = (name || "").toString().match(/\d+/);
  return match ? `INVERTER${parseInt(match[0], 10)}` : canonicalDeviceName(name, "INVERTER").toUpperCase().replace(/\s+/g, "");
}

async function postStore(body) {
  if (!STORE_TOKEN) throw new Error("VS_STORE_TOKEN is required for collector storage");
  const res = await fetch(`${API_BASE}/api_store.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json", "X-Store-Token": STORE_TOKEN },
    body: JSON.stringify(body),
  });
  if (!res.ok) throw new Error(`api_store.php HTTP ${res.status}`);
  const json = await res.json().catch(() => ({}));
  if (json.status && json.status !== "success") {
    throw new Error(json.message || JSON.stringify(json));
  }
  return json;
}

function readAlarmFaultState(values, alertHistory = []) {
  let hasAlarm = false;
  let hasFault = false;
  let faultCode = "";
  let workState = "";
  let statusText = "";

  for (const key of Object.keys(values || {})) {
    const lower = key.toLowerCase();
    const raw = values[key];
    const text = raw === null || raw === undefined ? "" : raw.toString().trim().toLowerCase();
    const numeric = parseFloat(raw);
    const active = text !== "" && text !== "0" && text !== "null" && text !== "normal" && text !== "false";

    if (/fault\s*code|faultcode/i.test(key)) {
      faultCode = raw === null || raw === undefined ? "" : raw.toString();
      if (Number.isFinite(numeric) ? numeric > 0 : active) hasFault = true;
      continue;
    }

    if (/fault|trip|error/i.test(lower) && active) hasFault = true;
    if (/alarm|warning|warn/i.test(lower) && active) hasAlarm = true;

    if (/work\s*state/i.test(key)) {
      workState = raw === null || raw === undefined ? "" : raw.toString();
      if (workState) statusText = `Work state ${workState}`;
    } else if (/status|state/i.test(lower) && text) {
      statusText = raw.toString();
      if (/fault|trip|error/i.test(text)) hasFault = true;
      if (/alarm|warning|warn/i.test(text)) hasAlarm = true;
    }
  }

  if (Array.isArray(alertHistory) && alertHistory.length) {
    hasAlarm = true;
    const latestAlert = alertHistory[alertHistory.length - 1];
    const alertText = typeof latestAlert === "string" ? latestAlert : JSON.stringify(latestAlert);
    statusText = statusText || alertText.slice(0, 100);
    if (/fault|trip|error/i.test(alertText)) hasFault = true;
  }

  return { hasAlarm, hasFault, faultCode, workState, statusText };
}

function parseStrings(values) {
  const strings = [];
  for (const key of Object.keys(values || {})) {
    const match = key.match(/^string\s*(\d+)\s*current$/i);
    if (!match) continue;
    const n = parseInt(match[1], 10);
    const voltKey = Object.keys(values).find((k) => new RegExp(`^string\\s*${n}\\s*volt(age)?$`, "i").test(k));
    const curr = num(values[key]);
    const volt = voltKey ? num(values[voltKey]) : 0;
    strings.push({ n, curr, volt, active: curr > 0.5 });
  }
  strings.sort((a, b) => a.n - b.n);
  return strings;
}

function inverterPayload(values, message = {}) {
  const strings = parseStrings(values);
  const dcPower = num(values["Total DC power"]);
  const activePower = num(values["Total active power"]);
  let eff = num(values.Efficiency || values.efficiency);
  if (!eff && dcPower > 0 && activePower > 0) eff = Math.min((activePower / (dcPower / 1000)) * 100, 100);

  const dailyGen = num(values["Daily power yields"]);
  const totalGen = num(values["Total power yields precise"] ?? values["Total power yields"]);
  const alertHistory = Array.isArray(message.alertHistory) ? message.alertHistory : [];
  const alarmFault = readAlarmFaultState(values, alertHistory);

  return {
    power: activePower,
    reactive: num(values["Total reactive power"]),
    pf: num(values["Power factor"]),
    vac_ab: num(values.RYvolatge ?? values["RY voltage"] ?? values["V12 (RY)"]),
    vac_bc: num(values["YB voltage"] ?? values["V23 (YB)"]),
    vac_ca: num(values["BR voltage"] ?? values["V31 (BR)"]),
    freq: num(values["Grid frequency precise"] ?? values["Grid frequency"]),
    i_a: num(values["RY current"]),
    i_b: num(values["YB current"]),
    i_c: num(values["BR current"]),
    eff,
    amb: num(values["Internal temperature"]),
    dailyGen,
    totalGen,
    dailyCO2: dailyGen * 0.8,
    totalCO2: totalGen * 0.8,
    dailyHrs: num(values["Daily running time"]),
    totalHrs: num(values["Total running time"]),
    activeStr: strings.filter((s) => s.active).length,
    totalStr: strings.length,
    strings,
    alertHistory,
    ...alarmFault,
  };
}

function vcbPayload(values) {
  return {
    power_3phase_kw: powerKw(values["3 Phase Active Power"] ?? values["3 phase active power"]),
    frequency_hz: num(values["Frequency (Hz)"] ?? values["Grid frequency"] ?? values.Frequency),
    voltage_r_v: num(values["R Phase-N Voltage"]),
    voltage_y_v: num(values["Y Phase-N Voltage"]),
    voltage_b_v: num(values["B Phase-N Voltage"]),
    voltage_ry_v: num(values["V12 (RY)"]),
    voltage_yb_v: num(values["V23 (YB)"]),
    voltage_br_v: num(values["V31 (BR)"]),
    current_r_a: num(values["L1 (R)"]),
    current_y_a: num(values["L2 (Y)"]),
    current_b_a: num(values["L3 (B)"]),
    power_r_kw: powerKw(values["Active Power R"]),
    power_y_kw: powerKw(values["Active Power Y"]),
    power_b_kw: powerKw(values["Active Power B"]),
    pf_q1: num(values["Q1 PF"]),
    pf_q2: num(values["Q2 PF"]),
    pf_q3: num(values["Q3 PF"]),
    vthd_r: num(values["Voltage THD R"]),
    vthd_y: num(values["Voltage THD Y"]),
    vthd_b: num(values["Voltage THD B"]),
    active_export_kwh: num(values["Active Total Export"]),
    active_import_kwh: num(values["Active Total Import"]),
    reactive_import_kvar: num(values["Reactive Import (Q1+Q2)"]),
    reactive_export_kvar: num(values["Reactive Export (Q3+Q4)"]),
    today_energy_kwh: num(values["vcb-today"] ?? values["Today Energy"] ?? values["Day Energy"]),
  };
}

function transformerPayload(device, values) {
  const lower = (device || "").toLowerCase();
  const findValue = (patterns) => {
    for (const key of Object.keys(values || {})) {
      const kl = key.toLowerCase();
      if (patterns.some((rx) => rx.test(kl))) return num(values[key]);
    }
    return 0;
  };
  const genericTemp = findValue([/temp/, /temperature/]);
  return {
    oil_temp_c: findValue([/oil.*temp/, /temp.*oil/, /^oil-temp$/]) || (lower.includes("oil") ? genericTemp : 0),
    winding_temp_c: findValue([/winding.*temp/, /temp.*winding/, /^winding-temp$/]) || (lower.includes("winding") ? genericTemp : 0),
  };
}

function shouldStoreLive(key) {
  const now = Date.now();
  const last = liveWriteSeen.get(key) || 0;
  if (now - last < 2000) return false;
  liveWriteSeen.set(key, now);
  return true;
}

async function handleMessage(message) {
  const plant_id = unitToPlant[message.unit_id];
  if (!plant_id) return;

  if (message.type === "device_list") {
    const devices = Array.isArray(message.devices) ? message.devices : [];
    const known = knownDevicesByUnit[message.unit_id] || new Set();
    for (const device of devices) {
      const name = (device.name || device.device || "").toString();
      if (/vcb|inv|transformer|oil|winding/i.test(name)) known.add(name);
    }
    knownDevicesByUnit[message.unit_id] = known;
    return;
  }

  if (message.type === "daily_data_result") {
    const rows = Array.isArray(message.data) ? message.data : [];
    for (const row of rows) {
      if (!row || !row.values) continue;
      await handleMessage({
        type: "data",
        unit_id: message.unit_id,
        task: /vcb/i.test(message.deviceName || message.device || row.device || "") ? "VCB" : "Inverter",
        device: row.device || message.deviceName || message.device || "",
        time: row.time || row.timestamp || "",
        values: row.values,
        alertHistory: row.alertHistory || message.alertHistory || [],
        historicalReplay: true,
      });
    }
    return;
  }

  if (!message.values) return;

  const task = (message.task || "").toString().toLowerCase();
  const device = canonicalDeviceName(message.device, message.task || "Device");
  const srcTime = sourceTime(message);
  const storeTime = message.historicalReplay ? srcTime : "";

  if (task === "inverter" || device.toLowerCase().includes("inverter")) {
    const canonical = canonicalInverterName(device);
    if (!message.historicalReplay && !shouldStoreLive(`${plant_id}:inverter:${canonical}`)) return;
    await postStore({
      plant_id,
      device_name: canonical,
      type: "inverter",
      source_time: storeTime,
      payload: inverterPayload(message.values, message),
    });
  } else if (task === "vcb" || device.toLowerCase().includes("vcb")) {
    if (!message.historicalReplay && !shouldStoreLive(`${plant_id}:vcb:${device}`)) return;
    await postStore({
      plant_id,
      device_name: device,
      type: "vcb",
      source_time: storeTime,
      payload: vcbPayload(message.values),
    });
  } else if (task === "transformer" || device.toLowerCase().includes("transformer")) {
    if (!message.historicalReplay && !shouldStoreLive(`${plant_id}:transformer:${device}`)) return;
    await postStore({
      plant_id,
      device_name: device,
      type: "transformer",
      source_time: storeTime,
      payload: transformerPayload(device, message.values),
    });
  }

  postStore({
    plant_id,
    unit_id: message.unit_id || "",
    task: message.task || "",
    device_name: device,
    type: "raw",
    source_time: srcTime,
    payload: message,
  }).catch((error) => {
    console.error("[collector] raw store error:", error.message);
  });
}

function connect() {
  console.log(`[collector] connecting ${WS_URL}`);
  const ws = new WebSocket(WS_URL);

  ws.onopen = () => {
    console.log("[collector] connected");
    for (const plant of PLANTS) {
      ws.send(JSON.stringify({ type: "subscribe", unit_id: plant.unit_id }));
      ws.send(JSON.stringify({ type: "get_devices", unit_id: plant.unit_id }));
      console.log(`[collector] subscribed ${plant.plant_id} / ${plant.unit_id}`);
    }
  };

  ws.onmessage = async (event) => {
    try {
      const raw = typeof event.data === "string" ? event.data : String(event.data);
      const message = JSON.parse(raw);
      await handleMessage(message);
      if (message.type === "device_list" && unitToPlant[message.unit_id]) {
        const today = new Date().toISOString().slice(0, 10);
        for (const device of Array.from(knownDevicesByUnit[message.unit_id] || [])) {
          ws.send(JSON.stringify({ type: "get_daily_data", unit_id: message.unit_id, device, date: today }));
        }
      }
      console.log(`[collector] stored ${message.unit_id || "-"} ${message.task || "-"} ${message.device || "-"} ${sourceTime(message) || ""}`);
    } catch (error) {
      console.error("[collector] message error:", error.message);
    }
  };

  ws.onerror = (event) => {
    console.error("[collector] websocket error:", event.message || String(event));
  };

  ws.onclose = () => {
    console.error("[collector] disconnected, reconnecting in 5s");
    setTimeout(connect, 5000);
  };

  const dailyTimer = setInterval(() => {
    if (ws.readyState !== WebSocket.OPEN) return;
    const today = todayLocal();
    for (const plant of PLANTS) {
      const devices = Array.from(knownDevicesByUnit[plant.unit_id] || []);
      if (!devices.length) {
        ws.send(JSON.stringify({ type: "get_devices", unit_id: plant.unit_id }));
        continue;
      }
      for (const device of devices) {
        ws.send(JSON.stringify({ type: "get_daily_data", unit_id: plant.unit_id, device, date: today }));
      }
    }
  }, 60000);

  ws.addEventListener("close", () => clearInterval(dailyTimer));
}

connect();
