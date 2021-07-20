<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";

/**
 * Controller: AppCriptoController
 * @package SGi_Controllers
 */
class CriptoController extends Controller
{
    function home($auth)
    {
        $this->variacionPrecio($auth);
    }


    function variacionPrecio($auth)
    {
        $this->addTitle('Variacion de precios');

        //if (!$auth->checkCsu('sgi.'))
        //{
        //     $this->adderror('No esta autorizado a visualizar esta pagina.');
        //     return null;
        //}

        //$arr['data'] = '';
        //$arr['hidden'] = '';
    
        $this->addView('variacionPrecio',$arr);
    }
    
    
}
