
<?php
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";


//LOG del Crontab BOT
if (!is_dir(LOG_PATH.'bot'))
    mkdir(LOG_PATH.'bot');
function logBot($msg)
{
    $logFile = LOG_PATH.'bot/bot_'.date('Ymd').'.log';
    $msg = "\n".date('H:i:s').' '.$msg;
    file_put_contents($logFile, $msg,FILE_APPEND);  
    echo $msg; 
}

//logBot('START');

$opr = new Operacion();
$usr = new UsrUsuario();
//Lista usuarios con ordenes existentes
$usuarios = $opr->getUsuariosActivos();
    
foreach ($usuarios as $idusuario)
{
    if (isset($api))
        unset($api);
    $usr->reset();
    $usr->load($idusuario);
    $ak = $usr->getConfig('bncak');
    $as = $usr->getConfig('bncas');
    $api = new BinanceAPI($ak,$as);      

    //CONTROLAR SI EL USUARIO TIENE LAS CLAVES CORRECTAS
    try {
      //        Lista ordenes abiertas en Binance
      $openOrders = $api->openOrders();
    } catch (Throwable $e) {
        $msg = "Error: " . $e->getMessage();
        logBot('Usuario: '.$idusuario.' '.$usr->get('ayn').' '.$msg);
        continue;
    }
    $binanceOpenOrders = array();
    foreach ($openOrders as $order)
    {
        $binanceOpenOrders[$order['orderId']] = $order['status'];
    }
    
    //        Lista las operaciones del Usuario
    $operaciones = $opr->getDataset('idusuario = '.$idusuario);
    foreach ($operaciones as $operacion) 
    {
        $data=array();

        $idoperacion = $operacion['idoperacion'];
        $data['symbol']=$operacion['symbol'];
        $symbol = $data['symbol'];
        $data['update']='';
        $data['actualizar']='';
        $data['eliminar']='';
        $data['compra']=array();
        $data['venta']=array();

        $opr->reset();
        $opr->load($idoperacion);
        $dbOrders = $opr->getOrdenes();
        //Match Binance y Db
        $oCompra = null;
        $oVenta = null;
        foreach ($dbOrders as $idoperacionorden => $order)
        {
            //Filtra las ordenes que se encuentran abiertas
            if ($order['status']==Operacion::OR_STATUS_NEW) 
            {
                $strSide = ($order['side']==Operacion::SIDE_BUY ? 'compra':'venta');

                $data[$strSide][$order['orderId']]['orderId']=$order['orderId'];
                
                //Si no existe la orden abierta en Binance y si en la DB, hay que tomar accion
                if (!isset($binanceOpenOrders[$order['orderId']]))
                {
                    $data['update'] = true;
                    $data['actualizar'] = $strSide;
                    $data['eliminar'] = ($strSide=='compra'?'venta':'compra');

                    //Busca en Binance si la orden se completo 
                    $trade = $api->orderTradeInfo($order['symbol'],$order['orderId']);
                    //Si la orden se complero
                    if (!empty($trade))
                    {
                        foreach ($trade as $rw)
                            $price += $rw['price'];
                        $price = toDec($price/count($trade),7);
                        $data[$strSide][$order['orderId']]['price'] = $price;
                        $data[$strSide][$order['orderId']]['status'] = Operacion::OR_STATUS_FILLED;
                    }
                    else
                    {
                        $data[$strSide][$order['orderId']]['status'] = 'UNKNOWN';  
                    }
                }
            }
        }

        if ($data['update'])
        {
            if ($data['actualizar'] == 'compra') //La operacion recompro por apalancamiento o es la primera compra
            {
                if (!empty($data['venta']) && $data['eliminar'] == 'venta')
                {
                    foreach ($data['venta'] as $orderId => $rw)
                    {
                        $opr->deleteOrder($orderId);
                        $api->cancel($data['symbol'], $orderId);
                    }
                }
                foreach ($data['compra'] as $orderId => $rw)
                {
                    if ($rw['price']>0)
                    {
                        $opr->completeOrder($orderId,$rw['price']);
                    }
                }

                //Crear las de venta y recompra por apalancamiento
                $symbolData = $api->getSymbolData($symbol);

                //Consulta billetera en Binance para ver si se puede recomprar
                // MONEDA BASE (Ej.: USDT ) $symbolData['quoteAsset']
                // $account = $api->account();
                // $account['balances'][$symbolData['quoteAsset']] //Obtener ['free'] y ['locked']
                
                //Obteniendo datos de ordenes anteriores
                $dbOrders = $opr->getOrdenes();

                $lastBuyPrice=0;
                $totUsdBuyed=0;
                $totUnitsBuyed=0;
                $lastUsdBuyed = 0;
                foreach ($dbOrders as $order)
                {
                    if ($order['side']==Operacion::SIDE_BUY)
                    {
                        $lastBuyPrice = $order['price'];
                        
                        $lastUsdBuyed = ($order['origQty']*$order['price']);
                        
                        $totUnitsBuyed += $order['origQty'];
                        $totUsdBuyed += ($order['origQty']*$order['price']);
                    }
                }
                
                //Orden para venta
                $newUsd = $totUsdBuyed * 1.02;
                $newPrice = toDec(($newUsd / $totUnitsBuyed),$symbolData['qtyDecsPrice']);
                $newQty = toDec($totUnitsBuyed,$symbolData['qtyDecs']);

                $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice;
                logBot('Operacion: '.$idoperacion.' '.$symbol.$msg);

                $errorEnOrden = false;
                try {
                    $limitOrder = $api->sell($symbol, $newQty, $newPrice);
                    $aOpr['idoperacion']  = $idoperacion;
                    $aOpr['side']         = Operacion::SIDE_SELL;
                    $aOpr['origQty']      = $newQty;
                    $aOpr['price']        = $newPrice;
                    $aOpr['orderId']      = $limitOrder['orderId'];
                    $opr->insertOrden($aOpr); 
                } catch (Throwable $e) {
                    $msg = "Error: " . $e->getMessage();
                    logBot('Operacion: '.$idoperacion.' '.$symbol.' '.$msg);
                    $errorEnOrden = true;
                }

                if (!$errorEnOrden)
                {
                    //Orden para recompra por apalancamiento
                    $newUsd = $lastUsdBuyed*$opr->get('multiplicador_compra');
                    $newPrice = toDec($lastBuyPrice - ( ($lastBuyPrice * $opr->get('multiplicador_porc')) / 100 ),$symbolData['qtyDecsPrice']);
                    $newQty = toDec(($newUsd/$newPrice),($symbolData['qtyDecs']*1));
        
                    $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice;
                    logBot('Operacion: '.$idoperacion.' '.$symbol.$msg);

                    try {
                        $limitOrder = $api->buy($symbol, $newQty, $newPrice);
                        $aOpr['idoperacion']  = $idoperacion;
                        $aOpr['side']         = Operacion::SIDE_BUY;
                        $aOpr['origQty']      = $newQty;
                        $aOpr['price']        = $newPrice;
                        $aOpr['orderId']      = $limitOrder['orderId'];
                        $opr->insertOrden($aOpr);               
                    } catch (Throwable $e) {
                        $msg = "Error: " . $e->getMessage();
                        logBot('Operacion: '.$idoperacion.' '.$symbol.' '.$msg);
                    }
                }
            }
            else //La operacion se vendio y debe finalizar
            {
                if (!empty($data['compra']) && $data['eliminar'] == 'compra')
                {
                    foreach ($data['compra'] as $orderId => $rw)
                    {
                        $opr->deleteOrder($orderId);
                        $api->cancel($data['symbol'], $orderId);
                    }
                }
                foreach ($data['venta'] as $orderId => $rw)
                {
                    if ($rw['price']>0)
                    {
                        $opr->completeOrder($orderId,$rw['price']);
                    }
                }
                if ($opr->autoRestart())
                    $opr->restart();
            }
        }
        else
        {
            if ($opr->autoRestart() && $opr->canStart())
            {
                logBot('idoperacion: '.$opr->get('idoperacion').'restart()');
                $opr->restart();
            }
        }
    }
}
//logBot('END');
