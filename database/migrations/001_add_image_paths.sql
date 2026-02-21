-- Migration: Add filesystem paths to image_info table
-- Date: 2026-02-14

ALTER TABLE `image_info`
ADD COLUMN `file_path` VARCHAR(255) NULL DEFAULT NULL AFTER `content_type`,
ADD COLUMN `thumb_path` VARCHAR(255) NULL DEFAULT NULL AFTER `file_path`;

-- Add index for faster lookups
CREATE INDEX `idx_image_file_path` ON `image_info` (`file_path`);
