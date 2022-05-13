<?php
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";

if (!Operacion::lockProcess('Crontab::apalancamiento()'))
{
    $lockFileText = Operacion::readLockFile();
    $msg = 'Error - Bot Apalancamiento Bloqueado - '.$lockFileText;
    Operacion::logBot($msg);

    include_once (MDL_PATH."NotificacionApp.php");
    $usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
    $registration_ids[] = $usr->getFCM_token();

    $title = 'ALERTA!';
    $body = 'Bot Apalancamiento Bloqueado'."\n".$lockFileText;
    $result = NotificacionApp::send($title,$body,$registration_ids);

    return null;
}

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE, $procStart);

//Operacion::logBot('START');

$usr = new UsrUsuario();
$opr = new Operacion();
//Lista usuarios con ordenes existentes
$usuarios = $opr->getUsuariosActivos();
    
foreach ($usuarios as $idusuario => $usuarioData)
{
    try {

        if (isset($opr))
            unset($opr);
        $opr = new Operacion();

        $usr->reset();
        $usr->load($idusuario);
        $ak = $usr->getConfig('bncak');
        $as = $usr->getConfig('bncas');

        if (isset($api))
            unset($api);
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

            if (substr($symbol,0,4) == 'LUNA')
                continue;

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
                        elseif (!empty($orderStatus) && $orderStatus['status']=='CANCELED')
                        {

                            $data['canceled'][$order['orderId']] = $strSide;
                        }
                        else
                        {
                            $data['unknown'][$order['orderId']] = $strSide;  
                        }
                    }
                }
            }

            $ordenDesconocidaEnBinance = false;
            if (!empty($data['unknown']))
            {
                foreach ($data['unknown'] as $orderId => $strSide)
                {
                    $ordenDesconocidaEnBinance = true;
                    $msg = 'Error - ORDEN DE '.strtoupper($strSide).' DESCONOCIDA EN BINANCE (orderId = '.$orderId.')';
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                }
            }
            if ($ordenDesconocidaEnBinance)
                continue;

            $ordenEliminadaEnBinance = false;
            //Control sobre ordenes eliminadas en Binance
            if (!empty($data['canceled']))
            {
                foreach ($data['canceled'] as $orderId => $strSide)
                {
                    $ordenEliminadaEnBinance = true;
                    $msg = ' ORDEN DE '.strtoupper($strSide).' CANCELADA EN BINANCE (orderId = '.$orderId.')';
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                    $opr->deleteOrder($orderId);
                    $opr->status();
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
                            $msg = ' Cancelar ORDEN DE VENTA en Binance (orderId = '.$orderId.')';
                            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);

                            //Control sobre la API de Binance para confirmar que la orden fue eliminada
                            sleep(1);
                            $orderStatus = $api->orderStatus($order['symbol'],$orderId);
                            while ($orderStatus['status']!='CANCELED')
                            {
                                $msg = ' ORDEN DE VENTA PENDIENTE DE ELIMINAR EN BINANCE (orderId = '.$orderId.')';
                                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                                sleep(1);
                                $orderStatus = $api->orderStatus($order['symbol'],$orderId);
                            }
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

                    $strControlUnitsBuyed = ' - totUnitsBuyed: '.($totUnitsBuyed*1).' - unitsFree: '.($unitsFree*1);
                    $strControlUsdFreeToBuy = ' - usdFreeToBuy: '.$usdFreeToBuy;
                    //Si la cantidad de unidades compradas segun DB es mayor a la cantidad de unidades en API
                    //Toma la cantidad de unidades en la API
                    if (($totUnitsBuyed*1) > ($unitsFree*1))
                    {
                        $msg = ' WARNING '.$strControlUnitsBuyed;
                        Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                        $totUnitsBuyed = $unitsFree;
                    }       

                    //Orden para venta
                    if ($maxCompraNum==1) 
                        $porcentaje = $opr->get('real_porc_venta_up');
                    else
                        $porcentaje = $opr->get('real_porc_venta_down');

                    $newUsd = $totUsdBuyed * (1+($porcentaje/100));
                    $newPrice = toDec(($newUsd / $totUnitsBuyed),$symbolData['qtyDecsPrice']);
                    $newQty = toDecDown($totUnitsBuyed,$symbolData['qtyDecs']);

                    $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice*$newQty).' +'.$porcentaje.'%';
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
                        $msg = "Error: " . $e->getMessage().$strControlUnitsBuyed;
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
            
                        /* Condiciones para crear orden de compra
                            El total comprado no supera capital_usd de la operacion
                            Hay billetera para comprar
                        */
                        if ($opr->get('capital_usd')>0 && ($totUsdBuyed+$newUsd) > $opr->get('capital_usd'))
                        {
                            $msg = ' Stop -> LIMITE DE CAPITAL '.$opr->get('capital_usd').' USD -> Qty:'.$newQty.' Price:'.$newPrice.' Total USD:'.($totUsdBuyed+$newUsd);
                            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.'  '.$msg);
                            //Se omite la compra por superar el limite de capital de la operacion (si esta seteado)
                            //No se agrega al Log para no generar cantidad de registros sin sentido
                        }
                        elseif ($newUsd > $usdFreeToBuy)
                        {
                            $msg = ' Stop -> APALANCAMIENTO INSUFICIENTE '.$strControlUsdFreeToBuy.' USD -> Qty:'.$newQty.' Price:'.$newPrice;
                            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                        }
                        else
                        {
                            $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newUsd).' -'.$multiplicador_porc.'%';
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
                                $msg = "Error: " . $e->getMessage().$strControlUsdFreeToBuy;
                                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                                $errorEnOrden = true;
                            }
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
                            $msg = ' Cancelar ORDEN DE COMPRA en Binance (orderId = '.$orderId.')';
                            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                            
                            //Control sobre la API de Binance para confirmar que la orden fue eliminada
                            sleep(1);
                            $orderStatus = $api->orderStatus($order['symbol'],$orderId);
                            while ($orderStatus['status']!='CANCELED')
                            {
                                $msg = ' ORDEN DE COMPRA PENDIENTE DE ELIMINAR EN BINANCE (orderId = '.$orderId.')';
                                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                                sleep(1);
                                $orderStatus = $api->orderStatus($order['symbol'],$orderId);
                            }
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
                elseif (!$opr->autoRestart() && $opr->status() == Operacion::OP_STATUS_COMPLETED)
                {
                    $opr->complete();
                }
            }
        }
    } catch (Throwable $e) {
        $msg = "Apalancamiento - Error: " . $e->getMessage();
        Operacion::logBot('u:'.$idusuario.' '.$msg);
        continue;
    }
}

$procEndU = microtime(true);

file_put_contents(STATUS_FILE, "\n".'Proceso: '.toDec($procEndU-$procStartU,4).' seg.',FILE_APPEND);

Operacion::unlockProcess();

Operacion::cleanLog();


