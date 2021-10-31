
<?php
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE, $procStart);


define('PORCENTAJE_VENTA_UP',2);
define('PORCENTAJE_VENTA_DOWN',1.75);


//Operacion::logBot('START');

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
        Operacion::logBot('u:'.$idusuario.' '.$msg);
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


        if ($opr->status() == Operacion::OP_STATUS_ERROR)
            continue;

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
                    $orderStatus = $api->orderStatus($order['symbol'],$order['orderId']);
                    
                    //Si la orden se completo
                    if (!empty($orderStatus) && $orderStatus['status']=='FILLED')
                    {

                        $data[$strSide][$order['orderId']]['origQty'] = $orderStatus['executedQty'];
                        $data[$strSide][$order['orderId']]['price'] = toDec(($orderStatus['cummulativeQuoteQty']/$orderStatus['executedQty']),7);
                        $data[$strSide][$order['orderId']]['status'] = Operacion::OR_STATUS_FILLED;
                    }
                    else
                    {
                        $data['unknown'][$order['orderId']] = $strSide;  
                    }
                }
            }
        }

        //Control sobre ordenes eliminadas en Binance
        $ordenEliminadaEnBinance = false;
        if (!empty($data['unknown']))
        {
            foreach ($data['unknown'] as $orderId => $strSide)
            {
                $ordenEliminadaEnBinance = true;
                $msg = ' ORDEN DE '.strtoupper($strSide).' ELIMINADA EN BINANCE (orderId = '.$orderId.')';
                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                $opr->deleteOrder($orderId);
            }
        }
        if ($ordenEliminadaEnBinance)
            continue;


        
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
                        sleep(1);
                    }
                }
                foreach ($data['compra'] as $orderId => $rw)
                {
                    if ($rw['price']>0)
                    {
                        $opr->updateOrder($orderId,$rw['price'],$rw['origQty']);
                    }
                }

                //Crear las de venta y recompra por apalancamiento

                //Consulta billetera en Binance para ver si se puede recomprar
                $symbolData = $api->getSymbolData($symbol);
                $account = $api->account();
                $asset = str_replace($symbolData['quoteAsset'],'',$symbol);
                $unitsFree = '0.00';
                $unitsLocked = '0.00';
                foreach ($account['balances'] as $balances)
                {
                    if ($balances['asset'] == $asset)
                    {
                        $unitsFree = $balances['free'];
                        $unitsLocked = $balances['locked'];
                    }
                    if ($balances['asset'] == $symbolData['quoteAsset'])
                    {
                        $usdFreeToBuy = $balances['free'];
                    }
                }

                //Obteniendo datos de ordenes anteriores
                $dbOrders = $opr->getOrdenes();

                $lastBuyPrice=0;
                $totUsdBuyed=0;
                $totUnitsBuyed=0;
                $lastUsdBuyed = 0;
                $maxCompraNum = 1; 
                foreach ($dbOrders as $order)
                {
                    if ($order['side']==Operacion::SIDE_BUY)
                    {
                        $lastBuyPrice = $order['price'];
                        
                        $lastUsdBuyed = ($order['origQty']*$order['price']);
                        
                        $totUnitsBuyed += $order['origQty'];
                        $totUsdBuyed += ($order['origQty']*$order['price']);

                        if ($order['compraNum']>$maxCompraNum)
                            $maxCompraNum = $order['compraNum'];
                    }
                }
                //Si la cantidad de unidades compradas segun DB es mayor a la cantidad de unidades en API
                //Toma la cantidad de unidades en la API
                if (($totUnitsBuyed*1) > ($unitsFree*1))
                    $totUnitsBuyed = $unitsFree;
                
                //Orden para venta
                if ($maxCompraNum==1) 
                    $porcentaje = PORCENTAJE_VENTA_UP;
                else
                    $porcentaje = PORCENTAJE_VENTA_DOWN;

                $newUsd = $totUsdBuyed * (1+($porcentaje/100));
                $newPrice = toDec(($newUsd / $totUnitsBuyed),$symbolData['qtyDecsPrice']);
                $newQty = toDecDown($totUnitsBuyed,$symbolData['qtyDecs']);

                $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice).' +'.$porcentaje.'%';
                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);

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
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                    $errorEnOrden = true;
                }

                if (!$errorEnOrden)
                {
                    //Orden para recompra por apalancamiento
                    $multiplicador_porc = $opr->get('multiplicador_porc');
                    if ($opr->get('multiplicador_porc_inc'))
                        $multiplicador_porc = $multiplicador_porc*$maxCompraNum; 
                    
                    $newUsd = $lastUsdBuyed*$opr->get('multiplicador_compra');
                    $newPrice = toDec($lastBuyPrice - ( ($lastBuyPrice * $multiplicador_porc) / 100 ),$symbolData['qtyDecsPrice']);
                    $newQty = toDec(($newUsd/$newPrice),($symbolData['qtyDecs']*1));
        
                    if ($newUsd < $usdFreeToBuy) //Hay billetera para comprar
                    {
                        $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice).' -'.$multiplicador_porc.'%';
                        Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);

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
                            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                            $errorEnOrden = true;
                        }
                    }
                    else
                    {
                        $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' APALANCAMIENTO INSUFICIENTE';
                        Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                    }
                }

                //if ($errorEnOrden)
                //{
                //    $msg = "AutoRestart: OFF";
                //    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                //    $opr->autoRestartOff();
                //}
            }
            else //La operacion se vendio y debe finalizar
            {
                if (!empty($data['compra']) && $data['eliminar'] == 'compra')
                {
                    foreach ($data['compra'] as $orderId => $rw)
                    {
                        $opr->deleteOrder($orderId);
                        $api->cancel($data['symbol'], $orderId);
                        sleep(1);
                    }
                }
                foreach ($data['venta'] as $orderId => $rw)
                {
                    if ($rw['price']>0)
                    {
                        $opr->updateOrder($orderId,$rw['price'],$rw['origQty']);
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
                $opr->restart();
            }
        }
    }
}

//Operacion::logBot('END');
$procEndU = microtime(true);

file_put_contents(STATUS_FILE, "\n".'Proceso: '.toDec($procEndU-$procStartU,4).' seg.',FILE_APPEND);
