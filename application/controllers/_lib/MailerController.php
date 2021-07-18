<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."Mailer.php";

/**
 * Controller: MailerController
 * @package SGi_Controllers
 */
class MailerController extends Controller
{
    function panel($auth)
    {
        $this->addTitle('TANet Mailer');

        if (!$auth->checkCsu('sgi.admSys.mailer'))
        {
             $this->adderror('No esta autorizado a visualizar esta pagina.');
             return null;
        }

        $mail = new Mailer();

        $ds = $mail->getOutboxMails();
        if (!empty($ds))
        {
            foreach ($ds as $rw)
            {
                $arr['mensajes'] .= '
                <div class="msgItem" id="msg_'.$rw['tmpID'].'" onclick="mostrarMensaje(\''.$rw['tmpID'].'\')">
                '.$rw['tmpID'].'
                </div>';
            }
        }
        else
        {
            $arr['mensajes'] = '<span style="padding: 5px;" class="info">No existen mensajes pendientes de envio</span>';
        }
   
        $this->addView('_lib/mailer',$arr);
    }
}

?>