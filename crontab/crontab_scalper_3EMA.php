<?php
include_once MDL_PATH."usr/UsrUsuario.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);

// CONFIGURACION

$capital = 1000;
$totalUsd = $capital;
$totalToken = 0;

//Posicion en USD para compra o venta
$usdPos = $capital*10/100;

//Luego de la compra inicial, la siguiente compra la hace si el precio sube el porcentaje especificado
$escalaRecompra = 1.5;

#Paramstros recibidos desde el scripto crontab.php
    //Token USD
    $tokenUSD = $tokenUSD;
    //Token
    $token = $token;

//Triple EMA 
$emaSlow = 16;
$emaMid  = 8;
$emaFast = 5;



/**
PROCESO GENERAL
    
    Si EMAcross_1h > 0
    
        Si el cruce de EMA en 15m cambia a alcista
        Si el cruce de EMA en 5m cambia a alcista
        
        Revision en 1m
        Si emaFast > emaMid y emaFast > ema Slow y precio > lastPrice*1.02
            Si hay USDT
                Compra parcial (USD $usdPos) (En cada minuto) si se mantiene la condicion

        Si emaFast < emaMid
            Si hay Tokens
                Vende parcial 
                    //Si la cantidad de Token en USD >= $usdPos*2, vende USD $usdPos*2 en Token
                    //Si la cantidad de Token en USD < $usdPos*2, vende total de Token

    Si EMAcross_1h < 0

        Vende el total de Token



*/


function analisisTecnico($klines,$prms=array())
{
    $emaMid  = $prms['emaMid'];
    $emaFast = $prms['emaFast'];
    $emaSlow = $prms['emaSlow'];
    $ajusteErrorEma = 0;
    foreach ($klines as $timestamp => $candel)
    {
        $close = (float)$candel['close'];
        if ($ajusteErrorEma == 0) 
            $ajusteErrorEma = ajuste($close);
        $aClose[] = $close*$ajusteErrorEma; //Corrige inconvenientes con monedas de precio muy bajo
    }
    $emaMid  = trader_ema($aClose, $emaMid);
    $emaSlow = trader_ema($aClose, $emaSlow);
    $emaFast = trader_ema($aClose, $emaFast);
    $emaCross = toDecDown(((end($emaMid)/end($emaSlow))-1)*100);

    $ret['close']    = $close;
    $ret['emaMid']   = end($emaMid)/$ajusteErrorEma;
    $ret['emaSlow']  = end($emaSlow)/$ajusteErrorEma;
    $ret['emaFast']  = end($emaFast)/$ajusteErrorEma;
    $ret['emaCross'] = $emaCross/$ajusteErrorEma;
    return $ret;
}

function ajuste($num)
{
    $aj = 10;
    while ($num<0.1)
    {
        $aj = $aj*10;
        $num = $num*10;
    }
    return $aj;
}

    
$usr = new UsrUsuario($idusuario);
$api = new BinanceAPI(); 

$symbol = $token.$tokenUSD;

$logFile = str_replace('.log','_'.$symbol.'.log',STATUS_FILE_SCLPR);
$datFile = str_replace('.log','_'.$symbol.'.dat',STATUS_FILE_SCLPR);

if (!is_file($logFile))
    file_put_contents($logFile, "\nSTART ".$procStart."\n");

if (!is_file($datFile))
{
    $json['capital'] = $capital;
    $json['totalUsd'] = $totalUsd;
    $json['totalToken'] = $totalToken;
    file_put_contents($datFile, json_encode($json));
}
else
{
    $json = json_decode(file_get_contents($datFile), true);
    $capital = $json['capital'];
    $totalUsd = $json['totalUsd'];
    $totalToken = $json['totalToken'];
}



$ultimaOperacion = 'V'; // C Compra - V Venta (Default para el inicio de la operacion)
$priceUltimaCompra = 0;

if (!is_file($logFile))
{
    $msg = 'Symbol: '.$symbol."\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
    $msg = 'Date            ;Side;Price;Qty;Operacion USD;TotalUSD,TotalToken;EMAcross_1m;EMAcross_5m;EMAcross_15m;EMAcross_1H;'."\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
    echo $symbol.' '.$msg."\n";
}

$lastPrice=0;
$compras = 0;
$prmsAT['emaMid']  = $emaMid;
$prmsAT['emaFast'] = $emaFast;
$prmsAT['emaSlow'] = $emaSlow;

//Obtener data de velas
$limit = $emaSlow+2;
$operarOk = true;

try {
    $klines_1h =  $api->candlesticks($symbol, '1h', $limit);
} catch (Throwable $e) {
    echo "\n\nERROR BINANCE".'No fue posible encontrar informacion para la moneda '.$symbol."\n\n";
    return false;
}

//Analisis tecnico
$ajusteErrorEma = 10000;
echo "\nAT: -> ";
$at = analisisTecnico($klines_1h,$prmsAT);
$lastClose   = $at['close'];
$emaMid_1h   = $at['emaMid'];
$emaSlow_1h  = $at['emaSlow'];
$emaCross_1h = $at['emaCross'];
echo "1h: ".($emaCross_1h>0?'OK':'-').' ';

