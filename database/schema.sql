-- CANDIDv2 Database Schema
-- Compatible with MariaDB 11+ and MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Table: user
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(32) NOT NULL,
    `pword` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
    `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
    `access` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '0=guest, 1-4=user, 5=admin',
    `fname` VARCHAR(32) DEFAULT NULL,
    `lname` VARCHAR(32) DEFAULT NULL,
    `email` VARCHAR(128) DEFAULT NULL,
    `numrows` TINYINT UNSIGNED NOT NULL DEFAULT 5,
    `numcols` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `name_disp` ENUM('fname', 'lname', 'both') NOT NULL DEFAULT 'fname',
    `init_disp` ENUM('480x360', '640x480', '800x600') NOT NULL DEFAULT '480x360',
    `onlist` ENUM('y', 'n') NOT NULL DEFAULT 'y',
    `theme` VARCHAR(32) DEFAULT 'default',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_username` (`username`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: session
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `session` (
    `session_id` VARCHAR(64) NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `expire` DATETIME NOT NULL,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_expire` (`expire`),
    CONSTRAINT `fk_session_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: category
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(64) NOT NULL,
    `descr` VARCHAR(255) DEFAULT NULL,
    `parent` INT UNSIGNED DEFAULT NULL,
    `haskids` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `owner` INT UNSIGNED NOT NULL,
    `public` ENUM('y', 'n') NOT NULL DEFAULT 'y',
    `sort_by` VARCHAR(32) DEFAULT NULL,
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_parent` (`parent`),
    KEY `idx_owner` (`owner`),
    KEY `idx_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent`) REFERENCES `category` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_category_owner` FOREIGN KEY (`owner`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: image_info
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `image_info` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED DEFAULT NULL COMMENT 'Primary category',
    `descr` VARCHAR(255) DEFAULT NULL,
    `added` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    `owner` INT UNSIGNED NOT NULL,
    `photographer` INT UNSIGNED DEFAULT NULL,
    `date_taken` DATETIME DEFAULT NULL,
    `access` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `last_view` DATETIME DEFAULT NULL,
    `private` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `width` SMALLINT UNSIGNED DEFAULT NULL,
    `height` SMALLINT UNSIGNED DEFAULT NULL,
    `content_type` VARCHAR(64) DEFAULT NULL,
    `file_path` VARCHAR(255) DEFAULT NULL COMMENT 'Filesystem path for full image',
    `thumb_path` VARCHAR(255) DEFAULT NULL COMMENT 'Filesystem path for thumbnail',
    `filename` VARCHAR(128) DEFAULT NULL,
    `camera` VARCHAR(128) DEFAULT NULL,
    `md5_hash` CHAR(32) DEFAULT NULL COMMENT 'For duplicate detection',
    `deleted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_category_id` (`category_id`),
    KEY `idx_owner` (`owner`),
    KEY `idx_photographer` (`photographer`),
    KEY `idx_date_taken` (`date_taken`),
    KEY `idx_added` (`added`),
    KEY `idx_md5_hash` (`md5_hash`),
    KEY `idx_deleted_at` (`deleted_at`),
    CONSTRAINT `fk_image_info_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_image_info_owner` FOREIGN KEY (`owner`) REFERENCES `user` (`id`),
    CONSTRAINT `fk_image_info_photographer` FOREIGN KEY (`photographer`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: image_file (full-size image data)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `image_file` (
    `image_id` INT UNSIGNED NOT NULL,
    `data` LONGBLOB,
    PRIMARY KEY (`image_id`),
    CONSTRAINT `fk_image_file_image` FOREIGN KEY (`image_id`) REFERENCES `image_info` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: image_thumb (thumbnail data)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `image_thumb` (
    `image_id` INT UNSIGNED NOT NULL,
    `data` MEDIUMBLOB,
    PRIMARY KEY (`image_id`),
    CONSTRAINT `fk_image_thumb_image` FOREIGN KEY (`image_id`) REFERENCES `image_info` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: image_category (many-to-many: images to categories)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `image_category` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_id` INT UNSIGNED NOT NULL,
    `category_id` INT UNSIGNED NOT NULL,
    `pri` ENUM('y', 'n') NOT NULL DEFAULT 'n' COMMENT 'Primary category flag',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_image_category` (`image_id`, `category_id`),
    KEY `idx_category_id` (`category_id`),
    CONSTRAINT `fk_image_category_image` FOREIGN KEY (`image_id`) REFERENCES `image_info` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_image_category_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: people (tag users in images)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `people` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `image_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_user_image` (`user_id`, `image_id`),
    KEY `idx_image_id` (`image_id`),
    CONSTRAINT `fk_people_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_people_image` FOREIGN KEY (`image_id`) REFERENCES `image_info` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: image_comment
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `image_comment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `image_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `stamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_image_id` (`image_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_stamp` (`stamp`),
    CONSTRAINT `fk_image_comment_image` FOREIGN KEY (`image_id`) REFERENCES `image_info` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_image_comment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Table: history (audit log)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `history` (
    `history_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `action` VARCHAR(255) NOT NULL,
    `message` TEXT,
    `datetime` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_addr` VARCHAR(45) DEFAULT NULL,
    `table_id` INT UNSIGNED DEFAULT NULL,
    `table_name` VARCHAR(32) DEFAULT NULL,
    PRIMARY KEY (`history_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_datetime` (`datetime`),
    KEY `idx_table` (`table_name`, `table_id`),
    CONSTRAINT `fk_history_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
