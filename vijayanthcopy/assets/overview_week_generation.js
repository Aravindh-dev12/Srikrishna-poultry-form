(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    const DAY_MS = 24 * 60 * 60 * 1000;
    let weeklyPayload = null;
    let lastFetchAt = 0;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function localDateKey(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    // Dashboard week is Sunday to Saturday.
    function weekDates(today = new Date()) {
        const start = new Date(today);
        const day = start.getDay(); // 0 = Sunday
        start.setHours(0, 0, 0, 0);
        start.setDate(start.getDate() - day);
        return Array.from({ length: 7 }, (_, index) => {
            const d = new Date(start.getTime() + index * DAY_MS);
            return {
                key: localDateKey(d),
                label: d.toLocaleDateString('en-IN', { weekday: 'short' }),
                short: d.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short' })
            };
        });
    }

    function findGenerationChart() {
        const canvas = document.getElementById('genChart');
        if (!canvas || typeof Chart === 'undefined') return null;

        // Chart.js v3/v4 supports Chart.getChart. Some builds accept the id, some accept the canvas.
        if (typeof Chart.getChart === 'function') {
            return Chart.getChart(canvas) || Chart.getChart('genChart') || null;
        }

        // Chart.js v2 stores instances differently. Support both direct canvas and chart.canvas paths.
        const instances = Chart.instances || {};
        const charts = Array.isArray(instances) ? instances : Object.values(instances);
        return charts.find(chart => {
            const chartCanvas = chart?.canvas || chart?.chart?.canvas || chart?.ctx?.canvas;
            return chartCanvas === canvas || chartCanvas?.id === 'genChart';
        }) || null;
    }

    function setTitle(days = weekDates()) {
        const canvas = document.getElementById('genChart');
        const card = canvas?.closest('.bg-white');
        const title = card?.querySelector('h4');
        if (title) title.textContent = `Generation Week (${days[0].short} - ${days[6].short})`;
    }

    function niceMax(value) {
        if (!Number.isFinite(value) || value <= 0) return 1000;
        const magnitude = Math.pow(10, Math.floor(Math.log10(value)));
        const normalized = value / magnitude;
        const rounded = normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
        return rounded * magnitude;
    }

    function readNumericText(id) {
        const text = document.getElementById(id)?.textContent || '';
        const n = parseFloat(text.replace(/,/g, '').replace(/[^0-9.\-]/g, ''));
        return Number.isFinite(n) ? n : 0;
    }

    function liveTodayKwh() {
        return Math.max(readNumericText('vcb_today'), readNumericText('today_energy_val'), 0);
    }

    function expectedDailyFromConfig() {
        const plantId = window.SIGNED_PLANT_ID || window.currentPlant || new URLSearchParams(window.location.search).get('plant') || '';
        const cfg = (window.SIGNED_PLANT_CONFIG && window.SIGNED_PLANT_CONFIG[plantId]) || {};
        const cap = parseFloat(cfg.capacity || window.plantCapacity || 0);
        return Number.isFinite(cap) && cap > 0 ? cap * 1000 * 0.8 * 5 : 1000;
    }

    function updateDataAttributes(labels, values) {
        const canvas = document.getElementById('genChart');
        if (!canvas) return;
        canvas.dataset.weekLabels = labels.join(',');
        canvas.dataset.weekValues = values.join(',');
    }

    function applyWeeklyChart() {
        const chart = findGenerationChart();
        const days = weekDates();
        const todayKey = localDateKey(new Date());
        const rows = (weeklyPayload && Array.isArray(weeklyPayload.data)) ? weeklyPayload.data : [];
        const dataMap = new Map(rows.map(row => [row.date, Number(row.actual || 0)]));
        const liveToday = liveTodayKwh();
        if (liveToday > 0) dataMap.set(todayKey, Math.max(dataMap.get(todayKey) || 0, liveToday));

        const expected = Math.max(...rows.map(row => Number(row.expected || 0)), expectedDailyFromConfig(), 1000);
        const labels = days.map(day => day.label); // Sun, Mon, Tue...
        const values = days.map(day => Number((dataMap.get(day.key) || 0).toFixed(2)));
        const maxValue = Math.max(...values, expected, 0);
        const suggestedMax = niceMax(Math.max(maxValue * 1.15, expected));

        setTitle(days);
        updateDataAttributes(labels, values);

        if (!chart) return;

        // Make this chart a weekly chart every time; the old live page code may try to reuse it as hourly.
        chart.config.type = 'bar';
        chart.data.labels = labels;
        chart.data.datasets = [{
            label: 'Current week generation (kWh)',
            data: values,
            backgroundColor: '#059669',
            borderRadius: 5,
            barPercentage: 0.56,
            categoryPercentage: 0.72
        }];
        chart.options.plugins = chart.options.plugins || {};
        chart.options.plugins.legend = { display: false };
        chart.options.scales = chart.options.scales || {};
        chart.options.scales.x = chart.options.scales.x || {};
        chart.options.scales.y = chart.options.scales.y || {};
        chart.options.scales.x.grid = { display: false };
        chart.options.scales.x.title = { display: true, text: 'Current week' };
        chart.options.scales.x.ticks = {
            autoSkip: false,
            maxRotation: 0,
            minRotation: 0,
            font: { size: 11, weight: '700' }
        };
        chart.options.scales.y.title = { display: true, text: 'Generation (kWh)' };
        chart.options.scales.y.beginAtZero = true;
        chart.options.scales.y.suggestedMax = suggestedMax;
        chart.options.scales.y.grid = { color: '#f1f5f9' };
        chart.options.scales.y.ticks = {
            callback: value => Number(value).toLocaleString('en-IN')
        };
        chart.update('none');
    }

    function loadWeeklyGeneration(force = false) {
        const now = Date.now();
        if (!force && now - lastFetchAt < 60000) return;
        lastFetchAt = now;
        const plantId = window.SIGNED_PLANT_ID || window.currentPlant || new URLSearchParams(window.location.search).get('plant') || '';
        if (!plantId) return;
        fetch(`api.php?action=get_weekly_energy&plant_id=${encodeURIComponent(plantId)}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(res => {
                if (!res || res.status !== 'success') return;
                weeklyPayload = res;
                applyWeeklyChart();
            })
            .catch(() => {});
    }

    function start() {
        setTitle();
        loadWeeklyGeneration(true);

        // Run frequently because the original page still updates the same canvas after WebSocket messages.
        // This keeps the axis as Sun-Sat and keeps today's bar live for every plant.
        applyWeeklyChart();
        setTimeout(applyWeeklyChart, 250);
        setTimeout(applyWeeklyChart, 750);
        setTimeout(applyWeeklyChart, 1500);
        setInterval(() => {
            loadWeeklyGeneration(false);
            applyWeeklyChart();
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();
