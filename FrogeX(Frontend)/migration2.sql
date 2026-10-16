-- ForgeX incident fixes: run this against the selected ForgeX database.
-- Uses INFORMATION_SCHEMA checks so this works on MySQL versions without
-- ALTER TABLE ... ADD COLUMN IF NOT EXISTS and can be safely rerun.

SET @column_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'incidents'
    AND COLUMN_NAME = 'xp_verified_awarded'
);
SET @migration_sql = IF(
  @column_exists = 0,
  'ALTER TABLE incidents ADD COLUMN xp_verified_awarded TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

SET @column_exists = (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'incidents'
    AND COLUMN_NAME = 'resolved_at'
);
SET @migration_sql = IF(
  @column_exists = 0,
  'ALTER TABLE incidents ADD COLUMN resolved_at TIMESTAMP NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE migration_stmt FROM @migration_sql;
EXECUTE migration_stmt;
DEALLOCATE PREPARE migration_stmt;

-- If you ran the earlier migration.sql, you'll also have an xp_submit_awarded
-- column sitting around. It's no longer used (there's no more "submission" XP
-- tier at all now), so it's harmless to leave, or you can drop it:
-- ALTER TABLE incidents DROP COLUMN xp_submit_awarded;

-- xp_verified_awarded: flags that an incident's verified XP has already been
-- paid out, so an admin flipping the status back and forth can't pay it twice.
-- resolved_at: the time an admin first accepts (resolves) the incident, used
-- to enforce the five-minute reporting cooldown.
