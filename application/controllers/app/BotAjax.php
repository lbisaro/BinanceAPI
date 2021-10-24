<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."binance/BinanceAPI.php";
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
        $tickerid = $_REQUEST['tickerid'];
        $csvFile = "c:\\dropbox\\cripto\\python\\scalper_".$tickerid.".csv";
        $this->ajxRsp->setEchoOut(true);
        
        $fila = 1;
        $ds=array();
        if (($gestor = fopen($csvFile, "r")) !== FALSE) {
            while (($datos = fgetcsv($gestor, 1000, ";")) !== FALSE) {
                array_shift($datos);
                $ds[] = $datos;
                $fila++;
            }
            fclose($gestor);
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
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];
        $arrToSet['auto_restart'] = 1; //Por default, la operacion se reinicia despues de cada venta


        $opr = new Operacion();
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

    function editarOperacion()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

        $arrToSet['inicio_usd'] = $_REQUEST['inicio_usd'];
        $arrToSet['multiplicador_porc'] = $_REQUEST['multiplicador_porc'];
        $arrToSet['multiplicador_compra'] = $_REQUEST['multiplicador_compra'];


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
        $opr->start();
        $this->ajxRsp->redirect('app.bot.verOperacion+id='.$opr->get('idoperacion'));        
    }
}