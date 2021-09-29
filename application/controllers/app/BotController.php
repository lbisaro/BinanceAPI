<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Operacion.php";

/**
 * Controller: BotController
 * @package SGi_Controllers
 */
class BotController extends Controller
{
    
    function operaciones($auth)
    {
        $this->addTitle('Operaciones');


    
    
        $arr['data'] = '';
        $arr['hidden'] = '';
    
        $this->addView('bot/operaciones',$arr);
    }

    function crearOperacion($auth)
    {
        $this->addTitle('Crear Operacion');
    
    
        $arr['data'] = '';
        $arr['hidden'] = '';

        $opr = new Operacion();
        $calculo = $opr->calcularOperacion($inicio_precio=8.1,$inicio_usd=50,$multiplicador_compra=2,$multiplicador_porc=10);
        
        debug($calculo);

        $this->addView('bot/crearOperacion',$arr);
    }    
    

    function revisarEstrategia($auth)    
    {
        $this->addTitle('Operaciones');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');
        
        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('bot/revisarEstrategia',$arr);

    }
}
