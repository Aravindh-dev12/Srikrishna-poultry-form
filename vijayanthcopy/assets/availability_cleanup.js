(function () {
    if (!/availability\.php$/i.test(window.location.pathname)) return;

    const removedTitles = [
        /grid\s+availability\s*\(\s*24h\s*\)/i,
        /plant\s+availability\s*\(\s*24h\s*\)/i
    ];

    function titleMatches(text) {
        return removedTitles.some(rx => rx.test(String(text || '').trim()));
    }

    function removeCard(card) {
        if (!card || card.dataset.availabilityRemoved === 'true') return;
        card.dataset.availabilityRemoved = 'true';
        card.remove();
    }

    function removeEmptyParentGrid(node) {
        const parent = node?.parentElement;
        if (!parent) return;
        const hasOnlyRemovedChartLayout = parent.className && /grid/.test(parent.className) && !parent.children.length;
        if (hasOnlyRemovedChartLayout) parent.remove();
    }

    function findChartCardFromCanvas(id) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        return canvas.closest('.bg-white') || canvas.parentElement?.parentElement || null;
    }

    function removeExtraAvailabilityCharts() {
        const cards = new Set();

        ['gridAvailChart', 'plantAvailChart'].forEach(id => {
            const card = findChartCardFromCanvas(id);
            if (card) cards.add(card);
        });

        document.querySelectorAll('h1,h2,h3,h4,div,span').forEach(el => {
            if (!titleMatches(el.textContent)) return;
            const card = el.closest('.bg-white') || el.closest('[class*="rounded"]') || el.parentElement;
            if (card) cards.add(card);
        });

        cards.forEach(card => {
            const parent = card.parentElement;
            removeCard(card);
            if (parent && /grid/.test(parent.className || '') && !parent.children.length) parent.remove();
        });
    }

    function scheduleRemoval() {
        removeExtraAvailabilityCharts();
        setTimeout(removeExtraAvailabilityCharts, 100);
        setTimeout(removeExtraAvailabilityCharts, 500);
        setTimeout(removeExtraAvailabilityCharts, 1200);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleRemoval, { once: true });
    } else {
        scheduleRemoval();
    }

    window.addEventListener('load', scheduleRemoval, { once: true });

    const observer = new MutationObserver(scheduleRemoval);
    observer.observe(document.documentElement, { childList: true, subtree: true });
})();