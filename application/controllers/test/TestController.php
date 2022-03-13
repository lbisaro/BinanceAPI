<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableFc.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."bot/Test.php";

/**
 * Controller: TestController
 * @package SGi_Controllers
 */
class TestController extends Controller
{
    
    function home($auth)
    {
        $this->addTitle('BackTesting - Home');
        if ($auth->get('idperfil') != UsrUsuario::PERFIL_ADM)
        {
            $this->addOnloadJs("goTo('".Controller::getLink('test','test','testEstrategias')."');");
        }
        else
        {
            $arr['data'] = '';
            $arr['hidden'] = '';
        
            $this->addView('test/home',$arr);
        }
    }
    
    
    function updateKlines_1m($auth)
    {
        $this->addTitle('Actualizar Precios 1m');

        $test = new Test();

        if (isset($_REQUEST['symbol']))
        {
            $symbols[] = strtoupper($_REQUEST['symbol']);
        }
        else
        {
            $symbols = $test->getSymbolsToUpdate();
        }
        $dataSymbols = '';
        if (!empty($symbols))
        {
            foreach ($symbols as $symbol)
            {
                $dataSymbols .= ($dataSymbols?',':'')."'".$symbol."'";
            }
            $arr['dataSymbols'] = $dataSymbols;
            $arr['fechaInicio'] = dateToStr($test->startKlines,true);
        }
   
        $this->addView('test/updateKlines_1m',$arr);
    }

    function testEstrategias($auth)
    {
        $this->addTitle('BackTesting de Estrategia');

        $test = new Test();

        if (isset($_REQUEST['symbol']))
        {
            $symbols[] = strtoupper($_REQUEST['symbol']);
        }
        else
        {
            $symbols = $test->getSymbolsToUpdate();
        }
        $dataSymbols = '';
        if (!empty($symbols))
        {
            foreach ($symbols as $symbol)
            {
                $dataSymbols .= ($dataSymbols?',':'')."'".$symbol."'";
            }
            $arr['dataSymbols'] = $dataSymbols;
            $arr['fechaInicio'] = dateToStr($test->startKlines,true);
        }

        $arr['resultado'] = 'Completar los campos y hacer clic en el boton Analizar';
        $arr['hidden'] = '';
    
        $this->addView('test/testEstrategias',$arr);
    }
    
    function testAT($auth)
    {
        $this->addTitle('BackTesting Analisis Tecnico');

        $test = new Test();

        if (isset($_REQUEST['symbol']))
        {
            $symbols[] = strtoupper($_REQUEST['symbol']);
        }
        else
        {
            $symbols = $test->getSymbolsToUpdate();
        }
        $dataSymbols = '';
        if (!empty($symbols))
        {
            foreach ($symbols as $symbol)
            {
                $dataSymbols .= ($dataSymbols?',':'')."'".$symbol."'";
            }
            $arr['dataSymbols'] = $dataSymbols;
            $arr['fechaInicio'] = dateToStr($test->startKlines,true);
        }

        $arr['resultado'] = 'Completar los campos y hacer clic en el boton Analizar';
        $arr['hidden'] = '';
    
        $this->addView('test/testAT',$arr);
    }
    
    
}
