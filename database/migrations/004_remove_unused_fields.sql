-- Migration: Remove unused tables and fields
-- Date: 2026-02-15

-- Drop unused tables
DROP TABLE IF EXISTS `user_pics`;
DROP TABLE IF EXISTS `user_mms`;
DROP TABLE IF EXISTS `category_pics`;

-- Remove unused fields from user table
ALTER TABLE `user`
    DROP COLUMN IF EXISTS `debug`,
    DROP COLUMN IF EXISTS `expire`,
    DROP COLUMN IF EXISTS `update_notice`;

-- Remove unused field from category table
ALTER TABLE `category`
    DROP COLUMN IF EXISTS `loc`;

-- Remove unused field from image_info table
ALTER TABLE `image_info`
    DROP COLUMN IF EXISTS `timestamp`;

-- Remove unused fields from session table
ALTER TABLE `session`
    DROP COLUMN IF EXISTS `last_query`,
    DROP COLUMN IF EXISTS `ip`;
