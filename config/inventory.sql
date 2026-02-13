USE urfoodhubcafe_db;

-- =============================================
-- INVENTORY TABLES - Run #3 (after user.sql)
-- =============================================

-- ============================================
-- DROP EXISTING TABLES (in correct order)
-- ============================================
DROP TABLE IF EXISTS inventory_batches;
DROP TABLE IF EXISTS item_categories;
DROP TABLE IF EXISTS inventory_items;
DROP TABLE IF EXISTS inventory_categories;
DROP TABLE IF EXISTS inventory_settings;

-- ============================================
-- INVENTORY CATEGORIES TABLE
-- ============================================
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

-- ============================================
-- INVENTORY ITEMS TABLE
-- ============================================
CREATE TABLE inventory_items (
    item_id VARCHAR(20) PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    image_filename VARCHAR(255) DEFAULT 'default-item.png',
    quantity_unit VARCHAR(20) NOT NULL,  -- kg, g, L, mL, pcs, boxes, etc.
    reorder_level DECIMAL(10, 2) NOT NULL DEFAULT 0,
    priority_method ENUM('fifo', 'fefo', 'manual') DEFAULT 'fifo',  -- Batch priority method
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_item_name (item_name),
    INDEX idx_priority_method (priority_method)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ITEM CATEGORIES (JUNCTION TABLE)
-- ============================================
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

-- ============================================
-- INVENTORY SETTINGS TABLE
-- ============================================
CREATE TABLE inventory_settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- INVENTORY BATCHES TABLE (UPDATED - total_cost instead of cost_per_unit)
-- ============================================
CREATE TABLE inventory_batches (
    batch_id VARCHAR(20) PRIMARY KEY,
    inventory_id VARCHAR(20) NOT NULL,
    batch_title VARCHAR(150) NOT NULL,
    initial_quantity DECIMAL(10, 2) NOT NULL,
    current_quantity DECIMAL(10, 2) NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,  -- CHANGED: Total cost of entire batch
    supplier_id VARCHAR(20),
    obtained_date DATE NOT NULL,
    expiration_date DATE DEFAULT NULL,
    batch_order INT DEFAULT 0,  -- NEW: For drag-and-drop ordering (FIFO by default)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    INDEX idx_inventory_id (inventory_id),
    INDEX idx_expiration (expiration_date),
    INDEX idx_obtained_date (obtained_date),
    INDEX idx_batch_order (batch_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SAMPLE DATA - INVENTORY CATEGORIES
-- ============================================
INSERT INTO inventory_categories (category_id, category_name, description, display_order) VALUES
('CAT-001', 'Ingredients', 'Raw materials for food and beverage preparation', 1),
('CAT-002', 'Utensils', 'Kitchen tools, cups, and serving equipment', 2),
('CAT-003', 'Packaging', 'Food containers, bags, and packaging materials', 3),
('CAT-004', 'Supplies', 'General operational and cleaning supplies', 4),
('CAT-005', 'Others', 'Miscellaneous items', 5);

-- ============================================
-- SAMPLE DATA - INVENTORY SETTINGS
-- ============================================
INSERT INTO inventory_settings (setting_key, setting_value) VALUES
('default_priority_method', 'fifo');

-- ============================================
-- SAMPLE DATA - INVENTORY ITEMS
-- ============================================
INSERT INTO inventory_items (item_id, item_name, quantity_unit, reorder_level) VALUES
-- Ingredients
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

-- Utensils
('UTN-001', 'Coffee Cups Small', 'pcs', 50),
('UTN-002', 'Coffee Cups Medium', 'pcs', 50),
('UTN-003', 'Coffee Cups Large', 'pcs', 50),
('UTN-004', 'Plastic Straws', 'pcs', 100),
('UTN-005', 'Wooden Stirrers', 'pcs', 100),
('UTN-006', 'Napkins', 'pcs', 200),
('UTN-007', 'Takeout Boxes Small', 'pcs', 30),
('UTN-008', 'Takeout Boxes Large', 'pcs', 30),

-- Packaging
('PKG-001', 'Paper Bags Small', 'pcs', 50),
('PKG-002', 'Paper Bags Large', 'pcs', 50),
('PKG-003', 'Cup Sleeves', 'pcs', 100),
('PKG-004', 'Cup Lids Small', 'pcs', 100),
('PKG-005', 'Cup Lids Medium', 'pcs', 100),
('PKG-006', 'Cup Lids Large', 'pcs', 100),

-- Supplies
('SUP-001', 'Cleaning Spray', 'bottles', 2),
('SUP-002', 'Dishwashing Liquid', 'bottles', 3),
('SUP-003', 'Hand Soap', 'bottles', 5),
('SUP-004', 'Tissue Paper Rolls', 'rolls', 10);

-- ============================================
-- SAMPLE DATA - ITEM CATEGORIES
-- ============================================
INSERT INTO item_categories (item_id, category_id, is_primary) VALUES
-- Ingredients
('ING-001', 'CAT-001', TRUE), ('ING-002', 'CAT-001', TRUE), ('ING-003', 'CAT-001', TRUE),
('ING-004', 'CAT-001', TRUE), ('ING-005', 'CAT-001', TRUE), ('ING-006', 'CAT-001', TRUE),
('ING-007', 'CAT-001', TRUE), ('ING-008', 'CAT-001', TRUE), ('ING-009', 'CAT-001', TRUE),
('ING-010', 'CAT-001', TRUE),

-- Utensils
('UTN-001', 'CAT-002', TRUE), ('UTN-002', 'CAT-002', TRUE), ('UTN-003', 'CAT-002', TRUE),
('UTN-004', 'CAT-002', TRUE), ('UTN-005', 'CAT-002', TRUE), ('UTN-006', 'CAT-002', TRUE),

-- Takeout boxes - dual category
('UTN-007', 'CAT-002', TRUE), ('UTN-007', 'CAT-003', FALSE),
('UTN-008', 'CAT-002', TRUE), ('UTN-008', 'CAT-003', FALSE),

-- Packaging
('PKG-001', 'CAT-003', TRUE), ('PKG-002', 'CAT-003', TRUE), ('PKG-003', 'CAT-003', TRUE),
('PKG-004', 'CAT-003', TRUE), ('PKG-005', 'CAT-003', TRUE), ('PKG-006', 'CAT-003', TRUE),

-- Supplies
('SUP-001', 'CAT-004', TRUE), ('SUP-002', 'CAT-004', TRUE),
('SUP-003', 'CAT-004', TRUE), ('SUP-004', 'CAT-004', TRUE);

-- ============================================
-- SAMPLE DATA - INVENTORY BATCHES (with total_cost and batch_order)
-- ============================================
INSERT INTO inventory_batches (batch_id, inventory_id, batch_title, initial_quantity, current_quantity, total_cost, supplier_id, obtained_date, expiration_date, batch_order) VALUES

-- Coffee Beans (FIFO order: older first)
('B-1001', 'ING-001', 'Premium Arabica Coffee Beans', 25.00, 25.00, 11250.00, 'SUP-001', '2025-01-10', '2026-06-10', 1),
('B-1002', 'ING-001', 'Robusta Coffee Beans', 30.00, 30.00, 10500.00, 'SUP-002', '2025-01-15', '2026-07-15', 2),

-- Milk (FIFO)
('B-1003', 'ING-002', 'Fresh Whole Milk', 20.00, 18.50, 1300.00, 'SUP-003', '2025-02-01', '2025-02-15', 1),
('B-1004', 'ING-002', 'Fresh Whole Milk', 15.00, 15.00, 975.00, 'SUP-003', '2025-02-05', '2025-02-19', 2),

-- Sugar
('B-1005', 'ING-003', 'White Granulated Sugar', 10.00, 7.50, 450.00, 'SUP-004', '2024-12-01', NULL, 1),

-- Syrups
('B-1006', 'ING-004', 'Hershey Chocolate Syrup', 5.00, 4.20, 900.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),
('B-1007', 'ING-005', 'Monin Vanilla Syrup', 5.00, 5.00, 1250.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),
('B-1008', 'ING-006', 'Monin Caramel Syrup', 5.00, 3.80, 1250.00, 'SUP-005', '2025-01-20', '2026-01-20', 1),

-- Whipped Cream
('B-1009', 'ING-007', 'Anchor Whipped Cream', 6.00, 4.50, 1920.00, 'SUP-006', '2025-02-01', '2025-03-15', 1),

-- Tea
('B-1010', 'ING-008', 'Earl Grey Tea Leaves', 2.00, 1.50, 560.00, 'SUP-007', '2024-11-15', '2025-11-15', 1),

-- Baking
('B-1011', 'ING-009', 'All-Purpose Flour', 25.00, 20.00, 875.00, 'SUP-004', '2024-12-10', NULL, 1),
('B-1012', 'ING-010', 'Fresh Eggs Grade A', 100, 85, 850.00, 'SUP-008', '2025-02-03', '2025-02-24', 1),

-- Cups
('B-1013', 'UTN-001', 'Omega Small Plastic Cups 8oz', 500, 450, 1250.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1014', 'UTN-002', 'Omega Medium Plastic Cups 12oz', 500, 480, 1500.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1015', 'UTN-003', 'Omega Large Plastic Cups 16oz', 500, 500, 1750.00, 'SUP-009', '2025-01-05', NULL, 1),

-- Straws
('B-1016', 'UTN-004', 'Plastic Straws Box', 1000, 820, 500.00, 'SUP-009', '2025-01-10', NULL, 1),
('B-1017', 'UTN-005', 'Wooden Coffee Stirrers', 1000, 950, 300.00, 'SUP-009', '2025-01-10', NULL, 1),

-- Napkins
('B-1018', 'UTN-006', 'White Paper Napkins', 1000, 750, 200.00, 'SUP-010', '2025-01-08', NULL, 1),

-- Takeout Boxes
('B-1019', 'UTN-007', 'Eco-Friendly Takeout Box Small', 200, 180, 1000.00, 'SUP-010', '2025-01-12', NULL, 1),
('B-1020', 'UTN-008', 'Eco-Friendly Takeout Box Large', 200, 200, 1500.00, 'SUP-010', '2025-01-12', NULL, 1),

-- Paper Bags
('B-1021', 'PKG-001', 'Brown Paper Bags Small', 300, 250, 600.00, 'SUP-010', '2024-12-20', NULL, 1),
('B-1022', 'PKG-002', 'Brown Paper Bags Large', 300, 280, 900.00, 'SUP-010', '2024-12-20', NULL, 1),

-- Cup Accessories
('B-1023', 'PKG-003', 'Insulated Cup Sleeves', 500, 430, 750.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1024', 'PKG-004', 'Plastic Lids 8oz', 500, 445, 500.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1025', 'PKG-005', 'Plastic Lids 12oz', 500, 475, 600.00, 'SUP-009', '2025-01-05', NULL, 1),
('B-1026', 'PKG-006', 'Plastic Lids 16oz', 500, 495, 700.00, 'SUP-009', '2025-01-05', NULL, 1),

-- Cleaning Supplies
('B-1027', 'SUP-001', 'Multi-Surface Cleaning Spray', 10, 8, 850.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1028', 'SUP-002', 'Dishwashing Liquid 1L', 12, 9, 660.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1029', 'SUP-003', 'Antibacterial Hand Soap', 15, 12, 675.00, 'SUP-011', '2025-01-15', NULL, 1),
('B-1030', 'SUP-004', 'Tissue Paper Rolls', 30, 22, 360.00, 'SUP-011', '2025-01-18', NULL, 1);