<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH.'bsbot/Bsbot.php';
/**
 * BsbotAjax
 *
 * @package SGi_Controllers
 */
class BsbotAjax extends ControllerAjax
{
    function add()
    {
        $bot = new Bsbot();
 
        $data['fecha'] = $_REQUEST['fecha']; 
        $data['tipo'] = $_REQUEST['tipo']; 
        $data['qty'] = $_REQUEST['qty']; 
        if ($bot->add($data))
        {
            $this->ajxRsp->redirect(Controller::getLink('bsbot','bsbot','home'));
        }
        else
        {
            $this->ajxRsp->addError($bot->getErrLog());
            return false;
        }
    }

    function del()
    {
        $bot = new Bsbot();
 
        $id = $_REQUEST['id']; 

        if ($bot->delete($id))
        {
            $this->ajxRsp->redirect(Controller::getLink('bsbot','bsbot','home'));
        }
        else
        {
            $this->ajxRsp->addError('No fue posible eliminar el bot');
            return false;
        }
    }
}