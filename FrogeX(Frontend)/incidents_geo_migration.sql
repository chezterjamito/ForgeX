-- ============================================================
-- ForgeX UPDATE script — adds map support to incident reports.
-- Safe to run on your existing database; only adds columns.
-- Usage: mysql -u root -p ForgeX < incidents_geo_migration.sql
-- ============================================================

ALTER TABLE incidents ADD COLUMN latitude DECIMAL(10,7) NULL;
ALTER TABLE incidents ADD COLUMN longitude DECIMAL(10,7) NULL;
