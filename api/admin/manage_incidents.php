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

    if (empty($_SESSION['is_admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Admins only"]);
        exit;
    }

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $sql = "SELECT i.*, u.username AS reported_by_username
                FROM incidents i JOIN users u ON u.user_id = i.reported_by
                ORDER BY i.reported_at DESC";
        echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data) || empty($data['incident_id']) || empty($data['status'])) {
            http_response_code(400);
            echo json_encode(["error" => "incident_id and status are required"]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE incidents SET status=? WHERE incident_id=?");
        $stmt->execute([$data['status'], $data['incident_id']]);
        echo json_encode(["message" => "Incident status updated"]);
        exit;
    }

    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEBUG) {
        echo json_encode(["error" => "Request failed", "details" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    } else {
        echo json_encode(["error" => "Request failed"]);
    }
}
