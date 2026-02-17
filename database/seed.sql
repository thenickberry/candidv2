-- CANDIDv2 Seed Data
-- Default admin user with bcrypt password

SET NAMES utf8mb4;

-- Insert default admin user
-- Password: 'changeme' (bcrypt hash)
-- IMPORTANT: Change this password immediately after first login!
INSERT INTO `user` (
    `username`,
    `pword`,
    `access`,
    `fname`,
    `lname`,
    `email`,
    `created`
) VALUES (
    'admin',
    '$2y$12$IBtp3D2L1b3pOVqzMzu5t.EytqcWr3VcuZFrneVZqI3G6qy4tuEfi',
    5,
    'Admin',
    'User',
    'admin@example.com',
    NOW()
);

-- Insert root category
INSERT INTO `category` (
    `name`,
    `descr`,
    `parent`,
    `owner`,
    `public`,
    `added`
) VALUES (
    'Root',
    'Top-level category',
    NULL,
    1,
    'y',
    NOW()
);
