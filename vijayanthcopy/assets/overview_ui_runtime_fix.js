(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    let scheduled = false;

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
        const byTableCell = tableCell?.closest('.bg-white');
        if (byTableCell) return byTableCell;

        return Array.from(document.querySelectorAll('.bg-white')).find(card => {
            const text = (card.textContent || '').replace(/\s+/g, ' ').trim();
            return /PLANT OVERVIEW/i.test(text) && /Active Power/i.test(text) && /Life Time Energy/i.test(text);
        }) || null;
    }

    function positionPlantInformation() {
        const plantCard = findPlantInformationCard();
        const overviewTable = findPlantOverviewCard();
        const content = document.querySelector('main > div.p-4, main > div.sm\:p-6, main > div');
        if (!plantCard || !overviewTable || !content || plantCard === overviewTable) return;

        plantCard.classList.add('overview-plant-info-card');
        overviewTable.classList.add('overview-main-table-card');

        let sideRow = document.querySelector('.overview-table-info-row');
        if (!sideRow) {
            sideRow = document.createElement('div');
            sideRow.className = 'overview-table-info-row';
            content.insertBefore(sideRow, overviewTable);
        }

        // Always rebuild this row so Plant Information never remains beside the inverter cards.
        if (overviewTable.parentElement !== sideRow) sideRow.appendChild(overviewTable);
        if (plantCard.parentElement !== sideRow) sideRow.appendChild(plantCard);

        const inverterGrid = document.getElementById('inverterGrid');
        const inverterRow = inverterGrid?.closest('.grid.grid-cols-12');
        const inverterPanel = inverterGrid?.closest('.bg-white');
        if (inverterRow) inverterRow.classList.add('overview-inverter-row');
        if (inverterPanel) inverterPanel.classList.add('overview-inverter-panel');
    }

    function apply() {
        scheduled = false;
        fixInverterGrid();
        positionPlantInformation();
    }

    function schedule() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(apply);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply, { once: true });
    else apply();

    const observer = new MutationObserver(schedule);
    const startObserver = () => {
        const content = document.querySelector('main');
        if (!content) {
            setTimeout(startObserver, 250);
            return;
        }
        observer.observe(content, { childList: true, subtree: true, characterData: true });
        schedule();
    };

    startObserver();
    setTimeout(apply, 500);
    setTimeout(apply, 1500);
    setTimeout(apply, 3000);
})();
