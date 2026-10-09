<?php
// TEMPORARY DEBUG MODE — shows the real error instead of a generic message.
// Set this back to false once login/register works.
$DEBUG = true;

$host = "localhost";
$dbname = "ForgeX";      // <-- must match exactly what SHOW DATABASES; gave you
$username = "root";
$password = "";           // <-- leave blank only if you never set a MySQL root password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header("Content-Type: application/json");
    if ($DEBUG) {
        echo json_encode(["error" => "Database connection failed", "details" => $e->getMessage()]);
    } else {
        echo json_encode(["error" => "Database connection failed"]);
    }
    exit;
}
