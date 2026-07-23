(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    const params = new URLSearchParams(window.location.search);
    const plantId = params.get('plant') || sessionStorage.getItem('vs_current_plant') || '';
    if (plantId !== 'vijayanth_cosmic') return;

    const wsUrl = 'wss://vinobasolar.scadahub.in:5001';
    const unitId = 'via-7mw';
    const commonDeviceNames = ['Inverter 3', 'INVERTER3', 'Inverter3', 'INV-3', 'INV3'];
    let detectedDeviceName = 'Inverter 3';
    let socket = null;
    let reconnectTimer = null;
    let pollTimer = null;
    let latestPower = null;
    let latestDaily = null;
    let latestTimestamp = 0;

    function readNumber(value) {
        if (value === null || value === undefined || value === '') return null;
        if (typeof value === 'object') {
            value = value.value ?? value.val ?? value.reading ?? value.data ?? value.result ?? null;
        }
        const n = parseFloat(String(value).replace(/,/g, ''));
        return Number.isFinite(n) ? n : null;
    }

    function normalizeKey(key) {
        return String(key || '').toLowerCase().replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
    }

    function findMetric(values, accepts, rejects) {
        if (!values || typeof values !== 'object') return null;
        for (const [key, raw] of Object.entries(values)) {
            const normalized = normalizeKey(key);
            if (rejects.some(rx => rx.test(normalized))) continue;
            if (!accepts.some(rx => rx.test(normalized))) continue;
            const value = readNumber(raw);
            if (value !== null) return value;
        }
        return null;
    }

    function extractTelemetry(values) {
        let power = findMetric(values,
            [/^total active power/, /^active power/, /^ac active power/, /^ac power/, /^output power/, /^power output/, /^p ac$/, /^pac$/, /real power/],
            [/reactive/, /apparent/, /nominal/, /rated/, /capacity/, /maximum/, /dc power/, /string/, /mppt/, /yield/, /energy/, /factor/]
        );
        if (power === null) {
            power = findMetric(values, [/power/], [/reactive/, /apparent/, /nominal/, /rated/, /capacity/, /maximum/, /dc/, /string/, /mppt/, /yield/, /energy/, /factor/]);
        }
        const daily = findMetric(values, [/daily power yield/, /daily.*yield/, /today.*energy/, /day.*energy/], []);
        if (power !== null && Math.abs(power) > 10000) power /= 1000;
        return { power, daily };
    }

    function isInverterThreeName(name) {
        const text = String(name || '');
        return /(?:inverter|inv|solar)\s*[-_#:]?\s*0*3\b/i.test(text) || /^0*3$/.test(text.trim());
    }

    function collectTelemetryCandidates(node, output, inheritedDevice) {
        if (!node || typeof node !== 'object') return;
        if (Array.isArray(node)) {
            node.forEach(item => collectTelemetryCandidates(item, output, inheritedDevice));
            return;
        }
        const device = node.device || node.deviceName || node.inverter || node.name || inheritedDevice || '';
        if (node.values && typeof node.values === 'object') {
            output.push({ device, values: node.values });
        }
        const direct = extractTelemetry(node);
        if (direct.power !== null || direct.daily !== null) {
            output.push({ device, values: node });
        }
        Object.values(node).forEach(value => {
            if (value && typeof value === 'object') collectTelemetryCandidates(value, output, device);
        });
    }

    function findCard() {
        const grid = document.getElementById('inverterGrid');
        if (!grid) return null;
        return Array.from(grid.children).find(card => /\binv(?:erter)?\s*0*3\b/i.test(card.textContent || '')) || null;
    }

    function render() {
        const card = findCard();
        if (!card || latestPower === null) return;
        const paragraphs = card.querySelectorAll('p');
        if (paragraphs[1]) paragraphs[1].innerHTML = `${latestPower.toFixed(1)} <span class="text-[10px] font-bold">kW</span>`;
        const dailyEl = Array.from(paragraphs).find(el => /kwh/i.test(el.textContent || ''));
        if (dailyEl && latestDaily !== null) dailyEl.textContent = `${latestDaily.toFixed(1)} kWh`;
        const isOn = latestPower > 0.5;
        card.classList.toggle('inv-on', isOn);
        card.classList.toggle('inv-off', !isOn);
        card.dataset.analyticsFallback = 'active';
    }

    function applyCandidate(candidate) {
        if (candidate.device && !isInverterThreeName(candidate.device)) return false;
        const telemetry = extractTelemetry(candidate.values);
        if (telemetry.power === null && telemetry.daily === null) return false;
        if (telemetry.power !== null) latestPower = telemetry.power;
        if (telemetry.daily !== null) latestDaily = telemetry.daily;
        latestTimestamp = Date.now();
        render();
        return true;
    }

    function requestAnalytics(deviceName) {
        if (!socket || socket.readyState !== WebSocket.OPEN) return;
        const today = new Date().toISOString().slice(0, 10);
        socket.send(JSON.stringify({
            type: 'get_analytics_data',
            unit_id: unitId,
            device: deviceName,
            tag: 'ALL',
            startDate: today,
            endDate: today,
            timePeriod: '1',
            method: 'last'
        }));
    }

    function requestAllKnownNames() {
        const names = [detectedDeviceName, ...commonDeviceNames].filter((name, index, list) => name && list.indexOf(name) === index);
        names.forEach((name, index) => setTimeout(() => requestAnalytics(name), index * 200));
    }

    function handleMessage(event) {
        try {
            const message = JSON.parse(event.data);
            if (message.unit_id && message.unit_id !== unitId) return;

            if (message.type === 'device_list' && Array.isArray(message.devices)) {
                const match = message.devices.find(item => {
                    const name = typeof item === 'string' ? item : (item.name || item.device || item.deviceName || '');
                    return isInverterThreeName(name);
                });
                if (match) {
                    detectedDeviceName = typeof match === 'string' ? match : (match.name || match.device || match.deviceName || detectedDeviceName);
                    requestAnalytics(detectedDeviceName);
                }
                return;
            }

            if (message.type !== 'analytics_data_result' && message.type !== 'daily_data_result' && !message.values) return;
            const candidates = [];
            collectTelemetryCandidates(message, candidates, message.device || message.deviceName || '');
            const matching = candidates.filter(candidate => !candidate.device || isInverterThreeName(candidate.device));
            for (let i = matching.length - 1; i >= 0; i--) {
                if (applyCandidate(matching[i])) break;
            }
        } catch (error) {
            console.warn('[Inverter3Fix] Unable to parse WebSocket response', error);
        }
    }

    function connect() {
        clearTimeout(reconnectTimer);
        clearInterval(pollTimer);
        try {
            socket = new WebSocket(wsUrl);
            socket.addEventListener('open', () => {
                socket.send(JSON.stringify({ type: 'subscribe', unit_id: unitId }));
                socket.send(JSON.stringify({ type: 'get_devices', unit_id: unitId }));
                requestAllKnownNames();
                pollTimer = setInterval(requestAllKnownNames, 15000);
            });
            socket.addEventListener('message', handleMessage);
            socket.addEventListener('close', () => {
                clearInterval(pollTimer);
                reconnectTimer = setTimeout(connect, 3000);
            });
            socket.addEventListener('error', () => socket.close());
        } catch (error) {
            reconnectTimer = setTimeout(connect, 3000);
        }
    }

    const observer = new MutationObserver(render);
    function observeGrid() {
        const grid = document.getElementById('inverterGrid');
        if (grid) observer.observe(grid, { childList: true, subtree: true, characterData: true });
        else setTimeout(observeGrid, 250);
    }

    observeGrid();
    setInterval(() => {
        if (latestTimestamp && Date.now() - latestTimestamp < 120000) render();
    }, 500);
    connect();
})();

