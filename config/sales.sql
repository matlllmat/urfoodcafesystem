USE urfoodhubcafe_db;

-- =============================================
-- SALES TABLES - Run #6 (after products.sql)
-- Depends on: users, products
-- =============================================

-- ============================================
-- DROP EXISTING TABLES (in correct order)
-- ============================================
DROP TABLE IF EXISTS sale_details;
DROP TABLE IF EXISTS sales;

-- ============================================
-- SALES TABLE
-- ============================================
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

-- ============================================
-- SALE DETAILS TABLE
-- ============================================
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

-- ============================================
-- SAMPLE DATA - SALES
-- ============================================
INSERT INTO sales (sale_id, staff_id, sale_date, sale_time, total_price, total_cost, profit, amount_paid, change_amount, status) VALUES
('SL-1001', 'ST-1001', '2025-10-12', '10:24:32', 199.99, 160.23, 39.76, 200.00, 0.01, 'completed'),
('SL-1002', 'ST-1001', '2025-10-12', '12:32:34', 250.00, 225.00, 25.00, 250.00, 0.00, 'completed');

-- ============================================
-- SAMPLE DATA - SALE DETAILS
-- ============================================
INSERT INTO sale_details (sale_detail_id, sale_id, product_id, quantity, price_per_unit, cost_per_unit, subtotal, is_manual) VALUES
-- SL-1001: 2x Small Original Coffee + 1x Overload Burger
('SD-0001', 'SL-1001', 'PROD-1004', 2, 50.00, 30.12, 100.00, 0),
('SD-0002', 'SL-1001', 'PROD-1001', 1, 99.99, 70.23, 99.99, 0),
-- SL-1002: 5x Small Original Coffee
('SD-0003', 'SL-1002', 'PROD-1004', 5, 50.00, 45.00, 250.00, 0);
