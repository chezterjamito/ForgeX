<?php
header("Content-Type: application/json");
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.power_id, p.power_name, p.category, p.description, p.rarity,
           up.power_level, up.xp
    FROM user_powers up
    JOIN powers p ON p.power_id = up.power_id
    WHERE up.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$power = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$power) {
    http_response_code(404);
    echo json_encode(["error" => "No power assigned yet"]);
    exit;
}

echo json_encode($power);
?>
