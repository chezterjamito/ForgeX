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
        $sql = "SELECT u.user_id, u.username, u.email, u.is_admin, u.is_public, u.created_at,
                       up.power_id, p.power_name, up.power_level, up.xp
                FROM users u
                LEFT JOIN user_powers up ON up.user_id = u.user_id
                LEFT JOIN powers p ON p.power_id = up.power_id
                ORDER BY u.user_id";
        echo json_encode($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC));
        exit;
    }

    if ($method === 'PUT') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data) || empty($data['user_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "user_id is required"]);
            exit;
        }
        $user_id = $data['user_id'];

        // ---- Admin flag toggle (kept for programmatic/future use) ----
        if (array_key_exists('is_admin', $data)) {
            if (!$data['is_admin']) {
                $count = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1")->fetchColumn();
                if ($count <= 1) {
                    http_response_code(400);
                    echo json_encode(["error" => "Cannot remove the last remaining admin"]);
                    exit;
                }
            }
            $stmt = $pdo->prepare("UPDATE users SET is_admin=? WHERE user_id=?");
            $stmt->execute([!empty($data['is_admin']) ? 1 : 0, $user_id]);
        }

        // ---- Power / level / XP edit ----
        $editingPower = array_key_exists('power_id', $data) || array_key_exists('power_level', $data) || array_key_exists('xp', $data);

        if ($editingPower) {
            $check = $pdo->prepare("SELECT id FROM user_powers WHERE user_id = ?");
            $check->execute([$user_id]);
            $exists = $check->fetch();

            if ($exists) {
                $stmt = $pdo->prepare("
                    UPDATE user_powers SET
                        power_id = COALESCE(?, power_id),
                        power_level = COALESCE(?, power_level),
                        xp = COALESCE(?, xp)
                    WHERE user_id = ?
                ");
                $stmt->execute([
                    array_key_exists('power_id', $data) ? $data['power_id'] : null,
                    array_key_exists('power_level', $data) ? $data['power_level'] : null,
                    array_key_exists('xp', $data) ? $data['xp'] : null,
                    $user_id
                ]);
            } else {
                if (empty($data['power_id'])) {
                    http_response_code(400);
                    echo json_encode(["error" => "This user hasn't discovered a power yet — choose a power to assign one"]);
                    exit;
                }
                $stmt = $pdo->prepare("INSERT INTO user_powers (user_id, power_id, power_level, xp) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $user_id,
                    $data['power_id'],
                    $data['power_level'] ?? 1,
                    $data['xp'] ?? 0
                ]);
            }
        }

        echo json_encode(["message" => "User updated"]);
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
