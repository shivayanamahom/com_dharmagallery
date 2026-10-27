CREATE TABLE IF NOT EXISTS `#__dharmagallery_uploads` (
 `path_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 `path` VARCHAR(1024) NOT NULL,
 `album_id` INT UNSIGNED NOT NULL DEFAULT 0,
 `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`path_hash`),
 KEY `idx_album` (`album_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
