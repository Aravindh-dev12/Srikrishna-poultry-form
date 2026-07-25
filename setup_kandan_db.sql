CREATE DATABASE IF NOT EXISTS kandan
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE kandan;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    plant_id VARCHAR(64) NOT NULL DEFAULT 'kandan',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_plant_role (plant_id, role, is_active)
) ENGINE=InnoDB;

-- Default Kandan accounts requested for the server sign-in page.
-- Passwords are stored as bcrypt hashes, never as plain text.
-- admin@kandan.com / admin@123
-- kandan@scada.com / landan@123
INSERT INTO users (email, password_hash, display_name, role, plant_id, is_active)
VALUES
    ('admin@kandan.com', '$2y$12$OZ3dUvwNklas4nY2YyLyZ.Bmx4cxxSqvIgLH6F2PfxbWqUEKzqx42', 'Administrator', 'admin', 'kandan', 1),
    ('kandan@scada.com', '$2y$12$YRIh14puisikIpylFngvGum6W0UwYiFyWAUY1MQoK/OEiJNoUfaea', 'Kandan User', 'user', 'kandan', 1)
ON DUPLICATE KEY UPDATE
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    role = VALUES(role),
    plant_id = VALUES(plant_id),
    is_active = VALUES(is_active);

CREATE TABLE IF NOT EXISTS inverter_latest (
    plant_id VARCHAR(64) NOT NULL DEFAULT 'kandan',
    inverter_name VARCHAR(64) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    power_kw DECIMAL(12,3) NOT NULL DEFAULT 0,
    dc_power_kw DECIMAL(12,3) NOT NULL DEFAULT 0,
    daily_gen_kwh DECIMAL(14,3) NOT NULL DEFAULT 0,
    total_gen_kwh DECIMAL(18,3) NOT NULL DEFAULT 0,
    internal_temp_c DECIMAL(8,3) NOT NULL DEFAULT 0,
    fault_code VARCHAR(128) NOT NULL DEFAULT '',
    strings_json JSON NULL,
    history_json JSON NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (plant_id, inverter_name),
    INDEX idx_latest_plant_snapshot (plant_id, snapshot_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inverter_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(64) NOT NULL DEFAULT 'kandan',
    inverter_name VARCHAR(64) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    power_kw DECIMAL(12,3) NOT NULL DEFAULT 0,
    dc_power_kw DECIMAL(12,3) NOT NULL DEFAULT 0,
    daily_gen_kwh DECIMAL(14,3) NOT NULL DEFAULT 0,
    total_gen_kwh DECIMAL(18,3) NOT NULL DEFAULT 0,
    internal_temp_c DECIMAL(8,3) NOT NULL DEFAULT 0,
    fault_code VARCHAR(128) NOT NULL DEFAULT '',
    strings_json JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_plant_inverter_time (plant_id, inverter_name, snapshot_at),
    INDEX idx_history_plant_snapshot (plant_id, snapshot_at)
) ENGINE=InnoDB;
