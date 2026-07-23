// auth.js - Per-tab plant guard for Vijayanath Solar Dashboard
// Uses sessionStorage only so each browser tab can display a different plant.
(function() {
    const token = sessionStorage.getItem('vs_token');
    const userStr = sessionStorage.getItem('vs_user');

    if (!token || !userStr) {
        window.location.replace('index.php');
        return;
    }

    let user;
    try {
        user = JSON.parse(userStr);
    } catch(e) {
        sessionStorage.removeItem('vs_token');
        sessionStorage.removeItem('vs_user');
        window.location.replace('index.php');
        return;
    }

    // Plant users can only access their assigned plant
    const urlParams = new URLSearchParams(window.location.search);
    const currentPlant = urlParams.get('plant') || '';

    if (user.role !== 'admin' && user.plant_id && currentPlant && currentPlant !== user.plant_id) {
        window.location.replace('overview.php?plant=' + encodeURIComponent(user.plant_id));
        return;
    }
})();
