<?php
header("Content-Type: application/json");
session_start();
require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT user_id, username, password_hash, is_admin FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(["error" => "Invalid credentials"]);
    exit;
}

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['is_admin'] = (bool) $user['is_admin'];

echo json_encode([
    "message" => "Login successful",
    "user_id" => $user['user_id'],
    "username" => $user['username'],
    "is_admin" => (bool) $user['is_admin']
]);
?>
