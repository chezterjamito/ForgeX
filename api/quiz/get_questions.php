<?php
header("Content-Type: application/json");
require '../config/db.php';

$stmt = $pdo->query("SELECT question_id, question_text FROM quiz_questions");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
