-- Vijayanath Solar: Single database setup for all plants
-- Run as: mysql -u root -p < setup_db.sql
-- All plant data is stored in one DB: vijayanth

CREATE DATABASE IF NOT EXISTS vijayanth;

USE vijayanth;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plant_id VARCHAR(50) DEFAULT '',
    auth_token VARCHAR(128) DEFAULT NULL,
    role VARCHAR(20) DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inverter_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    inverter_name VARCHAR(50) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    power_kw DECIMAL(10,3) NOT NULL DEFAULT 0,
    reactive_kvar DECIMAL(10,3) NOT NULL DEFAULT 0,
    power_factor DECIMAL(6,4) NOT NULL DEFAULT 0,
    vac_ab DECIMAL(8,2) NOT NULL DEFAULT 0,
    vac_bc DECIMAL(8,2) NOT NULL DEFAULT 0,
    vac_ca DECIMAL(8,2) NOT NULL DEFAULT 0,
    frequency_hz DECIMAL(6,2) NOT NULL DEFAULT 0,
    current_a DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_b DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_c DECIMAL(8,2) NOT NULL DEFAULT 0,
    efficiency DECIMAL(5,2) NOT NULL DEFAULT 0,
    ambient_temp DECIMAL(5,2) NOT NULL DEFAULT 0,
    daily_gen_kwh DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_gen_kwh DECIMAL(15,2) NOT NULL DEFAULT 0,
    daily_co2_kg DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_co2_kg DECIMAL(15,2) NOT NULL DEFAULT 0,
    daily_hours DECIMAL(6,2) NOT NULL DEFAULT 0,
    total_hours DECIMAL(10,2) NOT NULL DEFAULT 0,
    active_strings INT NOT NULL DEFAULT 0,
    total_strings INT NOT NULL DEFAULT 0,
    has_alarm TINYINT(1) NOT NULL DEFAULT 0,
    has_fault TINYINT(1) NOT NULL DEFAULT 0,
    fault_code VARCHAR(50) NOT NULL DEFAULT '',
    work_state VARCHAR(50) NOT NULL DEFAULT '',
    status_text VARCHAR(100) NOT NULL DEFAULT '',
    UNIQUE KEY unique_inv_snapshot (plant_id, inverter_name, snapshot_at),
    KEY idx_inv_plant_snapshot (plant_id, snapshot_at),
    KEY idx_inv_plant_name_snapshot (plant_id, inverter_name, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inverter_strings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    inverter_name VARCHAR(50) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    string_n INT NOT NULL,
    current_a DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_string_snapshot (plant_id, inverter_name, snapshot_at, string_n),
    KEY idx_str_plant_name_snapshot (plant_id, inverter_name, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inverter_alarm_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    inverter_name VARCHAR(50) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    severity VARCHAR(20) NOT NULL DEFAULT 'alarm',
    fault_code VARCHAR(50) NOT NULL DEFAULT '',
    work_state VARCHAR(50) NOT NULL DEFAULT '',
    status_text VARCHAR(255) NOT NULL DEFAULT '',
    alert_json JSON NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_alarm_event (plant_id, inverter_name, snapshot_at, severity, fault_code, work_state),
    KEY idx_alarm_active (plant_id, active, snapshot_at),
    KEY idx_alarm_inverter (plant_id, inverter_name, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vcb_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    device_name VARCHAR(50) NOT NULL DEFAULT 'VCB',
    snapshot_at DATETIME NOT NULL,
    power_3phase_kw DECIMAL(10,3) NOT NULL DEFAULT 0,
    frequency_hz DECIMAL(6,2) NOT NULL DEFAULT 0,
    voltage_r_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_y_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_b_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_ry_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_yb_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    voltage_br_v DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_r_a DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_y_a DECIMAL(8,2) NOT NULL DEFAULT 0,
    current_b_a DECIMAL(8,2) NOT NULL DEFAULT 0,
    power_r_kw DECIMAL(10,3) NOT NULL DEFAULT 0,
    power_y_kw DECIMAL(10,3) NOT NULL DEFAULT 0,
    power_b_kw DECIMAL(10,3) NOT NULL DEFAULT 0,
    pf_q1 DECIMAL(6,4) NOT NULL DEFAULT 0,
    pf_q2 DECIMAL(6,4) NOT NULL DEFAULT 0,
    pf_q3 DECIMAL(6,4) NOT NULL DEFAULT 0,
    vthd_r DECIMAL(6,2) NOT NULL DEFAULT 0,
    vthd_y DECIMAL(6,2) NOT NULL DEFAULT 0,
    vthd_b DECIMAL(6,2) NOT NULL DEFAULT 0,
    active_export_kwh DECIMAL(12,2) NOT NULL DEFAULT 0,
    active_import_kwh DECIMAL(12,2) NOT NULL DEFAULT 0,
    reactive_import_kvar DECIMAL(12,2) NOT NULL DEFAULT 0,
    reactive_export_kvar DECIMAL(12,2) NOT NULL DEFAULT 0,
    today_energy_kwh DECIMAL(12,2) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_vcb_snapshot (plant_id, device_name, snapshot_at),
    KEY idx_vcb_plant_snapshot (plant_id, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transformer_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    device_name VARCHAR(50) NOT NULL DEFAULT 'Transformer',
    snapshot_at DATETIME NOT NULL,
    oil_temp_c DECIMAL(5,2) NOT NULL DEFAULT 0,
    winding_temp_c DECIMAL(5,2) NOT NULL DEFAULT 0,
    UNIQUE KEY unique_trafo_snapshot (plant_id, device_name, snapshot_at),
    KEY idx_trafo_plant_snapshot (plant_id, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ws_raw_messages (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    unit_id VARCHAR(50) NOT NULL DEFAULT '',
    task VARCHAR(100) NOT NULL DEFAULT '',
    device_name VARCHAR(100) NOT NULL DEFAULT '',
    source_time VARCHAR(50) NOT NULL DEFAULT '',
    snapshot_at DATETIME NOT NULL,
    payload_json JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_plant_snapshot (plant_id, snapshot_at),
    KEY idx_unit_task_device (unit_id, task, device_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS telemetry_latest (
    plant_id VARCHAR(50) NOT NULL,
    type VARCHAR(20) NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    snapshot_at DATETIME NOT NULL,
    payload_json JSON NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (plant_id, type, device_name),
    KEY idx_latest_plant_type_snapshot (plant_id, type, snapshot_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plant_id VARCHAR(50) NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    quantity INT NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT '',
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    location VARCHAR(255) NOT NULL DEFAULT '',
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_plant (plant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DELIMITER //

DROP PROCEDURE IF EXISTS SeedPlantData //

CREATE PROCEDURE SeedPlantData(
    IN pPlant VARCHAR(50),
    IN pCap DECIMAL(5,2),
    IN pInvCount INT,
    IN pStrCount INT,
    IN pSnapshot DATETIME
)
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE s INT DEFAULT 1;
    DECLARE pwr DECIMAL(10,3);
    DECLARE dGen DECIMAL(12,2);
    DECLARE tGen DECIMAL(15,2);
    DECLARE dCO2 DECIMAL(10,2);
    DECLARE tCO2 DECIMAL(15,2);
    DECLARE iName VARCHAR(50);
    DECLARE strActive INT;
    DECLARE strCurr DECIMAL(8,2);
    DECLARE strVolt DECIMAL(8,2);

    SET pwr = ROUND(pCap * 650 / pInvCount, 3);
    SET dGen = ROUND(pCap * 4100 / pInvCount, 2);
    SET tGen = ROUND(dGen * 365, 2);
    SET dCO2 = ROUND(pCap * 2.8 / pInvCount, 2);
    SET tCO2 = ROUND(dCO2 * 365, 2);

    WHILE i <= pInvCount DO
        SET iName = CONCAT('Inverter ', i);
        INSERT IGNORE INTO inverter_data (
            plant_id, inverter_name, snapshot_at,
            power_kw, reactive_kvar, power_factor,
            vac_ab, vac_bc, vac_ca, frequency_hz,
            current_a, current_b, current_c,
            efficiency, ambient_temp,
            daily_gen_kwh, total_gen_kwh,
            daily_co2_kg, total_co2_kg,
            daily_hours, total_hours,
            active_strings, total_strings
        ) VALUES (
            pPlant, iName, pSnapshot,
            pwr, ROUND(pwr * 0.08, 3), ROUND(0.985 + RAND() * 0.01, 4),
            ROUND(432.5 + RAND() * 10, 2), ROUND(431.2 + RAND() * 10, 2), ROUND(433.8 + RAND() * 10, 2), 50.02,
            ROUND(pwr * 1000 / 440 / 1.732, 2), ROUND(pwr * 1000 / 440 / 1.732, 2), ROUND(pwr * 1000 / 440 / 1.732, 2),
            ROUND(97.5 + RAND() * 1.2, 2), ROUND(42 + RAND() * 8, 2),
            dGen, tGen, dCO2, tCO2,
            ROUND(5.5 + RAND() * 2, 2), ROUND(2800 + RAND() * 500, 2),
            pStrCount, pStrCount
        );
        SET s = 1;
        WHILE s <= pStrCount DO
            SET strActive = IF(RAND() > 0.15, 1, 0);
            SET strCurr = IF(strActive, ROUND(4.5 + RAND() * 3, 2), 0);
            SET strVolt = IF(strActive, ROUND(620 + RAND() * 80, 2), 0);
            INSERT IGNORE INTO inverter_strings (
                plant_id, inverter_name, snapshot_at, string_n,
                current_a, voltage_v, active
            ) VALUES (
                pPlant, iName, pSnapshot, s,
                strCurr, strVolt, strActive
            );
            SET s = s + 1;
        END WHILE;
        SET i = i + 1;
    END WHILE;
END //

DELIMITER ;

SET @snapshot = '2026-06-23 10:00:00';

-- Create/update login accounts after setup by running provision_login_users.php
-- with the three password environment variables set on the local server.

-- Live telemetry is inserted by api_store.php. No demo plant data is seeded.
