-- ForgeX anti-spam fix v2: run this against your database before using the
-- updated report_incident.php / manage_incidents.php. Safe to run even if you
-- already ran the previous migration.sql (XAMPP's MariaDB supports
-- "ADD COLUMN IF NOT EXISTS", unlike plain MySQL).

ALTER TABLE incidents
  ADD COLUMN IF NOT EXISTS xp_verified_awarded TINYINT(1) NOT NULL DEFAULT 0;

-- If you ran the earlier migration.sql, you'll also have an xp_submit_awarded
-- column sitting around. It's no longer used (there's no more "submission" XP
-- tier at all now), so it's harmless to leave, or you can drop it:
-- ALTER TABLE incidents DROP COLUMN xp_submit_awarded;

-- xp_verified_awarded: flags that an incident's verified XP has already been
-- paid out, so an admin flipping the status back and forth can't pay it twice.
