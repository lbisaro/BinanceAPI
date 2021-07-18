<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once LIB_PATH."Mailer.php";
include_once LIB_PATH."HtmlTableFc.php";

/**
 * MailerAjax
 *
 * @package SGi_Controllers
 */
class MailerAjax extends ControllerAjax
{
    function enviar()
    {
        $tmpID = $_REQUEST['tmpID'];
        
        try
        {
            $ml = new Mailer();
            $mail = $ml->getFromFile($tmpID);
            if (!empty($mail))
            {
                if ($mail->send())
                {
                    $this->ajxRsp->assign('mailer_resultado','innerHTML','Enviado OK');
                    $mail->deleteFile($tmpID);

                    if ($_REQUEST['returnTo'])
                        $this->ajxRsp->redirect(urldecode($_REQUEST['returnTo']));
                }
                else
                {
                    $this->ajxRsp->assign('mailer_resultado','innerHTML','No fue posible enviar el mail.');
                }
            }
            else
            {
                $this->ajxRsp->assign('mailer_resultado','innerHTML','No se encuentra una instancia con el tmpID '.$tmpID);
            }


        }
        catch (phpmailerException $e)
        {
            $this->ajxRsp->assign('mailer_resultado','innerHTML',$e->errorMessage());
            //$this->ajxRsp->addError($e->errorMessage()); //Pretty error messages from PHPMailer
        }
        catch (Exception $e)
        {
            $this->ajxRsp->assign('mailer_resultado','innerHTML',$e->getMessage());
            //$this->ajxRsp->addError($e->getMessage()); //Boring error messages from anything else!
        }
    }

    function eliminar()
    {
        $tmpID = $_REQUEST['tmpID'];
        
        $ml = new Mailer();
        $mail = $ml->getFromFile($tmpID);
        if (!empty($mail))
        {
            if ($mail->deleteFile($tmpID))
            {
                $this->ajxRsp->assign('mailer_resultado','innerHTML','El mensaje ha sido eliminado.');
                $this->ajxRsp->script("postEliminar('".$tmpID."')");
            }
            else
            {
                $this->ajxRsp->assign('mailer_resultado','innerHTML','No fue posible eliminar el mail.');
            }
        }
        else
        {
            $this->ajxRsp->assign('mailer_resultado','innerHTML','No se encuentra una instancia con el tmpID '.$tmpID);
        }

    }
    function mostrarMensaje()
    {
        $tmpID = $_REQUEST['tmpID'];
        
        $mail = new Mailer();
        $ml = $mail->getInstance($tmpID);

        $fc = new HtmlTableFc();
        $fc->addRow(array('De',$ml->FromName.'<'.$ml->From.'>'),$class=null,$height='10px',$valign='middle');
        if ($strTo = $ml->getStrTo())
            $fc->addRow(array('Para',$strTo),$class=null,$height='10px',$valign='middle');
        if ($strCc = $ml->getStrCc())
            $fc->addRow(array('CC',$strCc),$class=null,$height='10px',$valign='middle');
        if ($strBcc = $ml->getStrBcc())
            $fc->addRow(array('CCO',$strBcc),$class=null,$height='10px',$valign='middle');
        $fc->addRow(array('Fecha',dateToStr($ml->fileInfoDate,true),'Tamaño',$ml->fileInfoSize),$class=null,$height='10px',$valign='middle');
        $fc->addRow(array('Asunto',$ml->Subject),$class=null,$height='10px',$valign='middle');

        $mensaje = '<h2>Mensaje ID '.$tmpID.'</h2>';
        $mensaje .= '<div id="mailer_resultado">
                     <button class="html_button" onclick="enviar(\''.$tmpID.'\')">Enviar</button>
                     <button class="html_button html_button_danger" onclick="eliminar(\''.$tmpID.'\')">Eliminar</button>
                     </div>';
        $mensaje .= '<div style="margin: 3px; padding:7px;border: 1px solid #aaa; border-radius:5px;">'.$fc->get().'</div>';
        $mensaje .= '<div style="margin: 3px; padding:7px;border: 1px solid #aaa; border-radius:5px;">'.$ml->Body.'</div>';

        $this->ajxRsp->assign('mensaje','innerHTML',$mensaje);


    }
}
?>