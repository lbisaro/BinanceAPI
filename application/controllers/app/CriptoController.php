<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once MDL_PATH."Ticker.php";

/**
 * Controller: AppCriptoController
 * @package SGi_Controllers
 */
class CriptoController extends Controller
{
    function home($auth)
    {
        $this->addTitle('Variacion de precios');

        $tck = new Ticker();
        //if (!$auth->checkCsu('sgi.'))
        //{
        //     $this->adderror('No esta autorizado a visualizar esta pagina.');
        //     return null;
        //}

        $ds = $tck->getVariacionDePrecios();
        pr($ds['BTCUSDT']);
        $arr['data'] = 'HOME';
        $arr['hidden'] = '';
   
        $this->addView('ver',$arr);
    }
}
