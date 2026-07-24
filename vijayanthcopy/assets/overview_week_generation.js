(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    const DAY_MS = 24 * 60 * 60 * 1000;
    let weeklyPayload = { status: 'success', data: [] };
    let lastFetchAt = 0;
    let fallbackFetchRunning = false;

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function localDateKey(date) {
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    }

    function weekDates(today = new Date()) {
        const start = new Date(today);
        start.setHours(0, 0, 0, 0);
        start.setDate(start.getDate() - start.getDay());
        return Array.from({ length: 7 }, (_, index) => {
            const d = new Date(start.getTime() + index * DAY_MS);
            return {
                key: localDateKey(d),
                label: d.toLocaleDateString('en-IN', { weekday: 'short' }),
                short: d.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short' })
            };
        });
    }

    function currentPlantId() {
        return window.SIGNED_PLANT_ID || new URLSearchParams(window.location.search).get('plant') || '';
    }

    function findGenerationChart() {
        const canvas = document.getElementById('genChart');
        if (!canvas || typeof Chart === 'undefined') return null;
        if (typeof Chart.getChart === 'function') {
            return Chart.getChart(canvas) || Chart.getChart('genChart') || null;
        }
        const instances = Chart.instances || {};
        const charts = Array.isArray(instances) ? instances : Object.values(instances);
        return charts.find(chart => {
            const chartCanvas = chart?.canvas || chart?.chart?.canvas || chart?.ctx?.canvas;
            return chartCanvas === canvas || chartCanvas?.id === 'genChart';
        }) || null;
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
        const plantId = currentPlantId();
        const cfg = (window.SIGNED_PLANT_CONFIG && window.SIGNED_PLANT_CONFIG[plantId]) || {};
        const cap = parseFloat(cfg.capacity || 0);
        return Number.isFinite(cap) && cap > 0 ? cap * 1000 * 0.8 * 5 : 1000;
    }

    function niceMax(value) {
        if (!Number.isFinite(value) || value <= 0) return 1000;
        const magnitude = Math.pow(10, Math.floor(Math.log10(value)));
        const normalized = value / magnitude;
        const rounded = normalized <= 2 ? 2 : normalized <= 5 ? 5 : 10;
        return rounded * magnitude;
    }

    function normalizeRows(rows) {
        const map = new Map();
        (Array.isArray(rows) ? rows : []).forEach(row => {
            const key = row?.date || row?.day_date || '';
            const value = Number(row?.actual ?? row?.generation ?? row?.energy ?? 0);
            if (!key) return;
            const safeValue = Number.isFinite(value) && value > 0 ? value : 0;
            map.set(key, Math.max(map.get(key) || 0, safeValue));
        });
        return map;
    }

    function setTitle(days, total) {
        const canvas = document.getElementById('genChart');
        const card = canvas?.closest('.bg-white');
        const title = card?.querySelector('h4');
        if (!title) return;
        const formattedTotal = Number(total || 0).toLocaleString('en-IN', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        });
        title.textContent = `Generation Week (${days[0].short} - ${days[6].short}) · ${formattedTotal} kWh`;
    }

    function applyWeeklyChart() {
        const chart = findGenerationChart();
        const days = weekDates();
        const todayKey = localDateKey(new Date());
        const dataMap = normalizeRows(weeklyPayload.data);
        const liveToday = liveTodayKwh();

        if (liveToday > 0) {
            dataMap.set(todayKey, Math.max(dataMap.get(todayKey) || 0, liveToday));
        }

        const labels = days.map(day => day.label);
        const values = days.map(day => Number((dataMap.get(day.key) || 0).toFixed(2)));
        const total = values.reduce((sum, value) => sum + Math.max(0, value), 0);
        const expected = Math.max(
            expectedDailyFromConfig(),
            ...(weeklyPayload.data || []).map(row => Number(row.expected || 0)),
            1000
        );
        const suggestedMax = niceMax(Math.max(...values, expected) * 1.12);

        setTitle(days, total);

        const canvas = document.getElementById('genChart');
        if (canvas) {
            canvas.dataset.weekLabels = labels.join(',');
            canvas.dataset.weekValues = values.join(',');
            canvas.dataset.weekTotal = String(total.toFixed(2));
        }

        if (!chart) return;

        chart.config.type = 'bar';
        chart.data.labels = labels;
        chart.data.datasets = [{
            label: 'Current week generation (kWh)',
            data: values,
            backgroundColor: '#059669',
            borderRadius: 6,
            barPercentage: 0.58,
            categoryPercentage: 0.72
        }];
        chart.options.plugins = chart.options.plugins || {};
        chart.options.plugins.legend = { display: false };
        chart.options.plugins.tooltip = {
            callbacks: {
                label: context => `${Number(context.raw || 0).toLocaleString('en-IN', { maximumFractionDigits: 2 })} kWh`
            }
        };
        chart.options.scales = chart.options.scales || {};
        chart.options.scales.x = {
            grid: { display: false },
            title: { display: true, text: 'Current week' },
            ticks: { autoSkip: false, maxRotation: 0, minRotation: 0, font: { size: 11, weight: '700' } }
        };
        chart.options.scales.y = {
            beginAtZero: true,
            suggestedMax,
            title: { display: true, text: 'Generation (kWh)' },
            grid: { color: '#f1f5f9' },
            ticks: { callback: value => Number(value).toLocaleString('en-IN') }
        };
        chart.update('none');
    }

    function fetchDailyFallback(dateKey) {
        const plantId = currentPlantId();
        if (!plantId) return Promise.resolve(0);
        return fetch(`api.php?action=get_overview_hourly&plant_id=${encodeURIComponent(plantId)}&date=${encodeURIComponent(dateKey)}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(res => {
                if (!res || res.status !== 'success' || !res.data) return 0;
                const generation = Array.isArray(res.data.generation) ? res.data.generation : [];
                return generation.reduce((sum, value) => sum + Math.max(0, Number(value || 0)), 0);
            })
            .catch(() => 0);
    }

    function refreshWeekFromInverters() {
        if (fallbackFetchRunning) return;
        fallbackFetchRunning = true;

        const todayKey = localDateKey(new Date());
        const elapsedDays = weekDates().filter(day => day.key <= todayKey);

        Promise.all(elapsedDays.map(day =>
            fetchDailyFallback(day.key).then(value => ({ day, value }))
        ))
            .then(results => {
                const byDate = new Map((weeklyPayload.data || []).map(row => [row.date, row]));

                results.forEach(({ day, value }) => {
                    if (!Number.isFinite(value) || value <= 0) return;
                    const existing = byDate.get(day.key);
                    if (existing) {
                        existing.actual = Math.max(Number(existing.actual || 0), value);
                    } else {
                        const row = {
                            date: day.key,
                            day: day.label,
                            actual: value,
                            expected: expectedDailyFromConfig()
                        };
                        weeklyPayload.data.push(row);
                        byDate.set(day.key, row);
                    }
                });

                weeklyPayload.data.sort((a, b) => String(a.date).localeCompare(String(b.date)));
                applyWeeklyChart();
            })
            .finally(() => {
                fallbackFetchRunning = false;
            });
    }

    function loadWeeklyGeneration(force = false) {
        const now = Date.now();
        if (!force && now - lastFetchAt < 60000) return;
        lastFetchAt = now;

        const plantId = currentPlantId();
        if (!plantId) {
            applyWeeklyChart();
            return;
        }

        fetch(`api.php?action=get_weekly_energy&plant_id=${encodeURIComponent(plantId)}`, { cache: 'no-store' })
            .then(res => res.json())
            .then(res => {
                weeklyPayload = res && res.status === 'success'
                    ? { ...res, data: Array.isArray(res.data) ? res.data : [] }
                    : { status: 'success', data: [] };
                applyWeeklyChart();
                refreshWeekFromInverters();
            })
            .catch(() => {
                weeklyPayload = weeklyPayload || { status: 'success', data: [] };
                applyWeeklyChart();
                refreshWeekFromInverters();
            });
    }

    function start() {
        applyWeeklyChart();
        loadWeeklyGeneration(true);

        setTimeout(applyWeeklyChart, 250);
        setTimeout(applyWeeklyChart, 1000);
        setTimeout(refreshWeekFromInverters, 1500);

        // The original overview script also updates this canvas with hourly data.
        // Re-apply the weekly dataset frequently so the weekly chart remains visible.
        setInterval(applyWeeklyChart, 500);
        setInterval(() => loadWeeklyGeneration(false), 60000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
})();