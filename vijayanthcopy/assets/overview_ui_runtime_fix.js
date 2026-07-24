(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    let dataStarted = false;
    let syncQueued = false;

    function findOverviewCard() {
        return (document.getElementById('vcb_time') || document.getElementById('vcb_power'))?.closest('.bg-white') || null;
    }

    function findPlantInformationCard() {
        const heading = Array.from(document.querySelectorAll('h3')).find(el =>
            (el.textContent || '').trim().toLowerCase() === 'plant information'
        );
        return heading?.closest('.bg-white') || null;
    }

    function findInverterRow() {
        return document.getElementById('inverterGrid')?.closest('.grid.grid-cols-12') || null;
    }

    function findInverterPanel() {
        return document.getElementById('inverterGrid')?.closest('.bg-white') || null;
    }

    function addStyles() {
        if (document.getElementById('overviewCombinedTableStyles')) return;
        const style = document.createElement('style');
        style.id = 'overviewCombinedTableStyles';
        style.textContent = `
            .overview-combined-card {
                display:block!important; width:100%!important; min-width:0!important; max-width:none!important;
                grid-column:1/-1!important; flex:0 0 100%!important; margin:0 0 16px!important;
                border-radius:14px!important; overflow:hidden!important;
                box-shadow:0 8px 24px rgba(15,23,42,.06)!important;
            }
            .overview-combined-card .overflow-x-auto { width:100%!important; max-width:none!important; overflow-x:visible!important; }
            .overview-combined-card table {
                width:100%!important; min-width:0!important; max-width:none!important;
                table-layout:fixed!important; border-collapse:collapse!important;
            }
            .overview-combined-card th,
            .overview-combined-card td {
                border:1px solid #e2e8f0!important; padding:12px 10px!important;
                white-space:normal!important; overflow-wrap:anywhere!important;
            }
            .overview-combined-card thead th {
                background:#f8fafc!important; color:#475569!important; font-size:11px!important;
                font-weight:900!important; line-height:1.25!important;
            }
            .overview-info-labels th {
                background:#f8fafc!important; color:#64748b!important; text-align:left!important;
                font-size:9px!important; font-weight:900!important; letter-spacing:.06em!important;
                text-transform:uppercase!important;
            }
            .overview-info-values td {
                background:#fff!important; color:#0f172a!important; text-align:left!important;
                font-size:13px!important; font-weight:800!important; vertical-align:middle!important;
            }
            #plantStatusBadge {
                display:inline-flex!important; width:fit-content!important; align-items:center!important;
                justify-content:center!important; padding:5px 10px!important; border-radius:999px!important;
                background:#f1f5f9!important;
            }
            #plantStatusBadge.text-emerald-600 { background:#ecfdf5!important; color:#059669!important; }
            .overview-inverter-full-row { display:block!important; width:100%!important; max-width:none!important; }
            .overview-inverter-full-row > .bg-white { width:100%!important; max-width:none!important; }
            @media (max-width:900px) {
                .overview-combined-card .overflow-x-auto { overflow-x:auto!important; }
                .overview-combined-card table { min-width:900px!important; }
            }
        `;
        document.head.appendChild(style);
    }

    function moveToFullWidthParent(overviewCard, inverterRow) {
        document.querySelectorAll('#forcedOverviewInfoRow, .overview-table-info-row').forEach(wrapper => {
            if (wrapper.contains(overviewCard) && wrapper.parentElement) wrapper.parentElement.insertBefore(overviewCard, wrapper);
            if (!wrapper.children.length) wrapper.remove();
        });

        if (inverterRow?.parentElement) {
            inverterRow.parentElement.insertBefore(overviewCard, inverterRow);
            inverterRow.parentElement.style.setProperty('width', '100%', 'important');
            inverterRow.parentElement.style.setProperty('max-width', 'none', 'important');
            inverterRow.parentElement.style.setProperty('align-items', 'stretch', 'important');
        }

        overviewCard.classList.remove('col-span-9', 'lg:col-span-9', 'w-3/4', 'max-w-4xl', 'max-w-5xl', 'max-w-6xl');
        overviewCard.classList.add('overview-combined-card');
    }

    function collectValues(plantCard) {
        const values = {};
        plantCard?.querySelectorAll('.flex.justify-between').forEach(row => {
            const children = row.querySelectorAll(':scope > span');
            if (children.length >= 2) values[(children[0].textContent || '').trim()] = children[1];
        });
        return values;
    }

    function appendPlantInformationRows(table, values) {
        ['overviewPlantDetailsRow', 'overviewPlantLabelsRow', 'overviewPlantValuesRow'].forEach(id => document.getElementById(id)?.remove());

        const tbody = table.tBodies[0] || table.createTBody();
        const labels = ['Name', 'Capacity', 'Location', 'Service Number', 'Status'];
        const spans = [2, 1, 1, 1, 1];

        const labelRow = document.createElement('tr');
        labelRow.id = 'overviewPlantLabelsRow';
        labelRow.className = 'overview-info-labels';

        const valueRow = document.createElement('tr');
        valueRow.id = 'overviewPlantValuesRow';
        valueRow.className = 'overview-info-values';

        labels.forEach((label, index) => {
            const th = document.createElement('th');
            th.colSpan = spans[index];
            th.textContent = label;
            labelRow.appendChild(th);

            const td = document.createElement('td');
            td.colSpan = spans[index];
            td.appendChild(values[label] || document.createTextNode('--'));
            valueRow.appendChild(td);
        });

        tbody.append(labelRow, valueRow);
    }

    function syncOverviewWidth() {
        const overviewCard = findOverviewCard();
        const inverterPanel = findInverterPanel();
        if (!overviewCard || !inverterPanel) return;

        const width = Math.round(inverterPanel.getBoundingClientRect().width);
        if (width <= 0) return;

        overviewCard.style.setProperty('width', `${width}px`, 'important');
        overviewCard.style.setProperty('max-width', `${width}px`, 'important');
        overviewCard.style.setProperty('min-width', '0', 'important');
        overviewCard.style.setProperty('flex-basis', `${width}px`, 'important');
        overviewCard.style.setProperty('align-self', 'stretch', 'important');
    }

    function queueWidthSync() {
        if (syncQueued) return;
        syncQueued = true;
        requestAnimationFrame(() => {
            syncQueued = false;
            syncOverviewWidth();
        });
    }

    function applyLayout() {
        addStyles();
        const overviewCard = findOverviewCard();
        const table = overviewCard?.querySelector('table');
        const inverterRow = findInverterRow();
        if (!overviewCard || !table) return;

        moveToFullWidthParent(overviewCard, inverterRow);
        document.getElementById('overviewPlantDetailsRow')?.remove();

        const plantCard = findPlantInformationCard();
        if (plantCard && plantCard !== overviewCard) {
            appendPlantInformationRows(table, collectValues(plantCard));
            plantCard.remove();
        }

        if (inverterRow) inverterRow.classList.add('overview-inverter-full-row');
        queueWidthSync();
    }

    function startDataFallbacks() {
        if (dataStarted) return;
        dataStarted = true;
        try { if (typeof window.loadLatestSnapshot === 'function') window.loadLatestSnapshot(); } catch (error) { console.warn('[Overview] Latest snapshot load failed:', error); }
        try { if (typeof window.loadOverviewHourly === 'function') window.loadOverviewHourly(); } catch (error) { console.warn('[Overview] Hourly data load failed:', error); }
    }

    function apply() {
        applyLayout();
        startDataFallbacks();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply, { once:true });
    else apply();

    setTimeout(apply, 250);
    setTimeout(apply, 1000);
    setTimeout(apply, 2500);
    window.addEventListener('resize', queueWidthSync);

    const observer = new MutationObserver(queueWidthSync);
    observer.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
})();