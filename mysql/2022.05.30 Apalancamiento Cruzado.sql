ALTER TABLE `operacion` 
    ADD `tipo` INT(2) NOT NULL DEFAULT '0' AFTER `idoperacion`, 
    ADD INDEX (`tipo`);
ALTER TABLE `operacion` 
    CHANGE `inicio_usd` `inicio_usd` DECIMAL(16,8) NOT NULL, 
    CHANGE `capital_usd` `capital_usd` DECIMAL(16,8) NOT NULL;
