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
    require '../config/helpers.php';

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
        $incidentId = $data['incident_id'];
        $status = $data['status'];

        $stmt = $pdo->prepare("UPDATE incidents
            SET resolved_at = CASE WHEN status <> 'resolved' AND ? = 'resolved' THEN CURRENT_TIMESTAMP ELSE resolved_at END,
                status = ?
            WHERE incident_id = ?");
        $stmt->execute([$status, $status, $incidentId]);

        $xpAwardedThisCall = 0;

        // Verified XP is paid out exactly once, the first time an incident is
        // marked Resolved — xp_verified_awarded guards against paying it again
        // if the admin flips the status back and forth.
        if ($status === 'resolved') {
            $incStmt = $pdo->prepare("SELECT reported_by, severity, xp_verified_awarded FROM incidents WHERE incident_id = ?");
            $incStmt->execute([$incidentId]);
            $incident = $incStmt->fetch(PDO::FETCH_ASSOC);

            if ($incident && !$incident['xp_verified_awarded']) {
                $verifiedXpTable = ["low" => 10, "medium" => 25, "high" => 50, "critical" => 100];
                $xpAward = $verifiedXpTable[$incident['severity']] ?? 0;

                $powerStmt = $pdo->prepare("SELECT power_id FROM user_powers WHERE user_id = ?");
                $powerStmt->execute([$incident['reported_by']]);
                $power_id = $powerStmt->fetchColumn();

                if ($power_id && $xpAward > 0) {
                    $update = $pdo->prepare("UPDATE user_powers SET xp = xp + ? WHERE user_id = ? AND power_id = ?");
                    $update->execute([$xpAward, $incident['reported_by'], $power_id]);
                    recalc_level($pdo, $incident['reported_by'], $power_id);
                    $xpAwardedThisCall = $xpAward;
                }

                $flagStmt = $pdo->prepare("UPDATE incidents SET xp_verified_awarded = 1 WHERE incident_id = ?");
                $flagStmt->execute([$incidentId]);
            }
        }

        echo json_encode([
            "message" => "Incident status updated",
            "xp_awarded" => $xpAwardedThisCall
        ]);
        exit;
    }

    if ($method === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"), true);
        if (!is_array($data) || empty($data['incident_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "incident_id is required"]);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM incidents WHERE incident_id = ?");
        $stmt->execute([$data['incident_id']]);
        echo json_encode(["message" => "Incident deleted"]);
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
