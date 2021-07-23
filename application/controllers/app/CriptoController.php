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
        $this->addView('variacionPrecio',$arr);
    }
    
    function graficos($auth)    
    {
        $this->addTitle('Variacion de precios');
        $this->addView('grafico',$arr);

    }
}
