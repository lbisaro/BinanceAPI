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
    function historico()
    {
        $prms=array();
        $tickerid = $_REQUEST['tickerid'];
        $prms['interval'] = $_REQUEST['interval'];
        $prms['limit'] = $_REQUEST['limit'];
        if ($_REQUEST['ema'])
            $prms['ema'] = $_REQUEST['ema'];
        $this->ajxRsp->setEchoOut(true);
        $tck = new Ticker();

        $ds = $tck->getHistorico($tickerid,$prms);
        echo json_encode($ds);

    }

    function depth()
    {
        $symbol = strtoupper($_REQUEST['asset'].$_REQUEST['assetQuote']);

        $tck = new Ticker();
        $data = $tck->depth($symbol);

        $this->ajxRsp->assign('resultado','innerHTML',arrayToTable($data));

    }
}