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


        $opr = new Operacion();
        $opr->set($arrToSet);
        if ($opr->save())
        {
            if ($opr->start())
            {
                $this->ajxRsp->redirect(Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')));
            }
        }
        else
        {
            $this->ajxRsp->addError($opr->getErrLog()); 
        }
    }

    function checkMatch()
    {
        $this->ajxRsp->setEchoOut(true);
        $idoperacion = $_REQUEST['idoperacion'];
        $opr = new Operacion($idoperacion);
        $data = $opr->matchOrdenesEnBinance();
        echo json_encode($data);
    }
}