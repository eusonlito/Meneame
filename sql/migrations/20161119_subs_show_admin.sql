ALTER TABLE `subs` ADD `show_admin` BOOLEAN NOT NULL DEFAULT 0;
UPDATE `subs` SET `show_admin` = 1;