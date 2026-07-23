(function () {
    if (!/overview\.php$/i.test(window.location.pathname)) return;

    function setImportant(el, prop, value) {
        if (el) el.style.setProperty(prop, value, 'important');
    }

    function ensureStyle() {
        let style = document.getElementById('overviewForcePlantInfoStyle');
        if (!style) {
            style = document.createElement('style');
            style.id = 'overviewForcePlantInfoStyle';
            document.head.appendChild(style);
        }

        style.textContent = `
            .overview-header-status-wrap {
                display: inline-flex !important;
                align-items: center !important;
                gap: 10px !important;
                min-width: 0 !important;
            }
            #headerPlantName {
                margin: 0 !important;
                line-height: 1.15 !important;
            }
            #overviewHeaderStatusBadge {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                height: 24px !important;
                padding: 0 11px !important;
                border-radius: 999px !important;
                font-size: 11px !important;
                font-weight: 900 !important;
                line-height: 1 !important;
                border: 1px solid #d1d5db !important;
                background: #f8fafc !important;
                color: #64748b !important;
                white-space: nowrap !important;
            }
            #overviewHeaderStatusBadge.is-live {
                color: #047857 !important;
                background: #ecfdf5 !important;
                border-color: #a7f3d0 !important;
            }
            #overviewHeaderStatusBadge.is-standby,
            #overviewHeaderStatusBadge.is-offline {
                color: #b91c1c !important;
                background: #fef2f2 !important;
                border-color: #fecaca !important;
            }
            #refreshPulse,
            .live-connect-status,
            .connection-status,
            [data-live-connect-status] {
                display: none !important;
            }

            #forcedOverviewInfoRow {
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) 320px !important;
                gap: 18px !important;
                align-items: stretch !important;
                width: 100% !important;
                margin: 0 0 20px 0 !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card,
            #forcedOverviewInfoRow .forced-plant-info-card {
                margin: 0 !important;
                min-height: 0 !important;
                max-height: none !important;
                align-self: stretch !important;
                overflow: hidden !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card {
                width: 100% !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card {
                width: 320px !important;
                max-width: 320px !important;
                padding: 0 !important;
                border-radius: 12px !important;
                justify-self: stretch !important;
                background: #fff !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card h3,
            #forcedOverviewInfoRow .forced-plant-overview-card > .bg-emerald-700,
            #forcedOverviewInfoRow .forced-plant-overview-card > .plant-table-heading {
                margin: 0 !important;
                min-height: 34px !important;
                height: 34px !important;
                padding: 7px 14px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: var(--plant-header-bg) !important;
                border-color: var(--plant-border) !important;
                color: #111827 !important;
                font-size: 14px !important;
                font-weight: 900 !important;
                line-height: 1.1 !important;
                letter-spacing: .01em !important;
                text-align: center !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card h3 {
                border-bottom: 1px solid var(--plant-border) !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card > .overflow-x-auto {
                height: auto !important;
                min-height: 0 !important;
                display: block !important;
                overflow-x: auto !important;
                overflow-y: hidden !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card table {
                height: auto !important;
                margin: 0 !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card table th,
            #forcedOverviewInfoRow .forced-plant-overview-card table td {
                height: 46px !important;
                padding-top: 11px !important;
                padding-bottom: 11px !important;
                line-height: 1.15 !important;
                vertical-align: middle !important;
                font-size: 13px !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card table th {
                font-size: 13px !important;
                font-weight: 900 !important;
            }
            #forcedOverviewInfoRow .forced-plant-overview-card table tbody,
            #forcedOverviewInfoRow .forced-plant-overview-card table tr {
                height: auto !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card .space-y-2 {
                display: grid !important;
                grid-template-columns: 1fr !important;
                gap: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card .flex.justify-between {
                display: grid !important;
                grid-template-columns: 110px minmax(0, 1fr) !important;
                gap: 8px !important;
                align-items: center !important;
                min-height: 25px !important;
                height: 25px !important;
                padding: 4px 14px !important;
                margin: 0 !important;
                border: 0 !important;
                border-bottom: 1px solid #eef2f7 !important;
                background: #fff !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card .flex.justify-between:last-child {
                border-bottom: 0 !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card .flex.justify-between span:first-child {
                color: #64748b !important;
                font-size: 10px !important;
                font-weight: 700 !important;
                line-height: 1.1 !important;
                white-space: nowrap !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card .flex.justify-between span:last-child {
                color: #111827 !important;
                font-size: 10.5px !important;
                font-weight: 800 !important;
                line-height: 1.1 !important;
                text-align: right !important;
                overflow-wrap: anywhere !important;
                min-width: 0 !important;
            }
            #forcedOverviewInfoRow .forced-plant-info-card #plantStatusBadge {
                color: #059669 !important;
                background: transparent !important;
                border: 0 !important;
                padding: 0 !important;
                justify-self: end !important;
            }
            .forced-overview-inverter-row {
                display: block !important;
                width: 100% !important;
                margin: 0 0 16px 0 !important;
            }
            .forced-overview-inverter-row > .bg-white {
                width: 100% !important;
                max-width: none !important;
            }
            .forced-overview-kpi-row {
                width: 100% !important;
                max-width: none !important;
                display: grid !important;
                grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
                gap: 12px !important;
                align-items: stretch !important;
            }
            .forced-overview-kpi-row > div {
                width: 100% !important;
                min-width: 0 !important;
            }
            @media (max-width: 1100px) {
                #forcedOverviewInfoRow {
                    grid-template-columns: 1fr !important;
                    align-items: start !important;
                }
                #forcedOverviewInfoRow .forced-plant-info-card {
                    width: 100% !important;
                    max-width: none !important;
                }
                .forced-overview-kpi-row {
                    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                }
            }
            @media (max-width: 640px) {
                .overview-header-status-wrap {
                    flex-wrap: wrap !important;
                    gap: 6px !important;
                }
                .forced-overview-kpi-row {
                    grid-template-columns: 1fr !important;
                }
            }
        `;
    }

    function findPlantInfoCard() {
        const h = Array.from(document.querySelectorAll('h3')).find(el =>
            (el.textContent || '').trim().toLowerCase() === 'plant information'
        );
        return h ? h.closest('.bg-white') : null;
    }

    function findPlantOverviewCard() {
        const marker = document.getElementById('vcb_time') || document.getElementById('vcb_power') || document.getElementById('vcb_total');
        return marker ? marker.closest('.bg-white') : null;
    }

    function findContentWrapper(overviewCard) {
        return overviewCard?.parentElement?.closest('main > div') || document.querySelector('main > div.p-4') || document.querySelector('main > div');
    }

    function ensureHeaderStatus() {
        const headerName = document.getElementById('headerPlantName');
        if (!headerName) return null;
        let wrap = headerName.closest('.overview-header-status-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.className = 'overview-header-status-wrap';
            headerName.parentNode.insertBefore(wrap, headerName);
            wrap.appendChild(headerName);
        }
        let badge = document.getElementById('overviewHeaderStatusBadge');
        if (!badge) {
            badge = document.createElement('span');
            badge.id = 'overviewHeaderStatusBadge';
            badge.textContent = 'Connecting';
            wrap.appendChild(badge);
        } else if (badge.parentElement !== wrap) {
            wrap.appendChild(badge);
        }
        return badge;
    }

    function mirrorStatusToHeader() {
        const headerBadge = ensureHeaderStatus();
        if (!headerBadge) return;
        const cardBadge = document.getElementById('plantStatusBadge');
        const value = (cardBadge?.textContent || '').trim() || 'Connecting';
        const normalized = /live/i.test(value) ? 'Live' : (/standby|offline|fault|disconnected/i.test(value) ? 'Offline' : 'Connecting');
        headerBadge.textContent = normalized;
        headerBadge.classList.toggle('is-live', normalized === 'Live');
        headerBadge.classList.toggle('is-offline', normalized === 'Offline');
        headerBadge.classList.toggle('is-standby', normalized === 'Offline');
    }

    function fixKpiCards() {
        const today = document.getElementById('today_energy_val');
        const row = today ? today.closest('.grid') : null;
        if (!row) return;
        row.classList.add('forced-overview-kpi-row');
        setImportant(row, 'width', '100%');
        setImportant(row, 'max-width', 'none');
        setImportant(row, 'display', 'grid');
        setImportant(row, 'grid-template-columns', window.innerWidth <= 640 ? '1fr' : (window.innerWidth <= 1100 ? 'repeat(2, minmax(0, 1fr))' : 'repeat(5, minmax(0, 1fr))'));
        setImportant(row, 'gap', '12px');
        Array.from(row.children).forEach(card => {
            setImportant(card, 'width', '100%');
            setImportant(card, 'min-width', '0');
        });
    }

    function balanceTopHeights(overviewCard, plantInfoCard) {
        if (!overviewCard || !plantInfoCard || window.innerWidth <= 1100) {
            if (overviewCard) setImportant(overviewCard, 'height', 'auto');
            if (plantInfoCard) setImportant(plantInfoCard, 'height', 'auto');
            return;
        }
        setImportant(overviewCard, 'height', 'auto');
        setImportant(plantInfoCard, 'height', 'auto');
        setImportant(overviewCard, 'min-height', '0');
        setImportant(plantInfoCard, 'min-height', '0');
        const targetHeight = Math.ceil(Math.max(overviewCard.scrollHeight, plantInfoCard.scrollHeight));
        setImportant(overviewCard, 'height', `${targetHeight}px`);
        setImportant(plantInfoCard, 'height', `${targetHeight}px`);
    }

    function fixPosition() {
        ensureStyle();
        const overviewCard = findPlantOverviewCard();
        const plantInfoCard = findPlantInfoCard();
        const content = findContentWrapper(overviewCard);
        if (!overviewCard || !plantInfoCard || !content || overviewCard === plantInfoCard) return;

        ensureHeaderStatus();
        mirrorStatusToHeader();

        setImportant(content, 'display', 'flex');
        setImportant(content, 'flex-direction', 'column');
        setImportant(content, 'gap', '0');
        setImportant(content, 'grid-template-columns', 'none');
        setImportant(content, 'align-items', 'stretch');

        let row = document.getElementById('forcedOverviewInfoRow');
        if (!row) {
            row = document.createElement('div');
            row.id = 'forcedOverviewInfoRow';
            content.insertBefore(row, content.firstElementChild || null);
        }

        if (overviewCard.parentElement !== row) row.appendChild(overviewCard);
        if (plantInfoCard.parentElement !== row) row.appendChild(plantInfoCard);

        overviewCard.classList.add('forced-plant-overview-card');
        plantInfoCard.classList.add('forced-plant-info-card');

        const desktop = window.innerWidth > 1100;
        setImportant(row, 'display', 'grid');
        setImportant(row, 'grid-template-columns', desktop ? 'minmax(0, 1fr) 320px' : '1fr');
        setImportant(row, 'gap', '18px');
        setImportant(row, 'align-items', desktop ? 'stretch' : 'start');
        setImportant(row, 'width', '100%');
        setImportant(row, 'height', 'auto');
        setImportant(row, 'margin', '0 0 20px 0');

        setImportant(overviewCard, 'grid-column', '1');
        setImportant(overviewCard, 'grid-row', '1');
        setImportant(overviewCard, 'width', '100%');
        setImportant(overviewCard, 'min-height', '0');
        setImportant(overviewCard, 'max-height', 'none');
        setImportant(overviewCard, 'margin', '0');
        setImportant(overviewCard, 'align-self', desktop ? 'stretch' : 'start');
        setImportant(overviewCard, 'overflow', 'hidden');

        setImportant(plantInfoCard, 'grid-column', desktop ? '2' : '1');
        setImportant(plantInfoCard, 'grid-row', desktop ? '1' : '2');
        setImportant(plantInfoCard, 'width', desktop ? '320px' : '100%');
        setImportant(plantInfoCard, 'max-width', desktop ? '320px' : 'none');
        setImportant(plantInfoCard, 'min-height', '0');
        setImportant(plantInfoCard, 'max-height', 'none');
        setImportant(plantInfoCard, 'margin', '0');
        setImportant(plantInfoCard, 'align-self', desktop ? 'stretch' : 'start');
        setImportant(plantInfoCard, 'justify-self', 'stretch');
        setImportant(plantInfoCard, 'overflow', 'hidden');

        balanceTopHeights(overviewCard, plantInfoCard);

        const inverterGrid = document.getElementById('inverterGrid');
        const inverterRow = inverterGrid ? inverterGrid.closest('.grid.grid-cols-12') : null;
        const inverterPanel = inverterGrid ? inverterGrid.closest('.bg-white') : null;
        if (inverterRow) {
            inverterRow.classList.add('forced-overview-inverter-row');
            setImportant(inverterRow, 'display', 'block');
            setImportant(inverterRow, 'width', '100%');
            setImportant(inverterRow, 'margin', '0 0 16px 0');
        }
        if (inverterPanel) {
            inverterPanel.classList.remove('lg:col-span-9');
            inverterPanel.classList.add('col-span-12');
            setImportant(inverterPanel, 'width', '100%');
            setImportant(inverterPanel, 'max-width', 'none');
            setImportant(inverterPanel, 'margin', '0');
        }

        fixKpiCards();
    }

    function start() {
        fixPosition();
        let count = 0;
        const timer = setInterval(() => {
            fixPosition();
            count += 1;
            if (count > 80) clearInterval(timer);
        }, 200);
        setInterval(mirrorStatusToHeader, 1000);
        window.addEventListener('resize', fixPosition);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();
