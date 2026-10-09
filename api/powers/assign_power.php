<?php
header("Content-Type: application/json");
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not authenticated"]);
    exit;
}
$user_id = $_SESSION['user_id'];

$data = json_decode(file_get_contents("php://input"), true);
$answers = $data['answers'] ?? [];

$tally = [];
foreach ($answers as $a) {
    $stmt = $pdo->prepare("SELECT maps_to_category FROM quiz_questions WHERE question_id = ?");
    $stmt->execute([$a['question_id']]);
    $cat = $stmt->fetchColumn();
    if ($cat) $tally[$cat] = ($tally[$cat] ?? 0) + 1;
}
arsort($tally);
$winning_category = array_key_first($tally) ?? 'bizarre';

$stmt = $pdo->prepare("SELECT power_id FROM powers WHERE category = ? ORDER BY RAND() LIMIT 1");
$stmt->execute([$winning_category]);
$power_id = $stmt->fetchColumn();

if (!$power_id) {
    // fallback: no power exists yet for that category, pick any random power
    $stmt = $pdo->query("SELECT power_id FROM powers ORDER BY RAND() LIMIT 1");
    $power_id = $stmt->fetchColumn();
}

$insert = $pdo->prepare("INSERT INTO user_powers (user_id, power_id) VALUES (?, ?)");
$insert->execute([$user_id, $power_id]);

$powerStmt = $pdo->prepare("SELECT power_name, category, description, rarity FROM powers WHERE power_id = ?");
$powerStmt->execute([$power_id]);

echo json_encode(["message" => "Power assigned", "power" => $powerStmt->fetch(PDO::FETCH_ASSOC)]);
?>
