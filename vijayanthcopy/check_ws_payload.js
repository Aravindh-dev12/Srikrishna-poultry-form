const ws = new WebSocket("wss://vinobasolar.scadahub.in:5001");
const keys = new Set();
let count = 0;

function collectKeys(prefix, value) {
  if (!value || typeof value !== "object" || Array.isArray(value)) return;
  for (const key of Object.keys(value)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    keys.add(fullKey);
    collectKeys(fullKey, value[key]);
  }
}

function finish() {
  console.log("SUMMARY", JSON.stringify({ count, keys: [...keys].sort() }, null, 2));
  try { ws.close(); } catch (error) {}
  setTimeout(() => process.exit(0), 100);
}

ws.onopen = () => {
  console.log("OPEN");
  ws.send(JSON.stringify({ type: "subscribe", unit_id: "via-1mw" }));
  ws.send(JSON.stringify({ type: "subscribe", unit_id: "via-3mw" }));
};

ws.onmessage = (event) => {
  count++;
  const raw = typeof event.data === "string" ? event.data : String(event.data);
  console.log(`MESSAGE ${count}`, raw.slice(0, 4000));
  try {
    collectKeys("", JSON.parse(raw));
  } catch (error) {
    keys.add("NON_JSON");
  }
  if (count >= 5) finish();
};

ws.onerror = (event) => {
  console.error("ERROR", event.message || String(event));
};

setTimeout(finish, 45000);
