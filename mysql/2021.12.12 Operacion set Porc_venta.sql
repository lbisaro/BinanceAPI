ALTER TABLE `operacion` 
    ADD `porc_venta_up` DECIMAL(5,2) NOT NULL DEFAULT '0' AFTER `multiplicador_porc_inc`, 
    ADD `porc_venta_down` DECIMAL(5,2) NOT NULL DEFAULT '0' AFTER `porc_venta_up`;