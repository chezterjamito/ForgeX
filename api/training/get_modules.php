<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
header("Content-Type: application/json");

$DEBUG = true;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    session_start();
    require '../config/db.php';

    $power_id = $_GET['power_id'] ?? null;
    if (!$power_id) {
        http_response_code(400);
        echo json_encode(["error" => "power_id is required"]);
        exit;
    }

    $user_id = $_SESSION['user_id'] ?? null;

    // category is included so the frontend can pick the right training mini-game
    $stmt = $pdo->prepare("
        SELECT m.module_id, m.title, m.description, m.difficulty, m.xp_reward,
               p.category,
               COALESCE(up.status, 'not_started') AS status,
               COALESCE(up.progress_percent, 0) AS progress_percent
        FROM training_modules m
        JOIN powers p ON p.power_id = m.power_id
        LEFT JOIN user_training_progress up
            ON up.module_id = m.module_id AND up.user_id = ?
        WHERE m.power_id = ?
    ");
    $stmt->execute([$user_id, $power_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEBUG) {
        echo json_encode(["error" => "Request failed", "details" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    } else {
        echo json_encode(["error" => "Request failed"]);
    }
}
