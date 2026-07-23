-- Create or update the user login for Vijayanth Cosmic Powers Pvt Ltd.
-- Password is stored only as a bcrypt hash.

INSERT INTO users (email, password, role, plant_id)
VALUES (
    'vijayanth@scada.com',
    '$2y$12$QhT320zVzXbOaPIQYbDhIu6V5.gWBQT0/5TSSjNhlqq3YWV0bnXT.',
    'user',
    'vijayanth_cosmic'
)
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    plant_id = VALUES(plant_id),
    auth_token = NULL;
