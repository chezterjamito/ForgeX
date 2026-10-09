-- Run once against an existing ForgeX database so login can read admin status.
ALTER TABLE users
    ADD COLUMN is_admin BOOLEAN NOT NULL DEFAULT 0;
