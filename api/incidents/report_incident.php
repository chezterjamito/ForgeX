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

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Not authenticated"]);
        exit;
    }
    $user_id = $_SESSION['user_id'];

    // An accepted report (status = resolved) starts a five-minute reporting
    // cooldown. Calculate it in MySQL so its clock matches resolved_at.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $cooldownStmt = $pdo->prepare("SELECT GREATEST(0, 300 - TIMESTAMPDIFF(SECOND, MAX(resolved_at), CURRENT_TIMESTAMP))
            FROM incidents WHERE reported_by = ? AND resolved_at IS NOT NULL");
        $cooldownStmt->execute([$user_id]);
        $remaining = (int) $cooldownStmt->fetchColumn();
        echo json_encode(["cooldown_seconds" => $remaining]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["error" => "Method not allowed"]);
        exit;
    }

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

    $cooldownStmt = $pdo->prepare("SELECT GREATEST(0, 300 - TIMESTAMPDIFF(SECOND, MAX(resolved_at), CURRENT_TIMESTAMP))
        FROM incidents WHERE reported_by = ? AND resolved_at IS NOT NULL");
    $cooldownStmt->execute([$user_id]);
    $cooldownSeconds = (int) $cooldownStmt->fetchColumn();
    if ($cooldownSeconds > 0) {
        http_response_code(429);
        echo json_encode([
            "error" => "You can report another incident after the cooldown.",
            "cooldown_seconds" => $cooldownSeconds
        ]);
        exit;
    }

    $pendingXpTable = ["low" => 10, "medium" => 25, "high" => 50, "critical" => 100];
    if (!array_key_exists($severity, $pendingXpTable)) {
        http_response_code(400);
        echo json_encode(["error" => "severity must be one of: low, medium, high, critical"]);
        exit;
    }

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

    echo json_encode([
        "message" => "Incident reported",
        "incident_id" => $incidentId,
        "severity" => $severity,
        "xp_awarded" => 0,
        "xp_pending_admin_acceptance" => $pendingXpTable[$severity]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    if ($DEBUG) {
        echo json_encode(["error" => "Request failed", "details" => $e->getMessage(), "file" => $e->getFile(), "line" => $e->getLine()]);
    } else {
        echo json_encode(["error" => "Request failed"]);
    }
}
