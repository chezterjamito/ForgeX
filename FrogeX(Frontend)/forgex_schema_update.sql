-- ============================================================
-- ForgeX UPDATE script — run this on your EXISTING database.
-- It only ADDS things, nothing is dropped or deleted.
-- Usage:  mysql -u root -p ForgeX < forgex_schema_update.sql
-- ============================================================

-- 1. New columns needed for admin + profile features
ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN bio VARCHAR(255) NULL;

-- 1b. Bug fix: without this unique key, "Mark Complete" on a training module
--     was inserting a duplicate progress row every time instead of updating it.
ALTER TABLE user_training_progress ADD UNIQUE KEY unique_user_module (user_id, module_id);

-- 2. Make YOURSELF the admin — edit the username below to your own, then run this line.
--    (Uncomment by removing the leading "-- " before running, or run it separately in cmd.)
-- UPDATE users SET is_admin = 1 WHERE username = 'yourusername';

-- 3. Hard training modules (advanced/master tier, big XP payouts)
INSERT INTO training_modules (power_id, title, description, difficulty, xp_reward) VALUES
(1, 'Inferno Mastery', 'Sustain a fire large enough to light a room without losing control.', 'advanced', 250),
(1, 'Firestorm', 'Summon and independently direct multiple simultaneous flames.', 'master', 500),
(2, 'Wave Manipulation', 'Move a body of water several meters through open air.', 'advanced', 250),
(2, 'Tsunami Control', 'Redirect a large wave without letting it collapse.', 'master', 500),
(3, 'Deep Thought Reading', 'Access stored memories, not just surface-level thoughts.', 'advanced', 250),
(3, 'Mass Telepathy', 'Project a single thought to an entire crowd at once.', 'master', 500),
(4, 'Vehicle Lift', 'Lift a car off the ground completely unaided.', 'advanced', 250),
(4, 'Structural Support', 'Hold up a collapsing structure long enough for evacuation.', 'master', 500),
(6, 'Extended Dilation', 'Hold a time-slow field steady for over 5 minutes.', 'advanced', 300),
(6, 'Localized Time Stop', 'Freeze time completely within a small radius.', 'master', 600);
