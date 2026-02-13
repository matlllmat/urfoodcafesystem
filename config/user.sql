USE urfoodhubcafe_db;

-- =============================================
-- USER & PERMISSIONS TABLES - Run #2 (after reset.sql)
-- =============================================

-- ============================================
-- DROP EXISTING TABLES (in correct order)
-- ============================================
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- ============================================
CREATE TABLE users (
    staff_id VARCHAR(20) PRIMARY KEY,
    user_name VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contact VARCHAR(20),
    email VARCHAR(100),
    hire_date DATE,
    status ENUM('Active', 'Deactivated') DEFAULT 'Active',
    is_super_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PERMISSIONS TABLE
-- ============================================
CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- USER PERMISSIONS TABLE (Junction)
-- ============================================
CREATE TABLE user_permissions (
    staff_id VARCHAR(20),
    permission_id INT,
    PRIMARY KEY (staff_id, permission_id),
    FOREIGN KEY (staff_id) REFERENCES users(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SAMPLE DATA - USERS (passwords hashed with PASSWORD_DEFAULT / bcrypt)
-- Plaintext passwords for reference:
--   SystemAdmin = admin123
--   Barcoma     = barcoma123
--   Sapanta     = sapanta123
--   Aboguin     = aboguin123
-- ============================================
INSERT INTO users
(staff_id, user_name, password, contact, email, hire_date, status, is_super_admin)
VALUES
('ST-0001', 'SystemAdmin', '$2y$10$mMcIjTt21GfPbIxgz6pPMOqSdHhtLxmITOK/5SR212j5sir8erR6S', '0999999999', 'admin@system.com', '2025-01-08', 'Active', TRUE),
('ST-1001', 'Barcoma', '$2y$10$8zNI0EeVlp9LesBc56qF8.u1wWmELGOs9vnRlvTUl7eGYbHQcDnDK', '09100000', 'barcoma@gmail.com', '2025-01-08', 'Active', FALSE),
('ST-1002', 'Sapanta', '$2y$10$gBiM6zQYet5IkGq7blUdeODg1dxElb4Glstynq0Tv/yWb0bJsvCnm', '09100001', 'sapanta@gmail.com', '2025-01-08', 'Active', FALSE),
('ST-1003', 'Aboguin', '$2y$10$.GMu/j./EFff.boehP4keeuKhwl4KjzU88e9JIOtB/JqXkv/lA1hG', '09100002', 'aboguin@gmail.com', '2025-01-08', 'Deactivated', FALSE);

-- ============================================
-- SAMPLE DATA - PERMISSIONS
-- ============================================
INSERT INTO permissions (code, description) VALUES
('inventory.read', 'View inventory items'),
('inventory.create', 'Add inventory items'),
('inventory.update', 'Edit inventory items'),
('inventory.delete', 'Delete inventory items'),
('sales.read', 'View sales records'),
('sales.create', 'Create sales transactions'),
('reports.read', 'View reports'),
('users.manage', 'Manage system users'),
('page.create-sales', 'Access Create Sales page'),
('page.manage-products', 'Access Manage Products page'),
('page.product-categories', 'Access Product Categories page'),
('page.manage-inventory', 'Access Manage Inventory page'),
('page.reports', 'Access Reports page'),
('page.sales-history', 'Access Sales History page'),
('page.inventory-trail', 'Access Inventory Trail page');

-- ============================================
-- SAMPLE DATA - USER PERMISSIONS
-- ============================================
INSERT INTO user_permissions (staff_id, permission_id)
SELECT 'ST-1001', id FROM permissions
WHERE code IN ('inventory.read', 'inventory.update');