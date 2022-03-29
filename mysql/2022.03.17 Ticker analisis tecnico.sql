ALTER TABLE `tickers` 
        ADD `hst_min` DECIMAL(15,8) NOT NULL AFTER `created`, 
        ADD `hst_max` DECIMAL(15,8) NOT NULL AFTER `hst_min`,
        ADD `qty_decs_units` INT(3) NOT NULL AFTER `hst_max`,
        ADD `qty_decs_price` INT(3) NOT NULL AFTER `qty_decs_units`,
        ADD `quote_asset` VARCHAR(8) NOT NULL AFTER `qty_decs_price`,
        ADD `base_asset` VARCHAR(8) NOT NULL AFTER `quote_asset`;

ALTER TABLE `tickers` 
    CHANGE `qty_decs_units` `qty_decs_units` INT NOT NULL DEFAULT '0', 
    CHANGE `qty_decs_price` `qty_decs_price` INT NOT NULL DEFAULT '0';