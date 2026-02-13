USE urfoodhubcafe_db;

-- =============================================
-- PRODUCT TABLES - Run #5 (after inventory-trail.sql)
-- Depends on: inventory_items
-- =============================================

-- ============================================
-- DROP EXISTING TABLES (in correct order)
-- ============================================
DROP TABLE IF EXISTS product_category_map;
DROP TABLE IF EXISTS product_categories;
DROP TABLE IF EXISTS product_requirements;
DROP TABLE IF EXISTS products;

-- ============================================
-- PRODUCTS TABLE
-- ============================================
CREATE TABLE products (
    product_id VARCHAR(20) PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    image_filename VARCHAR(255) DEFAULT 'default-product.png',
    price DECIMAL(10, 2) NOT NULL,  -- Selling price
    status ENUM('Available', 'Unavailable', 'Discontinued') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_name (product_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PRODUCT REQUIREMENTS TABLE (Recipe / Bill of Materials)
-- Links products to inventory items with quantity needed
-- ============================================
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

-- ============================================
-- PRODUCT CATEGORIES TABLE
-- ============================================
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

-- ============================================
-- PRODUCT CATEGORY MAP (Many-to-Many Junction)
-- ============================================
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

-- ============================================
-- SAMPLE DATA - PRODUCT CATEGORIES
-- ============================================
INSERT INTO product_categories (category_id, category_name, description, display_order) VALUES
('PCAT-001', 'Burgers', 'Burger sandwiches and combos', 1),
('PCAT-002', 'Coffee', 'Hot and cold coffee beverages', 2),
('PCAT-003', 'Beverages', 'Non-coffee drinks and refreshments', 3);

-- ============================================
-- SAMPLE DATA - PRODUCTS
-- ============================================
INSERT INTO products (product_id, product_name, price) VALUES
('PROD-1001', 'Overload Burger', 99.99),
('PROD-1002', 'Small Coffee Matcha', 49.00),
('PROD-1003', 'Medium Coffee Matcha', 64.00),
('PROD-1004', 'Small Original Coffee', 50.00);

-- Update statuses for non-available products
UPDATE products SET status = 'Unavailable' WHERE product_id = 'PROD-1002';
UPDATE products SET status = 'Discontinued' WHERE product_id = 'PROD-1003';

-- ============================================
-- SAMPLE DATA - PRODUCT REQUIREMENTS
-- (What inventory items each product needs)
-- ============================================
INSERT INTO product_requirements (product_id, inventory_id, quantity_used) VALUES
-- Small Original Coffee: 0.2 kg coffee beans + 1 small cup + 1 small lid + 1 cup sleeve
('PROD-1004', 'ING-001', 0.02),   -- Coffee Beans (kg)
('PROD-1004', 'UTN-001', 1),      -- Coffee Cups Small (pcs)
('PROD-1004', 'PKG-004', 1),      -- Cup Lids Small (pcs)
('PROD-1004', 'PKG-003', 1),      -- Cup Sleeves (pcs)

-- Small Coffee Matcha: uses milk, sugar, small cup, lid, sleeve
('PROD-1002', 'ING-002', 0.15),   -- Milk (L)
('PROD-1002', 'ING-003', 0.02),   -- Sugar (kg)
('PROD-1002', 'UTN-001', 1),      -- Coffee Cups Small (pcs)
('PROD-1002', 'PKG-004', 1),      -- Cup Lids Small (pcs)
('PROD-1002', 'PKG-003', 1),      -- Cup Sleeves (pcs)

-- Medium Coffee Matcha: same as small but with medium cup
('PROD-1003', 'ING-002', 0.25),   -- Milk (L)
('PROD-1003', 'ING-003', 0.03),   -- Sugar (kg)
('PROD-1003', 'UTN-002', 1),      -- Coffee Cups Medium (pcs)
('PROD-1003', 'PKG-005', 1),      -- Cup Lids Medium (pcs)
('PROD-1003', 'PKG-003', 1),      -- Cup Sleeves (pcs)

-- Overload Burger: uses flour, eggs, takeout box, napkins, paper bag
('PROD-1001', 'ING-009', 0.15),   -- Flour (kg)
('PROD-1001', 'ING-010', 2),      -- Eggs (pcs)
('PROD-1001', 'UTN-007', 1),      -- Takeout Boxes Small (pcs)
('PROD-1001', 'UTN-006', 3),      -- Napkins (pcs)
('PROD-1001', 'PKG-001', 1);      -- Paper Bags Small (pcs)

-- ============================================
-- SAMPLE DATA - PRODUCT CATEGORY MAPPINGS
-- ============================================
INSERT INTO product_category_map (product_id, category_id, is_primary) VALUES
('PROD-1001', 'PCAT-001', TRUE),   -- Overload Burger -> Burgers (primary)
('PROD-1002', 'PCAT-002', TRUE),   -- Small Coffee Matcha -> Coffee (primary)
('PROD-1002', 'PCAT-003', FALSE),  -- Small Coffee Matcha -> Beverages
('PROD-1003', 'PCAT-002', TRUE),   -- Medium Coffee Matcha -> Coffee (primary)
('PROD-1003', 'PCAT-003', FALSE),  -- Medium Coffee Matcha -> Beverages
('PROD-1004', 'PCAT-002', TRUE);   -- Small Original Coffee -> Coffee (primary)
