(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    let layoutApplied = false;
    let dataStarted = false;

    function findOverviewCard() {
        const marker = document.getElementById('vcb_time') || document.getElementById('vcb_power');
        return marker?.closest('.bg-white') || null;
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

    function addCombinedTableStyles() {
        if (document.getElementById('overviewCombinedTableStyles')) return;
        const style = document.createElement('style');
        style.id = 'overviewCombinedTableStyles';
        style.textContent = `
            .overview-combined-card { width: 100% !important; }
            .overview-combined-details-cell { padding: 0 !important; background: #fff !important; }
            .overview-combined-details-title {
                display: flex; align-items: center; gap: 8px;
                padding: 9px 14px; border-bottom: 1px solid #e2e8f0;
                background: #f8fafc; color: #334155;
                font-size: 11px; font-weight: 900; text-transform: uppercase;
                letter-spacing: .06em;
            }
            .overview-combined-details-grid {
                display: grid; grid-template-columns: repeat(5, minmax(0, 1fr));
            }
            .overview-combined-detail {
                min-width: 0; padding: 12px 14px; text-align: left;
                border-right: 1px solid #e2e8f0; background: #fff;
            }
            .overview-combined-detail:last-child { border-right: 0; }
            .overview-combined-detail-label {
                margin-bottom: 5px; color: #64748b; font-size: 9px;
                font-weight: 800; text-transform: uppercase; letter-spacing: .05em;
            }
            .overview-combined-detail-value {
                color: #1e293b; font-size: 11px; font-weight: 800;
                overflow-wrap: anywhere;
            }
            .overview-inverter-full-row { display: block !important; width: 100% !important; }
            .overview-inverter-full-row > .bg-white { width: 100% !important; max-width: none !important; }
            @media (max-width: 900px) {
                .overview-combined-details-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .overview-combined-detail { border-bottom: 1px solid #e2e8f0; }
            }
            @media (max-width: 520px) {
                .overview-combined-details-grid { grid-template-columns: 1fr; }
                .overview-combined-detail { border-right: 0; }
            }
        `;
        document.head.appendChild(style);
    }

    function makeDetail(label, valueNode) {
        const item = document.createElement('div');
        item.className = 'overview-combined-detail';

        const labelEl = document.createElement('div');
        labelEl.className = 'overview-combined-detail-label';
        labelEl.textContent = label;

        const valueEl = document.createElement('div');
        valueEl.className = 'overview-combined-detail-value';
        valueEl.appendChild(valueNode);

        item.append(labelEl, valueEl);
        return item;
    }

    function combinePlantInformation() {
        if (layoutApplied) return;
        addCombinedTableStyles();

        const overviewCard = findOverviewCard();
        const plantCard = findPlantInformationCard();
        const table = overviewCard?.querySelector('table');
        if (!overviewCard || !plantCard || !table || overviewCard === plantCard) return;

        const values = {};
        plantCard.querySelectorAll('.flex.justify-between').forEach(row => {
            const children = row.querySelectorAll(':scope > span');
            if (children.length < 2) return;
            values[(children[0].textContent || '').trim()] = children[1];
        });

        const detailsRow = document.createElement('tr');
        detailsRow.id = 'overviewPlantDetailsRow';
        const detailsCell = document.createElement('td');
        detailsCell.colSpan = Math.max(1, table.tHead?.rows[0]?.cells.length || 6);
        detailsCell.className = 'overview-combined-details-cell border';

        const title = document.createElement('div');
        title.className = 'overview-combined-details-title';
        title.innerHTML = '<i class="fa-solid fa-circle-info text-emerald-600"></i><span>Plant Information</span>';

        const grid = document.createElement('div');
        grid.className = 'overview-combined-details-grid';
        ['Name', 'Capacity', 'Location', 'Service Number', 'Status'].forEach(label => {
            const node = values[label] || document.createTextNode('--');
            grid.appendChild(makeDetail(label, node));
        });

        detailsCell.append(title, grid);
        detailsRow.appendChild(detailsCell);
        (table.tBodies[0] || table.createTBody()).appendChild(detailsRow);

        overviewCard.classList.add('overview-combined-card');
        plantCard.remove();

        const inverterRow = findInverterRow();
        if (inverterRow) inverterRow.classList.add('overview-inverter-full-row');

        document.querySelectorAll('#forcedOverviewInfoRow, .overview-table-info-row').forEach(wrapper => {
            if (overviewCard.parentElement === wrapper && wrapper.parentElement) {
                wrapper.parentElement.insertBefore(overviewCard, wrapper);
            }
            if (!wrapper.children.length) wrapper.remove();
        });

        layoutApplied = true;
    }

    function startDataFallbacks() {
        if (dataStarted) return;
        dataStarted = true;

        // The page already opens its WebSocket. These loaders provide cached/latest
        // values immediately and hourly chart data when the socket is slow or offline.
        try {
            if (typeof window.loadLatestSnapshot === 'function') window.loadLatestSnapshot();
        } catch (error) {
            console.warn('[Overview] Latest snapshot load failed:', error);
        }

        try {
            if (typeof window.loadOverviewHourly === 'function') window.loadOverviewHourly();
        } catch (error) {
            console.warn('[Overview] Hourly data load failed:', error);
        }
    }

    function apply() {
        combinePlantInformation();
        startDataFallbacks();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply, { once: true });
    } else {
        apply();
    }

    // Retry briefly because the script is injected after the page and other UI scripts.
    setTimeout(apply, 250);
    setTimeout(apply, 1000);
    setTimeout(apply, 2500);
})();
