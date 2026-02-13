USE urfoodhubcafe_db;

-- =============================================
-- INVENTORY MOVEMENTS TABLE - Run #4 (after inventory.sql)
-- Depends on: inventory_items, inventory_batches
-- =============================================

-- ============================================
-- DROP EXISTING TABLE
-- ============================================
DROP TABLE IF EXISTS inventory_movements;

-- ============================================
-- INVENTORY MOVEMENTS TABLE
-- ============================================
CREATE TABLE inventory_movements (
    movement_id VARCHAR(20) PRIMARY KEY,
    inventory_id VARCHAR(20) NOT NULL,
    batch_id VARCHAR(20),

    -- Movement Details
    movement_type VARCHAR(50) NOT NULL,  -- 'initial_stock', 'restock', 'sale', 'disposal', 'adjustment', etc.
    quantity DECIMAL(10, 2) NOT NULL,  -- Negative for outbound, positive for inbound

    -- Quantity Tracking
    old_quantity DECIMAL(10, 2) NOT NULL,
    new_quantity DECIMAL(10, 2) NOT NULL,

    -- Cost Tracking
    unit_cost DECIMAL(10, 2) NOT NULL,
    total_value DECIMAL(10, 2) NOT NULL,

    -- Reference (optional - links to sale, purchase, etc.)
    reference_type VARCHAR(50),
    reference_id VARCHAR(20),

    -- Staff & Timing
    staff_id VARCHAR(20),
    movement_date DATETIME NOT NULL,

    -- Reason/Notes - Used for disposal reasons, adjustment explanations, etc.
    reason TEXT,

    -- Audit
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (inventory_id) REFERENCES inventory_items(item_id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES inventory_batches(batch_id) ON DELETE SET NULL,

    INDEX idx_inventory (inventory_id),
    INDEX idx_batch (batch_id),
    INDEX idx_movement_type (movement_type),
    INDEX idx_movement_date (movement_date),
    INDEX idx_reference (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
