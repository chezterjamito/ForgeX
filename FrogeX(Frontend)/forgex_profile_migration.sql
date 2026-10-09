-- Run once on an existing ForgeX database to enable the profile bio field.
ALTER TABLE users
    ADD COLUMN bio VARCHAR(255) NULL;
