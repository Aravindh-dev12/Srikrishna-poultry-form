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
            .overview-combined-card {
                width: 100% !important;
                max-width: none !important;
                margin-left: 0 !important;
                margin-right: 0 !important;
                border-radius: 14px !important;
                overflow: hidden !important;
                box-shadow: 0 8px 24px rgba(15, 23, 42, .06) !important;
            }
            .overview-combined-card > .bg-emerald-700 {
                min-height: 42px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 10px 18px !important;
                font-size: 15px !important;
                letter-spacing: .04em !important;
            }
            .overview-combined-card .overflow-x-auto {
                width: 100% !important;
            }
            .overview-combined-card table {
                width: 100% !important;
                min-width: 960px !important;
                border-collapse: separate !important;
                border-spacing: 0 !important;
            }
            .overview-combined-card table th {
                padding: 12px 14px !important;
                background: #f8fafc !important;
                color: #475569 !important;
                font-size: 11px !important;
                font-weight: 900 !important;
                letter-spacing: .025em !important;
                border-color: #e2e8f0 !important;
            }
            .overview-combined-card table td {
                padding: 13px 14px !important;
                font-size: 12px !important;
                border-color: #e2e8f0 !important;
            }
            .overview-combined-details-cell {
                padding: 0 !important;
                background: #fff !important;
                border-left: 1px solid #e2e8f0 !important;
                border-right: 1px solid #e2e8f0 !important;
                border-bottom: 1px solid #e2e8f0 !important;
            }
            .overview-combined-details-title {
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                padding: 10px 16px !important;
                border-bottom: 1px solid #e2e8f0 !important;
                background: linear-gradient(90deg, #f8fafc 0%, #f1f5f9 100%) !important;
                color: #334155 !important;
                font-size: 11px !important;
                font-weight: 900 !important;
                text-transform: uppercase !important;
                letter-spacing: .07em !important;
            }
            .overview-combined-details-grid {
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                width: 100% !important;
            }
            .overview-combined-detail {
                min-width: 0 !important;
                min-height: 82px !important;
                padding: 15px 18px !important;
                text-align: left !important;
                border-right: 1px solid #e2e8f0 !important;
                background: #fff !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
            }
            .overview-combined-detail:last-child { border-right: 0 !important; }
            .overview-combined-detail-label {
                margin-bottom: 7px !important;
                color: #64748b !important;
                font-size: 9px !important;
                font-weight: 900 !important;
                text-transform: uppercase !important;
                letter-spacing: .07em !important;
            }
            .overview-combined-detail-value {
                color: #0f172a !important;
                font-size: 13px !important;
                font-weight: 800 !important;
                line-height: 1.35 !important;
                overflow-wrap: anywhere !important;
            }
            #plantStatusBadge {
                display: inline-flex !important;
                width: fit-content !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 5px 10px !important;
                border-radius: 999px !important;
                background: #f1f5f9 !important;
            }
            #plantStatusBadge.text-emerald-600 {
                background: #ecfdf5 !important;
                color: #059669 !important;
            }
            .overview-inverter-full-row {
                display: block !important;
                width: 100% !important;
            }
            .overview-inverter-full-row > .bg-white {
                width: 100% !important;
                max-width: none !important;
            }
            @media (max-width: 900px) {
                .overview-combined-details-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
                .overview-combined-detail {
                    border-bottom: 1px solid #e2e8f0 !important;
                }
                .overview-combined-detail:nth-child(2n) { border-right: 0 !important; }
            }
            @media (max-width: 520px) {
                .overview-combined-card table { min-width: 760px !important; }
                .overview-combined-details-grid { grid-template-columns: 1fr !important; }
                .overview-combined-detail {
                    min-height: 68px !important;
                    border-right: 0 !important;
                }
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
        addCombinedTableStyles();

        const overviewCard = findOverviewCard();
        const table = overviewCard?.querySelector('table');
        if (!overviewCard || !table) return;

        overviewCard.classList.add('overview-combined-card');
        overviewCard.style.setProperty('width', '100%', 'important');
        overviewCard.style.setProperty('max-width', 'none', 'important');

        const existingRow = document.getElementById('overviewPlantDetailsRow');
        if (existingRow) {
            layoutApplied = true;
        } else {
            const plantCard = findPlantInformationCard();
            if (!plantCard || overviewCard === plantCard) return;

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
            detailsCell.className = 'overview-combined-details-cell';

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
            plantCard.remove();
            layoutApplied = true;
        }

        const inverterRow = findInverterRow();
        if (inverterRow) inverterRow.classList.add('overview-inverter-full-row');

        document.querySelectorAll('#forcedOverviewInfoRow, .overview-table-info-row').forEach(wrapper => {
            if (overviewCard.parentElement === wrapper && wrapper.parentElement) {
                wrapper.parentElement.insertBefore(overviewCard, wrapper);
            }
            if (!wrapper.children.length) wrapper.remove();
        });
    }

    function startDataFallbacks() {
        if (dataStarted) return;
        dataStarted = true;

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

    setTimeout(apply, 250);
    setTimeout(apply, 1000);
    setTimeout(apply, 2500);
})();
