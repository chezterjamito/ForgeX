<?php
header("Content-Type: application/json");
require '../config/db.php';

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPowers = $pdo->query("SELECT COUNT(*) FROM powers")->fetchColumn();
$totalIncidents = $pdo->query("SELECT COUNT(*) FROM incidents")->fetchColumn();
$worldChanging = $pdo->query("SELECT COUNT(*) FROM powers WHERE is_world_changing = 1")->fetchColumn();

echo json_encode([
    "total_users" => (int) $totalUsers,
    "total_powers" => (int) $totalPowers,
    "total_incidents" => (int) $totalIncidents,
    "world_changing_powers" => (int) $worldChanging
]);
?>
