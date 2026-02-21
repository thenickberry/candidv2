-- Migration: Add must_change_password column to user table
-- Date: 2026-02-14

ALTER TABLE `user`
ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `pword`;
