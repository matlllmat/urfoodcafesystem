-- =============================================
-- RESET DATABASE - Run this FIRST
-- =============================================
-- Execution Order:
--   1. reset.sql        (this file - drops & creates database)
--   2. user.sql         (users, permissions)
--   3. inventory.sql    (inventory categories, items, batches)
--   4. inventory-trail.sql (inventory movements - depends on inventory)
--   5. products.sql     (products, product categories - depends on inventory)
--   6. sales.sql        (sales, sale details - depends on users & products)
-- =============================================

DROP DATABASE IF EXISTS urfoodhubcafe_db;
CREATE DATABASE urfoodhubcafe_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;