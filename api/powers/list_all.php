<?php
header("Content-Type: application/json");
require '../config/db.php';

$category = $_GET['category'] ?? null;

if ($category) {
    $stmt = $pdo->prepare("SELECT * FROM powers WHERE category = ?");
    $stmt->execute([$category]);
} else {
    $stmt = $pdo->query("SELECT * FROM powers");
}

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
