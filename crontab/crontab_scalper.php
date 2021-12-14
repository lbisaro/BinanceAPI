<?php
include_once MDL_PATH."usr/UsrUsuario.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE_SCLPR, "\nSTART ".$procStart);

// CONFIGURACION

$totalUsd = 100;
$totalToken = 0;
$stopLoss = 1/100;

//Importe en USD para cada operacion 
$importeCompra = 80;
//Token USD
$tokenUSD = 'BUSD';
//Token
$token = 'BNB';




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

$msg = 'Date            ;Side;Price;Qty;TotalUSD,TotalToken;';
file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
echo $msg."\n";

while ($continueLoop)
{
    $at = $tck->getAnalisisTecnico($symbol,'5m');
    if (!empty($at))
    {
        $stopLossBreak = false;
        if ( $at['price'] <= ($priceUltimaCompra-$priceUltimaCompra*$stopLoss) )
            $stopLossBreak = true;

        $signal = '';
        if ($stopLossBreak || $at['signal']['rsi'] == 'V' || $at['signal']['macd'] == 'V' || $at['signal']['bb'] == 'V')
            $signal = 'V';
        elseif ($at['signal']['rsi'] == 'C' && $at['signal']['macd'] == 'C' && $at['signal']['bb'] == 'C')
            $signal = 'C';


        if ($ultimaOperacion =='V' && $signal == 'C')
        {
            $price = $at['price'];
            $qty = $importeCompra/$price;
            $totalToken = $totalToken+$qty;
            $totalUsd = $totalUsd-$importeCompra;

            $priceUltimaCompra = $price;

            $msg = date('Y-m-d H:i').';'.'BUY'.';'.$price.';'.$qty.';'.$totalUsd.';'.$totalToken.';';
            file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
            echo "\n".$msg."\n";

            $ultimaOperacion = 'C';
        }
        elseif ($ultimaOperacion =='C' && $signal == 'V')
        {
            $price = $at['price'];
            $qty = $totalToken;
            $totalToken = $totalToken-$qty;
            $totalUsd = $totalUsd+$qty*$price;

            $priceUltimaCompra = 0;

            $msg = date('Y-m-d H:i').';'.'SELL'.';'.$price.';'.$qty.';'.$totalUsd.';'.$totalToken.';'.($stopLossBreak?'STOP-LOSS':'SIGNAL');
            file_put_contents(STATUS_FILE_SCLPR, $msg, FILE_APPEND);
            echo "\n".$msg."\n";

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
