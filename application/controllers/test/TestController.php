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
        /*
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
        */
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
    
    
    function bot2($auth)
    {
        $this->addTitle('TITULO para Bot2');
    
        $test = new Test();
        $symbol = 'BTCUSDT';
        $capital = 1000;
        $compraInicial = 20;
        $prms['porcVentaUp'] = 2;
        $prms['porcVentaDown'] = 2;
        $result = $test->testBot2($symbol,$capital,$compraInicial,$prms);
        
        $arr['data'] .= '<h4>Billetera</h4>'.arrayToTableDg($result['account']);
        $arr['data'] .= '<h4>PNL INFO</h4>'.arrayToTableDg($result['pnlInfo']);
        //$arr['data'] .= '<h4>botOrders</h4>'.arrayToTableDg($result['botOrders']);
        $arr['data'] .= '<h4>openOrders</h4>'.arrayToTableDg($result['openOrders']);
        //$arr['data'] .= '<h4>pnlOrders</h4>'.arrayToTableDg($result['pnlOrders']);

        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
    
    
    
}
