<?php
header("Content-Type: application/json");
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}
$user_id = $_SESSION['user_id'];

$userStmt = $pdo->prepare("SELECT username, email, is_public, bio, created_at, is_admin FROM users WHERE user_id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$powerStmt = $pdo->prepare("
    SELECT p.power_id, p.power_name, p.category, p.description, p.rarity,
           up.power_level, up.xp, up.discovered_at
    FROM user_powers up JOIN powers p ON p.power_id = up.power_id
    WHERE up.user_id = ?
");
$powerStmt->execute([$user_id]);
$power = $powerStmt->fetch(PDO::FETCH_ASSOC);

$modCount = $pdo->prepare("SELECT COUNT(*) FROM user_training_progress WHERE user_id = ? AND status = 'completed'");
$modCount->execute([$user_id]);

$incCount = $pdo->prepare("SELECT COUNT(*) FROM incidents WHERE reported_by = ?");
$incCount->execute([$user_id]);

echo json_encode([
    "user" => $user,
    "power" => $power ?: null,
    "modules_completed" => (int) $modCount->fetchColumn(),
    "incidents_reported" => (int) $incCount->fetchColumn()
]);
?>
