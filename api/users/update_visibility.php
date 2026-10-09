<?php
header("Content-Type: application/json");
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$is_public = isset($data['is_public']) && $data['is_public'] ? 1 : 0;

$stmt = $pdo->prepare("UPDATE users SET is_public = ? WHERE user_id = ?");
$stmt->execute([$is_public, $_SESSION['user_id']]);

echo json_encode(["message" => "Visibility updated"]);
?>
