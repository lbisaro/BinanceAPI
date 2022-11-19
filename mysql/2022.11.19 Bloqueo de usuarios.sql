ALTER TABLE `usuario` ADD `block` TINYINT(1) NOT NULL DEFAULT '0' AFTER `acceso_datos`, ADD INDEX (`block`);
