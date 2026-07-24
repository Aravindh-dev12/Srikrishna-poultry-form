<?php
require 'config.php';
session_start();

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$email = strtolower(trim((string)($user['email'] ?? '')));
$role = strtolower((string)($user['role'] ?? 'user'));

$emailPlantMap = [
    'bojaraj@scada.com' => 'vijayanth',
    'krishna@scada.com' => 'krishna',
    'vijayanth@scada.com' => 'vijayanth_cosmic',
];

if ($role !== 'admin') {
    $assignedPlant = $emailPlantMap[$email] ?? strtolower(trim((string)($user['plant_id'] ?? '')));

    if (!isset($PLANTS[$assignedPlant])) {
        session_unset();
        session_destroy();
        header('Location: index.php?expired=1');
        exit;
    }

    $currentPlant = $assignedPlant;
    $_SESSION['user']['plant_id'] = $currentPlant;
    $_SESSION['plant_id'] = $currentPlant;
    $user = $_SESSION['user'];
} elseif (isset($_GET['plant']) && isset($PLANTS[$_GET['plant']])) {
    $currentPlant = $_GET['plant'];
} else {
    $currentPlant = getDefaultPlantId();
}

if (!isset($PLANTS[$currentPlant])) {
    $currentPlant = getDefaultPlantId();
}

if (basename($_SERVER['SCRIPT_NAME']) === 'admin.php' && $role !== 'admin') {
    header('Location: overview.php?plant=' . urlencode($currentPlant));
    exit;
}

$signedPlantIdJson = json_encode($currentPlant, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$signedRoleJson = json_encode($role, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$signedConfigJson = getPlantPublicConfigJson();
$currentPage = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));

ob_start(function ($html) use ($signedPlantIdJson, $signedRoleJson, $signedConfigJson, $currentPage) {
    if (stripos($html, '</body>') === false) return $html;

    $injection = "\n<script>window.SIGNED_PLANT_ID={$signedPlantIdJson};window.SIGNED_USER_ROLE={$signedRoleJson};window.SIGNED_PLANT_CONFIG={$signedConfigJson};</script>";
    $injection .= "\n<link rel=\"stylesheet\" href=\"assets/plant_ui_refinements.css?v=20260721-7\">";

    if ($currentPage === 'overview.php') {
        $injection .= "\n<link rel=\"stylesheet\" href=\"assets/overview_inverter_ui.css?v=20260721-5\">";
        $injection .= "\n<link rel=\"stylesheet\" href=\"assets/overview_compact_headers.css?v=20260721-2\">";
        $injection .= "\n<script src=\"assets/inverter3_fix.js?v=20260721-4\"></script>";
        $injection .= "\n<script src=\"assets/overview_ui_runtime_fix.js?v=20260724-5\"></script>";
        $injection .= "\n<script src=\"assets/overview_week_generation.js?v=20260724-2\"></script>";
    }

    if ($currentPage === 'sld.php') {
        $injection .= "\n<script src=\"assets/sld_live_dynamic.js?v=20260721-1\"></script>";
    }

    if ($currentPage === 'admin.php') {
        $injection .= "\n<script src=\"assets/admin_peak_hour.js?v=20260721-3\"></script>";
    }

    if ($currentPage === 'availability.php') {
        $injection .= "\n<script src=\"assets/availability_cleanup.js?v=20260721-2\"></script>";
    }

    $injection .= "\n<script src=\"assets/signed_plant_context.js?v=20260721-8\"></script>\n";

    return preg_replace('/<\/body>/i', $injection . '</body>', $html, 1);
});
?>