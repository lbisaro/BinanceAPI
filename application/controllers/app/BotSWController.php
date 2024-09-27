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

        if (!empty($capital))
        {
            $arr['addButtons'] .= '<a class="btn btn-info btn-sm" href="app.botSW.trade+id='.$id.'">Trade</a>';
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
        $capitalAvailable[$symbol_estable] = array('token_free'=>0,'price'=>'1.00');
        $capitalAvailable[$symbol_reserva] = array('token_free'=>0,'price'=>'1.00');
        $assets = array();
        if (!empty($account['balances']))
        {
            foreach ($account['balances'] as $rw)
            {
                if ($rw['free']>0)
                {
                    $price = 0;
                    if ($rw['asset'] == 'USDT' || $rw['asset'] == 'FDUSD' || $rw['asset'] == 'USDC')
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
        if (!in_array($symbol_estable , $assets))
            $assets[] = $symbol_estable;
        if (!in_array($symbol_reserva , $assets))
            $assets[] = $symbol_reserva;
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
                        ',"qtyDecsUnits": '.toDec($ai[$asset]['qtyDecsUnits'],10).
                        ',"qtyDecsPrice": '.toDec($ai[$asset]['qtyDecsPrice'],10).
                        '};';

            $dg->addRow($row);

        }
        $arr['htmlCapital'] = $dg->get();
        $arr['addButtons'] .= '<a class="btn btn-info btn-sm" href="app.botSW.ver+id='.$id.'">Regresar</a>';

        $arr['hidden'] .= Html::getTagInput('idbotsw',$id,'hidden');
        $this->addView('bot/BotSW.asignarCapital',$arr);
    }

    function trade($auth)
    {
        $id = $_REQUEST['id'];
        $this->addTitle('Bot Smart Wallet - Trade');

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

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);
        $account = $api->account();
        $prices = $api->prices();

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

        $tradeSymbols = $bot->getSymbolsForTrade();
        $dg = new HtmlTableDg();
        $dg->addHeader('Par');
        $dg->addHeader('Accion');
        if (!empty($tradeSymbols))
        {
            foreach ($tradeSymbols as $symbol => $rw)
            {
                $row = array($symbol,
                             '<span class="btn btn-sm btn-success" onclick="make_trade(\'buy\',\''.$rw['base'].'\',\''.$rw['quote'].'\');">Comprar</span>&nbsp;'.
                             '<span class="btn btn-sm btn-danger"  onclick="make_trade(\'sell\',\''.$rw['base'].'\',\''.$rw['quote'].'\');">Vender</span>');
                $dg->addRow($row);
            }
        }

        $arr['htmlTrade'] = $dg->get();
        $arr['idbotsw'] = $id;
        $arr['addButtons'] .= '<a class="btn btn-info btn-sm" href="app.botSW.ver+id='.$id.'">Regresar</a>';


        //Capital disponible para trade
        $assets = array();
        if (!empty($account['balances']))
        {
            foreach ($account['balances'] as $rw)
            {
                if ($rw['free']>0)
                {
                    $price = 0;
                    if ($rw['asset'] == 'USDT' || $rw['asset'] == 'FDUSD' || $rw['asset'] == 'USDC')
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
        if (!in_array($symbol_estable , $assets))
            $assets[] = $symbol_estable;
        if (!in_array($symbol_reserva , $assets))
            $assets[] = $symbol_reserva;
        $ai = $bot->getAssetsInfo($assets);
        $capital = $bot->getCapital();
        foreach ($capital as $asset => $rw)
            $capitalAvailable[$asset]['token_capital'] = $rw['qty'];
        foreach ($capitalAvailable as $asset=>$rw)
        {
            $tokenCapital = toDecDown($rw['token_capital'],$ai[$asset]['qtyDecsUnits']);
            $price = toDecDown($rw['price'],$ai[$asset]['qtyDecsPrice']);
            $tokenFree = toDecDown($rw['token_free'],$ai[$asset]['qtyDecsUnits']);


            $tokenUsd = toDec($tokenCapital*$price);
            if ($tokenUsd == 0)
                $tokenUsd = '';
            if ($asset == $symbol_reserva)
                $accion = '';

            $arr['jsonData'] .= '
            const data_'.$asset.' = {
                        "Free": '.$tokenFree.',"Capital": '.$tokenCapital.',"Price": '.$price.
                        ',"qtyDecsUnits": '.$ai[$asset]['qtyDecsUnits'].
                        ',"qtyDecsPrice": '.$ai[$asset]['qtyDecsPrice'].
                        '};';

        }


        //Ordenes
        $capital = $bot->getCapital();
        $orders = $bot->getOrdersFull();
        $pares = [];
        $pares[''] = 'Todos';
        foreach ($orders as $rw)
            $pares[$rw['base_asset'].$rw['quote_asset']] = $rw['base_asset'].$rw['quote_asset'];
        
        $selectPar = Html::getTagSelect('par',$pares,null,array('onchange'=>'filter_par();'));
        
        $dg = new HtmlTableDg('ordenes');
        $dg->setCaption('Ordenes ejecutadas');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader($selectPar);
        $dg->addHeader('Base',null,null,'right');
        $dg->addHeader('Quote',null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');
        foreach ($capital as $base_asset => $rw)
        {
            if ($base_asset != $symbol_estable)
            {

                $row = array();
                $row[] = 'Capital';

                $row[] = '';
                $row[] = $base_asset.$symbol_estable;
                $row[] = floatval($rw['qty']);
                $row[] = toDec($rw['inUSD'],2);
                $row[] = floatval($rw['price']);

                $price = $prices[$base_asset.$symbol_estable];
                $ref = toDec((($price/$rw['price'])-1)*100,2);
                $alertClass = 'text-secondary';
                if ($rw['side'] == BotSW::SIDE_BUY)
                    if ($ref>0)
                        $alertClass = 'alert alert-success';
                    elseif ($ref<0)
                        $alertClass = 'alert alert-danger';
                if ($rw['side'] == BotSW::SIDE_SELL)
                    if ($ref<0)
                        $alertClass = 'alert alert-success';
                    elseif ($ref>0)
                        $alertClass = 'alert alert-danger';
                $row[] = '<span class="'.$alertClass.'" style="padding:0px;margin:0px;">'.$ref.'%</span> ';
                $class = ' '.$base_asset.$symbol_estable;

                $dg->addRow($row,$class); 
            }
        }

        foreach ($orders as $rw)
        {
            $row = array();
            $class = ($rw['side']==BotSW::SIDE_BUY ?'text-success':'text-danger');
        
            $row[] = '<a href="http://192.168.1.11/app.bot.verOrden+symbol='.$rw['base_asset'].$rw['quote_asset'].'&orderId='.$rw['orderId'].'" target="_blank">'.
                     ($rw['side']==BotSW::SIDE_BUY ?'Compra':'Venta').'</a>';

            $row[] = dateToStr($rw['datetime'],true);
            $row[] = $rw['base_asset'].$rw['quote_asset'];
            $row[] = floatval($rw['base_qty']);
            $row[] = ($rw['quote_asset']=='USDT'?toDec($rw['quote_qty'],2):floatval($rw['quote_qty']));
            $row[] = floatval($rw['price']);

            $price = $prices[$rw['base_asset'].$rw['quote_asset']];
            $ref = toDec((($price/$rw['price'])-1)*100,2);
            $alertClass = 'text-secondary';
            if ($rw['side'] == BotSW::SIDE_BUY)
                if ($ref>0)
                    $alertClass = 'alert alert-success';
                elseif ($ref<0)
                    $alertClass = 'alert alert-danger';
            if ($rw['side'] == BotSW::SIDE_SELL)
                if ($ref<0)
                    $alertClass = 'alert alert-success';
                elseif ($ref>0)
                    $alertClass = 'alert alert-danger';
            if ($rw['pnl_id'] == 0)
                    $row[] = '<span class="'.$alertClass.'" style="padding:0px;margin:0px;">'.$ref.'%</span>';
                else
                    $row[] = '&nbsp;';
            
            $class .= ' '.$rw['base_asset'].$rw['quote_asset'];

            $dg->addRow($row,$class);        

        }


        $arr['orders'] = $dg->get();
        $this->addView('bot/BotSW.trade',$arr);

    }

    
}
