<?php
// 200 XP per level. Included by any endpoint that changes a user's XP.
function recalc_level($pdo, $user_id, $power_id) {
    $stmt = $pdo->prepare("SELECT xp FROM user_powers WHERE user_id = ? AND power_id = ?");
    $stmt->execute([$user_id, $power_id]);
    $xp = (int) $stmt->fetchColumn();

    $level = intdiv($xp, 200) + 1;

    $update = $pdo->prepare("UPDATE user_powers SET power_level = ? WHERE user_id = ? AND power_id = ?");
    $update->execute([$level, $user_id, $power_id]);

    return $level;
}
?>
