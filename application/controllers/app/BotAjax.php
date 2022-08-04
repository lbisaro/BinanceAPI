<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Test.php";
include_once MDL_PATH."bot/Operacion.php";

/**
 * BotAjax
 *
 * @package SGi_Controllers
 */
class BotAjax extends ControllerAjax
{
    function revisarEstrategia()
    {
        $this->ajxRsp->setEchoOut(true);
        
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        $symbol = $opr->get('symbol');

        $ordenes = $opr->getOrdenes($enCurso=false);
        if (!empty($ordenes))
        {
            $iniDate = date('Y-m-d H:i:s');
            foreach ($ordenes as $rw)
            {
                if ($rw['updated']<$iniDate)
                    $iniDate = $rw['updated'];
                $updated = substr($rw['updated'],0,14).'00';
                
                if ($updated >= date('Y-m-d H',strToTime($iniDate.' -1000 hour')).':00')
                {
                    $prices[$updated]['datetime'] = $updated;
                    if ($rw['side']==Operacion::SIDE_SELL)
                    {
                        if ($rw['status'] != Operacion::OR_STATUS_NEW)
                            $prices[$updated]['venta'] = $rw['price'];
                        else
                            $prices[$updated]['ventaAbierta'] = $rw['price'];
                    }
                    else
                    {
                        if ($rw['status'] != Operacion::OR_STATUS_NEW)
                            $prices[$updated]['compra'] = $rw['price'];
                        else
                            $prices[$updated]['compraAbierta'] = $rw['price'];
                    }
                }
            }
            
            //Agrega 3 horas antes de la primer operacion
            $startTime = date('Y-m-d H:i:s',strToTime($iniDate.' -3 hour'));
            $endTime = date('Y-m-d H:i:s');
            
            $test = new Test();
            $ds = $test->getKlines($symbol,$interval='1h',$startTime,$endTime);
            foreach ($ds as $rw)
            {   
                $date = $rw['datetime'];
                $prices[$date]['datetime'] = $rw['datetime'];
                $prices[$date]['price'] = ($rw['high']+$rw['low'])/2;
                $prices[$date]['high'] = $rw['high'];
                $prices[$date]['low'] = $rw['low'];
            }

            ksort($prices);

            $lastCompraVenta = 0;
            $compraAbierta = 0;
            $ventaAbierta = 0;
            foreach ($prices as $k => $rw)
            {

                $compra = $venta = 0;
                if ($compra = $rw['compra'])
                {
                    $lastCompraVenta = $compra;
                }
                elseif ($venta = $rw['venta'])
                {
                    $lastCompraVenta = $venta;
                }

                if ($rw['compraAbierta'])
                    $compraAbierta = $rw['compraAbierta'];
                if ($rw['ventaAbierta'])
                    $ventaAbierta = $rw['ventaAbierta'];

                $prices[$k]['compraAbierta'] = $compraAbierta;
                $prices[$k]['ventaAbierta'] = $ventaAbierta;
                $prices[$k]['compraVenta'] = $lastCompraVenta;
                if ($venta)
                    $lastCompraVenta = 0;
            }

            unset($ds);
            $ds[] = array('strFechaHoraActual',
                          'Precio',
                          'Compra',
                          'Venta',
                          'Compra Venta',
                          'Compra Abierta',
                          'Venta Abierta',
                          'Precio Maximo',
                          'Precio Minimo'
                          );
            foreach ($prices as $rw)
            {
                $ds[] = array($rw['datetime'],
                              toDec($rw['price'],8),
                              toDec($rw['compra'],8),
                              toDec($rw['venta'],8),
                              $rw['compraVenta'],
                              toDec($rw['compraAbierta'],8),
                              toDec($rw['ventaAbierta'],8),
                              toDec($rw['high'],8),
                              toDec($rw['low'],8)
                              );
                
            }
        }
        echo json_encode($ds);        
    }

    function symbolData()
    {
        $this->ajxRsp->setEchoOut(true);
        $symbol = strtoupper($_REQUEST['symbol']);
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);
        $data = $api->getSymbolData($symbol);

        $tck = new ticker($symbol);
        if ($tck->get('tickerid') == $symbol)
            $data['show_check_MPAuto'] = true;
        else
            $data['show_check_MPAuto'] = false;

