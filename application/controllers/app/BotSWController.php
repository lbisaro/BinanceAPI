<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";

include_once MDL_PATH."bot/BotSW.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";

/**
 * Controller: BotSWController
 * @package SGi_Controllers
 */
class BotSWController extends Controller
{
    function crear($auth)
    {
        $this->addTitle('Nuevo Bot Smart Wallet');

        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }

        $bot = new BotSW();
   
        $arr['data'] = '';

        $this->addView('bot/BotSW.crear',$arr);
    }    

    function ver($auth)
    {
        $this->addTitle('Bot Smart Wallet');

        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }

        $id = $_REQUEST['id'];
        
        $bot = new BotSW($id);
        if (!$id || $bot->get('idbotsw') != $id)
        {
            $this->addError('Se debe especificar un ID valido');
            return null;
        }

        if ($bot->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina');
            return null;
        }

        $arr['titulo'] = $bot->get('titulo');
        $arr['symbol_estable'] = $bot->get('symbol_estable');
        $arr['symbol_reserva'] = $bot->get('symbol_reserva');
        
        $arr['strEstado'] = $bot->get('strEstado');
        $arr['estado_msg'] = $bot->get('estado_msg');
        if ($bot->isOnline())
            $arr['estado_class'] = 'text-primary';
        elseif($bot->isStop())
            $arr['estado_class'] = 'text-danger';
        else
            $arr['estado_class'] = 'text-info';
        
        $capital = $bot->getCapital();
        if (!empty($capital))
        {
            $dg = new HtmlTableDg();
            $dg->addHeader('Token');
            $dg->addHeader('Cantidad Token',null,null,'center');
            $dg->addHeader('Precio',null,null,'center');
            $dg->addHeader('Cantidad USD',null,null,'right');
            $dg->addHeader('Proporcion',null,null,'right');
            $totCapInUSD = 0;
            
            $assets = array();
            foreach ($capital as $asset => $rw)
                $assets[] = $asset;

            $ai = $bot->getAssetsInfo($assets);
            
            foreach ($capital as $asset => $rw)
            {
                $row = array($asset,
                             toDecDown($rw['qty'],$ai[$asset]['qtyDecsUnits']),
                             toDecDown($rw['price'],$ai[$asset]['qtyDecsPrice']),
                             $rw['inUSD'],
                             $rw['part'].'%'
                            );
                $dg->addRow($row);
                $totCapInUSD += $rw['inUSD'];
            }
            $dg->addFooter(array('Total','','',toDec($totCapInUSD),''));
            $arr['htmlCapital'] = $dg->get();

            $ak = $auth->getConfig('bncak');
            $as = $auth->getConfig('bncas');
            $api = new BinanceAPI($ak,$as);
            $prices = $api->prices();
            
            $posiciones = $bot->getPosiciones($prices);
            if (!empty($posiciones))
            {
                $dg = new HtmlTableDg();
                $dg->addHeader('Token');
                $dg->addHeader('Cantidad Token',null,null,'center');
                $dg->addHeader('Precio',null,null,'center');
                $dg->addHeader('Cantidad USD',null,null,'right');
                $dg->addHeader('Proporcion',null,null,'right');
                $totPosInUSD = 0;
                foreach ($posiciones as $asset => $rw)
                {
                    $ai = $bot->getAssetsInfo(array($asset));
                    $qty = toDecDown($rw['pos']['qty'],$ai[$asset]['qtyDecsUnits']);
                    $qtyDif = toDecDown($rw['dif']['qty'],$ai[$asset]['qtyDecsUnits']);
                    $row = array($asset,
                                 $qty.'<br><small>'.coloredNum($qtyDif).'</small>',
                                 toDecDown($rw['pos']['price'],$ai[$asset]['qtyDecsPrice']),
                                 $rw['pos']['inUSD'].'<br><small>'.coloredNum($rw['dif']['inUSD']).'</small>',
                                 $rw['pos']['part'].'%'.'<br><small>'.coloredNum($rw['dif']['part'].'%').'</small>'
                                );
                    $dg->addRow($row);
                    $totPosInUSD += $rw['pos']['inUSD'];
                }
                $dg->addFooter(array('Total','','',toDec($totPosInUSD).'<br><small>'.coloredNum(toDec($totPosInUSD-$totCapInUSD)).'</small>',''));
                $arr['htmlPosiciones'] = $dg->get();
            }
                        
            $openOrders = $bot->getOpenOrders();
            $arr['showOrders'] = 'false';
            if (!empty($openOrders))
            {
                $arr['showOrders'] = 'true';
                $arr['htmlOpenOrders'] = 'MOSTRAR ORDENES';
            }
        }
        else
        {
            $arr['htmlCapital'] = '<b class="text-danger">Se requiere asignar capital</b>';        
        }


        if ($bot->isStandby())
        {
            if (empty($capital))
                $arr['addButtons'] .= '<a class="btn btn-success btn-sm" href="app.botSW.asignarCapital+id='.$id.'">Asignar Capital</a>';
            else
                $arr['addButtons'] .= '<a class="btn btn-info btn-sm" href="app.botSW.asignarCapital+id='.$id.'">Modificar Capital</a>';
            
            if (!empty($capital))
            {
                $arr['addButtons'] .= '<button class="btn btn-success btn-sm" onclick="start()">
                        <span class="glyphicon glyphicon glyphicon-off" aria-hidden="true"></span> Encender
                    </button>';
            }
                
            $arr['addButtons'] .= '<button class="btn btn-danger btn-sm" onclick="stop()">
                    <span class="glyphicon glyphicon glyphicon-off" aria-hidden="true"></span> Apagar
                </button>';
        }
        elseif ($bot->isStop())
        {
            $arr['addButtons'] .= '<button class="btn btn-success btn-sm" onclick="start()">
                    <span class="glyphicon glyphicon glyphicon-off" aria-hidden="true"></span> Encender
                </button>';
        }
        elseif ($bot->isOnline())
        {
            $arr['addButtons'] .= '<button class="btn btn-warning btn-sm" onclick="standby()">
                    <span class="glyphicon glyphicon glyphicon-off" aria-hidden="true"></span> Pausar
                </button>';
        }


        $arr['hidden'] .= Html::getTagInput('idbotsw',$id,'hidden');
        $this->addView('bot/BotSW.ver',$arr);
    }


    function asignarCapital($auth)
    {
        $this->addTitle('Bot Smart Wallet - Asignar Capital');

        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }

        $id = $_REQUEST['id'];
        
        $bot = new BotSW($id);
        if (!$id || $bot->get('idbotsw') != $id)
        {
            $this->addError('Se debe especificar un ID valido');
            return null;
        }

        if ($bot->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina');
            return null;
        }
        if (!$bot->isStandby())
        {
            $this->addError('El Bot debe estar ['.$bot->getTipoEstado(BotSW::ESTADO_STANDBY).'] para poder realizar la operacion');
            return null;
        }

        $symbol_estable = $bot->get('symbol_estable');
        $symbol_reserva = $bot->get('symbol_reserva');
        $arr['titulo'] = $bot->get('titulo');
        $arr['symbol_estable'] = $symbol_estable;
        $arr['symbol_reserva'] = $symbol_reserva;
        
        $arr['strEstado'] = $bot->get('strEstado');
        $arr['estado_msg'] = $bot->get('estado_msg');
        if ($bot->isOnline())
            $arr['estado_class'] = 'text-primary';
        elseif($bot->isStop())
            $arr['estado_class'] = 'text-danger';
        else
            $arr['estado_class'] = 'text-info';

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);
        $account = $api->account();
        $prices = $api->prices();
        $capitalAvailable[$symbol_estable] = array();
        $capitalAvailable[$symbol_reserva] = array();
        $assets = array();
        if (!empty($account['balances']))
        {
            foreach ($account['balances'] as $rw)
            {
                if ($rw['free']>0)
                {
                    $price = 0;
                    if ($rw['asset'] == 'USDT' || $rw['asset'] == 'BUSD' || $rw['asset'] == 'USDC')
                        $price = '1.00';
                    else
                        $price = $prices[$rw['asset'].'USDT'];
                    if ($price>0)
                    {
                        $capitalAvailable[$rw['asset']]['token_free'] = $rw['free'];
                        $capitalAvailable[$rw['asset']]['price'] = $price;
                        $assets[] = $rw['asset'];
                    }
                    
                }
            }
        }

        $ai = $bot->getAssetsInfo($assets);
        
        $capital = $bot->getCapital();
        foreach ($capital as $asset => $rw)
            $capitalAvailable[$asset]['token_capital'] = $rw['qty'];
 
        $dg = new HtmlTableDg();
        $dg->addHeader('Moneda');
        $dg->addHeader('Precio',null,null,'center');
        $dg->addHeader('Disponible Token',null,null,'center');
        $dg->addHeader('Disponible USD',null,null,'center');
        $dg->addHeader('Capital Token',null,null,'center');
        $dg->addHeader('Capital USD',null,null,'center');
        $dg->addHeader('Accion',null,null,'right');
        $arr['jsonData'] .= '';
        foreach ($capitalAvailable as $asset=>$rw)
        {
            $tokenCapital = toDecDown($rw['token_capital'],$ai[$asset]['qtyDecsUnits']);
            $price = toDecDown($rw['price'],$ai[$asset]['qtyDecsPrice']);
            $tokenFree = toDecDown($rw['token_free'],$ai[$asset]['qtyDecsUnits']);

            if ($tokenCapital == 0)
                $accion = '<div class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalAsignarCapital" data-asset="'.$asset.'">Asignar</div>';
            else
                $accion = '<div class="btn btn-secondary btn-sm" data-toggle="modal" data-target="#modalAsignarCapital" data-asset="'.$asset.'">Modificar</div>';
            $tokenUsd = toDec($tokenCapital*$price);
            if ($tokenUsd == 0)
                $tokenUsd = '';
            if ($asset == $symbol_reserva)
                $accion = '';


            $row = array($asset.' '.$symbol_estable,
                         $price,
                         $tokenFree,
                         toDec($tokenFree*$price),
                         $tokenCapital,
                         $tokenUsd,
                         $accion                     
                         );

            $arr['jsonData'] .= '
            const data_'.$asset.' = {
                        "Free": '.$tokenFree.',"Capital": '.$tokenCapital.',"Price": '.$price.
                        ',"qtyDecsUnits": '.$ai[$asset]['qtyDecsUnits'].
                        ',"qtyDecsPrice": '.$ai[$asset]['qtyDecsPrice'].
                        '};';

            $dg->addRow($row);
        }
        $arr['htmlCapital'] = $dg->get();



        $arr['hidden'] .= Html::getTagInput('idbotsw',$id,'hidden');
        $this->addView('bot/BotSW.asignarCapital',$arr);
    }

    function agregarOrdenes($auth)
    {
        $this->addTitle('Agregar Ordenes');

        $idbotsw = $_REQUEST['id'];
        $symbol = trim(strtoupper($_REQUEST['symbol']));
        $bot = new BotSW($idbotsw);
        
        if (!empty($symbol))
        {
            /*
            $oprOrders = $opr->getOrdenes($enCurso = false);
            $audit = array();
            $lastComplete = null;
            foreach ($oprOrders as $k => $v)
            {
                if ($v['completed'])
                {
                    $lastComplete = $v['updated'];
                    //unset($oprOrders[$k]);
                }
                $auditBot[$v['orderId']] = $v;
            }
            */
            if (!$lastComplete)
                $lastComplete = date('Y-m-d 00:00:00',strtotime('-15 days'));

            $ak = $auth->getConfig('bncak');
            $as = $auth->getConfig('bncas');
            $api = new BinanceAPI($ak,$as);  
            try {

                //Informacion de la moneda
                $symbolData = $api->getSymbolData($symbol);
                $qtyDecsPrice = $symbolData['qtyDecsPrice'];
                
                //Historico
                $ordersHst = $api->orders($symbol); 
                $show = false; 
                foreach ($ordersHst as $k => $v)
                {
                    $v['datetime'] = date('Y-m-d H:i:s',$ordersHst[$k]['time']/1000);

                    //Correccion para ordenes parciales
                    if (!isset($strQtyDecs))
                    {
                        $strQtyDecs = strlen($v['cummulativeQuoteQty'])-strpos($v['cummulativeQuoteQty'], '.')-1;
                        
                    }
                    $v['qtyDecs'] = $strQtyDecs;
                    if (($v['price']*1)==0)
                        $v['price'] = toDec(toDec($v['cummulativeQuoteQty']/$v['executedQty'],$qtyDecsPrice),$strQtyDecs);

                    unset($v['clientOrderId']);
                    unset($v['orderListId']);
                    unset($v['origQuoteOrderQty']);
                    unset($v['timeInForce']);
                    unset($v['stopPrice']);
                    unset($v['icebergQty']);
                    unset($v['updateTime']);
                    unset($v['isWorking']);
                    unset($v['time']);
                    //$lastComplete = '2022-08-01 00:00:00';
                    if ($v['datetime'] >= $lastComplete && $v['status']!='CANCELED' && $v['status']!='EXPIRED')
                    {
                        if ($auditBot[$v['orderId']])
                        {
                            $v['bot'] = true;
                        }
                        else
                        {
                            $tradeInfo = $api->orderTradeInfo($symbol,$v['orderId']);
                            $tradeQty = 0;
                            $tradeUsd = 0;
                            if (!empty($tradeInfo))
                            {
                                foreach($tradeInfo as $tii)
                                {
                                    $tradeQty += $tii['qty'];
                                    $tradeUsd += $tii['quoteQty'];
                                }
                                $v['price'] = toDec(toDec($tradeUsd/$tradeQty,$qtyDecsPrice),$strQtyDecs);
                            }
                            $v['bot'] = false;
                        }
                        $audit[$v['orderId']] = $v;
                    }

                } 

                $dg = new HtmlTableDg();
                $dg->addHeader('orderId');
                $dg->addHeader('Price');
                $dg->addHeader('Cantidad');
                $dg->addHeader('USD');
                $dg->addHeader('side');
                $dg->addHeader('BNC status');
                $dg->addHeader('Fecha');
                $dg->addHeader('BOT');
                $dg->addHeader('Check');
                $dg->addHeader('SQL');
                
                foreach ($audit as $rw)
                {
                    $row = array();
                    $row[] = $rw['orderId'];
                    $row[] = $rw['price'];
                    $row[] = $rw['origQty'];
                    $row[] = toDec($rw['origQty']*$rw['price']);
                    $row[] = ($rw['side']=='BUY'?'Compra':'Venta');
                    $row[] = $rw['status'];
                    $row[] = $rw['datetime'];
                    $row[] = ($rw['bot']?'OK':'Falta');
                    if ($rw['bot'] || $rw['status']!='FILLED')
                        $row[] = '&nbsp;';
                    else
                        $row[] = '<input type="checkbox" id="chk_'.$rw['orderId'].'" onclick="refresh();" />';

                    $sql='';
                    if (!$rw['bot'] && $rw['status']=='FILLED')
                    {
                        //Preparando SQL
                        $side = ($rw['side']=='BUY'?'0':'1');
                        $status = ($rw['status']=='FILLED'?'10':'0');

                        $inputs .= "\n".'<input type="hidden" id="side_'.$rw['orderId'].'" value="'.$side.'">';
                        $inputs .=      '<input type="hidden" id="status_'.$rw['orderId'].'" value="'.$status.'">';
                        $inputs .=      '<input type="hidden" id="origQty_'.$rw['orderId'].'" value="'.$rw['origQty'].'">';
                        $inputs .=      '<input type="hidden" id="price_'.$rw['orderId'].'" value="'.$rw['price'].'">';
                        $inputs .=      '<input type="hidden" id="orderId_'.$rw['orderId'].'" value="'.$rw['orderId'].'">';
                        $inputs .=      '<input type="hidden" id="datetime_'.$rw['orderId'].'" value="'.$rw['datetime'].'">';
                    }
                    $row[] = '';
                    $class='';
                    if (!$rw['bot'] && $rw['status']=='NEW')
                        $class='text-success';
                    elseif (!$rw['bot'] && $rw['status']!='NEW')
                        $class='text-primary';
                    $dg->addRow($row,$class,$height='25px',$valign='middle',$id="tr_".$rw['orderId']);
                }
                $arr['data'] = $dg->get();
                
            } catch (Exception $e) {
                $this->addOnLoadJs("alert('El par ".$symbol." no se encuentra disponible para operar.');");                
                $symbol = '';
            }
        }
            
        
        $arr['data'] .= $inputs;
        $arr['symbol'] = $symbol;
        $arr['hidden'] = Html::getTagInput('idbotsw',$idbotsw,'hidden');
    
        $this->addView('bot/BotSW.auditarOrdenes',$arr);
    }
}
