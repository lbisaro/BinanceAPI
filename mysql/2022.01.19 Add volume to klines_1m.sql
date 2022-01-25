#Agregar volumen a la tabla de Klines
ALTER TABLE `klines_1m` ADD `volume` DECIMAL(12,2) NOT NULL AFTER `low`;

