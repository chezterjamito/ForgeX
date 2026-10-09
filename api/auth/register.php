<?php
header("Content-Type: application/json");
require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$email || !$password) {
    http_response_code(400);
    echo json_encode(["error" => "All fields are required"]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);
    if ($check->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(["error" => "Username or email already taken"]);
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hash]);

    echo json_encode(["message" => "Account created", "user_id" => $pdo->lastInsertId()]);
} catch (PDOException $e) {
    error_log('Registration database error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Registration failed",
        "details" => $e->getMessage()
    ]);
} catch (Throwable $e) {
    error_log('Registration server error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "error" => "Registration failed",
        "details" => $e->getMessage()
    ]);
}
?>
