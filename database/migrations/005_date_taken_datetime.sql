-- Migration: Change date_taken from DATE to DATETIME to store time component
-- Run: docker exec -i candidv2-db-1 mysql -u candid -pcandid candid < database/migrations/005_date_taken_datetime.sql

ALTER TABLE `image_info` MODIFY COLUMN `date_taken` DATETIME DEFAULT NULL;
