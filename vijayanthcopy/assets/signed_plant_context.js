(function () {
    const plantId = String(window.SIGNED_PLANT_ID || '').trim();
    const config = window.SIGNED_PLANT_CONFIG || {};
    const role = String(window.SIGNED_USER_ROLE || 'user').toLowerCase();
    if (!plantId || !config[plantId]) return;

    const plant = config[plantId];
    const plantName = plant.name || plantId.replace(/[-_]/g, ' ');
    const capacity = Number(plant.capacity || 0);
    const location = plant.location || '';
    const unitId = plant.ws_unit_id || '';

    const themeMap = {
        vijayanth: { key: 'violet', main: '#8b5cf6', headerBg: '#ede9fe', border: '#ddd6fe' },
        bojaraj: { key: 'violet', main: '#8b5cf6', headerBg: '#ede9fe', border: '#ddd6fe' },
        krishna: { key: 'emerald', main: '#10b981', headerBg: '#d1fae5', border: '#a7f3d0' },
        vijayanth_cosmic: { key: 'blue', main: '#3b82f6', headerBg: '#dbeafe', border: '#bfdbfe' }
    };
    const theme = themeMap[plantId] || themeMap.vijayanth_cosmic;

    function syncBrowserPlant() {
        let storedUser = {};
        try { storedUser = JSON.parse(sessionStorage.getItem('vs_user') || '{}'); } catch (e) {}
        if (role !== 'admin') {
            storedUser.plant_id = plantId;
            storedUser.role = storedUser.role || role;
            sessionStorage.setItem('vs_user', JSON.stringify(storedUser));
            sessionStorage.setItem('vs_current_plant', plantId);
        }
    }

    function installThemeStyles() {
        if (document.getElementById('plant-ui-theme-style')) return;
        const style = document.createElement('style');
        style.id = 'plant-ui-theme-style';
        style.textContent = `
            :root {
                --plant-main: ${theme.main};
                --plant-header-bg: ${theme.headerBg};
                --plant-border: ${theme.border};
            }
            #sidebar { background: #fff !important; border-right-color: #e2e8f0 !important; }
            #sidebar #sidebarHeader,
            #sidebar #sidebarNav,
            #sidebar > div:last-child { background: transparent !important; }
            #sidebar #sidebarNav {
                overflow-y: auto !important;
                scrollbar-width: none !important;
                -ms-overflow-style: none !important;
            }
            #sidebar #sidebarNav::-webkit-scrollbar {
                width: 0 !important;
                height: 0 !important;
                display: none !important;
            }
            #sidebar #logoWrapper {
                background: #f1f5f9 !important;
                color: var(--plant-main) !important;
                box-shadow: none !important;
                border: 1px solid #e2e8f0 !important;
            }
            #sidebar #sidebarPlantName,
            #sidebar #sidebarNav .nav-item,
            #sidebar #adminBackDashboard,
            body[data-plant-theme] main header h1,
            body[data-plant-theme] main header h2,
            body[data-plant-theme] .plant-themed-text { color: #111827 !important; }
            #sidebar #sidebarNav .nav-item {
                position: relative;
                background: transparent !important;
                border-left-color: transparent !important;
            }
            #sidebar #sidebarNav .nav-item > i {
                width: 20px !important;
                height: 20px !important;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: var(--plant-main) !important;
                background: transparent !important;
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            #sidebar #sidebarNav .nav-item:hover,
            #sidebar #sidebarNav .plant-active-nav,
            #sidebar #adminBackDashboard:hover {
                background: #f8fafc !important;
                color: #111827 !important;
            }
            #sidebar #sidebarNav .plant-active-nav {
                border-left: 4px solid var(--plant-main) !important;
                font-weight: 700 !important;
            }
            #sidebar #sidebarNav .plant-active-nav > i {
                color: var(--plant-main) !important;
                background: transparent !important;
                border: 0 !important;
                box-shadow: none !important;
            }
            #sidebar #adminBackDashboard { background: transparent !important; }
            #sidebar #collapseSidebarBtn {
                color: var(--plant-main) !important;
                border-color: var(--plant-border) !important;
                background: #fff !important;
            }
            body[data-plant-theme] table thead,
            body[data-plant-theme] .plant-table-heading,
            body[data-plant-theme] .bg-emerald-700 {
                background: var(--plant-header-bg) !important;
                color: #111827 !important;
                border-color: var(--plant-border) !important;
            }
            body[data-plant-theme] table thead th,
            body[data-plant-theme] .plant-table-heading * { color: #111827 !important; }
        `;
        document.head.appendChild(style);
    }

    function applyTheme() {
        installThemeStyles();
        document.body?.setAttribute('data-plant-theme', theme.key);
        document.documentElement.style.setProperty('--plant-main', theme.main);
        document.documentElement.style.setProperty('--plant-header-bg', theme.headerBg);
        document.documentElement.style.setProperty('--plant-border', theme.border);

        const footerBrand = document.querySelector('#sidebar .text-emerald-700');
        if (footerBrand) {
            footerBrand.classList.remove('text-emerald-700');
            footerBrand.style.color = '#111827';
        }

        const currentPage = (window.location.pathname.split('/').pop() || 'overview.php').toLowerCase();
        document.querySelectorAll('#sidebarNav a').forEach(link => {
            link.classList.remove('bg-emerald-100', 'text-emerald-700', 'border-emerald-600', 'plant-active-nav');
            let linkPage = '';
            try { linkPage = new URL(link.getAttribute('href') || '', window.location.href).pathname.split('/').pop().toLowerCase(); } catch (e) {}
            if (linkPage === currentPage) link.classList.add('plant-active-nav');
        });
    }

    function fixSidebarAndLinks() {
        const sidebarName = document.getElementById('sidebarPlantName');
        if (sidebarName && sidebarName.textContent !== plantName) sidebarName.textContent = plantName;
        document.querySelectorAll('#sidebarNav a').forEach(link => {
            const raw = link.getAttribute('href') || '';
            if (!raw || raw.includes('logout.php')) return;
            try {
                const url = new URL(raw, window.location.href);
                if (url.searchParams.get('plant') !== plantId) {
                    url.searchParams.set('plant', plantId);
                    link.setAttribute('href', url.pathname.split('/').pop() + url.search);
                }
            } catch (e) {}
        });
        applyTheme();
    }

    function ensureReportsPlant() {
        if (!/reports\.php$/i.test(window.location.pathname)) return;
        try {
            if (typeof plants !== 'undefined' && Array.isArray(plants)) {
                const existing = plants.find(item => item.id === plantId);
                const entry = { id: plantId, name: plantName, unit_id: unitId, capacity, location };
                if (existing) Object.assign(existing, entry); else plants.push(entry);
            }
        } catch (e) {}
        const select = document.getElementById('plantSelect');
        if (select) {
            let option = Array.from(select.options).find(item => item.value === plantId);
            if (!option) { option = document.createElement('option'); option.value = plantId; select.appendChild(option); }
            option.textContent = plantName;
            select.value = plantId;
            if (role !== 'admin') { select.style.display = 'none'; select.disabled = true; }
        }
        const nameEl = document.getElementById('reportPlantName');
        const locationEl = document.getElementById('reportLocation');
        const capacityEl = document.getElementById('reportCapacity');
        if (nameEl) nameEl.textContent = plantName.toUpperCase();
        if (locationEl) locationEl.textContent = location || '--';
        if (capacityEl) capacityEl.textContent = `${capacity.toFixed(1)} MW`;
    }

    function correctUrlForPlantUser() {
        if (role === 'admin') return;
        const url = new URL(window.location.href);
        if (url.searchParams.get('plant') !== plantId) {
            url.searchParams.set('plant', plantId);
            history.replaceState(null, '', url.pathname + url.search + url.hash);
        }
    }

    function apply() {
        syncBrowserPlant();
        correctUrlForPlantUser();
        fixSidebarAndLinks();
        ensureReportsPlant();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply, { once: true });
    else apply();

    const sidebarContainer = document.getElementById('sidebar-container');
    if (sidebarContainer) {
        const observer = new MutationObserver(() => {
            fixSidebarAndLinks();
            if (document.getElementById('sidebarNav')) observer.disconnect();
        });
        observer.observe(sidebarContainer, { childList: true, subtree: true });
    }

    setTimeout(apply, 250);
    setTimeout(apply, 1000);
})();