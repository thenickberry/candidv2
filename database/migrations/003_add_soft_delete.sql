-- Migration: Add soft-delete support for categories and images
-- This adds deleted_at and deleted_by columns to enable trash/restore functionality

ALTER TABLE `category`
ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN `deleted_by` INT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `image_info`
ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL,
ADD COLUMN `deleted_by` INT UNSIGNED NULL DEFAULT NULL;

CREATE INDEX `idx_category_deleted_at` ON `category` (`deleted_at`);
CREATE INDEX `idx_image_deleted_at` ON `image_info` (`deleted_at`);
