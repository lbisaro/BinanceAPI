<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."Ticker.php";

/**
 * CriptoAjax
 *
 * @package SGi_Controllers
 */
class CriptoAjax extends ControllerAjax
{
    function variacionPrecio()
    {
        $this->ajxRsp->setEchoOut(true);
        $tck = new Ticker();

        $ds = $tck->getVariacionDePrecios();
        echo json_encode($ds);

    }
    function historico()
    {
        $tickerid = $_REQUEST['tickerid'];
        $prms=array();
        if ($_REQUEST['ema'])
            $prms['ema'] = $_REQUEST['ema'];
        $this->ajxRsp->setEchoOut(true);
        $tck = new Ticker();

        $ds = $tck->getHistorico($tickerid,$prms);
        echo json_encode($ds);

    }
}