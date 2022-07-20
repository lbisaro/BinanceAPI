ALTER TABLE `operacion` 
    ADD `base_start_in_usd` DECIMAL(15,8) NULL DEFAULT NULL AFTER `stop`,
    ADD `quote_start_in_usd` DECIMAL(15,8) NULL DEFAULT NULL,
    ADD `start` DATETIME NULL DEFAULT NULL ;