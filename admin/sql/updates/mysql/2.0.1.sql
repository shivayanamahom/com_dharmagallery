ALTER TABLE `#__dharmagallery_albums` ADD COLUMN `mode` VARCHAR(16) NOT NULL DEFAULT 'gallery';
UPDATE `#__dharmagallery_albums` a SET a.`mode` = 'files' WHERE EXISTS (SELECT 1 FROM `#__dharmagallery_photos` p WHERE p.`album_id` = a.`id` AND p.`type` = 'file');
