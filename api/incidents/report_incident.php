<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
header("Content-Type: application/json");

$DEBUG = true; // set to false once this is confirmed working

set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    session_start();
    require '../config/db.php';
    require '../config/helpers.php';

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Not authenticated"]);
        exit;
    }
    $user_id = $_SESSION['user_id'];

    $data = json_decode(file_get_contents("php://input"), true);
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid or missing JSON body"]);
        exit;
    }

    $description = trim($data['description'] ?? '');
    $severity = $data['severity'] ?? 'low';

    if (!$description) {
        http_response_code(400);
        echo json_encode(["error" => "Description is required"]);
        exit;
    }

    $xpTable = ["low" => 10, "medium" => 25, "high" => 50, "critical" => 100];
    if (!array_key_exists($severity, $xpTable)) {
        http_response_code(400);
        echo json_encode(["error" => "severity must be one of: low, medium, high, critical"]);
        exit;
    }
    $xpAward = $xpTable[$severity];

    // Coordinates are optional — only present if the user allowed browser geolocation
    $latitude = isset($data['latitude']) && $data['latitude'] !== '' ? (float) $data['latitude'] : null;
    $longitude = isset($data['longitude']) && $data['longitude'] !== '' ? (float) $data['longitude'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO incidents (reported_by, involved_user_id, power_id, description, severity, location, latitude, longitude)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id,
        $data['involved_user_id'] ?? null,
        $data['power_id'] ?? null,
        $description,
        $severity,
        $data['location'] ?? null,
        $latitude,
        $longitude
    ]);
    $incidentId = $pdo->lastInsertId();

    $powerStmt = $pdo->prepare("SELECT power_id FROM user_powers WHERE user_id = ?");
    $powerStmt->execute([$user_id]);
    $power_id = $powerStmt->fetchColumn();

    $newLevel = null;
    $totalXp = null;

    if ($power_id) {
        $update = $pdo->prepare("UPDATE user_powers SET xp = xp + ? WHERE user_id = ? AND power_id = ?");
        $update->execute([$xpAward, $user_id, $power_id]);
        $newLevel = recalc_level($pdo, $user_id, $power_id);

        $xpStmt = $pdo->prepare("SELECT xp FROM user_powers WHERE user_id = ? AND power_id = ?");
        $xpStmt->execute([$user_id, $power_id]);
        $totalXp = (int) $xpStmt->fetchColumn();
    } else {
        $xpAward = 0;
    }

    echo json_encode([
        "message" => "Incident reported",
        "incident_id" => $incidentId,
        "severity" => $severity,
        "xp_awarded" => $xpAward,
        "total_xp" => $totalXp,
        "new_level" => $newLevel
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEBUG) {
        echo json_encode(["error" => "Request failed", "details" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    } else {
        echo json_encode(["error" => "Request failed"]);
    }
}
