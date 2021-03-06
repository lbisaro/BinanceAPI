<?php

$operaciones = $opr->getOperacionesPorTipo($idusuario,Operacion::OP_TIPO_APLSHRT);
foreach ($operaciones as $operacion) 
{
    $ordenExpiradaenBinance = false;
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

    if ($opr->get('stop'))
        continue;

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
                    $data[$strSide][$order['orderId']]['price'] = toDec(($orderStatus['cummulativeQuoteQty']/$orderStatus['executedQty']),10);
                    $data[$strSide][$order['orderId']]['status'] = Operacion::OR_STATUS_FILLED;
                }
                elseif (!empty($orderStatus) && ($orderStatus['status']=='CANCELED'))
                {

                    $data['canceled'][$order['orderId']] = $strSide;
                }
                elseif (!empty($orderStatus) && ($orderStatus['status']=='EXPIRED'))
                {
                    $ordenExpiradaenBinance = true;
                }
                else
                {
                    $data['unknown'][$order['orderId']] = $strSide;  
                }
            }
        }
    }

    if ($ordenExpiradaenBinance)
        continue;

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
        $symbolData = $api->getSymbolData($symbol);

        if ($data['actualizar'] == 'venta') //La operacion recompro por apalancamiento o es la primera compra
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
                        $msg = ' ORDEN DE VENTA PENDIENTE DE ELIMINAR EN BINANCE (orderId = '.$orderId.')';
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

            //Crear las de compra y reventa por apalancamiento

            //Consulta billetera en Binance para ver si se puede revender
            $account = $api->account();
            $asset = str_replace($symbolData['baseAsset'],'',$symbol);
            $unitsFree = '0.00';
            $unitsLocked = '0.00';
            foreach ($account['balances'] as $balances)
            {
                if ($balances['asset'] == $symbolData['baseAsset'])
                {
                    $unitsFree = $balances['free'];
                    $unitsLocked = $balances['locked'];
                }
                if ($balances['asset'] == $asset)
                {
                    $quoteFree = $balances['free'];
                }
            }

            //Obteniendo datos de ordenes anteriores
            $dbOrders = $opr->getOrdenes($enCurso=true,$order='idoperacionorden');

            $lastSellPrice=0;
            $totQuoteSelled=0;
            $totUnitsSelled=0;
            $lastBaseSelled = 0;
            $maxVentaNum = 1; 
            foreach ($dbOrders as $order)
            {
                if ($order['side']==Operacion::SIDE_SELL)
                {
                    $lastSellPrice = $order['price'];
                    
                    $lastBaseSelled = $order['origQty'];
                    $totUnitsSelled += $order['origQty'];
                    $totQuoteSelled += ($order['origQty']*$order['price']);

                    if ($order['ventaNum']>$maxVentaNum)
                        $maxVentaNum = $order['ventaNum'];
                }
            }

            $strControlUnitsSelled = ' - totUnitsSelled: '.($totUnitsSelled*1).' - unitsFree: '.($unitsFree*1);
            $strControlBaseFree = ' - unitsFree: '.$unitsFree;
            //Si la cantidad de unidades vendidas segun DB es mayor a la cantidad de unidades en API
            //Toma la cantidad de unidades en la API
            //if (($totUnitsSelled*1) > ($unitsFree*1))
            //{
            //    $msg = ' WARNING '.$strControlUnitsSelled;
            //    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
            //    $totUnitsSelled = $unitsFree;
            //}       

            //Orden para compra
            if ($maxVentaNum==1) 
                $porcentaje = $opr->get('real_porc_venta_up');
            else
                $porcentaje = $opr->get('real_porc_venta_down');

            if ($opr->get('destino_profit')==Operacion::OP_DESTINO_PROFIT_QUOTE)
            {
                //Compra obteniendo beneficios en Quote
                $newQty = toDecDown($totUnitsSelled,$symbolData['qtyDecs']);
                $newQuote = $totQuoteSelled * (1-($porcentaje/100));
                $newPrice = toDec(($newQuote / $totUnitsSelled),$symbolData['qtyDecsPrice']);
                
            }
            else
            {
                //Compra obteniendo beneficios en Base
                $newQty = toDec($totUnitsSelled * (1+($porcentaje/100)),$symbolData['qtyDecs']); 
                $newPrice = toDec($totQuoteSelled/$newQty,$symbolData['qtyDecsPrice']);
            }

            $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' '.$symbolData['baseAsset'].':'.toDec($newPrice*$newQty,$symbolData['qtyDecsPrice']).' +'.$porcentaje.'%';
            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);

            $errorEnOrden = false;
            
            try {
                $limitOrder = $api->buy($symbol, $newQty, $newPrice);
                $aOpr['idoperacion']  = $idoperacion;
                $aOpr['side']         = Operacion::SIDE_BUY;
                $aOpr['origQty']      = $newQty;
                $aOpr['price']        = $newPrice;
                $aOpr['orderId']      = $limitOrder['orderId'];
                $opr->insertOrden($aOpr); 

            } catch (Throwable $e) {
                $msg = "Error: " . $e->getMessage().$strControlUnitsSelled;
                Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                $errorEnOrden = true;
            }
            


            if (!$errorEnOrden)
            {
                //Orden para reventa por apalancamiento
                $multiplicador_porc = $opr->get('multiplicador_porc');
                if ($opr->get('multiplicador_porc_inc'))
                    $multiplicador_porc = $multiplicador_porc*$maxVentaNum; 
                $newBase = $lastBaseSelled*$opr->get('multiplicador_compra');
                $newPrice = toDec($lastSellPrice + ( ($lastSellPrice * $multiplicador_porc) / 100 ),$symbolData['qtyDecsPrice']);
                $newQty = toDec($newBase,($symbolData['qtyDecs']*1));
    
                // Condiciones para crear orden de venta
                //  Hay billetera para comprar
                //  El total vendido no supera capital_usd de la operacion
                
                
                if ($opr->get('capital_usd')>0 && ($totUnitsSelled+$newBase) > $opr->get('capital_usd'))
                {
                    $msg = ' Stop -> LIMITE DE CAPITAL '.$opr->get('capital_usd').' '.$symbolData['quoteAsset'].' -> Qty:'.$newQty.' Price:'.$newPrice.' Total '.$symbolData['baseAsset'].':'.($totUnitsSelled+$newBase);
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.'  '.$msg);
                    //Se omite la compra por superar el limite de capital de la operacion (si esta seteado)
                    //No se agrega al Log para no generar cantidad de registros sin sentido
                }
                elseif ($newQty > $unitsFree)
                {
                    $msg = ' Stop -> APALANCAMIENTO INSUFICIENTE '.$strControlBaseFree.' '.$symbolData['baseAsset'].' -> Qty:'.$newQty.' Price:'.$newPrice;
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);
                }
                else
                {
                    $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice.' '.$symbolData['baseAsset'].':'.toDec($newBase,$symbolData['qtyDecsPrice']).' -'.$multiplicador_porc.'%';
                    Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg);

                    try {
                        $limitOrder = $api->sell($symbol, $newQty, $newPrice);
                        $aOpr['idoperacion']  = $idoperacion;
                        $aOpr['side']         = Operacion::SIDE_SELL;
                        $aOpr['origQty']      = $newQty;
                        $aOpr['price']        = $newPrice;
                        $aOpr['orderId']      = $limitOrder['orderId'];
                        $opr->insertOrden($aOpr);               
                    } catch (Throwable $e) {
                        $msg = "Error: " . $e->getMessage().$strControlBaseFreeToSell;
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
        else //La operacion se compro y debe finalizar
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