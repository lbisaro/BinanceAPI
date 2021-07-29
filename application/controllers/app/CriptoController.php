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
        $this->variacionPrecio($auth);
    }

    function variacionPrecio($auth)
    {
        $this->addTitle('Precios');
        $this->addView('variacionPrecio',$arr);
    }
    
    function compararPorcentaje($auth)    
    {
        $this->addTitle('Comparar');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');

        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('compararPorcentaje',$arr);

    }    

    function operaciones($auth)    
    {
        $this->addTitle('Operaciones');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');

        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('operaciones',$arr);

    }
}
