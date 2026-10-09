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
        $sql = "SELECT m.*, p.power_name FROM training_modules m JOIN powers p ON p.power_id = m.power_id ORDER BY m.module_id";
        echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && !is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing JSON body"]);
        exit;
    }

    if ($method === 'POST') {
        if (empty($data['power_id']) || empty($data['title'])) {
            http_response_code(400);
            echo json_encode(["error" => "power_id and title are required"]);
            exit;
        }
        $stmt = $pdo->prepare("INSERT INTO training_modules (power_id, title, description, difficulty, xp_reward) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$data['power_id'], $data['title'], $data['description'] ?? '', $data['difficulty'] ?? 'beginner', $data['xp_reward'] ?? 50]);
        echo json_encode(["message" => "Module created", "module_id" => $pdo->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        if (empty($data['module_id']) || empty($data['power_id']) || empty($data['title'])) {
            http_response_code(400);
            echo json_encode(["error" => "module_id, power_id, and title are required"]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE training_modules SET power_id=?, title=?, description=?, difficulty=?, xp_reward=? WHERE module_id=?");
        $stmt->execute([$data['power_id'], $data['title'], $data['description'] ?? '', $data['difficulty'] ?? 'beginner', $data['xp_reward'] ?? 50, $data['module_id']]);
        echo json_encode(["message" => "Module updated"]);
        exit;
    }

    if ($method === 'DELETE') {
        if (empty($data['module_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "module_id is required"]);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM training_modules WHERE module_id=?");
        $stmt->execute([$data['module_id']]);
        echo json_encode(["message" => "Module deleted"]);
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
