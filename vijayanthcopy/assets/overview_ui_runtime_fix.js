(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    let scheduled = false;
    let observer = null;

    function numberFromText(text) {
        const match = String(text || '').replace(/,/g, '').match(/-?\d+(?:\.\d+)?/);
        return match ? Number(match[0]) : null;
    }

    function isRealInverterCard(card) {
        const label = card.querySelector('p:first-of-type')?.textContent || '';
        return /\binv(?:erter)?\s*0*\d+\b/i.test(label.trim());
    }

    function normalizePower(card) {
        const powerLine = card.querySelector('p:nth-of-type(2)');
        if (!powerLine) return;
        let value = numberFromText(powerLine.textContent);
        if (!Number.isFinite(value)) return;
        if (Math.abs(value) > 10000) value /= 1000;
        const decimals = Math.abs(value) >= 1000 ? 0 : 1;
        const expected = value.toFixed(decimals);
        const unitInline = powerLine.querySelector('.overview-power-unit');
        if (!unitInline || powerLine.children.length !== 2 || powerLine.querySelector('.overview-power-value')?.textContent !== expected) {
            powerLine.innerHTML = `<span class="overview-power-value">${expected}</span><span class="overview-power-unit">kW</span>`;
        }
    }

    function fixInverterGrid() {
        const grid = document.getElementById('inverterGrid');
        if (!grid) return;
        const cards = Array.from(grid.children).filter(card => !card.classList.contains('col-span-full'));
        cards.forEach(card => {
            if (!isRealInverterCard(card)) {
                card.remove();
                return;
            }
            card.classList.add('overview-inverter-card');
            normalizePower(card);
        });
        const validCount = Array.from(grid.children).filter(card => !card.classList.contains('col-span-full') && isRealInverterCard(card)).length;
        const count = document.getElementById('overviewInvCount');
        if (count && count.textContent !== String(validCount)) count.textContent = String(validCount);
    }

    function findPlantInformationCard() {
        const heading = Array.from(document.querySelectorAll('h3')).find(el =>
            (el.textContent || '').trim().toLowerCase() === 'plant information'
        );
        return heading?.closest('.bg-white') || null;
    }

    function findPlantOverviewCard() {
        const tableCell = document.getElementById('vcb_time') || document.getElementById('vcb_power') || document.getElementById('vcb_today');
        return tableCell?.closest('.bg-white') || null;
    }

    function createDetailCell(label, valueNode) {
        const item = document.createElement('div');
        item.className = 'min-w-0 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5';

        const labelEl = document.createElement('div');
        labelEl.className = 'text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1';
        labelEl.textContent = label;

        const valueWrap = document.createElement('div');
        valueWrap.className = 'text-xs font-extrabold text-slate-800 break-words';
        valueWrap.appendChild(valueNode);

        item.append(labelEl, valueWrap);
        return item;
    }

    function mergePlantDetailsIntoOverview() {
        const plantCard = findPlantInformationCard();
        const overviewCard = findPlantOverviewCard();
        const table = overviewCard?.querySelector('table');
        if (!plantCard || !overviewCard || !table || plantCard === overviewCard) return;
        if (table.querySelector('#overviewPlantDetailsRow')) {
            plantCard.remove();
            return;
        }

        const values = {};
        plantCard.querySelectorAll('.flex.justify-between').forEach(row => {
            const spans = row.querySelectorAll(':scope > span');
            if (spans.length < 2) return;
            const label = (spans[0].textContent || '').trim();
            values[label] = spans[1];
        });

        const tbody = table.tBodies[0] || table.createTBody();
        const detailsRow = document.createElement('tr');
        detailsRow.id = 'overviewPlantDetailsRow';

        const detailsCell = document.createElement('td');
        detailsCell.colSpan = Math.max(1, table.tHead?.rows[0]?.cells.length || 6);
        detailsCell.className = 'border border-t-0 bg-white p-0 text-left';

        const heading = document.createElement('div');
        heading.className = 'flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-2';
        heading.innerHTML = '<span class="text-xs font-black uppercase tracking-wider text-slate-700"><i class="fa-solid fa-circle-info mr-2 text-emerald-600"></i>Plant Details</span>';

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2 p-3';

        const fields = ['Name', 'Capacity', 'Location', 'Service Number', 'Status'];
        fields.forEach(label => {
            const valueNode = values[label] || document.createTextNode('--');
            grid.appendChild(createDetailCell(label, valueNode));
        });

        detailsCell.append(heading, grid);
        detailsRow.appendChild(detailsCell);
        tbody.appendChild(detailsRow);

        overviewCard.classList.add('overview-main-table-card', 'overflow-hidden');
        plantCard.remove();

        const oldRows = document.querySelectorAll('#forcedOverviewInfoRow, .overview-table-info-row');
        oldRows.forEach(row => {
            if (!row.isConnected) return;
            if (overviewCard.parentElement === row) row.parentElement?.insertBefore(overviewCard, row);
            if (!row.children.length) row.remove();
        });

        const inverterGrid = document.getElementById('inverterGrid');
        const inverterRow = inverterGrid?.closest('.grid.grid-cols-12');
        const inverterPanel = inverterGrid?.closest('.bg-white');
        if (inverterRow) {
            inverterRow.classList.add('overview-inverter-row');
            inverterRow.style.setProperty('display', 'block', 'important');
            inverterRow.style.setProperty('width', '100%', 'important');
        }
        if (inverterPanel) {
            inverterPanel.classList.add('overview-inverter-panel');
            inverterPanel.style.setProperty('width', '100%', 'important');
            inverterPanel.style.setProperty('max-width', 'none', 'important');
        }
    }

    function apply() {
        scheduled = false;
        fixInverterGrid();
        mergePlantDetailsIntoOverview();
    }

    function schedule() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(apply);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply, { once: true });
    else apply();

    const startObserver = () => {
        const content = document.querySelector('main');
        if (!content) {
            setTimeout(startObserver, 250);
            return;
        }
        observer = new MutationObserver(schedule);
        observer.observe(content, { childList: true, subtree: true });
        schedule();
    };

    startObserver();
    setTimeout(apply, 500);
    setTimeout(apply, 1500);
    setTimeout(apply, 3000);
})();
