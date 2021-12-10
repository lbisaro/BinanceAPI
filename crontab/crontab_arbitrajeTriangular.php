<?php
include_once MDL_PATH."usr/UsrUsuario.php";
include_once MDL_PATH."bot/BotArbitrajeAT.php";

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE_AT, "\nSTART ".$procStart);

// CONFIGURACION

//Importe en USD para cada operacion 
$importe = 40;
//Token USD
$tokenUSD = 'USDT';
//Token BASE
$tokensBase[] = 'ETH';
$tokensBase[] = 'BNB';
$tokensBase[] = 'BUSD';
$tokensBase[] = 'USDC';




/**
PROCESO GENERAL

Obtener precios de todas las monedas
Definir tokenUSD (Ej. USDT) y tokenBase = (Ej.:BTC)
Buscar los pares posibles (Ej: MATIC-USDT, MATIC-BTC) 
Definir token (Ej.: MATIC)
Comparar precios y buscar el error
Si el modulo el error es menor a (Ej: -0.4) ejecutar operacion

    Registrar en DDBB el inicio la operacion con los precios de referencia

    Comprar token con tokenUSD (Ej.: MATIC-USDT)
        Poner Orden MARKET y esperar que se ejecute en un loop
        Si el loop demora mas de 30 segundos revisar si continua el error 
    Vender token a tokenBase (Ej.: MATIC-BTC)
        Poner Orden MARKET y esperar que se ejecute en un loop
    Vender tokenBase a tokenUSD (Ej.: BTC-USDT)
        Poner Orden MARKET y esperar que se ejecute en un loop
    
    Registrar en DDBB el resultado del importe comprado y el importe resultado


*/

    
$idusuario = 1;
$usr = new UsrUsuario($idusuario);

$ak = $usr->getConfig('bncak');
$as = $usr->getConfig('bncas');

$bot = new BotArbitrajeAT($ak,$as);

$continueLoop = true;
while ($continueLoop)
{
    foreach ($tokensBase as $tokenBase)
    {
        $tokens = $bot->readTokens($tokenUSD,$tokenBase);
        if (!empty($tokens))
        {
            

            echo "\n";
            $bestToken = '';
            $bestPerc = 0;
            foreach ($tokens as $token => $rw)
            {
                if ($rw['cambioPerc'] < $bestPerc)
                {
                    $bestToken = $token;
                    $bestPerc = $rw['cambioPerc'];
                }

            }
            
            $token = $bestToken;
            $rw = $tokens[$token];

            $msg = date('H:i:s').';'.$token.';'.toDec($bestPerc).'%'.';';
            print_r($msg."\n");
            file_put_contents(STATUS_FILE_AT, "\n".$msg,FILE_APPEND);
            print_r(str_replace('.',',',$token.$tokenUSD.': '.$tokens[$token][$token.$tokenUSD]['askPrice'])."\n");
            print_r(str_replace('.',',',$token.$tokenBase.': '.$tokens[$token][$token.$tokenBase]['bidPrice'])."\n");
            print_r(str_replace('.',',',$tokenBase.$tokenUSD.': '.$tokens[$token][$tokenBase.$tokenUSD]['bidPrice'])."\n");
            print_r('CambioPerc: '.$tokens[$token]['cambioPerc']."\n");
            print_r('Via: '.$tokens[$token]['via']."\n");

            
    /* ANULAR COMPRA-VENTA para pruebas 


            
            $continueLoop = false;

            //Comprar token con tokenUSD (Ej.: MATIC-USDT)
            $symbol = $token.$tokenUSD;
            $decs = $rw['info'][$symbol]['qtyDecsBase'];
            $qty = toDec($importe/$rw[$symbol]['askPrice'],$decs);

            $decs = $rw['info'][$symbol]['qtyDecsBase'];
            $price = toDec($rw[$symbol]['askPrice'],$decs);
            
            $order = $bot->buy($symbol,$qty,$price);
            if ($order)
            {
                $orderToCheck[] = array('orderId'=>$order['orderId'],'symbol'=>$symbol); 

                $buyedQty = $order['executedQty'];
                $orderId = $order['orderId'];
                $importeInicial = $order['executedQty']*$order['cummulativeQuoteQty'];
                
                //Vender token a tokenBase (Ej.: MATIC-BTC)
                $symbol = $token.$tokenBase;
                $decs = $rw['info'][$symbol]['qtyDecsBase'];
                $qty = toDecDown($buyedQty,$decs);

                $decs = $rw['info'][$symbol]['qtyDecsBase'];
                $price = toDec($rw[$symbol]['bidPrice'],$decs);

                $order = $bot->sell($symbol,$qty,$price);
                if ($order)
                {
                    $orderToCheck[] = array('orderId'=>$order['orderId'],'symbol'=>$symbol);

                    $selledQty = $order['cummulativeQuoteQty'];
                    $orderId = $order['orderId'];
                    
                    //Vender tokenBase a tokenUSD (Ej.: BTC-USDT)
                    $symbol = $tokenBase.$tokenUSD;
                    $decs = $rw['info'][$symbol]['qtyDecsBase'];
                    $qty = toDecDown($selledQty,$decs);

                    $decs = $rw['info'][$symbol]['qtyDecsBase'];
                    $price = toDec($rw[$symbol]['bidPrice'],$decs);

                    $order = $bot->sell($symbol,$qty,$price);
                    if ($order)
                    {
                        $orderToCheck[] = array('orderId'=>$order['orderId'],'symbol'=>$symbol); 

                        //Consultar estado de la orden
                        //$orderStatus = $api->orderStatus($order['symbol'],$order['orderId']);
                        print_r("\n".'OK ');

                        $symbol = $token.$tokenUSD;
                        print_r("\n".$symbol.' BID: '.$rw[$symbol]['bidPrice'].' ASK: '.$rw[$symbol]['askPrice']);
                        $symbol = $token.$tokenBase;
                        print_r("\n".$symbol.' BID: '.$rw[$symbol]['bidPrice'].' ASK: '.$rw[$symbol]['askPrice']);
                        $symbol = $tokenBase.$tokenUSD;
                        print_r("\n".$symbol.' BID: '.$rw[$symbol]['bidPrice'].' ASK: '.$rw[$symbol]['askPrice']);
                        
                        print_r("\nORDENES:");
                        foreach ($orderToCheck as $order)
                        {
                            $orderStatus = $bot->orderStatus($order['symbol'],$order['orderId']);
                            foreach ($orderStatus as $os)
                                print_r("\n".$os['symbol'].' Side:'.($os['isBuyer']?'C':'V').' Price: '.$os['price'].' USD: '.$os['quoteQty']);

                        }
                        
                    }
                    else
                    {
                        print_r("\n".'ERROR en 3');
                        print_r($bot->getErrLog());  
                    }
                }
                else
                {
                    print_r("\n".'ERROR en 2');
                    print_r($bot->getErrLog());
                }
            }
            else
            {
                print_r("\n".'ERROR en 1');
                print_r($bot->getErrLog());
            }
     FIN - ANULAR COMPRA-VENTA para pruebas */
            
        }
        else
        {
            //print_r("\n".'NO HAY TOKENS DISPONIBLES');
            echo '.'.$tokenBase;
        }

        if ($continueLoop)
            sleep(10);
        
    }
}




//Operacion::logBot('END');
$procEndU = microtime(true);

file_put_contents(STATUS_FILE_AT, "\n".'Proceso: '.toDec($procEndU-$procStartU,4).' seg.',FILE_APPEND);
