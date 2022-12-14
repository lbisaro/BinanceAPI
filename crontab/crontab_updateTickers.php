<?php
include_once MDL_PATH."bot/Operacion.php";
include_once MDL_PATH."binance/BinanceAPI.php";

$opr = new Operacion();
$api = new BinanceAPI();
$tck = new Ticker();

$symbols = $opr->getAllSymbols();

$addSymbols = $tck->getDataSet();
if (!empty($addSymbols))
{
    foreach ($addSymbols as $rw)
    {
        if (!isset($symbols[$rw['tickerid']]))
        {
            $symbols[$rw['tickerid']] = $rw;
            $symbols[$rw['tickerid']]['symbol'] = $rw['tickerid'];
        }
    }
}

if (!empty($symbols))
{
    $prm = array();
    foreach ($symbols as $rw)
        $prm[] = $rw['symbol'];


    $apiRsp = $api->exchangeInfo($prm);
    $apiInfo = array();

    foreach ($symbols as $rw)
    {
        $symbol = $rw['symbol'];

        $apiInfo[$symbol]['qty_decs_units'] = intval($api->numberOfDecimals($apiRsp['symbols'][$symbol]['filters'][1]['minQty']));
        $apiInfo[$symbol]['qty_decs_price'] = intval($api->numberOfDecimals($apiRsp['symbols'][$symbol]['filters'][0]['minPrice']));
        $apiInfo[$symbol]['quote_asset'] = $apiRsp['symbols'][$symbol]['quoteAsset'];
        $apiInfo[$symbol]['base_asset'] = $apiRsp['symbols'][$symbol]['baseAsset'];
        $apiInfo[$symbol]['qty_decs_quote'] = $tck->presetDecs[$apiInfo[$symbol]['quote_asset']];
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