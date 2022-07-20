ALTER TABLE `operacion` 
    ADD `base_start_in_usd` DECIMAL(15,8) NULL DEFAULT NULL AFTER `stop`,
    ADD `quote_start_in_usd` DECIMAL(15,8) NOT NULL DEFAULT ,
    ADD `start` DATETIME NULL DEFAULT NULL AFTER `capital_in_usd`;