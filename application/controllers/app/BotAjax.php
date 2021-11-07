<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."Ticker.php";
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
                
                $updated = substr($rw['updated'],0,14).':00';
                $prices[$updated]['price'] = $rw['price'];
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
            
            //Agrega 1 hora antes de la primer operacion
            $iniDate = date('Y-m-d H:i:s',strToTime($iniDate.' - 1 hours'));

            $tck = new Ticker();
            $prms=array('startTime'=>$iniDate,
                        'interval'=>'1h');
            $ds = $tck->getHistorico($symbol,$prms);

            foreach ($ds['prices'][$symbol] as $rw)
            {   

                $date = date('Y-m-d H:i',strtotime($rw['date']));
                $prices[$date]['datetime'] = $date;
                $prices[$date]['price'] = $rw['price'];
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
                          'Venta Abierta'
                          );
            foreach ($prices as $rw)
            {
                $ds[] = array($rw['datetime'],
                              toDec($rw['price'],8),
                              $rw['compra'],
                              $rw['venta'],
                              $rw['compraVenta'],
                              toDec($rw['compraAbierta'],8),
                              toDec($rw['ventaAbierta'],8)
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
        $arrToSet['multiplicador_porc'] = $_REQUEST['multiplicador_porc'];
        $arrToSet['multiplicador_porc_inc'] = ($_REQUEST['multiplicador_porc_inc']?1:0);
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];
        $arrToSet['auto_restart'] = 1; //Por default, la operacion se reinicia despues de cada venta


        $opr = new Operacion();
        $opr->set($arrToSet);
        if ($opr->save())
        {
            if ($opr->start())
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

        $arrToSet['inicio_usd'] = $_REQUEST['inicio_usd'];
        $arrToSet['multiplicador_porc'] = $_REQUEST['multiplicador_porc'];
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];
        $arrToSet['multiplicador_porc_inc'] = ($_REQUEST['multiplicador_porc_inc']?1:0);
        

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
        $content = str_ireplace('PENDIENTE DE ELIMINAR ','<b class="badge badge-warning">PENDIENTE DE ELIMINAR </b>',$content);
        $content = str_ireplace('START ORDER ','<b class="badge badge-info">START ORDER </b>',$content);
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

    function detenerOperacion()
    {
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        if ($opr->detener())
            $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$idoperacion));
        else
            $this->ajxRsp->addError($opr->getErrLog());
    }
}