(function () {
    function normalize(text) {
        return (text || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function removeAvailabilityPanels() {
        if (!/availability\.php$/i.test(window.location.pathname)) return;
        const removedParents = new Set();
        document.querySelectorAll('h3').forEach((heading) => {
            const label = normalize(heading.textContent);
            if (label !== 'grid availability (24h)' && label !== 'plant availability (24h)') return;
            const card = heading.closest('.bg-white');
            const parent = card?.parentElement;
            if (card) card.remove();
            if (parent) removedParents.add(parent);
        });
        removedParents.forEach((parent) => {
            if (!parent.children.length) parent.remove();
        });
    }

    function compactPlantInformation() {
        if (!/overview\.php$/i.test(window.location.pathname)) return;
        const heading = Array.from(document.querySelectorAll('h3')).find(node => normalize(node.textContent) === 'plant information');
        const card = heading?.closest('.bg-white');
        if (!card) return;
        const inverterCard = card.parentElement?.children?.[0];
        card.classList.remove('p-4', 'lg:col-span-3');
        card.classList.add('p-3');
        card.style.gridColumn = 'span 2 / span 2';
        card.style.alignSelf = 'start';
        card.style.maxWidth = '280px';
        card.style.justifySelf = 'end';
        card.querySelectorAll('.text-xs').forEach(node => {
            node.classList.remove('text-xs');
            node.classList.add('text-[10px]');
        });
        heading.classList.remove('mb-3', 'pb-2');
        heading.classList.add('mb-2', 'pb-1');
        if (inverterCard) {
            inverterCard.classList.remove('lg:col-span-9');
            inverterCard.style.gridColumn = 'span 10 / span 10';
        }
    }

    function cleanSldDiagram() {
        if (!/sld\.php$/i.test(window.location.pathname)) return;
        const svg = document.querySelector('.sld-svg');
        if (!svg) return;
        svg.querySelectorAll('text').forEach(node => {
            const label = normalize(node.textContent);
            if (!label.includes('winding') && !label.includes('oil temp') && !label.includes('oil temperature')) return;
            const group = node.closest('g');
            if (group) group.remove();
            else node.remove();
        });
        document.querySelectorAll('h3, p, span').forEach(node => {
            const label = normalize(node.textContent);
            if (!label.includes('winding temp') && !label.includes('oil temperature')) return;
            const card = node.closest('.rounded-lg, .rounded-xl');
            if (card && !card.closest('#sidebar')) card.remove();
        });
        const summary = Array.from(document.querySelectorAll('h2')).find(node => normalize(node.textContent) === 'power flow overview')?.closest('.bg-white');
        if (summary) {
            summary.classList.remove('p-5');
            summary.classList.add('p-4');
            summary.querySelectorAll('.p-5').forEach(card => {
                card.classList.remove('p-5');
                card.classList.add('p-3');
            });
        }
    }

    function applySharedUiFixes() {
        removeAvailabilityPanels();
        compactPlantInformation();
        cleanSldDiagram();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', applySharedUiFixes, { once: true });
    else applySharedUiFixes();
    setTimeout(applySharedUiFixes, 250);
    setTimeout(applySharedUiFixes, 1000);
})();
