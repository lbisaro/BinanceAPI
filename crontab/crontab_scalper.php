<?php
include_once MDL_PATH."usr/UsrUsuario.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE_SCLPR, "\nSTART ".$procStart."\n");

// CONFIGURACION

$totalUsd = 1000;
$totalToken = 0;
$stopLoss = 0.75/100;
$takeProfit = 1.75/100;

//Importe en USD para cada operacion 
$importeCompra = 100;
//Token USD
$tokenUSD = 'USDT';
//Token
$token = 'LUNA';




/**
PROCESO GENERAL
    
    Si existe una señal de compra y no hay compra en curso
    Compra

    Si hay una compra en curso y existe una señal de venta o cae del stop-loss y 
    Vende


*/

    
$idusuario = 1;
$usr = new UsrUsuario($idusuario);

$tck = new Ticker();

$continueLoop = true;
$symbol = $token.$tokenUSD;

$ultimaOperacion = 'V'; // C Compra - V Venta (Default para el inicio de la operacion)
$priceUltimaCompra = 0;

$msg = 'Symbol: '.$symbol."\n";
file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
$msg = 'Date            ;Side;Price;Qty;Operacion USD;TotalUSD,TotalToken;'."\n";
file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
echo $msg."\n";

while ($continueLoop)
{
    $at = $tck->getAnalisisTecnico($symbol,'5m');
    if (!empty($at))
    {
        $stopLossBreak = false;
        $stopLossPrice = ($priceUltimaCompra-$priceUltimaCompra*$stopLoss);
        if ( $at['candel']['low'] <= $stopLossPrice )
            $stopLossBreak = true;
        $takeProfitBreak = false;
        $takeProfitPrice = ($priceUltimaCompra+$priceUltimaCompra*$takeProfit);
        if ( $at['candel']['high'] >= $takeProfitPrice )
            $takeProfitBreak = true;

        $signal = '';
        $sellSignal = ($at['signal']['rsi'] == 'V' || $at['signal']['macd'] == 'V' || $at['signal']['bb'] == 'V');
        $buySignal = ($at['signal']['rsi'] == 'C' && $at['signal']['macd'] == 'C' && $at['signal']['bb'] == 'C');

        if ($ultimaOperacion =='C' && ($stopLossBreak || $takeProfitBreak))
            $signal = 'V';
        elseif ($ultimaOperacion =='V' && $buySignal)
            $signal = 'C';
        else
            $signal = '-';


        if ($ultimaOperacion =='V' && $signal == 'C')
        {
            $price = $at['price'];
            $qty = $importeCompra/$price;
            $totalToken = $totalToken+$qty;
            $totalUsd = $totalUsd-$importeCompra;

            $priceUltimaCompra = $price;

            $msg = date('Y-m-d H:i').';'.'BUY'.';'.$price.';'.$qty.';-'.toDec($price*$qty).';'.$totalUsd.';'.$totalToken.';'."\n";
            file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
            echo $msg."\n";

            $ultimaOperacion = 'C';
        }
        elseif ($ultimaOperacion =='C' && $signal == 'V')
        {
            if ($takeProfitBreak)
            {
                $price = $takeProfitPrice;
                $buyMsg = 'TAKE-PROFIT';
            }
            if ($stopLossBreak)
            {
                $price = $stopLossPrice;
                $buyMsg = 'STOP-LOSS';
            }
            else
            {
                $price = $at['price'];
                $buyMsg = 'SIGNAL';
            }
            $qty = $totalToken;
            $totalToken = $totalToken-$qty;
            $totalUsd = $totalUsd+$qty*$price;

            $priceUltimaCompra = 0;

            $msg = date('Y-m-d H:i').';'.'SELL '.$buyMsg.';'.$price.';'.$qty.';'.toDec($price*$qty).';'.$totalUsd.';'.$totalToken.';'."\n";
            file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
            echo $msg."\n";

            $ultimaOperacion = 'V';
        }
        else
        {
            echo $ultimaOperacion;
            
        }
    }
    else
    {
        print_r("\n".'NO HAY INFORMACION SOBRE '.$symbol);
        echo '.';
    }
            

    if ($continueLoop)
        sleep(60); //1 minutos
        
    
}




//Operacion::logBot('END');
$procEndU = microtime(true);

file_put_contents(STATUS_FILE_AT, "\n".'Proceso: '.toDec($procEndU-$procStartU,4).' seg.',FILE_APPEND);
