(function () {
    function num(value) {
        const n = parseFloat(value);
        return Number.isFinite(n) ? n : 0;
    }

    function powerKw(value) {
        const n = num(value);
        return Math.abs(n) > 10000 ? n / 1000 : n;
    }

    function canonicalInverterName(name) {
        const match = (name || '').toString().match(/\d+/);
        return match ? `INVERTER${parseInt(match[0], 10)}` : (name || 'INVERTER').toString().toUpperCase().replace(/\s+/g, '');
    }

    function parseStrings(values) {
        const strings = [];
        Object.keys(values || {}).forEach((key) => {
            const match = key.match(/^string\s*(\d+)\s*current$/i);
            if (!match) return;
            const n = parseInt(match[1], 10);
            const voltKey = Object.keys(values).find((k) => new RegExp(`^string\\s*${n}\\s*volt(age)?$`, 'i').test(k));
            const curr = num(values[key]);
            const volt = voltKey ? num(values[voltKey]) : 0;
            strings.push({ n, curr, volt, active: curr > 0.5 });
        });
        strings.sort((a, b) => a.n - b.n);
        return strings;
    }

    function readAlarmFaultState(values, alertHistory) {
        let hasAlarm = false;
        let hasFault = false;
        let faultCode = '';
        let workState = '';
        let statusText = '';

        Object.keys(values || {}).forEach((key) => {
            const lower = key.toLowerCase();
            const raw = values[key];
            const text = raw === null || raw === undefined ? '' : raw.toString().trim().toLowerCase();
            const numeric = parseFloat(raw);
            const active = text !== '' && text !== '0' && text !== 'null' && text !== 'normal' && text !== 'false';

            if (/fault\s*code|faultcode/i.test(key)) {
                faultCode = raw === null || raw === undefined ? '' : raw.toString();
                if (Number.isFinite(numeric) ? numeric > 0 : active) hasFault = true;
                return;
            }
            if (/fault|trip|error/i.test(lower) && active) hasFault = true;
            if (/alarm|warning|warn/i.test(lower) && active) hasAlarm = true;
            if (/work\s*state/i.test(key)) {
                workState = raw === null || raw === undefined ? '' : raw.toString();
                if (workState) statusText = `Work state ${workState}`;
            } else if (/status|state/i.test(lower) && text) {
                statusText = raw.toString();
            }
        });

        if (Array.isArray(alertHistory) && alertHistory.length) {
            hasAlarm = true;
            const latest = alertHistory[alertHistory.length - 1];
            const text = typeof latest === 'string' ? latest : JSON.stringify(latest);
            statusText = statusText || text.slice(0, 100);
            if (/fault|trip|error/i.test(text)) hasFault = true;
        }
        return { hasAlarm, hasFault, faultCode, workState, statusText };
    }

    function inverterPayload(values, message) {
        const strings = parseStrings(values);
        const dcPower = num(values['Total DC power']);
        const activePower = num(values['Total active power']);
        let eff = num(values.Efficiency || values.efficiency);
        if (!eff && dcPower > 0 && activePower > 0) eff = Math.min((activePower / (dcPower / 1000)) * 100, 100);
        const dailyGen = num(values['Daily power yields']);
        const totalGen = num(values['Total power yields precise'] ?? values['Total power yields']);
        const alarmFault = readAlarmFaultState(values, message.alertHistory || []);

        return {
            power: activePower,
            reactive: num(values['Total reactive power']),
            pf: num(values['Power factor']),
            vac_ab: num(values.RYvolatge ?? values['RY voltage'] ?? values['V12 (RY)']),
            vac_bc: num(values['YB voltage'] ?? values['V23 (YB)']),
            vac_ca: num(values['BR voltage'] ?? values['V31 (BR)']),
            freq: num(values['Grid frequency precise'] ?? values['Grid frequency']),
            i_a: num(values['RY current']),
            i_b: num(values['YB current']),
            i_c: num(values['BR current']),
            eff,
            amb: num(values['Internal temperature']),
            dailyGen,
            totalGen,
            dailyCO2: dailyGen * 0.8,
            totalCO2: totalGen * 0.8,
            dailyHrs: num(values['Daily running time']),
            totalHrs: num(values['Total running time']),
            activeStr: strings.filter((s) => s.active).length,
            totalStr: strings.length,
            strings,
            alertHistory: message.alertHistory || [],
            ...alarmFault
        };
    }

    function vcbPayload(values) {
        return {
            power_3phase_kw: powerKw(values['3 Phase Active Power'] ?? values['3 phase active power']),
            frequency_hz: num(values['Frequency (Hz)'] ?? values['Grid frequency'] ?? values.Frequency),
            voltage_r_v: num(values['R Phase-N Voltage']),
            voltage_y_v: num(values['Y Phase-N Voltage']),
            voltage_b_v: num(values['B Phase-N Voltage']),
            voltage_ry_v: num(values['V12 (RY)']),
            voltage_yb_v: num(values['V23 (YB)']),
            voltage_br_v: num(values['V31 (BR)']),
            current_r_a: num(values['L1 (R)']),
            current_y_a: num(values['L2 (Y)']),
            current_b_a: num(values['L3 (B)']),
            power_r_kw: powerKw(values['Active Power R']),
            power_y_kw: powerKw(values['Active Power Y']),
            power_b_kw: powerKw(values['Active Power B']),
            pf_q1: num(values['Q1 PF'] ?? values['Power factor']),
            pf_q2: num(values['Q2 PF']),
            pf_q3: num(values['Q3 PF']),
            vthd_r: num(values['Voltage THD R']),
            vthd_y: num(values['Voltage THD Y']),
            vthd_b: num(values['Voltage THD B']),
            active_export_kwh: num(values['Active Total Export']),
            active_import_kwh: num(values['Active Total Import']),
            reactive_import_kvar: num(values['Reactive Import (Q1+Q2)']),
            reactive_export_kvar: num(values['Reactive Export (Q3+Q4)']),
            today_energy_kwh: num(values['vcb-today'] ?? values['Today Energy'] ?? values['Day Energy'])
        };
    }

    function transformerPayload(device, values) {
        const lower = (device || '').toLowerCase();
        const findValue = (patterns) => {
            for (const key of Object.keys(values || {})) {
                const kl = key.toLowerCase();
                if (patterns.some((rx) => rx.test(kl))) return num(values[key]);
            }
            return 0;
        };
        const genericTemp = findValue([/temp/, /temperature/]);
        return {
            oil_temp_c: findValue([/oil.*temp/, /temp.*oil/, /^oil-temp$/]) || (lower.includes('oil') ? genericTemp : 0),
            winding_temp_c: findValue([/winding.*temp/, /temp.*winding/, /^winding-temp$/]) || (lower.includes('winding') ? genericTemp : 0)
        };
    }

    const queue = [];
    let running = 0;
    const seen = new Map();
    const tabId = `${Date.now()}-${Math.random().toString(36).slice(2)}`;

    function canStoreForPlant(plantId) {
        try {
            const key = `vs_store_leader_${plantId}`;
            const now = Date.now();
            const current = JSON.parse(localStorage.getItem(key) || '{}');
            if (!current.id || current.id === tabId || (current.expires || 0) < now) {
                localStorage.setItem(key, JSON.stringify({ id: tabId, expires: now + 5000 }));
                return true;
            }
            return false;
        } catch (e) {
            return true;
        }
    }

    function postStore(body) {
        queue.push(body);
        drain();
    }

    function drain() {
        while (running < 2 && queue.length) {
            running++;
            const body = queue.shift();
            fetch('api_store.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
                keepalive: false
            }).then(async (response) => {
                const result = await response.json().catch(() => ({}));
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || `Storage HTTP ${response.status}`);
                }
            }).catch((error) => {
                console.error('[LiveWsStore] SQL storage failed:', error.message, body.type, body.device_name);
            }).finally(() => {
                running--;
                drain();
            });
        }
    }

    function shouldStore(key) {
        const now = Date.now();
        const last = seen.get(key) || 0;
        if (now - last < 1500) return false;
        seen.set(key, now);
        return true;
    }

    function cacheLivePayload(plantId, type, deviceName, payload) {
        try {
            const key = `vs_fast_snapshot_${plantId}`;
            const snapshot = JSON.parse(localStorage.getItem(key) || 'null') || {
                status: 'success', plant_id: plantId, data: { vcb: null, inverters: [], transformers: [] }
            };
            snapshot.status = 'success';
            snapshot.plant_id = plantId;
            snapshot._cached_at = Date.now();
            snapshot.data = snapshot.data || { vcb: null, inverters: [], transformers: [] };
            if (type === 'inverter') {
                const row = {
                    plant_id: plantId, inverter_name: deviceName, snapshot_at: new Date().toISOString(),
                    power_kw: payload.power, reactive_kvar: payload.reactive, power_factor: payload.pf,
                    vac_ab: payload.vac_ab, vac_bc: payload.vac_bc, vac_ca: payload.vac_ca,
                    frequency_hz: payload.freq, current_a: payload.i_a, current_b: payload.i_b, current_c: payload.i_c,
                    efficiency: payload.eff, ambient_temp: payload.amb, daily_gen_kwh: payload.dailyGen,
                    total_gen_kwh: payload.totalGen, daily_co2_kg: payload.dailyCO2, total_co2_kg: payload.totalCO2,
                    daily_hours: payload.dailyHrs, total_hours: payload.totalHrs, active_strings: payload.activeStr,
                    total_strings: payload.totalStr, has_alarm: payload.hasAlarm ? 1 : 0, has_fault: payload.hasFault ? 1 : 0,
                    fault_code: payload.faultCode || '', work_state: payload.workState || '', status_text: payload.statusText || '',
                    strings: (payload.strings || []).map(s => ({ string_n: s.n, current_a: s.curr, voltage_v: s.volt, active: s.active ? 1 : 0 }))
                };
                const rows = Array.isArray(snapshot.data.inverters) ? snapshot.data.inverters : [];
                const index = rows.findIndex(item => canonicalInverterName(item.inverter_name) === deviceName);
                if (index >= 0) rows[index] = row; else rows.push(row);
                snapshot.data.inverters = rows;
            } else if (type === 'vcb') {
                snapshot.data.vcb = { plant_id: plantId, device_name: deviceName, snapshot_at: new Date().toISOString(), ...payload };
            }
            const serialized = JSON.stringify(snapshot);
            localStorage.setItem(key, serialized);
            sessionStorage.setItem(key, serialized);
        } catch (e) {}
    }

    function storeDataMessage(message, plantId, historicalReplay) {
        if (!message || !message.values || !plantId) return;
        if (historicalReplay) return;
        const task = (message.task || '').toString().toLowerCase();
        const device = (message.device || message.deviceName || message.task || 'Device').toString();
        const lowerDevice = device.toLowerCase();
        const sourceTime = historicalReplay ? (message.time || message.timestamp || message.ts || '') : '';

        if (task === 'inverter' || lowerDevice.includes('inverter') || lowerDevice.includes('inv')) {
            const deviceName = canonicalInverterName(device);
            const payload = inverterPayload(message.values, message);
            cacheLivePayload(plantId, 'inverter', deviceName, payload);
            if (!canStoreForPlant(plantId)) return;
            if (!historicalReplay && !shouldStore(`${plantId}:inv:${deviceName}`)) return;
            postStore({ plant_id: plantId, device_name: deviceName, type: 'inverter', source_time: sourceTime, payload });
        } else if (task === 'vcb' || lowerDevice.includes('vcb')) {
            const payload = vcbPayload(message.values);
            cacheLivePayload(plantId, 'vcb', device, payload);
            if (!canStoreForPlant(plantId)) return;
            if (!historicalReplay && !shouldStore(`${plantId}:vcb:${device}`)) return;
            postStore({ plant_id: plantId, device_name: device, type: 'vcb', source_time: sourceTime, payload });
        } else if (task === 'transformer' || lowerDevice.includes('transformer')) {
            if (!canStoreForPlant(plantId)) return;
            if (!historicalReplay && !shouldStore(`${plantId}:trafo:${device}`)) return;
            postStore({ plant_id: plantId, device_name: device, type: 'transformer', source_time: sourceTime, payload: transformerPayload(device, message.values) });
        }
    }

    window.LiveWsStore = {
        fastSnapshot(plantId) {
            const key = `vs_fast_snapshot_${plantId}`;
            let cached = null;
            try {
                cached = JSON.parse(localStorage.getItem(key) || sessionStorage.getItem(key) || 'null');
                if (cached && cached._cached_at && Date.now() - cached._cached_at > 300000) cached = null;
            } catch (e) {}

            const request = fetch(`api.php?action=get_fast_snapshot&plant_id=${encodeURIComponent(plantId)}`, { cache: 'no-store' })
                .then((response) => {
                    if (!response.ok) throw new Error(`Snapshot HTTP ${response.status}`);
                    return response.json();
                })
                .then((json) => {
                    if (json && json.status === 'success') {
                        try {
                            json._cached_at = Date.now();
                            const serialized = JSON.stringify(json);
                            localStorage.setItem(key, serialized);
                            sessionStorage.setItem(key, serialized);
                        } catch (e) {}
                        window.dispatchEvent(new CustomEvent('plant-fast-snapshot', { detail: { plantId, snapshot: json } }));
                    }
                    return json;
                });

            // Navigation between pages renders the last snapshot synchronously;
            // the live socket and background request then replace it with fresh data.
            if (cached && cached.status === 'success') {
                request.catch(() => {});
                return Promise.resolve({ ok: true, json: () => Promise.resolve(cached) });
            }
            return request.then((json) => ({ ok: true, json: () => Promise.resolve(json) }));
        },
        todayLocal() {
            const d = new Date();
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        },
        storeMessage(message, plantId) {
            if (!message || !plantId) return;
            if (message.type === 'daily_data_result') {
                return;
            }
            storeDataMessage(message, plantId, false);
        },
        storeAnalyticsResult(message, plantId) {
            if (!message || !plantId) return;
            const rawDevice = message.deviceName || message.device || message.request?.device || '';
            const deviceName = canonicalInverterName(rawDevice);
            let points = message.data ?? message.analyticsData ?? message.result ?? message.results ?? [];
            if (!Array.isArray(points) && points && typeof points === 'object') {
                points = points[rawDevice] ?? points.data ?? points.points ?? points.values ?? [];
            }
            if (!Array.isArray(points)) return;
            points.forEach((point) => {
                const timestamp = point?.timestamp ?? point?.time ?? point?.dateTime ?? point?.datetime ?? point?.bucket ?? '';
                if (!timestamp) return;
                const values = point?.values || {};
                const dailyGen = num(point?.value ?? point?.last ?? point?.aggregatedValue ??
                    values['Daily power yields'] ?? values['daily power yields']);
                postStore({
                    plant_id: plantId,
                    device_name: deviceName,
                    type: 'inverter_report',
                    source_time: timestamp,
                    payload: { dailyGen }
                });
            });
        },
        requestTodayForDevices(ws, unitId, devices) {
            if (!ws || ws.readyState !== WebSocket.OPEN || !Array.isArray(devices)) return;
            const today = this.todayLocal();
            devices.forEach((device) => {
                const name = (device.name || device.device || '').toString();
                if (!/vcb|inv|inverter|transformer|oil|winding/i.test(name)) return;
                ws.send(JSON.stringify({ type: 'get_daily_data', unit_id: unitId, device: name, date: today }));
            });
        }
    };
})();
