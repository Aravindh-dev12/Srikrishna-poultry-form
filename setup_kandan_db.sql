CREATE DATABASE IF NOT EXISTS kandan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kandan;

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
