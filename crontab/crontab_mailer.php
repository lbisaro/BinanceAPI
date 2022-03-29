<?php
include_once LIB_PATH."Mailer.php";
$MailTo = 'leonardo.bisaro@gmail.com';

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