-- =========================================================
-- Work Order Safety Checks & Checklists Migration
-- Version: 1.0
-- Description: Creates tables for safety pre-flight checks
--              and work order checklists with completion tracking
-- =========================================================

-- ─────────────────────────────────────────────────────────────
-- Safety Checks Master Table (template items)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wo_safety_checks (
    safety_id INT AUTO_INCREMENT PRIMARY KEY,
    safety_text VARCHAR(255) NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    category_id INT DEFAULT NULL COMMENT 'Optional: link to asset_categories for category-specific checks',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id),
    INDEX idx_sort (sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Safety Check Completions (per work order tracking)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wo_safety_completions (
    completion_id INT AUTO_INCREMENT PRIMARY KEY,
    wo_id INT NOT NULL,
    safety_id INT NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    completed_by INT DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_wo_safety (wo_id, safety_id),
    INDEX idx_wo (wo_id),
    INDEX idx_safety (safety_id),
    FOREIGN KEY (wo_id) REFERENCES work_orders(wo_id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Checklists Master Table (checklist definitions)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wo_checklists (
    checklist_id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_name VARCHAR(100) NOT NULL,
    category_id INT DEFAULT NULL COMMENT 'Link to asset_categories for category-specific checklists, NULL = general',
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Checklist Items (items within each checklist)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wo_checklist_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    checklist_id INT NOT NULL,
    item_text VARCHAR(255) NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    requires_photo TINYINT(1) DEFAULT 0,
    is_verifiable TINYINT(1) DEFAULT 0 COMMENT '1 = can be auto-verified by system, 0 = manual check required',
    verification_type VARCHAR(50) DEFAULT NULL COMMENT 'Type of auto-verification: photo_before, photo_after, time_logged, etc.',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_checklist (checklist_id),
    INDEX idx_sort (sort_order),
    FOREIGN KEY (checklist_id) REFERENCES wo_checklists(checklist_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Checklist Completions (per work order tracking)
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS wo_checklist_completions (
    completion_id INT AUTO_INCREMENT PRIMARY KEY,
    wo_id INT NOT NULL,
    item_id INT NOT NULL,
    is_done TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL COMMENT 'Path to uploaded photo if requires_photo',
    completed_by INT DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    verification_method VARCHAR(20) DEFAULT 'manual' COMMENT 'manual or auto',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_wo_item (wo_id, item_id),
    INDEX idx_wo (wo_id),
    INDEX idx_item (item_id),
    FOREIGN KEY (wo_id) REFERENCES work_orders(wo_id) ON DELETE CASCADE,
    FOREIGN KEY (completed_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────
-- Seed Default Safety Checks
-- ─────────────────────────────────────────────────────────────
INSERT INTO wo_safety_checks (safety_text, is_mandatory, sort_order) VALUES
('Verify work area is clear and safe', 1, 1),
('Confirm equipment is powered off before inspection', 1, 2),
('ESD protection measures in place (wrist strap/mat)', 1, 3),
('Personal protective equipment (PPE) worn if required', 0, 4),
('Ladder/elevation equipment secured and stable (if used)', 0, 5),
('Fire extinguisher accessible and in working condition', 0, 6),
('Emergency exit routes identified and clear', 0, 7)
ON DUPLICATE KEY UPDATE safety_text = VALUES(safety_text);

-- ─────────────────────────────────────────────────────────────
-- Seed Default General Repair Checklist
-- ─────────────────────────────────────────────────────────────
INSERT INTO wo_checklists (checklist_name, category_id, description) VALUES
('General Repair Checklist', NULL, 'Standard checklist for general repair work orders')
ON DUPLICATE KEY UPDATE checklist_name = VALUES(checklist_name);

-- Get the checklist_id for the general checklist
SET @general_checklist_id = (SELECT checklist_id FROM wo_checklists WHERE checklist_name = 'General Repair Checklist' LIMIT 1);

-- Insert checklist items
INSERT INTO wo_checklist_items (checklist_id, item_text, is_mandatory, requires_photo, is_verifiable, verification_type, sort_order) VALUES
(@general_checklist_id, 'Capture before-repair photo', 1, 1, 1, 'photo_before', 1),
(@general_checklist_id, 'Perform visual inspection', 1, 0, 0, NULL, 2),
(@general_checklist_id, 'Perform power-on test', 1, 0, 0, NULL, 3),
(@general_checklist_id, 'Verify core functionality', 1, 0, 0, NULL, 4),
(@general_checklist_id, 'Document findings and actions', 1, 0, 0, NULL, 5),
(@general_checklist_id, 'Capture after-repair photo', 1, 1, 1, 'photo_after', 6)
ON DUPLICATE KEY UPDATE item_text = VALUES(item_text);

-- ─────────────────────────────────────────────────────────────
-- Done
-- ─────────────────────────────────────────────────────────────
SELECT 'Safety checks and checklists tables created successfully!' AS status;
