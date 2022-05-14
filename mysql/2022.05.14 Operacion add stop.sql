ALTER TABLE `operacion` 
    ADD `stop` TINYINT NOT NULL DEFAULT '0' AFTER `auto_restart`, 
    ADD INDEX (`stop`);