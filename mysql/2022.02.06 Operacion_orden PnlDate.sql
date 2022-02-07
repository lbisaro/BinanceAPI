ALTER TABLE `operacion_orden` 
    ADD `pnlDate` DATETIME NULL DEFAULT NULL AFTER `completed`, 
    ADD INDEX `pnlDate` (`pnlDate`);