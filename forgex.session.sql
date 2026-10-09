-- ForgeX database schema
-- Import with: mysql -u root -p forgex < forgex_schema.sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_public BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE powers (
    power_id INT AUTO_INCREMENT PRIMARY KEY,
    power_name VARCHAR(100) NOT NULL,
    category ENUM(
        'elemental',
        'mental',
        'physical',
        'bizarre',
        'reality',
        'social'
    ) NOT NULL,
    description TEXT,
    rarity ENUM('common', 'uncommon', 'rare', 'legendary') DEFAULT 'common',
    is_world_changing BOOLEAN DEFAULT 0
);
CREATE TABLE user_powers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    power_id INT NOT NULL,
    power_level INT DEFAULT 1,
    xp INT DEFAULT 0,
    discovered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (power_id) REFERENCES powers(power_id)
);
CREATE TABLE quiz_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    question_text VARCHAR(255) NOT NULL,
    maps_to_category ENUM(
        'elemental',
        'mental',
        'physical',
        'bizarre',
        'reality',
        'social'
    )
);
CREATE TABLE training_modules (
    module_id INT AUTO_INCREMENT PRIMARY KEY,
    power_id INT NOT NULL,
    title VARCHAR(150),
    description TEXT,
    difficulty ENUM('beginner', 'intermediate', 'advanced', 'master') DEFAULT 'beginner',
    xp_reward INT DEFAULT 50,
    FOREIGN KEY (power_id) REFERENCES powers(power_id)
);
CREATE TABLE user_training_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    module_id INT NOT NULL,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    progress_percent INT DEFAULT 0,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES training_modules(module_id)
);
CREATE TABLE incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    reported_by INT NOT NULL,
    involved_user_id INT NULL,
    power_id INT NULL,
    description TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
    location VARCHAR(150),
    status ENUM('open', 'investigating', 'resolved') DEFAULT 'open',
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES users(user_id),
    FOREIGN KEY (involved_user_id) REFERENCES users(user_id),
    FOREIGN KEY (power_id) REFERENCES powers(power_id)
);
CREATE TABLE society_zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100),
    restricted_category ENUM(
        'elemental',
        'mental',
        'physical',
        'bizarre',
        'reality',
        'social'
    ) NULL,
    description TEXT
);
-- ---- Sample seed data so the app isn't empty on first run ----
INSERT INTO powers (
        power_name,
        category,
        description,
        rarity,
        is_world_changing
    )
VALUES (
        'Pyrokinesis',
        'elemental',
        'Generate and control fire.',
        'uncommon',
        0
    ),
    (
        'Hydrokinesis',
        'elemental',
        'Manipulate water at will.',
        'uncommon',
        0
    ),
    (
        'Telepathy',
        'mental',
        'Read and project thoughts.',
        'rare',
        1
    ),
    (
        'Super Strength',
        'physical',
        'Lift far beyond human limits.',
        'common',
        0
    ),
    (
        'Talk to Houseplants',
        'bizarre',
        'Plants respond, but only complain.',
        'common',
        0
    ),
    (
        'Time Dilation',
        'reality',
        'Slow down time in a small radius.',
        'legendary',
        1
    ),
    (
        'Perfect Small Talk',
        'social',
        'Every conversation goes smoothly.',
        'common',
        0
    );
INSERT INTO quiz_questions (question_text, maps_to_category)
VALUES (
        'You are most drawn to nature\'s raw forces.',
        'elemental'
    ),
    (
        'You would rather out-think a problem than out-fight it.',
        'mental'
    ),
    (
        'You want to physically push your limits.',
        'physical'
    ),
    (
        'You are okay with your power being more funny than useful.',
        'bizarre'
    ),
    (
        'You are fascinated by bending the rules of reality itself.',
        'reality'
    ),
    (
        'You get energy from being around people.',
        'social'
    );
INSERT INTO training_modules (
        power_id,
        title,
        description,
        difficulty,
        xp_reward
    )
VALUES (
        1,
        'Spark Control',
        'Learn to summon a small, controlled flame.',
        'beginner',
        50
    ),
    (
        1,
        'Sustained Burn',
        'Hold a flame steady for over a minute.',
        'intermediate',
        100
    ),
    (
        3,
        'Reading Surface Thoughts',
        'Pick up simple, surface-level thoughts.',
        'beginner',
        50
    );