$noOperar = true;

if ($emaCross_1h>0)
{
    try {
        $klines_15m = $api->candlesticks($symbol, '15m', $limit);
    } catch (Throwable $e) {
        echo "\n\nERROR BINANCE".'No fue posible encontrar informacion para la moneda '.$symbol."\n\n";
        return false;
    }
    $at = analisisTecnico($klines_15m,$prmsAT);
    $lastClose   = $at['close'];
    $emaMid_15m   = $at['emaMid'];
    $emaSlow_15m  = $at['emaSlow'];
    $emaCross_15m = $at['emaCross'];
    echo "15m: ".($emaCross_15m>0?'OK':'-').' ';
    if ($emaCross_15m>0)
    {
        try {
            $klines_5m =  $api->candlesticks($symbol, '5m', $limit);
        } catch (Throwable $e) {
            echo "\n\nERROR BINANCE".'No fue posible encontrar informacion para la moneda '.$symbol."\n\n";
            return false;
        }
        $at = analisisTecnico($klines_5m,$prmsAT);
        $lastClose   = $at['close'];
        $emaMid_5m   = $at['emaMid'];
        $emaSlow_5m  = $at['emaSlow'];
        $emaCross_5m = $at['emaCross'];
        echo "5m: ".($emaCross_5m>0?'OK':'-').' ';
        if ($emaCross_5m>0)
        {
            $noOperar = false;
            try {
                $klines_1m =  $api->candlesticks($symbol, '1m', $limit);
            } catch (Throwable $e) {
                echo "\n\nERROR BINANCE".'No fue posible encontrar informacion para la moneda '.$symbol."\n\n";
                return false;
            }
            $at = analisisTecnico($klines_1m,$prmsAT);
            $lastClose   = $at['close'];
            $emaFast_1m = $at['emaFast'];
            $emaMid_1m  = $at['emaMid'];
            $emaSlow_1m = $at['emaSlow'];
            $emaCross_1m = $at['emaCross'];
            echo "1m EMAcross: ".toDec($emaCross_1m);
            //echo ' -  F>M & M>S: '.$emaFast_1m.'>'.$emaMid_1m.' && '.$emaMid_1m.'>'.$emaSlow_1m;
            echo "\n";
            if ($emaFast_1m>$emaMid_1m && $emaMid_1m>$emaSlow_1m) 
            {
                //Comprar parcial

                if (!$lastPrice)
                    $condicionlastPrice = true;
                elseif ($lastClose>= $lastPrice*(1+($escalaRecompra/100)))
                    $condicionlastPrice = true;
                else
                    $condicionlastPrice = false;

                if ($totalUsd > 0 && $condicionlastPrice)
                {

                    $price = $lastClose;
                    if ($totalUsd>=$usdPos)
                        $importeCompra = $usdPos;
                    else  
                        $importeCompra = $totalUsd;
                    $qty = $importeCompra/$price;
                    $totalToken = $totalToken+$qty;
                    $totalUsd = $totalUsd-$importeCompra;

                    $lastPrice = $price;
                    $compras++;
                    
                    $msg = date('Y-m-d H:i').';'.'BUY'.';'.$price.';'.$qty.';-'.toDec($price*$qty).';'.toDec($totalUsd).';'.$totalToken.';'.toDec($emaCross_1m).';'.toDec($emaCross_5m).';'.toDec($emaCross_15m).';'.toDec($emaCross_1H).';'."\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                    echo $symbol.' '.$msg;
                }                       
            }   
            else
            {
                //Vender total
                if ($totalToken > 0)
                {
                    $price = $lastClose;
                    $qty = $totalToken;
                    $totalToken = $totalToken-$qty;
                    $totalUsd = $totalUsd+($qty*$price);

                    $compras = 0;
                    $msg = date('Y-m-d H:i').';'.'SELL'.';'.$price.';'.$qty.';'.toDec($price*$qty).';'.toDec($totalUsd).';'.$totalToken.';'.toDec($emaCross_1m).';'.toDec($emaCross_5m).';'.toDec($emaCross_15m).';'.toDec($emaCross_1H).';'."\n";
                    file_put_contents($logFile, $msg, FILE_APPEND);
                    echo $symbol.' '.$msg; 

                    $lastPrice = 0;                   
                }
            }           
        }
        
    }
}    

if ($noOperar)
{
    //Vender total
    if ($totalToken > 0)
    {
        $price = $lastClose;
        $qty = $totalToken;
        $totalToken = $totalToken-$qty;
        $totalUsd = $totalUsd+($qty*$price);

        $msg = date('Y-m-d H:i').';'.'SELL-FULL'.';'.$price.';'.$qty.';'.toDec($price*$qty).';'.toDec($totalUsd).';'.$totalToken.';'."\n";
        file_put_contents($logFile, $msg, FILE_APPEND);
        echo "\n".$symbol.' '.$msg."\n"; 

        $lastPrice=0;                   
    }
    
}

//Guardando datos de la operacion
$json['date'] = date('Y-m-d H:i:s');
$json['capital'] = $capital;
$json['totalUsd'] = $totalUsd;
$json['totalToken'] = $totalToken;
file_put_contents($datFile, json_encode($json));