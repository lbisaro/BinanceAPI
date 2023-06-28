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
        $this->addOnloadJs("goTo('".Controller::getLink('test','test','testEstrategias')."');");
    }


    function polyfit($auth)
    {
        $this->addTitle('PolyFit');
    
        include_once LIB_PATH."PolyFit.php";

        $x = [0, 1, 2, 3, 4, 5];
        //$y = [1, 3, 2, 5, 7, 8];

        //2x+3
        foreach($x as $k => $v)
            $y[$k] = 2*$v + 5;
        
        //2x+3 con ruido
        foreach($x as $k => $v)
            $y[$k] = $v*$v + 2*$v + 5 + rand(-1.7,1.8);
        


        // Fit a polynomial of degree 2 to the data
        $linearRegression = new PolyFit();
        $linearRegression->train($x, $y);
        $rlp = $linearRegression->getRegressionLinePoints();
        $dfrl = $linearRegression->getDifferencesFromRegressionLine();
        $cs = $linearRegression->getCumulativeSumOfDifferencesFromRegressionLine();

        
        
        $dg = new HtmlTableDg();
        $dg->addHeader('X');
        $dg->addHeader('Y');
        $dg->addHeader('Reg X');
        $dg->addHeader('Reg Y');
        $dg->addHeader('Diff From Reg Line');
        $dg->addHeader('Cumm Sum');
        
        foreach ($x as $k => $v)
        {
            $row = array($x[$k],
                         $y[$k],   
                         $rlp[$k]->getX(),   
                         $rlp[$k]->getY(),
                         $dfrl[$k],
                         $cs[$k] 
                        );
            $dg->addRow($row);
        }


    
        $arr['data'] .= '<p>Ordenada al origen: ' . $linearRegression->getIntercept() .'</p>';
        $arr['data'] .= '<p>Pendiente: ' . $linearRegression->getSlope() .'</p>';
        $arr['data'] .= '<p>Funcion: ' . $linearRegression->getSlope() .'x + '.$linearRegression->getIntercept().'</p>';
        $arr['data'] .= '<p>R-Squared: ' . $linearRegression->getRSquared().'</p>';


        $arr['data'] .= $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
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
        $tck = new Ticker();
        $ds = $tck->getDataSet(null,'tickerid');
        $symbolsBotAuto = '';
        foreach ($ds as $rw)
        {
            if (in_array($rw['tickerid'], $symbols))
            {
                $symbolsBotAuto .= ($symbolsBotAuto?',':'')."'".$rw['tickerid']."'";
            }
        }
        
        $arr['symbolsBotAuto'] = $symbolsBotAuto;
        $start='2021-06-01 00:00';
        $end=date('Y-m-d ',strtotime('-90 days')).' 00:00';
        while ($start<=$end)
        {
            $rangoEnd = date('m-d',strtotime($start.' +90 days'));
            $strRango = substr($start,0,10).' al '.$rangoEnd;

            $arr['rangoFechas'][] = '<OPTION value="'.$start.'" >'.$strRango.'</OPTION>';
            $start=date('Y-m-d',strtotime($start.' +1 month')).' 00:00';
        }
        $arr['rangoFechas'][] = '<OPTION SELECTED value="'.date('Y-m-d',strtotime('-90 days')).' 00:00" >Ultimos 90 dias</OPTION>';
        $arr['rangoFechas'][] = '<OPTION value="2023-04-29 00:00" >Inicio USDTARS</OPTION>';

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
    

    function mailer($auth)
    {
        $this->addTitle('Mailer');
    
        include_once LIB_PATH."Mailer.php";
        $MailTo = 'leonardo.bisaro@gmail.com';

        echo "<br/><br/><br/><br/><br/><br/>";
        echo "<pre>";
        try
        {
            $mail = new Mailer();
            $mail->FromName = MAILER_FromName;
            $mail->AddReplyTo(MAILER_From, MAILER_FromName);
            
            
            $aMailTo = preg_split("/[\s;,]+/",$MailTo);
            $destinatarios = 0;
            foreach($aMailTo AS $To)
            {
                $To = trim($To);
                if (!empty($To))
                {
                    $mail->AddCC($To, $To);
                    $destinatarios++;
                }
            }

            $mail->Subject = 'Prueba '.date('Y-m-d H:i:s');

            // Texto alternativo
            $msgTxt   = 'Envio de prueba';
            $html   = '<b>Envio de prueba</b>';

            // Texto HTML
            $msgHtml   = $htmlStyle.$html;;

            $mail->AltBody = $msgTxt;
            $mail->MsgHTML($msgHtml);
            if ($mail->send()) {
              echo 'success';
            } else {
              echo 'failed to send';
              echo $mail->ErrorInfo;
            }
            
        }
        catch (phpmailerException $e)
        {
            echo "phpmailerException: ".$e->errorMessage(); //Pretty error messages from PHPMailer
        }
        catch (Exception $e)
        {
            echo "Exception: ".$e->getMessage(); //Boring error messages from anything else!
        }
        echo "</pre>";
    
        $arr['data'] = '';
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
   
    
}
