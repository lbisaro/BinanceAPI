#Establecer el destino del PROFIT

ALTER TABLE `operacion` ADD `destino_profit` INT(1) NOT NULL DEFAULT '0' AFTER `capital_usd`;