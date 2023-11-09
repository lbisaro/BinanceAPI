ALTER TABLE `operacion` 
    ADD `stop_loss` DECIMAL(5.2) NOT NULL DEFAULT '0' AFTER `porc_venta_down`, 
    ADD `max_op_perdida` INT(2) NOT NULL DEFAULT '0' AFTER `stop_loss`;