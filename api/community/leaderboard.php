<?php
header("Content-Type: application/json");
require '../config/db.php';

$sort = $_GET['sort'] ?? 'xp';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$allowedSort = ['xp', 'power_level'];
if (!in_array($sort, $allowedSort)) $sort = 'xp';

$sql = "SELECT u.username, p.power_name, p.category, up.power_level, up.xp
        FROM user_powers up
        JOIN users u ON u.user_id = up.user_id
        JOIN powers p ON p.power_id = up.power_id
        WHERE u.is_public = 1
        ORDER BY up.$sort $order
        LIMIT 50";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
