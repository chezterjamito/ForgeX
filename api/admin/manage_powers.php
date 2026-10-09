<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
header("Content-Type: application/json");

$DEBUG = true; // set to false once everything is confirmed working

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
        echo json_encode($pdo->query("SELECT * FROM powers ORDER BY power_id")->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing JSON body"]);
        exit;
    }

    if ($method === 'POST') {
        if (empty($data['power_name']) || empty($data['category'])) {
            http_response_code(400);
            echo json_encode(["error" => "power_name and category are required"]);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO powers (power_name, category, description, rarity, is_world_changing) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['power_name'],
            $data['category'],
            $data['description'] ?? '',
            $data['rarity'] ?? 'common',
            !empty($data['is_world_changing']) ? 1 : 0
        ]);
        echo json_encode(["message" => "Power created", "power_id" => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        if (empty($data['power_id']) || empty($data['power_name']) || empty($data['category'])) {
            http_response_code(400);
            echo json_encode(["error" => "power_id, power_name, and category are required"]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE powers SET power_name=?, category=?, description=?, rarity=?, is_world_changing=? WHERE power_id=?");
        $stmt->execute([
            $data['power_name'],
            $data['category'],
            $data['description'] ?? '',
            $data['rarity'] ?? 'common',
            !empty($data['is_world_changing']) ? 1 : 0,
            $data['power_id']
        ]);
        echo json_encode(["message" => "Power updated"]);
        exit;
    }

    if ($method === 'DELETE') {
        if (empty($data['power_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "power_id is required"]);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM powers WHERE power_id=?");
        $stmt->execute([$data['power_id']]);
        echo json_encode(["message" => "Power deleted"]);
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
