-- ForgeX anti-spam fix: run this once against your database before using the
-- updated report_incident.php. Adjust the database name below if yours differs.

ALTER TABLE incidents
  ADD COLUMN xp_submit_awarded INT NOT NULL DEFAULT 0 AFTER longitude,
  ADD COLUMN xp_verified_awarded TINYINT(1) NOT NULL DEFAULT 0 AFTER xp_submit_awarded;

-- xp_submit_awarded: how much "submission" XP this specific report actually earned
--                     (0 if it was on cooldown, a duplicate, or past the daily cap).
--                     Used to calculate the daily cap for future reports.
-- xp_verified_awarded: flag so an admin can't accidentally pay out the big
--                       "verified" XP bonus twice by re-saving the same incident.
