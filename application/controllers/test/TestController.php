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
