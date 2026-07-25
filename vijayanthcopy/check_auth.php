<?php
require 'config.php';
session_start();
if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) { header('Location: index.php'); exit; }
$user = $_SESSION['user'];
$role = strtolower((string)($user['role'] ?? 'user'));
$requested = strtolower(trim((string)($_GET['plant'] ?? '')));
$assigned = strtolower(trim((string)($user['plant_id'] ?? '')));
if ($role === 'admin') {
    $currentPlant = isset($PLANTS[$requested]) ? $requested : (isset($PLANTS['krishna']) ? 'krishna' : getDefaultPlantId());
} else {
    $currentPlant = isset($PLANTS[$assigned]) ? $assigned : (isset($PLANTS['krishna']) ? 'krishna' : getDefaultPlantId());
}
$_SESSION['user']['plant_id'] = $currentPlant;
$_SESSION['plant_id'] = $currentPlant;
?>