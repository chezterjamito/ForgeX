<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
header("Content-Type: application/json");

$DEBUG = true;

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    require '../config/db.php';

    $stmt = $pdo->query("
        SELECT i.incident_id, i.description, i.severity, i.location, i.status, i.reported_at,
               i.latitude, i.longitude,
               u.username AS reported_by_username
        FROM incidents i
        JOIN users u ON u.user_id = i.reported_by
        ORDER BY i.reported_at DESC
        LIMIT 50
    ");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEBUG) {
        echo json_encode(["error" => "Request failed", "details" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    } else {
        echo json_encode(["error" => "Request failed"]);
    }
}
