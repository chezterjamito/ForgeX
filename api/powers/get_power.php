<?php
header("Content-Type: application/json");
require '../config/db.php';

$user_id = $_GET['user_id'] ?? null;
if (!$user_id) {
    http_response_code(400);
    echo json_encode(["error" => "user_id is required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.username, p.power_name, p.category, p.description, p.rarity,
           up.power_level, up.xp
    FROM user_powers up
    JOIN users u ON u.user_id = up.user_id
    JOIN powers p ON p.power_id = up.power_id
    WHERE up.user_id = ? AND u.is_public = 1
");
$stmt->execute([$user_id]);
$power = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$power) {
    http_response_code(404);
    echo json_encode(["error" => "Power not found or profile is private"]);
    exit;
}

echo json_encode($power);
?>
