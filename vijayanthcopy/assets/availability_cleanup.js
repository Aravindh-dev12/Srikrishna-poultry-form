(function () {
    if (!/availability\.php$/i.test(window.location.pathname)) return;

    const removedTitles = [
        /grid\s+availability\s*\(\s*24h\s*\)/i,
        /plant\s+availability\s*\(\s*24h\s*\)/i
    ];

    function titleMatches(text) {
        return removedTitles.some(rx => rx.test(String(text || '').trim()));
    }

    function findChartCardFromCanvas(id) {
        const canvas = document.getElementById(id);
        if (!canvas) return null;
        return canvas.closest('.bg-white.rounded-lg') || canvas.closest('.bg-white') || null;
    }

    function removeCardSafely(card) {
        if (!card || card.dataset.availabilityRemoved === 'true') return;

        // Never remove the main page, the main content wrapper, or the sidebar.
        if (card.matches('main, main > div, #sidebar-container, #sidebar')) return;
        if (card.contains(document.getElementById('invTimeline'))) return;
        if (card.contains(document.getElementById('currentStatusChart'))) return;

        const parent = card.parentElement;
        card.dataset.availabilityRemoved = 'true';
        card.remove();

        // Remove only an empty chart grid wrapper, never a general content container.
        if (
            parent &&
            parent.children.length === 0 &&
            parent.matches('.grid.grid-cols-1.lg\\:grid-cols-2')
        ) {
            parent.remove();
        }
    }

    function removeExtraAvailabilityCharts() {
        const cards = new Set();

        ['gridAvailChart', 'plantAvailChart'].forEach(id => {
            const card = findChartCardFromCanvas(id);
            if (card) cards.add(card);
        });

        // Only inspect actual headings. Scanning generic div/span text can match
        // an entire page wrapper and accidentally remove all availability content.
        document.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(heading => {
            if (!titleMatches(heading.textContent)) return;
            const card = heading.closest('.bg-white.rounded-lg') || heading.closest('.bg-white');
            if (card) cards.add(card);
        });

        cards.forEach(removeCardSafely);
    }

    function scheduleRemoval() {
        removeExtraAvailabilityCharts();
        setTimeout(removeExtraAvailabilityCharts, 100);
        setTimeout(removeExtraAvailabilityCharts, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scheduleRemoval, { once: true });
    } else {
        scheduleRemoval();
    }

    window.addEventListener('load', scheduleRemoval, { once: true });
})();