        echo json_encode($data);
    }

    function crearOperacion()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

        $arrToSet['symbol'] = $_REQUEST['symbol'];
        $arrToSet['inicio_usd'] = $_REQUEST['inicio_usd'];
        $arrToSet['capital_usd'] = $_REQUEST['capital_usd'];
        $arrToSet['destino_profit'] = ($_REQUEST['destino_profit']?1:0);
        $arrToSet['multiplicador_porc'] = $_REQUEST['multiplicador_porc'];
        $arrToSet['multiplicador_porc_inc'] = ($_REQUEST['multiplicador_porc_inc']?1:0);
        $arrToSet['multiplicador_porc_auto'] = ($_REQUEST['multiplicador_porc_auto']?1:0);
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];
        $arrToSet['porc_venta_up'] = $_REQUEST['porc_venta_up'];
        $arrToSet['porc_venta_down'] = $_REQUEST['porc_venta_down'];
        $arrToSet['auto_restart'] = $_REQUEST['auto_restart'];
        $arrToSet['tipo'] = $_REQUEST['tipo'];

        $opr = new Operacion();
        $opr->set($arrToSet);
        if ($opr->save())
        {
            //Se crea el Ticker para tener info sobre el Symbol de la operacion
            $tck = new Ticker($opr->get('symbol'));
            if ($tck->get('tickerid') != $opr->get('symbol'))
                $tck->set(array('tickerid'=>$opr->get('symbol')));
            if (!$tck->save())
            {
                $opr->delete();
                $this->ajxRsp->addError('No fue posible crear la operacion. Error en Ticker');
                $this->ajxRsp->addError($opr->getErrLog());
            }

            if (!$arrToSet['auto_restart'])
            {
                $this->ajxRsp->redirect('app.bot.verOperacion+id='.$opr->get('idoperacion'));        
            }
            elseif ($opr->start())
            {
                $this->ajxRsp->redirect('app.bot.verOperacion+id='.$opr->get('idoperacion'));        
            }
            else
            {
                $opr->delete();
                $this->ajxRsp->addError('No fue posible crear la operacion.');
                $this->ajxRsp->addError($opr->getErrLog());
            }
        }
        else
        {
            $this->ajxRsp->addError($opr->getErrLog()); 
        }
    }

    function editarOperacion()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

        $arrToSet['capital_usd'] = $_REQUEST['capital_usd'];
        $arrToSet['inicio_usd'] = $_REQUEST['inicio_usd'];
        $arrToSet['destino_profit'] = ($_REQUEST['destino_profit']?'1':'0');
        $arrToSet['multiplicador_porc'] = $_REQUEST['multiplicador_porc'];
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];
        $arrToSet['multiplicador_porc_inc'] = ($_REQUEST['multiplicador_porc_inc']?1:0);
        $arrToSet['multiplicador_porc_auto'] = ($_REQUEST['multiplicador_porc_auto']?1:0);
        $arrToSet['porc_venta_up'] = $_REQUEST['porc_venta_up'];
        $arrToSet['porc_venta_down'] = $_REQUEST['porc_venta_down'];
       

        $opr = new Operacion($_REQUEST['idoperacion']);
        if ($auth->get('idusuario') != $opr->get('idusuario'))
        {
            $this->ajxRsp->addError('No esta autorizado a editar la operacion.');
            return false;
        }
        $opr->set($arrToSet);
        if ($opr->save())
        {
            $opr->start();
            $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')));
        }
        else
        {
            $this->ajxRsp->addError($opr->getErrLog()); 
        }
    }

    function toogleAutoRestart()
    {
        $opr = new Operacion($_REQUEST['idoperacion']);
        $newAutoRestart = $opr->toogleAutoRestart();
        $this->ajxRsp->script('setAutoRestartTo('.$newAutoRestart.');');        
    }

    function start()
    {
        $opr = new Operacion($_REQUEST['idoperacion']);
        if ($opr->start())
            $this->ajxRsp->redirect('app.bot.verOperacion+id='.$opr->get('idoperacion'));        
        else
            $this->ajxRsp->addError($opr->getErrLog());
    }

    function showLog()
    {
        $prms=array();
        if ($_REQUEST['idusuario'])
            $prms['idusuario']=$_REQUEST['idusuario'];
        if ($_REQUEST['idoperacion'])
            $prms['idoperacion']=$_REQUEST['idoperacion'];
        if ($_REQUEST['symbol'])
            $prms['symbol']=$_REQUEST['symbol'];
        

        $file = $_REQUEST['file'];
        $folder = LOG_PATH.'bot/';
        $content = '';
        $archivo = fopen($folder.$file,'r');
        $lin=0;
        while ($linea = fgets($archivo)) 
        {
            $salto='';
            if (!(substr($linea,-1)==="\n"))
                $salto .= "\n";
            if (strstr(strtolower($linea),'error'))
                $linea = '<span class="text-danger">'.$linea.'</span>';
            
            $show = true;
            if ($prms['idusuario'] && !strpos($linea,' u:'.$prms['idusuario'].' ') )
                $show = false;
            if ($prms['idoperacion'] && !strpos($linea,' o:'.$prms['idoperacion'].' ') )
                $show = false;
            if ($prms['symbol'] && !strpos($linea,' s:'.$prms['symbol'].' ') )
                $show = false;

            if ($show)
                $content = $linea.$salto.$content; 
        }

        $content = str_ireplace('Buy ','<b class="badge badge-success">BUY </b>',$content);
        $content = str_ireplace('Sell ','<b class="badge badge-danger">SELL </b>',$content);
        $content = str_ireplace('Stop ','<b class="badge badge-warning">STOP </b>',$content);
        $content = str_ireplace('Warning ','<b class="badge badge-warning">WARNING </b>',$content);
        $content = str_ireplace('PENDIENTE DE ELIMINAR ','<b class="badge badge-warning">PENDIENTE DE ELIMINAR </b>',$content);
        $content = str_ireplace('START ORDER ','<b class="badge badge-info">START ORDER </b>',$content);
        $content = str_ireplace('STOP_BOT','<b class="badge badge-danger">STOP BOT </b>',$content);
        $content = str_ireplace('LIQUIDAR_OPERACION','<b class="badge badge-danger">LIQUIDAR OPERACION </b>',$content);
        if (!empty($content))
        {
            $this->ajxRsp->assign('contenido','innerHTML','<code class="text-dark">'.nl2br($content).'</code>');
        }
        else
        {
            $this->ajxRsp->assign('contenido','innerHTML','<div class="alert alert-danger">No se ha encontrado contenido en el log.</div>');
        }
        $this->ajxRsp->script("activate('".$file."')");
    }

    function apagarBot()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);

        $params['delOrdenesActivas']  = ($_REQUEST['delOrdenesActivas']?true:false);
        $params['autoRestartOff']     = ($_REQUEST['autoRestartOff']?true:false);
        $params['delOrdenesBinance']  = ($_REQUEST['delOrdenesBinance']?true:false);
        
        $opr->apagarBot($params);
        $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$idoperacion));

            
    }

    function liquidarOp()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);

        $params['autoRestartOff']     = ($_REQUEST['autoRestartOff']?true:false);
        
        if ($opr->liquidarOp($params))
            $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$idoperacion));
        else
            $this->ajxRsp->addError($opr->getErrLog());
            
    }

    function resolverApalancamiento()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        $idusuario = $opr->get('idusuario');
        $symbol = $opr->get('symbol');
        $multiplicador_porc = $opr->get('multiplicador_porc');
        
        $auth = UsrUsuario::getAuthInstance();
        if ($auth->get('idusuario') != $idusuario)
        {
            $this->ajxRsp->addError('No esta autorizado a realizar la operacion');
            return false;
        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as); 
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

        $newUsd = $_REQUEST['qtyUSD'];
        $newQty = toDec($newUsd/$_REQUEST['symbolPrice'],($symbolData['qtyDecs']*1));
        $newPrice = toDec($_REQUEST['symbolPrice'],($symbolData['qtyDecsPrice']*1));

        if ($newUsd>$usdFreeToBuy)
        {
            $this->ajxRsp->addError('No es posible registrar la orden - El importe disponible en USD es de '.$usdFreeToBuy);
            return false;
        }


        try {
            $limitOrder = $api->buy($symbol, $newQty, $newPrice);
            $aOpr['idoperacion']  = $idoperacion;
            $aOpr['side']         = Operacion::SIDE_BUY;
            $aOpr['origQty']      = $newQty;
            $aOpr['price']        = $newPrice;
            $aOpr['orderId']      = $limitOrder['orderId'];
    
            $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice).' -'.$multiplicador_porc.'% - RESOLVER APALANCAMIENTO';
            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
    
            $opr->insertOrden($aOpr);     
    
            $this->ajxRsp->redirect('app.bot.verOperacion+id='.$idoperacion); 

        } catch (Throwable $e) {
            $msg = "Error: " . $e->getMessage();
            $this->ajxRsp->addError('No es posible registrar la orden<br/>REPORTE BINANCE<br/>'.$msg);
        }
    }

    function crearOrdenDeCompra()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        $idusuario = $opr->get('idusuario');
        $symbol = $opr->get('symbol');
        $multiplicador_porc = $opr->get('multiplicador_porc');
        
        $auth = UsrUsuario::getAuthInstance();
        if ($auth->get('idusuario') != $idusuario)
        {
            $this->ajxRsp->addError('No esta autorizado a realizar la operacion');
            return false;
        }        

        if ($_REQUEST['qtyUSD']<10)
        {
            $this->ajxRsp->addError('El Importe USD debe ser mayor a 10');
            return false;
        }

        if ($_REQUEST['symbolPrice']<0)
        {
            $this->ajxRsp->addError('El Precio de compra debe ser mayor a 0');
            return false;
        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as); 
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

        $newUsd = $_REQUEST['qtyUSD'];
        $newQty = toDec($newUsd/$_REQUEST['symbolPrice'],($symbolData['qtyDecs']*1));
        $newPrice = toDec($_REQUEST['symbolPrice'],($symbolData['qtyDecsPrice']*1));

        if ($newUsd>$usdFreeToBuy)
        {
            $this->ajxRsp->addError('No es posible registrar la orden - El importe disponible en USD es de '.$usdFreeToBuy);
            return false;
        }


        try {
            $limitOrder = $api->buy($symbol, $newQty, $newPrice);
            $aOpr['idoperacion']  = $idoperacion;
            $aOpr['side']         = Operacion::SIDE_BUY;
            $aOpr['origQty']      = $newQty;
            $aOpr['price']        = $newPrice;
            $aOpr['orderId']      = $limitOrder['orderId'];
    
            $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice).' -'.$multiplicador_porc.'% - RESOLVER APALANCAMIENTO';
            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
    
            $opr->insertOrden($aOpr);     
    
            $this->ajxRsp->redirect('app.bot.verOperacion+id='.$idoperacion); 

        } catch (Throwable $e) {
            $msg = "Error: " . $e->getMessage();
            $this->ajxRsp->addError('No es posible registrar la orden<br/>REPORTE BINANCE<br/>'.$msg);
        }
    }

    function resolverVenta()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        $idusuario = $opr->get('idusuario');
        $symbol = $opr->get('symbol');
        $multiplicador_porc = $opr->get('multiplicador_porc');
        
        $auth = UsrUsuario::getAuthInstance();
        if ($auth->get('idusuario') != $idusuario)
        {
            $this->ajxRsp->addError('No esta autorizado a realizar la operacion');
            return false;
        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as); 
        //Consulta billetera en Binance para ver si se puede recomprar
        $symbolData = $api->getSymbolData($symbol);
        $account = $api->account();
        $asset = str_replace($symbolData['baseAsset'],'',$symbol);
        $token = str_replace($symbolData['quoteAsset'],'',$symbol);
        $unitsFree = '0.00';
        $unitsLocked = '0.00';
        foreach ($account['balances'] as $balances)
        {
            if ($balances['asset'] == $token)
            {
                $unitsFree = $balances['free'];
                $unitsLocked = $balances['locked'];
            }
            if ($balances['asset'] == $symbolData['quoteAsset'])
            {
                $usdFreeToBuy = $balances['free'];
            }
        }

        $newPrice = toDec($_REQUEST['symbolPrice'],($symbolData['qtyDecsPrice']*1));
        $newQty = toDec($_REQUEST['qtyUnit'],($symbolData['qtyDecs']*1));


        if ($newQty>$unitsFree)
        {
            $this->ajxRsp->addError('No es posible registrar la orden - El importe disponible en unidades de '.$token.' es de '.$unitsFree);
            return false;
        }


        try {
            $limitOrder = $api->sell($symbol, $newQty, $newPrice);
            $aOpr['idoperacion']  = $idoperacion;
            $aOpr['side']         = Operacion::SIDE_SELL;
            $aOpr['origQty']      = $newQty;
            $aOpr['price']        = $newPrice;
            $aOpr['orderId']      = $limitOrder['orderId'];
    
            $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice*$newQty).' - RESOLVER VENTA';
            Operacion::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
        
            $opr->insertOrden($aOpr);     
    
            $this->ajxRsp->redirect('app.bot.verOperacion+id='.$idoperacion); 

        } catch (Throwable $e) {
            $msg = "Error: " . $e->getMessage();
            $this->ajxRsp->addError('No es posible registrar la orden<br/>REPORTE BINANCE<br/>'.$msg);
        }
    }

    function liquidarOrden()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $idoperacionorden = $_REQUEST['idoperacionorden'];
        /** Proceso
            - Bloquear el proceso del Robot (O esperar que sea posible)
            - Binance - Eliminar la ordenes de compra abiertas
            - DDBB - Eliminar Compras y ventas abiertas 
            - Verificar los orderId eliminados en Binance
            - Vender la orden de compra#1 a precio MARKET
            - Verificar que se haya vendido y Pasar a completadas la orden compra#1 y la venta Market, 
              actualizando el pnlDate de ambas a fecha y hora actual
            - Crear nueva compra apalancada replicando la orden de compra#1
            - Desbloquear el proceso del Robot 
        */
        
        // - Bloquear el proceso del Robot (O esperar que sea posible)
        $lockFileText = file_get_contents(LOCK_FILE);
        if (Operacion::lockProcess('BotAjax::liquidarOrden()'))
        {
            $auth = UsrUsuario::getAuthInstance();
            $ak = $auth->getConfig('bncak');
            $as = $auth->getConfig('bncas');
            $api = new BinanceAPI($ak,$as); 

            $opr = new Operacion($idoperacion);
            $ordenesActivas = $opr->getOrdenes();
            if (!empty($ordenesActivas))
            {
                foreach ($ordenesActivas as $k=>$orden)
                {
                    if ($idoperacionorden == $orden['idoperacionorden'])
                        $orderToLiquidar = $orden;
                    elseif ($orden['status']==Operacion::OR_STATUS_NEW)
                        $orderToDelete[] = $orden['orderId'];
                }
            }
            
            // - Binance - Eliminar la ordenes de compra abiertas
            // - DDBB - Eliminar Compras y ventas abiertas 
            foreach ($orderToDelete as $orderId)
            {
                $opr->deleteOrder($orderId);
                $api->cancel($opr->get('symbol'), $orderId);
            }
            
            // - Verificar los orderId eliminados en Binance
            while (!empty($orderToDelete))
            {
                sleep(2);                   
                foreach ($orderToDelete as $k => $orderId)
                {
                    $orderStatus = $api->orderStatus($opr->get('symbol'),$orderId);
                    if ($orderStatus['status'] == 'CANCELED')
                        unset($orderToDelete[$k]);
                }            
            }

            // - Vender la orden de compra#1 a precio MARKET
            // - Verificar que se haya vendido y Pasar a completadas la orden compra#1 y la venta Market, 
            //   actualizando el pnlDate de ambas a fecha y hora actual
            // - Crear nueva compra apalancada replicando la orden de compra#1, si aun hay compras pendientes
            if (!$opr->liquidarOrden($idoperacionorden)) 
            {
                $msg = 'No fue posible liquidar la orden.';
                $errLog = $opr->getErrLog();
                if (!empty($errLog))
                    foreach ($errLog as $err)
                        $msg .= ' - '.$err;
                $msgClass = 'danger';
                $this->ajxRsp->script("statusMessage('".$msg."','".$msgClass."');"); 
                $opr->status();
                $opr->trySolveError();              
            }
            else
            {
                $msg = 'Orden Liquidada con exito';
                $msgClass = 'success';
                $this->ajxRsp->script("statusMessage('".$msg."','".$msgClass."');");
                $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')));
                
            }
        }
        else
        {
            $msg = 'Se debe esperar a que el BOT realice las operaciones de rutina para proceder con la liquidacion de la orden.';
            $msgClass = 'warning';
            $this->ajxRsp->script("statusMessage('".$msg."','".$msgClass."');");
            $this->ajxRsp->script("setTimeout(liquidarOrden,3000);");
        }

        // - Desbloquear el proceso del Robot 
        Operacion::unlockProcess();
                        
    }

    function getMultiplicadorPorcAuto()
    {
        $this->ajxRsp->setEchoOut(true);
        $tickerid = $_REQUEST['symbol'];
        
        $tck = new Ticker($tickerid);
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as); 
        $symbolData = $api->getSymbolData($tickerid);
        $palancas = $tck->calcularPalancas($symbolData['price']);
        $qtyPalancas = count($palancas['porc']);
        $multPorc = $tck->calcularMultiplicadorDePorcentaje($qtyPalancas,end($palancas['porc']));
        echo toDec($multPorc);
    }

    function cargarOrdenesCompletas()
    {
        $opr = new Operacion($_REQUEST['idoperacion']);
        $dg = New HtmlTableDg();

        $tck = new Ticker();
        $symbolData = $tck->getSymbolData($opr->get('symbol'));
        $symbolPrice = $symbolData['price'];


        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader($symbolData['baseAsset'],null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader($symbolData['quoteAsset'],null,null,'right');

        $ordenes = $opr->getOrdenes($enCurso=false,'pnlDate, side, price');
        foreach ($ordenes as $rw)
        {
            if ($rw['completed'])
            {
                $usdDecs = $symbolData['qtyDecsQuote'];
                $usd = toDec($rw['origQty']*$rw['price'],$usdDecs);

                $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank" label="'.$rw['orderId'].'">'.$rw['sideStr'].'</a>';
                
                $row = array($link,
                             $rw['updatedStr'],
                             ($rw['side']!=Operacion::SIDE_BUY?'-':'').(toDec($rw['origQty']*1,$symbolData['qtyDecs'])),
                             (toDec($rw['price']*1,$symbolData['qtyDecsPrice'])),
                             ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd
                            );
 
                $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

                
                if ($rw['side']==Operacion::SIDE_SELL)
                {
                    $totVentas++;
                    $gananciaUsd += $usd;
                }
                else
                {
                    $gananciaUsd -= $usd;
                }
            }

        }
        $this->ajxRsp->assign('ordenesCompletas','innerHTML',$dg->get());
    }

    function lunabusdOrder()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

        $symbol = 'LUNABUSD';
        $type = $_REQUEST['op_type'];
        $side = $_REQUEST['op_side'];
        $qty = $_REQUEST['op_qty'];
        if (isset($_REQUEST['op_price']))
            $price = $_REQUEST['op_price'];
        
        try {

            if ($type=='limit')
            {
                if ($side=='buy')
                    $order = $api->buy($symbol, $qty, $price);
                elseif ($side=='sell')
                    $order = $api->sell($symbol, $qty, $price);
            }
            elseif ($type=='market')
            {
                if ($side=='buy')
                    $order = $api->marketBuy($symbol, $qty);
                elseif ($side=='sell')
                    $order = $api->marketSell($symbol, $qty);
            }
            if ($order['orderId'])
                $this->ajxRsp->redirect('app.bot.lunabusd+');
        
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->ajxRsp->addError('Informe de error de Binance'); 
            $this->ajxRsp->addError($msg); 
        }            
            
    }

    function toogleStop()
    {
        $opr = new Operacion($_REQUEST['idoperacion']);
        if ($opr->get('stop'))
        {
            $newAutoRestart = $opr->toogleStop();
            $this->ajxRsp->redirect('app.bot.verOperacion+id='.$opr->get('idoperacion'));   
        }
        else
        {
            $this->ajxRsp->redirect('app.bot.apagarBot+id='.$opr->get('idoperacion'));
        }

    }

}