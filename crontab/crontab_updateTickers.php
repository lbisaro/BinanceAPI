<?php
include_once MDL_PATH."bot/Operacion.php";
include_once MDL_PATH."binance/BinanceAPI.php";

$opr = new Operacion();
$api = new BinanceAPI();
$tck = new Ticker();

$symbols = $opr->getAllSymbols();

if (!empty($symbols))
{
    $prm = array();
    foreach ($symbols as $rw)
        $prm[] = $rw['symbol'];


    $apiRsp = $api->exchangeInfo($prm);

    foreach ($symbols as $rw)
    {
        $symbol = $rw['symbol'];

        $apiInfo[$symbol]['qty_decs_units'] = intval($api->numberOfDecimals($apiRsp['symbols'][$symbol]['filters'][2]['minQty']));
        $apiInfo[$symbol]['qty_decs_price'] = intval($api->numberOfDecimals($apiRsp['symbols'][$symbol]['filters'][0]['minPrice']));
        $apiInfo[$symbol]['quote_asset'] = $apiRsp['symbols'][$symbol]['quoteAsset'];
        $apiInfo[$symbol]['base_asset'] = $apiRsp['symbols'][$symbol]['baseAsset'];
        
    }

    foreach ($symbols as $rw)
    {
        $symbol = $rw['symbol'];
        $tck->reset();
        $tck->load($symbol);

        $arrToSet = $apiInfo[$symbol];
        $arrToSet['tickerid'] = $symbol;
        echo "\n".$symbol;
        if (!$symbols[$symbol]['quote_asset']) //No existe registro para el Ticker
        {
            $arrToSet['hst_min'] = -1;
            $arrToSet['hst_max'] = -1;
            $arrToSet['max_drawdown'] = -1;
            $tck->set($arrToSet);
            $tck->tableInsert(DB_NAME,'tickers');
            echo " NEW";
        }
        else
        {
            $tck->set($arrToSet);
            $tck->tableUpdate(DB_NAME,'tickers');
            echo " UPDATE";
        }

    }   
    
}

?>