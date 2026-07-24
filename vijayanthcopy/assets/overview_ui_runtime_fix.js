(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    let scheduled = false;

    function ensureLayoutStyles() {
        if (document.getElementById('overviewBelowInverterStyles')) return;

        const style = document.createElement('style');
        style.id = 'overviewBelowInverterStyles';
        style.textContent = `
            .overview-inverter-row-fixed {
                display: block !important;
                width: 100% !important;
                margin-bottom: 16px !important;
            }
            .overview-inverter-row-fixed > .overview-inverter-panel-fixed {
                width: 100% !important;
                max-width: none !important;
            }
            .overview-plant-info-below {
                display: block !important;
                width: 100% !important;
                max-width: none !important;
                margin: 0 0 16px 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
                border-radius: 12px !important;
            }
            .overview-plant-info-below > h3 {
                margin: 0 !important;
                padding: 11px 16px !important;
                border-bottom: 1px solid #e2e8f0 !important;
                background: #f8fafc !important;
                color: #1e293b !important;
                font-size: 13px !important;
                font-weight: 900 !important;
                letter-spacing: .04em !important;
                text-transform: uppercase !important;
            }
            .overview-plant-info-below > .space-y-2 {
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                gap: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .overview-plant-info-below > .space-y-2 > .flex.justify-between {
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: flex-start !important;
                gap: 5px !important;
                min-width: 0 !important;
                min-height: 72px !important;
                padding: 12px 16px !important;
                margin: 0 !important;
                border: 0 !important;
                border-right: 1px solid #e2e8f0 !important;
                background: #fff !important;
            }
            .overview-plant-info-below > .space-y-2 > .flex.justify-between:last-child {
                border-right: 0 !important;
            }
            .overview-plant-info-below > .space-y-2 > .flex.justify-between span:first-child {
                color: #64748b !important;
                font-size: 10px !important;
                font-weight: 800 !important;
                letter-spacing: .04em !important;
                text-transform: uppercase !important;
            }
            .overview-plant-info-below > .space-y-2 > .flex.justify-between span:last-child {
                color: #1e293b !important;
                font-size: 12px !important;
                font-weight: 800 !important;
                text-align: left !important;
                overflow-wrap: anywhere !important;
            }
            @media (max-width: 900px) {
                .overview-plant-info-below > .space-y-2 {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
                .overview-plant-info-below > .space-y-2 > .flex.justify-between {
                    border-bottom: 1px solid #e2e8f0 !important;
                }
            }
            @media (max-width: 520px) {
                .overview-plant-info-below > .space-y-2 {
                    grid-template-columns: 1fr !important;
                }
                .overview-plant-info-below > .space-y-2 > .flex.justify-between {
                    min-height: 60px !important;
                    border-right: 0 !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

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
        const valueEl = powerLine.querySelector('.overview-power-value');
        const unitEl = powerLine.querySelector('.overview-power-unit');
        if (!valueEl || !unitEl || valueEl.textContent !== expected) {
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

        const validCount = Array.from(grid.children)
            .filter(card => !card.classList.contains('col-span-full') && isRealInverterCard(card)).length;
        const count = document.getElementById('overviewInvCount');
        if (count && count.textContent !== String(validCount)) count.textContent = String(validCount);
    }

    function findPlantInformationCard() {
        const heading = Array.from(document.querySelectorAll('h3')).find(el =>
            (el.textContent || '').trim().toLowerCase() === 'plant information'
        );
        return heading?.closest('.bg-white') || null;
    }

    function restorePlantCardFromMergedRow() {
        const mergedRow = document.getElementById('overviewPlantDetailsRow');
        if (!mergedRow) return null;

        const labels = ['Name', 'Capacity', 'Location', 'Service Number', 'Status'];
        const valueBlocks = Array.from(mergedRow.querySelectorAll('.grid > div'));
        const card = document.createElement('div');
        card.className = 'bg-white rounded-lg shadow-sm border border-gray-200 p-4';

        const heading = document.createElement('h3');
        heading.className = 'text-sm font-bold text-gray-700 mb-3 border-b pb-2';
        heading.textContent = 'Plant Information';

        const details = document.createElement('div');
        details.className = 'space-y-2 text-xs';

        labels.forEach((label, index) => {
            const row = document.createElement('div');
            row.className = 'flex justify-between border-b border-gray-100 pb-1';
            const labelEl = document.createElement('span');
            labelEl.className = 'text-gray-500';
            labelEl.textContent = label;
            const valueEl = document.createElement('span');
            valueEl.className = 'font-semibold text-gray-800';

            const sourceValue = valueBlocks[index]?.querySelector('div:last-child');
            if (sourceValue) {
                while (sourceValue.firstChild) valueEl.appendChild(sourceValue.firstChild);
            } else {
                valueEl.textContent = '--';
            }
            if (label === 'Status' && !valueEl.id) valueEl.id = 'plantStatusBadge';

            row.append(labelEl, valueEl);
            details.appendChild(row);
        });

        card.append(heading, details);
        mergedRow.remove();
        return card;
    }

    function positionPlantInformationBelowInverters() {
        ensureLayoutStyles();

        const inverterGrid = document.getElementById('inverterGrid');
        const inverterRow = inverterGrid?.closest('.grid.grid-cols-12');
        const inverterPanel = inverterGrid?.closest('.bg-white');
        if (!inverterRow || !inverterPanel) return;

        let plantCard = findPlantInformationCard();
        if (!plantCard) plantCard = restorePlantCardFromMergedRow();
        if (!plantCard) return;

        inverterRow.classList.add('overview-inverter-row-fixed');
        inverterPanel.classList.add('overview-inverter-panel-fixed');
        plantCard.classList.add('overview-plant-info-below');

        if (plantCard.parentElement !== inverterRow.parentElement || plantCard.previousElementSibling !== inverterRow) {
            inverterRow.insertAdjacentElement('afterend', plantCard);
        }

        document.querySelectorAll('#forcedOverviewInfoRow, .overview-table-info-row').forEach(wrapper => {
            const overviewCard = wrapper.querySelector('#vcb_time')?.closest('.bg-white');
            if (overviewCard && wrapper.parentElement) wrapper.parentElement.insertBefore(overviewCard, wrapper);
            if (!wrapper.children.length) wrapper.remove();
        });
    }

    function apply() {
        scheduled = false;
        fixInverterGrid();
        positionPlantInformationBelowInverters();
    }

    function schedule() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(apply);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply, { once: true });
    } else {
        apply();
    }

    const startObserver = () => {
        const main = document.querySelector('main');
        if (!main) {
            setTimeout(startObserver, 250);
            return;
        }
        new MutationObserver(schedule).observe(main, { childList: true, subtree: true });
        schedule();
    };

    startObserver();
    setTimeout(apply, 500);
    setTimeout(apply, 1500);
    setTimeout(apply, 3000);
})();