-- =============================================
-- UrFoodHub Cafe System - Full Database Import
-- =============================================
-- Single-file import for web hosting / phpMyAdmin.
-- This file combines all individual SQL files in the
-- correct dependency order so there are no FK conflicts.
--
-- Source files (in order):
--   1. reset.sql
--   2. user.sql
--   3. inventory.sql
--   4. inventory-trail.sql
--   5. products.sql
--   6. sales.sql
-- =============================================

-- =============================================
-- IMPORTANT: Before importing, select your database
-- in phpMyAdmin first (left sidebar). No need to
-- create one — your hosting provider already did.
-- =============================================

USE if0_41136192_urfoodhubcafe_db;
--

-- =============================================
-- 2) USERS & PERMISSIONS
-- =============================================

DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS users;

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

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE user_permissions (
    staff_id VARCHAR(20),
    permission_id INT,
    PRIMARY KEY (staff_id, permission_id),
    FOREIGN KEY (staff_id) REFERENCES users(staff_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Users (passwords hashed with bcrypt / PASSWORD_DEFAULT)
-- Plaintext passwords for reference:
--   SystemAdmin = admin123
--   Barcoma     = barcoma123
--   Sapanta     = sapanta123
--   Aboguin     = aboguin123
INSERT INTO users
(staff_id, user_name, password, contact, email, hire_date, status, is_super_admin)
VALUES
('ST-0001', 'SystemAdmin', '$2y$10$mMcIjTt21GfPbIxgz6pPMOqSdHhtLxmITOK/5SR212j5sir8erR6S', '0999999999', 'admin@system.com', '2025-01-08', 'Active', TRUE),
('ST-1001', 'Barcoma', '$2y$10$8zNI0EeVlp9LesBc56qF8.u1wWmELGOs9vnRlvTUl7eGYbHQcDnDK', '09100000', 'barcoma@gmail.com', '2025-01-08', 'Active', FALSE),
('ST-1002', 'Sapanta', '$2y$10$gBiM6zQYet5IkGq7blUdeODg1dxElb4Glstynq0Tv/yWb0bJsvCnm', '09100001', 'sapanta@gmail.com', '2025-01-08', 'Active', FALSE),
('ST-1003', 'Aboguin', '$2y$10$.GMu/j./EFff.boehP4keeuKhwl4KjzU88e9JIOtB/JqXkv/lA1hG', '09100002', 'aboguin@gmail.com', '2025-01-08', 'Deactivated', FALSE);

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

INSERT INTO user_permissions (staff_id, permission_id)
SELECT 'ST-1001', id FROM permissions
WHERE code IN ('inventory.read', 'inventory.update');

-- =============================================
-- 3) INVENTORY
-- =============================================

DROP TABLE IF EXISTS inventory_batches;
DROP TABLE IF EXISTS item_categories;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS inventory_categories;
DROP TABLE IF EXISTS inventory_settings;

CREATE TABLE inventory_categories (
    category_id VARCHAR(20) PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_items (
    item_id VARCHAR(20) PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    image_filename VARCHAR(255) DEFAULT 'default-item.png',
    quantity_unit VARCHAR(20) NOT NULL,
    reorder_level DECIMAL(10, 2) NOT NULL DEFAULT 0,
    priority_method ENUM('fifo', 'fefo', 'manual') DEFAULT 'fifo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_name (item_name),
    INDEX idx_priority_method (priority_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE item_categories (
    item_id VARCHAR(20) NOT NULL,
    category_id VARCHAR(20) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (item_id, category_id),
    FOREIGN KEY (item_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES inventory_categories(category_id) ON DELETE CASCADE,
    INDEX idx_item (item_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE inventory_batches (
    batch_id VARCHAR(20) PRIMARY KEY,
    inventory_id VARCHAR(20) NOT NULL,
    batch_title VARCHAR(150) NOT NULL,
    initial_quantity DECIMAL(10, 2) NOT NULL,
    current_quantity DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    supplier_id VARCHAR(20),
    obtained_date DATE NOT NULL,
    expiration_date DATE DEFAULT NULL,
    batch_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_expiration (expiration_date),
    INDEX idx_obtained_date (obtained_date),
    INDEX idx_batch_order (batch_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Inventory Categories
INSERT INTO inventory_categories (category_id, category_name, description, display_order) VALUES
('CAT-001', 'Ingredients', 'Raw materials for food and beverage preparation', 1),
('CAT-002', 'Utensils', 'Kitchen tools, cups, and serving equipment', 2),
('CAT-003', 'Packaging', 'Food containers, bags, and packaging materials', 3),
('CAT-004', 'Supplies', 'General operational and cleaning supplies', 4),
('CAT-005', 'Others', 'Miscellaneous items', 5);

INSERT INTO inventory_settings (setting_key, setting_value) VALUES
('default_priority_method', 'fifo');

-- Sample Inventory Items
INSERT INTO inventory_items (item_id, item_name, quantity_unit, reorder_level) VALUES
('ING-001', 'Coffee Beans', 'kg', 5.00),
('ING-002', 'Milk', 'L', 10.00),
('ING-003', 'Sugar', 'kg', 3.00),
('ING-004', 'Chocolate Syrup', 'L', 2.00),
('ING-005', 'Vanilla Syrup', 'L', 2.00),
('ING-006', 'Caramel Syrup', 'L', 2.00),
('ING-007', 'Whipped Cream', 'L', 3.00),
('ING-008', 'Tea Leaves', 'kg', 1.00),
('ING-009', 'Flour', 'kg', 5.00),
('ING-010', 'Eggs', 'pcs', 50),
('UTN-001', 'Coffee Cups Small', 'pcs', 50),
('UTN-002', 'Coffee Cups Medium', 'pcs', 50),
('UTN-003', 'Coffee Cups Large', 'pcs', 50),
('UTN-004', 'Plastic Straws', 'pcs', 100),
('UTN-005', 'Wooden Stirrers', 'pcs', 100),
('UTN-006', 'Napkins', 'pcs', 200),
('UTN-007', 'Takeout Boxes Small', 'pcs', 30),
('UTN-008', 'Takeout Boxes Large', 'pcs', 30),
('PKG-001', 'Paper Bags Small', 'pcs', 50),
('PKG-002', 'Paper Bags Large', 'pcs', 50),
('PKG-003', 'Cup Sleeves', 'pcs', 100),
('PKG-004', 'Cup Lids Small', 'pcs', 100),
('PKG-005', 'Cup Lids Medium', 'pcs', 100),
('PKG-006', 'Cup Lids Large', 'pcs', 100),
('SUP-001', 'Cleaning Spray', 'bottles', 2),
('SUP-002', 'Dishwashing Liquid', 'bottles', 3),
('SUP-003', 'Hand Soap', 'bottles', 5),
('SUP-004', 'Tissue Paper Rolls', 'rolls', 10);

-- Sample Item-Category Mappings
INSERT INTO item_categories (item_id, category_id, is_primary) VALUES
('ING-001', 'CAT-001', TRUE), ('ING-002', 'CAT-001', TRUE), ('ING-003', 'CAT-001', TRUE),
('ING-004', 'CAT-001', TRUE), ('ING-005', 'CAT-001', TRUE), ('ING-006', 'CAT-001', TRUE),
('ING-007', 'CAT-001', TRUE), ('ING-008', 'CAT-001', TRUE), ('ING-009', 'CAT-001', TRUE),
('ING-010', 'CAT-001', TRUE),
('UTN-001', 'CAT-002', TRUE), ('UTN-002', 'CAT-002', TRUE), ('UTN-003', 'CAT-002', TRUE),
('UTN-004', 'CAT-002', TRUE), ('UTN-005', 'CAT-002', TRUE), ('UTN-006', 'CAT-002', TRUE),
('UTN-007', 'CAT-002', TRUE), ('UTN-007', 'CAT-003', FALSE),
('UTN-008', 'CAT-002', TRUE), ('UTN-008', 'CAT-003', FALSE),
('PKG-001', 'CAT-003', TRUE), ('PKG-002', 'CAT-003', TRUE), ('PKG-003', 'CAT-003', TRUE),
('PKG-004', 'CAT-003', TRUE), ('PKG-005', 'CAT-003', TRUE), ('PKG-006', 'CAT-003', TRUE),
('SUP-001', 'CAT-004', TRUE), ('SUP-002', 'CAT-004', TRUE),
('SUP-003', 'CAT-004', TRUE), ('SUP-004', 'CAT-004', TRUE);

-- Sample Inventory Batches
INSERT INTO inventory_batches (batch_id, inventory_id, batch_title, initial_quantity, current_quantity, total_cost, supplier_id, obtained_date, expiration_date, batch_order) VALUES
('B-1001', 'ING-001', 'Premium Arabica Coffee Beans', 25.00, 25.00, 11250.00, 'SUP-001', '2025-01-10', '2026-06-10', 1),
('B-1002', 'ING-001', 'Robusta Coffee Beans', 30.00, 30.00, 10500.00, 'SUP-002', '2025-01-15', '2026-07-15', 2),
('B-1003', 'ING-002', 'Fresh Whole Milk', 20.00, 18.50, 1300.00, 'SUP-003', '2025-02-01', '2025-02-15', 1),
('B-1004', 'ING-002', 'Fresh Whole Milk', 15.00, 15.00, 975.00, 'SUP-003', '2025-02-05', '2025-02-19', 2),
('B-1005', 'ING-003', 'White Granulated Sugar', 10.00, 7.50, 450.00, 'SUP-004', '2024-12-01', NULL, 1),
('B-1006', 'ING-004', 'Hershey Chocolate Syrup', 5.00, 4.20, 900.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),
('B-1007', 'ING-005', 'Monin Vanilla Syrup', 5.00, 5.00, 1250.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),
('B-1008', 'ING-006', 'Monin Caramel Syrup', 5.00, 3.80, 1250.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),
('B-1009', 'ING-007', 'Anchor Whipped Cream', 6.00, 4.50, 1920.00, 'SUP-006', '2025-02-01', '2025-03-15', 1),
('B-1010', 'ING-008', 'Earl Grey Tea Leaves', 2.00, 1.50, 560.00, 'SUP-007', '2024-11-15', '2025-11-15', 1),
('B-1011', 'ING-009', 'All-Purpose Flour', 25.00, 20.00, 875.00, 'SUP-004', '2024-12-10', NULL, 1),
('B-1012', 'ING-010', 'Fresh Eggs Grade A', 100, 85, 850.00, 'SUP-008', '2025-02-03', '2025-02-24', 1),
('B-1013', 'UTN-001', 'Omega Small Plastic Cups 8oz', 500, 450, 1250.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1014', 'UTN-002', 'Omega Medium Plastic Cups 12oz', 500, 480, 1500.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1015', 'UTN-003', 'Omega Large Plastic Cups 16oz', 500, 500, 1750.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1016', 'UTN-004', 'Plastic Straws Box', 1000, 820, 500.00, 'SUP-009', '2025-01-10', NULL, 1),
('B-1017', 'UTN-005', 'Wooden Coffee Stirrers', 1000, 950, 300.00, 'SUP-009', '2025-01-10', NULL, 1),
('B-1018', 'UTN-006', 'White Paper Napkins', 1000, 750, 200.00, 'SUP-010', '2025-01-08', NULL, 1),
('B-1019', 'UTN-007', 'Eco-Friendly Takeout Box Small', 200, 180, 1000.00, 'SUP-010', '2025-01-12', NULL, 1),
('B-1020', 'UTN-008', 'Eco-Friendly Takeout Box Large', 200, 200, 1500.00, 'SUP-010', '2025-01-12', NULL, 1),
('B-1021', 'PKG-001', 'Brown Paper Bags Small', 300, 250, 600.00, 'SUP-010', '2024-12-20', NULL, 1),
('B-1022', 'PKG-002', 'Brown Paper Bags Large', 300, 280, 900.00, 'SUP-010', '2024-12-20', NULL, 1),
('B-1023', 'PKG-003', 'Insulated Cup Sleeves', 500, 430, 750.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1024', 'PKG-004', 'Plastic Lids 8oz', 500, 445, 500.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1025', 'PKG-005', 'Plastic Lids 12oz', 500, 475, 600.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1026', 'PKG-006', 'Plastic Lids 16oz', 500, 495, 700.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1027', 'SUP-001', 'Multi-Surface Cleaning Spray', 10, 8, 850.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1028', 'SUP-002', 'Dishwashing Liquid 1L', 12, 9, 660.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1029', 'SUP-003', 'Antibacterial Hand Soap', 15, 12, 675.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1030', 'SUP-004', 'Tissue Paper Rolls', 30, 22, 360.00, 'SUP-011', '2025-01-18', NULL, 1);

-- =============================================
-- 4) INVENTORY MOVEMENTS
-- =============================================

DROP TABLE IF EXISTS inventory_movements;

CREATE TABLE inventory_movements (
    movement_id VARCHAR(20) PRIMARY KEY,
    inventory_id VARCHAR(20) NOT NULL,
    batch_id VARCHAR(20),
    movement_type VARCHAR(50) NOT NULL,
    quantity DECIMAL(10, 2) NOT NULL,
    old_quantity DECIMAL(10, 2) NOT NULL,
    new_quantity DECIMAL(10, 2) NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_value DECIMAL(10, 2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id VARCHAR(20),
    staff_id VARCHAR(20),
    movement_date DATETIME NOT NULL,
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,
    INDEX idx_inventory (inventory_id),
    INDEX idx_batch (batch_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_movement_date (movement_date),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 5) PRODUCTS
-- =============================================

DROP TABLE IF EXISTS product_category_map;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS product_requirements;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    product_id VARCHAR(20) PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    image_filename VARCHAR(255) DEFAULT 'default-product.png',
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('Available', 'Unavailable', 'Discontinued') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_name (product_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_requirements (
    product_id VARCHAR(20) NOT NULL,
    inventory_id VARCHAR(20) NOT NULL,
    quantity_used DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (product_id, inventory_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_inventory (inventory_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_categories (
    category_id VARCHAR(20) PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE product_category_map (
    product_id VARCHAR(20) NOT NULL,
    category_id VARCHAR(20) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (product_id, category_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES product_categories(category_id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Product Categories
INSERT INTO product_categories (category_id, category_name, description, display_order) VALUES
('PCAT-001', 'Burgers', 'Burger sandwiches and combos', 1),
('PCAT-002', 'Coffee', 'Hot and cold coffee beverages', 2),
('PCAT-003', 'Beverages', 'Non-coffee drinks and refreshments', 3);

-- Sample Products
INSERT INTO products (product_id, product_name, price) VALUES
('PROD-1001', 'Overload Burger', 99.99),
('PROD-1002', 'Small Coffee Matcha', 49.00),
('PROD-1003', 'Medium Coffee Matcha', 64.00),
('PROD-1004', 'Small Original Coffee', 50.00);

UPDATE products SET status = 'Unavailable' WHERE product_id = 'PROD-1002';
UPDATE products SET status = 'Discontinued' WHERE product_id = 'PROD-1003';

-- Sample Product Requirements (Recipe / Bill of Materials)
INSERT INTO product_requirements (product_id, inventory_id, quantity_used) VALUES
('PROD-1004', 'ING-001', 0.02),
('PROD-1004', 'UTN-001', 1),
('PROD-1004', 'PKG-004', 1),
('PROD-1004', 'PKG-003', 1),
('PROD-1002', 'ING-002', 0.15),
('PROD-1002', 'ING-003', 0.02),
('PROD-1002', 'UTN-001', 1),
('PROD-1002', 'PKG-004', 1),
('PROD-1002', 'PKG-003', 1),
('PROD-1003', 'ING-002', 0.25),
('PROD-1003', 'ING-003', 0.03),
('PROD-1003', 'UTN-002', 1),
('PROD-1003', 'PKG-005', 1),
('PROD-1003', 'PKG-003', 1),
('PROD-1001', 'ING-009', 0.15),
('PROD-1001', 'ING-010', 2),
('PROD-1001', 'UTN-007', 1),
('PROD-1001', 'UTN-006', 3),
('PROD-1001', 'PKG-001', 1);

-- Sample Product Category Mappings
INSERT INTO product_category_map (product_id, category_id, is_primary) VALUES
('PROD-1001', 'PCAT-001', TRUE),
('PROD-1002', 'PCAT-002', TRUE),
('PROD-1002', 'PCAT-003', FALSE),
('PROD-1003', 'PCAT-002', TRUE),
('PROD-1003', 'PCAT-003', FALSE),
('PROD-1004', 'PCAT-002', TRUE);

-- =============================================
-- 6) SALES
-- =============================================

DROP TABLE IF EXISTS sale_details;
DROP TABLE IF EXISTS sales;

CREATE TABLE sales (
    sale_id VARCHAR(20) PRIMARY KEY,
    staff_id VARCHAR(20) NOT NULL,
    sale_date DATE NOT NULL,
    sale_time TIME NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_cost DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    profit DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    change_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('completed', 'voided') NOT NULL DEFAULT 'completed',
    voided_at DATETIME DEFAULT NULL,
    voided_by VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(staff_id),
    FOREIGN KEY (voided_by) REFERENCES users(staff_id),
    INDEX idx_staff (staff_id),
    INDEX idx_sale_date (sale_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sale_details (
    sale_detail_id VARCHAR(20) PRIMARY KEY,
    sale_id VARCHAR(20) NOT NULL,
    product_id VARCHAR(20) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price_per_unit DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cost_per_unit DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    is_manual TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    INDEX idx_sale (sale_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Sales
INSERT INTO sales (sale_id, staff_id, sale_date, sale_time, total_price, total_cost, profit, amount_paid, change_amount, status) VALUES
('SL-1001', 'ST-1001', '2025-10-12', '10:24:32', 199.99, 160.23, 39.76, 200.00, 0.01, 'completed'),
('SL-1002', 'ST-1001', '2025-10-12', '12:32:34', 250.00, 225.00, 25.00, 250.00, 0.00, 'completed');

-- Sample Sale Details
INSERT INTO sale_details (sale_detail_id, sale_id, product_id, quantity, price_per_unit, cost_per_unit, subtotal, is_manual) VALUES
('SD-0001', 'SL-1001', 'PROD-1004', 2, 50.00, 30.12, 100.00, 0),
('SD-0002', 'SL-1001', 'PROD-1001', 1, 99.99, 70.23, 99.99, 0),
('SD-0003', 'SL-1002', 'PROD-1004', 5, 50.00, 45.00, 250.00, 0);
