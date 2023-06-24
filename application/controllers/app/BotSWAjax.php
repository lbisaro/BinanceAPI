<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."bot/BotSW.php";

/**
 * BotSWAjax
 *
 * @package SGi_Controllers
 */
class BotSWAjax extends ControllerAjax
{
    function crear()
    {
        $arrToSet['titulo'] = $_REQUEST['titulo'];
        $arrToSet['symbol_estable'] = $_REQUEST['symbol_estable'];
        $arrToSet['symbol_reserva'] = $_REQUEST['symbol_reserva'];
        $bot = new BotSW();
        $bot->set($arrToSet);
        if ($bot->saveNew())
            $this->ajxRsp->redirect(Controller::getLink('app','BotSW','ver','id='.$bot->get('idbotsw')));
        else
            $this->ajxRsp->addError($bot->getErrLog()); 
    }

    function asignarCapital()
    {
        $id = $_REQUEST['idbotsw'];
        $asset = $_REQUEST['asset'];
        $qty = $_REQUEST['capital'];
        $price = $_REQUEST['price'];
        
        $bot = new BotSW($id);
        if ($bot->asignarCapital($asset,$qty,$price))
            $this->ajxRsp->redirect(Controller::getLink('app','BotSW','ver','id='.$bot->get('idbotsw')));
        else
            $this->ajxRsp->addError($bot->getErrLog());
        
    }

    function setStatus()
    {
        $id = $_REQUEST['idbotsw'];
        $newStatus = $_REQUEST['newStatus'];
        $bot = new BotSW($id);
        if ($bot->setStatus($newStatus))
            $this->ajxRsp->redirect(Controller::getLink('app','BotSW','ver','id='.$id));
        else
            $this->ajxRsp->addError($bot->getErrLog());
    }

    function agregarOrdenesMakeSql()
    {
        
        $idbotsw  = $_REQUEST['idbotsw'];
        $ejecutar = $_REQUEST['execute'];
        $symbol   = $_REQUEST['symbol'];
        $bot = new BotSW($idbotsw);
        
        $assets = $bot->separateSymbol($symbol);
        
        $base_asset = $assets['base'];
        $quote_asset = $assets['quote'];
        
        foreach ($_REQUEST as $id=>$value)
        {
            $qry = array();
            if (substr($id,0,8) == 'orderId_')
            {
                $orderId = substr($id,8);
                $side = $_REQUEST['side_'.$orderId];
                $status = $_REQUEST['status_'.$orderId];
                $origQty = $_REQUEST['origQty_'.$orderId];
                $price = $_REQUEST['price_'.$orderId];
                $datetime = $_REQUEST['datetime_'.$orderId];
                $sql = '';
                if ($_REQUEST['chk_'.$orderId])
                {
                    $sql = "INSERT INTO bot_sw_orden_log (idbotsw,base_asset,quote_asset,side,origQty,price,orderId,datetime) VALUES ".
                            "(".$idbotsw.",'".$base_asset."','".$quote_asset."',".$side.",".$origQty.",".$price.",'".$orderId."','".$datetime."');";
                    $qry[] = $sql;
                }
                if ($ejecutar)
                {
                    $db = DB::getInstance();
                    foreach ($qry as $ins)
                    {
                        $db->query($ins);
                    }
                    $this->ajxRsp->redirect('app.botSW.agregarOrdenes+id='.$idbotsw);
                }
                else
                {
                    $this->ajxRsp->assign('tr_'.$orderId.'_10','innerHTML',$sql);
                    if (!empty($qry))
                        $this->ajxRsp->script("$('#btn_ejecutar').show();");
                }
            }
        }

    }
}