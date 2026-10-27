CREATE TABLE IF NOT EXISTS `#__dharmagallery_albums` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `description` MEDIUMTEXT NULL,
    `params` MEDIUMTEXT NULL,
    `alias` VARCHAR(255) NOT NULL DEFAULT '',
    `cover` VARCHAR(255) NOT NULL DEFAULT '',
    `ordering` INT NOT NULL DEFAULT 0,
    `published` TINYINT NOT NULL DEFAULT 1,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `mode` VARCHAR(16) NOT NULL DEFAULT 'gallery',
    `asset_id` INT UNSIGNED NOT NULL DEFAULT 0,
    `access` INT UNSIGNED NOT NULL DEFAULT 1,
    `language` CHAR(7) NOT NULL DEFAULT '*',
    `checked_out` INT UNSIGNED NULL DEFAULT NULL,
    `checked_out_time` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified` DATETIME NULL DEFAULT NULL,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `metadesc` VARCHAR(1024) NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `idx_parent` (`parent_id`),
    KEY `idx_alias` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__dharmagallery_photos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `album_id` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL DEFAULT '',
    `title` VARCHAR(255) NOT NULL DEFAULT '',
    `ordering` INT NOT NULL DEFAULT 0,
    `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `catid` INT UNSIGNED NOT NULL DEFAULT 0,
    `path` VARCHAR(1024) NOT NULL DEFAULT '',
    `type` VARCHAR(16) NOT NULL DEFAULT 'image',
    `mime` VARCHAR(127) NOT NULL DEFAULT '',
    `size` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `alt` VARCHAR(255) NOT NULL DEFAULT '',
    `description` TEXT NULL,
    `published` TINYINT NOT NULL DEFAULT 1,
    `access` INT UNSIGNED NOT NULL DEFAULT 1,
    `checked_out` INT UNSIGNED NULL DEFAULT NULL,
    `checked_out_time` DATETIME NULL DEFAULT NULL,
    `created_by` INT UNSIGNED NOT NULL DEFAULT 0,
    `modified` DATETIME NULL DEFAULT NULL,
    `modified_by` INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_album` (`album_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__dharmagallery_uploads` (
 `path_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 `path` VARCHAR(1024) NOT NULL,
 `album_id` INT UNSIGNED NOT NULL DEFAULT 0,
 `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`path_hash`),
 KEY `idx_album` (`album_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
