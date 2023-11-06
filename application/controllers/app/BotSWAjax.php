<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."bot/BotSW.php";
include_once MDL_PATH."Ticker.php";

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

    function trade()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);
        

        $id = $_REQUEST['idbotsw'];
        $usd = $_REQUEST['capitalUSD'];
        $symbol = $_REQUEST['trade_symbol'];
        $action = $_REQUEST['trade_action'];

        $tck = new Ticker();
        $bot = new BotSW($id);
        $symbolData = $tck->getSymbolData($symbol);

        if (!empty($symbolData))
        {
            //foreach ($symbolData as $k=>$v)
            //    $this->ajxRsp->debug('symbolData: '.$k.': '.$v);
                
            $prices = $api->prices();
            $price = $prices[$symbol];

            if ($price > 0)
            {
                //$this->ajxRsp->debug('Price: '.$symbol.' = '.$price);
                $origQty = toDec($usd/$price,$symbolData['qtyDecs']);
                //$this->ajxRsp->debug('origQty: '.$origQty);

                
                $baseAsset = $symbolData['baseAsset'];
                $quoteAsset = $symbolData['quoteAsset'];
                $baseFree = 0;
                $quoteFree = 0;
                $account = $api->account();
                if (!empty($account['balances']))
                {
                    foreach ($account['balances'] as $rw)
                    {
                        if ($rw['free']>0)
                        {
                            if ($rw['asset'] == $baseAsset)
                                $baseFree = $rw['free'];
                            if ($rw['asset'] == $quoteAsset)
                                $quoteFree = $rw['free'];
                        }
                    }
                }
                //$this->ajxRsp->debug('Free for '.$baseAsset.': '.$baseFree);
                //$this->ajxRsp->debug('Free for '.$quoteAsset.': '.$quoteFree);
                if ($action == 'buy')
                {
                    if ($quoteFree > $usd)
                    {
                        $order = $api->marketBuy($symbol, $origQty);
                        if ($order['status'] == 'FILLED')
                        {
                            $origQty = $order['executedQty'];
                            $price = toDec($order['cummulativeQuoteQty']/$order['executedQty'],$symbolData['qtyDecs']);
                        }
                        $bot->addOrder(date('Y-m-d h:i:s'),$baseAsset,$quoteAsset,BotSW::SIDE_BUY,$origQty,$price,$order['orderId']);
                        $this->ajxRsp->assign('trade_msg','innerHTML','La orden se ejecuto con exito<br> ');
                        $this->ajxRsp->append('trade_msg','innerHTML',' orderId: '.$order['orderId']. ' - '.
                                                                      ' status: '.$order['status']. ' - '.
                                                                      ' side: '.$order['side']. ' - '.
                                                                      ' executedQty: '.$order['executedQty']. ' - '.
                                                                      ' cummulativeQuoteQty: '.$order['cummulativeQuoteQty']. ' - '.
                                                                      ' price: '.$price);
                        $this->ajxRsp->append('trade_msg','class','text-success');
                        $this->ajxRsp->script("$('#trade').show();");

                     }
                    else
                    {
                        $this->ajxRsp->addError('No cuenta con balance suficiente en '.$quoteAsset.' para realizar la operacion.');
                    }
                }
                else
                {
                    if ($baseFree > $origQty)
                    {
                        $order = $api->marketSell($symbol, $origQty);
                        if ($order['status'] == 'FILLED')
                        {
                            $origQty = $order['executedQty'];
                            $price = toDec($order['cummulativeQuoteQty']/$order['executedQty'],$symbolData['qtyDecs']);
                        }
                        $bot->addOrder(date('Y-m-d h:i:s'),$baseAsset,$quoteAsset,BotSW::SIDE_SELL,$origQty,$price,$order['orderId']);
                        $this->ajxRsp->assign('trade_msg','innerHTML','La orden se ejecuto con exito<br> ');
                        $this->ajxRsp->append('trade_msg','innerHTML',' orderId: '.$order['orderId']. ' - '.
                                                                      ' status: '.$order['status']. ' - '.
                                                                      ' side: '.$order['side']. ' - '.
                                                                      ' executedQty: '.$order['executedQty']. ' - '.
                                                                      ' cummulativeQuoteQty: '.$order['cummulativeQuoteQty']. ' - '.
                                                                      ' price: '.$price);
                        $this->ajxRsp->append('trade_msg','class','text-success');
                        $this->ajxRsp->script("$('#trade').show();");
                    }
                    else
                    {
                        $this->ajxRsp->addError('No cuenta con balance suficiente en '.$baseAsset.' para realizar la operacion.');
                    }
                }
            
            }
            else
            {
                $this->ajxRsp->addError('No fue posible encontrar el precio de '.$symbol);
            }
            
        }
        else
        {
            $this->ajxRsp->addError('No fue posible encontrar el informacion sobre '.$symbol);
        }
        
    }
}