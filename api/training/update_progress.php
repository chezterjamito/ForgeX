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

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Not authenticated"]);
        exit;
    }
    $user_id = $_SESSION['user_id'];

    $data = json_decode(file_get_contents("php://input"), true);
    $module_id = $data['module_id'] ?? null;
    if (!$module_id) {
        http_response_code(400);
        echo json_encode(["error" => "module_id is required"]);
        exit;
    }

    // performance: 0.0 - 1.0 score reported by the training mini-game.
    // The server, not the client, decides what that score is worth — never
    // trust a client-sent xp/progress value directly.
    $performance = isset($data['performance']) ? (float) $data['performance'] : 0.4;
    $performance = max(0.0, min(1.0, $performance));

    $PASS_THRESHOLD = 0.4;
    $passed = $performance >= $PASS_THRESHOLD;

    $progressPercent = (int) round($performance * 100);
    $status = $passed ? 'completed' : 'in_progress';

    $stmt = $pdo->prepare("
        INSERT INTO user_training_progress (user_id, module_id, status, progress_percent, completed_at)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            progress_percent = VALUES(progress_percent),
            completed_at = VALUES(completed_at)
    ");
    $completedAt = $passed ? date('Y-m-d H:i:s') : null;
    $stmt->execute([$user_id, $module_id, $status, $progressPercent, $completedAt]);

    $xpAwarded = 0;
    $newLevel = null;

    if ($passed) {
        $modStmt = $pdo->prepare("SELECT xp_reward, power_id FROM training_modules WHERE module_id = ?");
        $modStmt->execute([$module_id]);
        $mod = $modStmt->fetch(PDO::FETCH_ASSOC);

        if (!$mod) {
            http_response_code(404);
            echo json_encode(["error" => "Module not found"]);
            exit;
        }

        // Scale the reward by how well the challenge was played: a bare pass
        // pays roughly half the listed reward, a flawless run pays it in full.
        $multiplier = 0.5 + 0.5 * $performance;
        $xpAwarded = (int) round($mod['xp_reward'] * $multiplier);

        $update = $pdo->prepare("UPDATE user_powers SET xp = xp + ? WHERE user_id = ? AND power_id = ?");
        $update->execute([$xpAwarded, $user_id, $mod['power_id']]);

        $newLevel = recalc_level($pdo, $user_id, $mod['power_id']);
    }

    echo json_encode([
        "message" => $passed ? "Challenge passed" : "Challenge not passed",
        "passed" => $passed,
        "performance" => $performance,
        "xp_awarded" => $xpAwarded,
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
