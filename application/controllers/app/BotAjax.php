<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";

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
}