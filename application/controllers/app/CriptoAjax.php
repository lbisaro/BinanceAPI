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
}