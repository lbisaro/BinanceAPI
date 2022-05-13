 <?php
include_once MDL_PATH."bot/Test.php";

$test = new Test();

$symbols = $test->getSymbolsToUpdate();

if (!empty($symbols))
{
    foreach ($symbols as $symbol)
    {
        if (substr($symbol,0,4) != 'LUNA' && substr($symbol,0,3) != 'UST')
        {
            echo "\n".$symbol;
            while ($test->updateKlines_1m($symbol))
            {
                $status = $test->getUpdateStatus();
                echo "\n".$symbol.' -> '.
                          ' lote: '.$status['lote'].
                          ' start: '.$status['start'].
                          ' qtyKlines: '.$status['qtyKlines'].
                          ' last: '.$status['last'];
                usleep(100000); //100ms
            }
        }
    }
}
echo "\n";












/*


$reporte = array();

//Iniciando el reporte
$reporte[] = "\nINICIO $hostname\n";

$fichero = ROOT_DIR.'/log.txt';

$bnc = new Binance();
$tck = new Ticker();
file_put_contents($fichero, "\n"."Crontab.1 ".date('H:i:s'),FILE_APPEND);
$prices = $bnc->price();
file_put_contents($fichero, "\n"."Crontab.2 ".date('H:i:s')."\n",FILE_APPEND);
$tck->addPrices($prices);
file_put_contents($fichero, "\n"."Crontab.3 ".date('H:i:s')."\n",FILE_APPEND);
$reporte[] = "\n\Ticker Prices OK\n";
//foreach ($prices as $k => $v)
//    $reporte[] = "\t$k: $v\n";

if ($msgNewTicker = $tck->getNewTicker())
{
    $MailTo = 'leonardo.bisaro@gmail.com,martin.bisaro@gmail.com';

    $htmlNewTicker = str_replace("\n","<br>",$msgNewTicker);

    try
    {
        $mail = new Mailer();
        $mail->FromName = $replyNam;
        $mail->AddReplyTo($replyDir, $replyNam);
        
        
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

        $mail->Subject = 'Nueva Moneda en Binance '.date('Y-m-d H:i:s');

        // Texto alternativo
        $msgTxt   = 'Se ha encontrado una nueva moneda en Binance';
        $html   = '<b>Se ha encontrado una nueva moneda en Binance</b>';

        $html .= "<hr>".$htmlNewTicker;
        $msgTxt .= "\n".$msgNewTicker;


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
}
$reporte[] = "\n\n";
        

        

$reporte[] = "\n\n";
$reporte[] = "\nEND $hostname\n";
//--------------------------------------------------------------------------------------------------------------------

foreach ($reporte as $it)
    echo $it;
*/

?>