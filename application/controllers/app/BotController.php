<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once LIB_PATH."HtmlTableFc.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Operacion.php";
include_once MDL_PATH."bot/BotSW.php";
include_once MDL_PATH."binance/BinanceAPI.php";

/**
 * Controller: BotController
 * @package SGi_Controllers
 */
class BotController extends Controller
{
    
    function operaciones($auth)
    {
        $this->addTitle('Bot');

        $bot = new BotSW();
        $ds = $bot->getActivos();
        if (!empty($ds))
        {
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
            $dg->setCaption('Bots - Smart Wallet');
            $dg->addHeader('Titulo');
            $dg->addHeader('StableCoin',null,null,'center');
            $dg->addHeader('Assets',null,null,'center');
            $dg->addHeader('Capital USD',null,null,'center');
            $dg->addHeader('Estado',null,null,'center');
            
            foreach ($ds as $rw)
            {
                $row = array();
                $row[] = '<a href="'.Controller::getLink('app','botSW','ver','id='.$rw['idbotsw']).'" >'.$rw['titulo'].'</a>';
                $row[] = $rw['strEstables'];
                $row[] = $rw['strMonedas'];
                $row[] = toDec($rw['capital']);
                $row[] = '<span class="'.$rw['strEstadoClass'].'">'.$rw['strEstado'].'</span>';
                $dg->addRow($row);
            }
            $arr['lista'] = $dg->get();
        }

        $opr = new Operacion();
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario'),'stop, auto_restart DESC, symbol');
        
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->setCaption('Bots - Apalancamiento');
        $dg->addHeader('Bot');
        $dg->addHeader('Capital',null,null,'center');
        $dg->addHeader('Compra inicial',null,null,'center');
        $dg->addHeader('Multiplicadores',null,null,'center');
        $dg->addHeader('Porcentaje de venta',null,null,'center');
        $dg->addHeader('Stop-Loss',null,null,'center');
        $dg->addHeader('Estado',null,null,'center');
        $dg->addHeader('Recompra Automatica',null,null,'center');

        if (!empty($ds))
        {
            $operacionesStop=0;
            foreach ($ds as $rw)
            {
                $opr->reset();
                $opr->set($rw);
                
                $strTipoOp = '';
                if ($opr->get('tipo')==Operacion::OP_TIPO_APLSHRT)
                    $strTipoOp = ' <span class="text-danger">'.$opr->getTipoOperacion($opr->get('tipo'),$nombrecorto=true).'</span>';
                elseif ($opr->get('tipo')==Operacion::OP_TIPO_APLCRZ)
                    $strTipoOp = ' <span class="text-success">'.$opr->getTipoOperacion($opr->get('tipo'),$nombrecorto=true).'</span>';

                $link = '<a class="" href="'.Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')).'">'.$opr->get('symbol').$strTipoOp.'</a>';

                $autoRestart = '<span class="glyphicon glyphicon-'.($opr->autoRestart()?'ok text-success':'ban-circle text-danger').'"></span>';
                $compras = $opr->get('compras');
                if ($compras < 1)
                    $strCompras = '';
                else
                    $strCompras = ' <br/>Compras x '.$compras;
                $ventas = $opr->get('ventas');
                if ($ventas < 1)
                    $strVentas = '';
                else
                    $strVentas = ' <br/>Ventas x '.$ventas;

                $stopLoss = '-';
                if ($opr->get('stop_loss'))
                {
                    $stopLoss = $opr->get('str_stop_loss');
                    if ($opr->get('max_op_perdida'))
                    {
                        $stopLoss .= '<br>'.$opr->get('str_max_op_perdida');
                    }
                }
                
                $strEstado = $opr->get('strEstado').$strCompras.$strVentas;
                
                if ($opr->get('stop'))
                    $strEstado = '<span class="text-danger">APAGADO</span>';

                $data = array($link,
                              $opr->get('capital_usd'),
                              $opr->get('inicio_usd'),
                              'x'.$opr->get('multiplicador_compra').' / '.
                              $opr->get('multiplicador_porc').'% '.
                                         ($opr->get('multiplicador_porc_inc')?' Inc':'').
                                         ($opr->get('multiplicador_porc_auto')?' Auto':''),
                              $opr->get('strPorcVenta'),
                              $stopLoss,
                              $strEstado,
                              $autoRestart
                              );

                $opClass = '';
                if ($opr->get('stop'))
                {
                    $opClass .= 'op_stop ';
                    $operacionesStop++;
                }
                if ($rw['ordenesActivas']>0)
                    $rowsActivas[] = array('data'=>$data,'class'=>$opClass);
                else
                    $rowsInactivas[] = array('data'=>$data,'class'=>$opClass.' table-secondary text-secondary');
            }
        }
        if (!empty($rowsActivas))
            foreach ($rowsActivas as $rw)
                $dg->addRow($rw['data'],$rw['class']);
        if (!empty($rowsInactivas))
            foreach ($rowsInactivas as $rw)
                $dg->addRow($rw['data'],$rw['class']);
     
        $arr['lista'] .= $dg->get();
        if ($operacionesStop>0)
            $arr['lista'] .= '<button id="toogleStopped" class="btn btn-secondary btn-block btn-sm" onclick="toogleStopOp();">Mostrar Bots apagados</button>';
        $arr['hidden'] = '';
    
        $this->addView('bot/operaciones',$arr);
    }

    function crearOperacion($auth)
    {
        $this->addTitle('Crear Operacion');

        $arr['data'] = '';
        $arr['hidden'] = '';

        $arr['tipo'] = $_REQUEST['tipo'];
        if (!isset($_REQUEST['tipo']))
            $arr['tipo'] = Operacion::OP_TIPO_APLCRZ;

        $arr['PORCENTAJE_VENTA_UP'] = toDec(Operacion::PORCENTAJE_VENTA_UP);
        $arr['PORCENTAJE_VENTA_DOWN'] = toDec(Operacion::PORCENTAJE_VENTA_DOWN);

        $opr = new Operacion();
        $arr['strTipoOp'] = $opr->getTipoOperacion($arr['tipo']);

        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
        {
            $this->addView('bot/crearOperacionShort',$arr);
        }
        else
        {
            $this->addView('bot/crearOperacion',$arr);
        }
    }   

    function editarOperacion($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Editar Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);
        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);
        $symbolData = $api->getSymbolData($opr->get('symbol'));
        $arr['symbolPrice'] = $symbolData['price'];
        $arr['quoteAsset'] = $symbolData['quoteAsset'];
        $arr['baseAsset'] = $symbolData['baseAsset'];
        $arr['qtyDecs'] = $symbolData['qtyDecs'];
        $arr['qtyDecsPrice'] = $symbolData['qtyDecsPrice'];
                
        $arr['symbol'] = $opr->get('symbol');
        $arr['capital_usd'] = $opr->get('capital_usd');
        $arr['inicio_usd'] = $opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc');
        $arr['stop_loss'] = $opr->get('stop_loss');
        $arr['max_op_perdida'] = $opr->get('max_op_perdida');
        
        if ($opr->get('multiplicador_porc_inc'))
            $arr['mpi_checked'] = 'CHECKED';
        
        if ($opr->get('multiplicador_porc_auto'))
            $arr['mpa_checked'] = 'CHECKED';
        
        if ($opr->get('destino_profit'))
            $arr['dp_selected_1'] = 'SELECTED';
        else
            $arr['dp_selected_0'] = 'SELECTED';

        $arr['idoperacion'] = $opr->get('idoperacion');
        if ($opr->get('tipo') != Operacion::OP_TIPO_APLCRZ)
            $opr->set(array('tipo'=>Operacion::OP_TIPO_APLCRZ));
        $arr['tipo'] = $opr->get('tipo');

        $arr['porc_venta_up']    = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));

        $arr['PORCENTAJE_VENTA_UP'] = toDec(Operacion::PORCENTAJE_VENTA_UP);
        $arr['PORCENTAJE_VENTA_DOWN'] = toDec(Operacion::PORCENTAJE_VENTA_DOWN);

        $tck = new Ticker($opr->get('symbol'));

        $arr['show_check_MPAuto'] = 'false';
        if ($tck->get('tickerid') == $opr->get('symbol'))
            $arr['show_check_MPAuto'] = 'true';

        $arr['strTipoOp'] = $opr->getTipoOperacion($opr->get('tipo'));

        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            $this->addView('bot/editarOperacionShort',$arr);
        else
            $this->addView('bot/editarOperacion',$arr);
    }    

    function verOperacion($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);



        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }

        if (!$opr->get('start') || !$opr->get('base_start_in_usd') || !$opr->get('quote_start_in_usd'))
            $opr->loadStartData();

        $tck = new Ticker();
        $symbolData = $tck->getSymbolData($opr->get('symbol'));
        $symbolPrice = $symbolData['price'];

        $link = $this->__selectOperacion($idoperacion,'app.bot.verOperacion+id=');
        $arr['idoperacion'] = $idoperacion;
        $arr['tipo'] = $opr->get('tipo');
        $arr['strTipo'] = '<h4 class="text-'.($arr['tipo']==Operacion::OP_TIPO_APLSHRT?'danger':'success').'">'.$opr->getTipoOperacion($opr->get('tipo')).'</h4>';
        $arr['symbolSelector'] = $link;

        if ($arr['tipo']!=Operacion::OP_TIPO_APLSHRT)
        {
            $arr['capital_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecsQuote']);
            $arr['inicio_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecsQuote']);
        }
        else
        {
            $arr['capital_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecs']);
            $arr['inicio_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecs']);
        }

        $arr['strDestinoProfit'] = 'Obtener ganancia en <b>'.($opr->get('destino_profit')?$symbolData['baseAsset']:$symbolData['quoteAsset']).'</b>';
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'').
                                     ($opr->get('multiplicador_porc_auto')?' Automatico':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));        

        $arr['stop_loss'] = $opr->get('str_stop_loss');
        $arr['str_max_op_perdida'] = $opr->get('str_max_op_perdida');

        if (!$opr->get('stop'))
        {
            $arr['estado'] = $opr->get('strEstado');
            $status = $opr->status();
            if ($opr->get('tipo')==Operacion::OP_TIPO_APL || $opr->get('tipo')==Operacion::OP_TIPO_APLCRZ)
            {
                if ($status==Operacion::OP_STATUS_APALANCAOFF)
                    $arr['estado'] .= '<br/><a class="btn btn-sm btn-warning" href="'.Controller::getLink('app','bot','resolverApalancamiento','id='.$idoperacion).'">Resolver Apalancamiento</a>';
                elseif ($status==Operacion::OP_STATUS_STOP_CAPITAL)
                    $arr['estado'] .= '<br/><a class="btn btn-sm btn-info" href="'.Controller::getLink('app','bot','resolverApalancamiento','id='.$idoperacion).'&msg=addCompra">Agregar Apalancamiento</a>';
            }
            
        }
        else
        {
            $arr['estado'] = '<b class="text-danger">APAGADO</b>';
        }

        if ($status==Operacion::OP_STATUS_READY)
        {
            if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            {
                $arr['crearOrden_btn'] .= '<br/><a class="btn btn-sm btn-danger" href="'.Controller::getLink('app','bot','crearOrdenDeVenta','id='.$idoperacion).'">Crear Nueva Venta de Compra LIMIT</a>';
                $arr['start_btn'] .= '&nbsp;<button class="btn btn-sm btn-danger" onclick="startOperacion();">Crear Nueva Orden de Venta MARKET</button>';
            }
            else
            {
                $arr['crearOrden_btn'] .= '<br/><a class="btn btn-sm btn-success" href="'.Controller::getLink('app','bot','crearOrdenDeCompra','id='.$idoperacion).'">Crear Nueva Orden de Compra LIMIT</a>';
                $arr['start_btn'] .= '&nbsp;<button class="btn btn-sm btn-success" onclick="startOperacion();">Crear Nueva Orden de Compra MARKET</button>';
            }
        }

        //if ($status==Operacion::OP_STATUS_VENTAOFF)
        //    $arr['estado'] .= '<br/><a class="btn btn-sm btn-danger" href="'.Controller::getLink('app','bot','resolverVenta','id='.$idoperacion).'">Resolver Venta</a>';

        if ($opr->autoRestart())
            $autoRestart = '<button id="arBtn" class="btn btn-sm btn-success">
                <span class="glyphicon glyphicon-ok"></span>
                </button>';
        else
            $autoRestart = '<button id="arBtn" class="btn btn-sm btn-danger">
                <span class="glyphicon glyphicon-ban-circle"></span>
                </button>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        if ($opr->get('stop'))
        {
            $arr['toogleStopText'] = 'Encender';
            $arr['toogleStopClass'] = 'success';
        }
        else
        {
            $arr['toogleStopText'] = 'Apagar';
            $arr['toogleStopClass'] = 'danger';
        }

        $order = 'price DESC';
        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            $order = 'price ASC';
        $ordenes = $opr->getOrdenes($enCurso=true,$order);

        $stopLossPrice = $opr->getStopLossPrice($symbolPrice);

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader($symbolData['baseAsset'],null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader($symbolData['quoteAsset'],null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');

        $totVentas = 0;
        $gananciaUsd = 0;
        $pnlOpenOrders = array();
        $ordenesALiquidar = 0;
        foreach ($ordenes as $rw)
        {
            $usdDecs = $symbolData['qtyDecsQuote'];
            $usd = $rw['origQty']*$rw['price'];

            if ($rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank" label="'.$rw['orderId'].'">'.$rw['sideStr'].'</a>';
            
            if ($rw['status']==Operacion::OR_STATUS_FILLED)
                $ordenesALiquidar++;

            if (!$rw['completed'] && $rw['status']==Operacion::OR_STATUS_FILLED)
                $link .= ' <span class="glyphicon glyphicon-ok" style="font-size: 0.7em;"></span>';
            
            $row = array($link,
                         $rw['updatedStr'],
                         ($rw['side']!=Operacion::SIDE_BUY?'-':'').(toDec($rw['origQty']*1,$symbolData['qtyDecs'])),
                         (toDec($rw['price']*1,$symbolData['qtyDecsPrice'])),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').toDec($usd,$usdDecs)
                        );
            if ($rw['price']>0)
            {

                $porc = toDec((($symbolPrice/$rw['price'])-1)*100,2);
                $refUSD = '';
                if ($rw['status']==Operacion::OR_STATUS_FILLED)
                {
                    $textClass = ($porc<0?'text-danger':'text-success');
                    $refUSD = $symbolData['quoteAsset'].' '.toDec(($usd * $porc) / 100,$symbolData['qtyDecsQuote']);
                }
                else
                {
                    $textClass = 'text-secondary';
                }
                $row[] = '<span class="'.$textClass.'" title="'.$refUSD.'">'.$porc.'%</span>';
            }
            else
            {
                $row[] = '&nbsp;';
            }

            $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

            //PNL Abiertas
            if ($rw['status'] == Operacion::OR_STATUS_FILLED)
            {
                if ($rw['side']==Operacion::SIDE_BUY)
                {
                    $pnlOpenOrders[] = array('origQty'=>-$rw['origQty'],'price'=>$rw['price']);
                }
                else
                {
                    $pnlOpenOrders[] = array('origQty'=>$rw['origQty'],'price'=>$rw['price']);
                }
            }
        }

        if ($stopLossPrice > 0)
        {
            $stopLossPrice = toDec($stopLossPrice,$symbolData['qtyDecsPrice']);
            $ref = toDec((($symbolPrice/$stopLossPrice)-1)*100,2).'%';
            $dg->addRow(array('Stop-Loss','','',$stopLossPrice,'', $ref));
        }

        $pnlAbiertas = 0;
        if (!empty($pnlOpenOrders))
        {
            $pnlAbiertasBase = 0;
            $pnlAbiertasQuote = 0;
            foreach ($pnlOpenOrders as $rw)
            {
                $pnlAbiertasUnits += $rw['origQty'];
                $pnlAbiertasQuote += $rw['origQty']*$rw['price'];
            }
            if ($opr->isLong())
            {
                if ($opr->get('destino_profit') == Operacion::OP_DESTINO_PROFIT_QUOTE)
                    $pnlOpenOrders[] = array('origQty'=>abs($pnlAbiertasUnits),'price'=>$symbolPrice);
                else
                    $pnlOpenOrders[] = array('origQty'=>abs($pnlAbiertasQuote/$symbolPrice),'price'=>$symbolPrice);
            }
            else
            {
                if ($opr->get('destino_profit') == Operacion::OP_DESTINO_PROFIT_QUOTE)
                    $pnlOpenOrders[] = array('origQty'=>abs($pnlAbiertasUnits)*(-1),'price'=>$symbolPrice);
                else
                    $pnlOpenOrders[] = array('origQty'=>abs($pnlAbiertasQuote/$symbolPrice)*(-1),'price'=>$symbolPrice);
            }

            foreach ($pnlOpenOrders as $rw)
            {
                if ($opr->get('destino_profit') == Operacion::OP_DESTINO_PROFIT_QUOTE)
                    $pnlAbiertas += $rw['origQty']*$rw['price'];
                else
                    $pnlAbiertas -= $rw['origQty'];
            }

        }


        $arr['ordenesActivas'] = $dg->get();

        //PNL
        $pnlOp = $opr->getPnlOperacion($idoperacion);

        if ($pnlOp['base']!=0 || $pnlOp['quote']!=0 )
        {
            $capitalReal = $opr->get('capital_usd');
            if ($opr->isLong() && $opr->get('destino_profit') != Operacion::OP_DESTINO_PROFIT_QUOTE)
                $capitalReal = $opr->get('capital_usd')/$symbolPrice;
            elseif ($opr->isShort() && $opr->get('destino_profit') == Operacion::OP_DESTINO_PROFIT_QUOTE)
                $capitalReal = $opr->get('capital_usd')*$symbolPrice;

            if ($opr->get('destino_profit') == Operacion::OP_DESTINO_PROFIT_QUOTE)
            {
                $strGanancia = $pnlOp['quote_asset'].' '.toDec($pnlOp['quote'],$pnlOp['quote_decs']);
                $pnlOp['quoteFull'] = $pnlOp['quote'];
                if ($pnlOp['base'] != 0)
                {
                    $pnlOp['quoteFull'] += $pnlOp['base']*$symbolPrice;
                    $strGanancia .= ' + '.$pnlOp['base_asset'].' '.toDec($pnlOp['base'],$pnlOp['base_decs']).'';
                }
                if ($capitalReal>0)
                {
                    $arr['pnlAbiertas'] = $pnlOp['quote_asset'].' '.toDec($pnlAbiertas,$pnlOp['quote_decs']);
                    $arr['pnlAbiertas'] .= '<br>'.toDec(($pnlAbiertas/$capitalReal)*100).'%';

                    $arr['pnlCompletas'] = $pnlOp['quote_asset'].' '.toDec($pnlOp['quoteFull'],$pnlOp['quote_decs']);
                    $arr['pnlCompletas'] .= '<br>'.toDec(($pnlOp['quoteFull']/$capitalReal)*100).'%';
                    if ($pnlOp['base'] != 0)
                        $arr['pnlCompletas'] .= '<br><small><i class="text-secondary">'.$strGanancia.'</i></small>';
                    
                    $arr['pnlGeneral'] = $pnlOp['quote_asset'].' '.toDec($pnlOp['quoteFull']+$pnlAbiertas,$pnlOp['quote_decs']);;
                    $arr['pnlGeneral'] .= '<br>'.toDec((($pnlOp['quoteFull']+$pnlAbiertas)/$capitalReal)*100).'%';
                }
            }
            else
            {
                $strGanancia = $pnlOp['base_asset'].' '.toDec($pnlOp['base'],$pnlOp['base_decs']);
                $pnlOp['baseFull'] = $pnlOp['base'];
                if ($pnlOp['quote'] != 0)
                {
                    $pnlOp['baseFull'] += $pnlOp['quote']/$symbolPrice;
                    $strGanancia .= ' + '.$pnlOp['quote_asset'].' '.toDec($pnlOp['quote'],$pnlOp['quote_decs']).'';
                }

                $arr['pnlAbiertas'] = $pnlOp['base_asset'].' '.toDec($pnlAbiertas,$pnlOp['base_decs']);
                $arr['pnlAbiertas'] .= '<br>'.toDec(($pnlAbiertas/$capitalReal)*100).'%';

                $arr['pnlCompletas'] = $pnlOp['base_asset'].' '.toDec($pnlOp['baseFull'],$pnlOp['base_decs']);
                $arr['pnlCompletas'] .= '<br>'.toDec(($pnlOp['baseFull']/$capitalReal)*100).'%';

                if ($pnlOp['quote'] != 0)
                    $arr['pnlCompletas'] .= '<br><small><i class="text-secondary">'.$strGanancia.'</i></small>';
                
                $arr['pnlGeneral'] = $pnlOp['base_asset'].' '.toDec($pnlOp['baseFull']+$pnlAbiertas,$pnlOp['base_decs']);;
                $arr['pnlGeneral'] .= '<br>'.toDec((($pnlOp['baseFull']+$pnlAbiertas)/$capitalReal)*100).'%';

            }
        }
        
        if ($ordenesALiquidar>0)
            $arr['addButtons'] = '<a class="btn btn-warning btn-sm" href="app.bot.liquidarOp+id='.$idoperacion.'">Liquidar</a>';

        $arr['symbol'] = $opr->get('symbol');

        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            $this->addView('bot/verOperacionShort',$arr);
        else
            $this->addView('bot/verOperacion',$arr);
    }    

    function apagarBot($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Apagar Bot #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }

        $tck = new Ticker();
        $symbolData = $tck->getSymbolData($opr->get('symbol'));
        $symbolPrice = $symbolData['price'];

        $link = $this->__selectOperacion($idoperacion,'app.bot.verOperacion+id=');
        $arr['idoperacion'] = $idoperacion;
        $arr['tipo'] = $opr->get('tipo');
        $arr['strTipo'] = '<h4 class="text-'.($arr['tipo']==Operacion::OP_TIPO_APLSHRT?'danger':'success').'">'.$opr->getTipoOperacion($opr->get('tipo')).'</h4>';
        $arr['symbolSelector'] = $link;

        if ($arr['tipo']!=Operacion::OP_TIPO_APLSHRT)
        {
            $arr['capital_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecsQuote']);
            $arr['inicio_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecsQuote']);
        }
        else
        {
            $arr['capital_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecs']);
            $arr['inicio_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecs']);
        }

        $arr['strDestinoProfit'] = 'Obtener ganancia en <b>'.($opr->get('destino_profit')?$symbolData['baseAsset']:$symbolData['quoteAsset']).'</b>';
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'').
                                     ($opr->get('multiplicador_porc_auto')?' Automatico':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));

        if (!$opr->get('stop'))
        {
            $arr['estado'] = $opr->get('strEstado');
            $status = $opr->status();
        }
        else
        {
            $arr['estado'] = '<b class="text-danger">APAGADO</b>';
        }

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $order = 'price DESC';
        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            $order = 'price ASC';
        $ordenes = $opr->getOrdenes($enCurso=true,$order);


        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader($symbolData['baseAsset'],null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader($symbolData['quoteAsset'],null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');

        $totVentas = 0;
        $gananciaUsd = 0;
        $pnlOpenOrders = array();
        foreach ($ordenes as $rw)
        {
            $usdDecs = $symbolData['qtyDecsQuote'];
            $usd = $rw['origQty']*$rw['price'];

            if ($rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank" label="'.$rw['orderId'].'">'.$rw['sideStr'].'</a>';
        
            if (!$rw['completed'] && $rw['status']==Operacion::OR_STATUS_FILLED)
                $link .= ' <span class="glyphicon glyphicon-ok" style="font-size: 0.7em;"></span>';
            
            $row = array($link,
                         $rw['updatedStr'],
                         ($rw['side']!=Operacion::SIDE_BUY?'-':'').(toDec($rw['origQty']*1,$symbolData['qtyDecs'])),
                         (toDec($rw['price']*1,$symbolData['qtyDecsPrice'])),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').toDec($usd,$usdDecs)
                        );
            if ($rw['price']>0)
            {

                $porc = toDec((($symbolPrice/$rw['price'])-1)*100,);
                $refUSD = '';
                if ($rw['status']==Operacion::OR_STATUS_FILLED)
                {
                    $textClass = ($porc<0?'text-danger':'text-success');
                    $refUSD = $symbolData['quoteAsset'].' '.toDec(($usd * $porc) / 100,$symbolData['qtyDecsQuote']);
                }
                else
                {
                    $textClass = 'text-secondary';
                }
                $row[] = '<span class="'.$textClass.'" title="'.$refUSD.'">'.$porc.'%</span>';
            }
            else
            {
                $row[] = '&nbsp;';
            }

            $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

        }

        $arr['ordenesActivas'] = $dg->get();

        $this->addView('bot/apagarBot',$arr);
    }      


    function liquidarOp($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Liquidar Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }

        $tck = new Ticker();
        $symbolData = $tck->getSymbolData($opr->get('symbol'));
        $symbolPrice = $symbolData['price'];

        $link = $this->__selectOperacion($idoperacion,'app.bot.verOperacion+id=');
        $arr['idoperacion'] = $idoperacion;
        $arr['tipo'] = $opr->get('tipo');
        $arr['strTipo'] = '<h4 class="text-'.($arr['tipo']==Operacion::OP_TIPO_APLSHRT?'danger':'success').'">'.$opr->getTipoOperacion($opr->get('tipo')).'</h4>';
        $arr['symbolSelector'] = $link;

        if ($arr['tipo']!=Operacion::OP_TIPO_APLSHRT)
        {
            $arr['capital_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecsQuote']);
            $arr['inicio_usd'] = $symbolData['quoteAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecsQuote']);
        }
        else
        {
            $arr['capital_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('capital_usd'),$symbolData['qtyDecs']);
            $arr['inicio_usd'] = $symbolData['baseAsset'].' '.toDec($opr->get('inicio_usd'),$symbolData['qtyDecs']);
        }

        $arr['strDestinoProfit'] = 'Obtener ganancia en <b>'.($opr->get('destino_profit')?$symbolData['baseAsset']:$symbolData['quoteAsset']).'</b>';
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'').
                                     ($opr->get('multiplicador_porc_auto')?' Automatico':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));

        if (!$opr->get('stop'))
        {
            $arr['estado'] = $opr->get('strEstado');
            $status = $opr->status();
        }
        else
        {
            $arr['estado'] = '<b class="text-danger">APAGADO</b>';
        }

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $order = 'price DESC';
        if ($arr['tipo'] == Operacion::OP_TIPO_APLSHRT)
            $order = 'price ASC';
        $ordenes = $opr->getOrdenes($enCurso=true,$order);
        if (empty($ordenes))
        {
            $this->addError('No es posible liquidar una operacion sin ordenes abiertas.');
            return false;
        }

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader($symbolData['baseAsset'],null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader($symbolData['quoteAsset'],null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');

        $totVentas = 0;
        $gananciaUsd = 0;
        $pnlOpenOrders = array();
        foreach ($ordenes as $rw)
        {
            $usdDecs = $symbolData['qtyDecsQuote'];
            $usd = $rw['origQty']*$rw['price'];

            if ($rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank" label="'.$rw['orderId'].'">'.$rw['sideStr'].'</a>';
        
            if (!$rw['completed'] && $rw['status']==Operacion::OR_STATUS_FILLED)
                $link .= ' <span class="glyphicon glyphicon-ok" style="font-size: 0.7em;"></span>';
            
            $row = array($link,
                         $rw['updatedStr'],
                         ($rw['side']!=Operacion::SIDE_BUY?'-':'').(toDec($rw['origQty']*1,$symbolData['qtyDecs'])),
                         (toDec($rw['price']*1,$symbolData['qtyDecsPrice'])),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').toDec($usd,$usdDecs)
                        );
            if ($rw['price']>0)
            {

                $porc = toDec((($symbolPrice/$rw['price'])-1)*100,);
                $refUSD = '';
                if ($rw['status']==Operacion::OR_STATUS_FILLED)
                {
                    $textClass = ($porc<0?'text-danger':'text-success');
                    $refUSD = $symbolData['quoteAsset'].' '.toDec(($usd * $porc) / 100,$symbolData['qtyDecsQuote']);
                }
                else
                {
                    $textClass = 'text-secondary';
                }
                $row[] = '<span class="'.$textClass.'" title="'.$refUSD.'">'.$porc.'%</span>';
            }
            else
            {
                $row[] = '&nbsp;';
            }

            $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

        }

        $arr['ordenesActivas'] = $dg->get();

        $this->addView('bot/liquidarOperacion',$arr);
    }      

    function revisarEstrategia($auth)    
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Revisar Estrategia');
        $this->addTitle('Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }

        $arr['idoperacion'] = $opr->get('idoperacion');
        $arr['symbol'] = $opr->get('symbol');
        $this->addView('bot/revisarEstrategia',$arr);

    }

    function estadisticas($auth)
    {
        $this->addTitle('Estadisticas');
    
        $arr['data'] = '';
        $arr['hidden'] = '';

        $opr = new Operacion();
       

        //Revision de estadisticas Diaria y Mensual


        //PNL por Operacion
        $data = $opr->getPnlOperacion();
        unset($dg);
        
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Operacion');
        $dg->addHeader('Dias Operando');
        $dg->addHeader('Capital');
        //$dg->addHeader('Destino Ganancia');
        $dg->addHeader('Ganancia');
        $dg->addHeader('% Ganancia');
        foreach ($data as $rw)
        {
            $row = array();
            $row[] = $rw['symbol'].' '.$rw['strTipo'];
            $row[] = diferenciaFechas($rw['inicio'],date('Y-m-d H:i:s'));
            $row[] = $rw['capital_asset'].' '.toDec($rw['capital'],$rw['capital_decs']);
            //$row[] = $rw['asset_profit'];
            if ($rw['base_asset']==$rw['asset_profit'])
                $row[] = $rw['base_asset'].' '.toDec($rw['realBase'],$rw['base_decs']).
                         ($rw['quote']!=0 ?'<i class="text-secondary"><small> ('.$rw['base_asset'].' '.toDec($rw['base'],$rw['base_decs']).' + '.
                          $rw['quote_asset'].' '.toDec($rw['quote'],$rw['quote_decs']).')</small></i>':'');
            else
                $row[] = $rw['quote_asset'].' '.toDec($rw['realQuote'],$rw['quote_decs']).
                         ($rw['base']!=0 ?'<i class="text-secondary"><small> ('.$rw['quote_asset'].' '.toDec($rw['quote'],$rw['quote_decs']).' + '.
                          $rw['base_asset'].' '.toDec($rw['base'],$rw['base_decs']).')</small></i>':'');
            $row[] = $rw['porc_ganancia'].'%';

            $dg->addRow($row);
        }
        $arr['lista'] .= '<h4 class="text-info">PNL Operaciones</h4>'.$dg->get();
        
        //PNL Diario
        $data = $opr->getPnlDiario();

        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $maxQtyDecs = 2;
        $dg->addHeader('Fecha');
        if (!empty($data['assets']))
        {
            foreach ($data['assets'] as $asset)
            {
                $dg->addHeader($asset,null,null,'right');
            }
        }

        $curDate = date('Y-m-d');
        $days=0;
        $iniDate = $data['iniDate'];
        if ($iniDate < date('Y-m-').'01')
            $iniDate = date('Y-m-').'01';
        while ($curDate>=$iniDate)
        {
            $days++;
            $row=array();
            $row[] = dateToStr($curDate);
            foreach ($data['assets'] as $asset)
            {
                if (toDec($data[$curDate][$asset],$data['assets_decs'][$asset]) != 0)
                    $row[] = toDec($data[$curDate][$asset],$data['assets_decs'][$asset]);
                else
                    $row[] = '';
            }
            $dg->addRow($row);
            $curDate = date('Y-m-d',strtotime($curDate.' - 1 day'));
        }

        $row=array();
        $row[] = 'Total';
        if (!empty($data['assets']))
        {
            foreach ($data['assets'] as $asset)
            {
                $row[] = toDec($data['total'][$asset],$data['assets_decs'][$asset]);
            }
        }
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= '<h4 class="text-info">PNL Diario</h4>'.$dg->get();


        //PNL Mensual
        $data = $opr->getPnlMensual();

        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Fecha');
        if (!empty($data['assets']))
        {
            foreach ($data['assets'] as $asset)
            {
                $dg->addHeader($asset,null,null,'right');
            }
        }

        $curDate = date('Y-m');
        $months=0;
        $iniMonth = $data['iniMonth'];
        while ($curDate>=$iniMonth)
        {
            $months++;
            $row=array();
            $row[] = $curDate;
            foreach ($data['assets'] as $asset)
            {
                if (toDec($data[$curDate][$asset],$data['assets_decs'][$asset])!=0)
                    $row[] = toDec($data[$curDate][$asset],$data['assets_decs'][$asset]);
                else
                    $row[] = '';
            }
            $dg->addRow($row);
            $curDate = date('Y-m',strtotime($curDate.'-01'.' -1 months'));
        }

        $row=array();
        $row[] = 'Total';
        if (!empty($data['assets']))
        {
            foreach ($data['assets'] as $asset)
            {
                $row[] = toDec($data['total'][$asset],$data['assets_decs'][$asset]);
            }
        }
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= '<h4 class="text-info">PNL Mensual</h4>'.$dg->get();
    
        $this->addView('bot/estadisticas',$arr);
    }
    
    
    function verOrden($auth)
    {
        $this->addTitle('Ver Orden Binance');

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);

        $symbol = $_REQUEST['symbol'];
        $orderId = $_REQUEST['orderId'];
                
        $orderStatus = $api->orderStatus($symbol,$orderId);
        $orderTradeInfo = $api->orderTradeInfo($symbol,$orderId);

        if ($orderStatus['executedQty'])
            $orderStatus['calculatedPrice'] = toDec($orderStatus['cummulativeQuoteQty']/$orderStatus['executedQty'],8);
        else
            $orderStatus['calculatedPrice'] = '';

        $fc = new HtmlTableFc();
        $fc->addRow(array('Symbol',$orderStatus['symbol'],'ORDER ID',$orderStatus['orderId']));
        $fc->addRow(array('Qty',$orderStatus['executedQty'],'Quote',$orderStatus['cummulativeQuoteQty']));
        $fc->addRow(array('Price',$orderStatus['calculatedPrice']));
        $fc->addRow(array('Status',$orderStatus['status']));
        $fc->addRow(array('Side - Type',$orderStatus['side'].' - '.$orderStatus['type']));
        $fc->addRow(array('Updated',date('d/m/Y H:i', ($orderStatus['updateTime']/1000) - (3 * 60 * 60))));

        $arr['data'] = $fc->get().arrayToTableDg($orderTradeInfo);
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
    
    
    function log($auth)
    {
        $this->addTitle('Log');

        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }

        $folder = LOG_PATH.'bot/';
        $logFiles=array();
        $errorFiles=array();

        $scandir = scandir($folder,SCANDIR_SORT_DESCENDING);
        foreach ($scandir as $file)
        {
            if (substr($file,0,4) == 'bot_')
            {
                $logFiles[] = $file;
            }
        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

        $status = file_get_contents($folder.'status.log');

        $timeOffset = $api->getTimeOffset();
        $status .= "\nServer Time Offset: ".toDec($timeOffset);

        $arr['files'] = '';
        if (!empty($logFiles))
        {
            $arr['files'] .='<div class="list-group">';
            foreach ($logFiles as $file)
            {
                if (!isset($firstFile))
                    $firstFile = $file;
                if (substr($file,0,9)=='bot_error')
                    $arr['files'] .= '<a class="list-group-item list-group-item-danger" id="'.$file.'" onclick="show(\''.$file.'\');">'.$file.'</a>';
                else
                    $arr['files'] .= '<a class="list-group-item list-group-item-action" id="'.$file.'" onclick="show(\''.$file.'\');">'.$file.'</a>';
            }
            $arr['files'] .='</div>';
        }

        $opr = new Operacion();
        $ds = $opr->getDataset('','symbol');
        if (!empty($ds))
        {
            foreach ($ds as $rw)
            {
                $opt_symbol[$rw['symbol']] = $rw['symbol'];
                $opt_idoperacion[$rw['idoperacion']] = $rw['idoperacion'].' - '.$rw['symbol'];
            }
            $arr['symbol_options'] = '';
            foreach ($opt_symbol as $k=>$v)
                $arr['symbol_options'] .= '<option value="'.$k.'">'.$v.'</option>';
            
            $arr['idoperacion_options'] = '';
            asort($opt_idoperacion);
            foreach ($opt_idoperacion as $k=>$v)
                $arr['idoperacion_options'] .= '<option value="'.$k.'">'.$v.'</option>';
        }

        $usr = new UsrUsuario();
        $ds = $usr->getDataset('','ayn');
        if (!empty($ds))
        {
            $arr['idusuario_options'] = '';
            foreach ($ds as $rw)
                $arr['idusuario_options'] .= '<option value="'.$rw['idusuario'].'">'. $rw['ayn'].' (#'.$rw['idusuario'].')'.'</option>';
        }                 
            
        
        $arr['status'] = nl2br($status);
        
        $arr['firstFile'] = $firstFile;
    
        $this->addView('bot/verLogs',$arr);
    }
    
    function resolverApalancamiento($auth)
    {
        $idoperacion = $_REQUEST['id'];
        if ($_REQUEST['msg']=='addCompra')
            $this->addTitle('Agregar Apalancamiento Operacion #'.$idoperacion);
        else
            $this->addTitle('Resolver Apalancamiento Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }
        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['idoperacion'] = $idoperacion;
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));
        $arr['estado'] = $opr->get('strEstado');

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $ordenes = $opr->getOrdenes($enCurso=false);

        $dgA = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dgA->addHeader('ID');
        $dgA->addHeader('Tipo');
        $dgA->addHeader('Unidades',null,null,'right');
        $dgA->addHeader('Precio',null,null,'right');
        $dgA->addHeader('USD',null,null,'right');
        $dgA->addHeader('Estado');
        $dgA->addHeader('Fecha Hora');

        $dgB = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dgB->addHeader('ID');
        $dgB->addHeader('Tipo');
        $dgB->addHeader('Unidades',null,null,'right');
        $dgB->addHeader('Precio',null,null,'right');
        $dgB->addHeader('USD',null,null,'right');
        $dgB->addHeader('Estado');
        $dgB->addHeader('Fecha Hora');

        $totVentas = 0;
        $gananciaUsd = 0;
        $maxCompraNum = 0;
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);

            if (!$rw['completed'] && $rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank">'.$rw['orderId'].'</a>';
        
            $row = array($link,
                         $rw['sideStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd,
                         $rw['statusStr'],
                         $rw['updatedStr']
                        );

            if (!$rw['completed'])
                $dgA->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);
            else
                $dgB->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

            if ($rw['completed'])
            {
                if ($rw['side']==Operacion::SIDE_SELL)
                {
                    $totVentas++;
                    $gananciaUsd += $usd;
                }
                else
                {
                    $gananciaUsd -= $usd;
                }
            }

            //Calculo de parametros propuestos
            if ($rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED && $rw['completed']<1)
            {
                $lastBuyPrice = $rw['price'];
                
                $lastUsdBuyed = ($rw['origQty']*$rw['price']);
                
                $totUnitsBuyed += $rw['origQty'];
                $totUsdBuyed += ($rw['origQty']*$rw['price']);

                if ($rw['compraNum']>$maxCompraNum)
                    $maxCompraNum = $rw['compraNum'];
        
             }


        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as);    

        $symbol = $opr->get('symbol');

        //Consulta billetera en Binance para ver si se puede recomprar
        $symbolData = $api->getSymbolData($symbol);
        $account = $api->account();
        $asset = str_replace($symbolData['quoteAsset'],'',$symbol);
        $unitsFree = '0.00';
        $unitsLocked = '0.00';
        foreach ($account['balances'] as $balances)
        {
            if ($balances['asset'] == $asset)
            {
                $unitsFree = $balances['free'];
                $unitsLocked = $balances['locked'];
            }
            if ($balances['asset'] == $symbolData['quoteAsset'])
            {
                $usdFreeToBuy = $balances['free'];
            }
        }

        $strControlUnitsBuyed = ' - totUnitsBuyed: '.$totUnitsBuyed.' - unitsFree: '.$unitsFree;
        $strControlUsdFreeToBuy = ' - usdFreeToBuy: '.$usdFreeToBuy;
        //Si la cantidad de unidades compradas segun DB es mayor a la cantidad de unidades en API
        //Toma la cantidad de unidades en la API
        if (($totUnitsBuyed*1) > ($unitsFree*1))
            $totUnitsBuyed = $unitsFree;
        
        //Orden para recompra por apalancamiento
        $multiplicador_porc = $opr->get('multiplicador_porc');
        if ($opr->get('multiplicador_porc_inc'))
            $multiplicador_porc = $multiplicador_porc*$maxCompraNum; 

        $newUsd = $lastUsdBuyed*$opr->get('multiplicador_compra');
        $newPrice = toDec($lastBuyPrice - ( ($lastBuyPrice * $multiplicador_porc) / 100 ),$symbolData['qtyDecsPrice']);
        $newQty = toDec(($newUsd/$newPrice),($symbolData['qtyDecs']*1));

        $arr['symbolPrice'] = $newPrice;
        //$newQty = toDecDown($totUnitsBuyed,$symbolData['qtyDecs']);
        $arr['qtyUSD'] = toDec($newUsd);





        $arr['ordenesActivas'] = $dgA->get();
        $arr['idoperacion'] = $opr->get('idoperacion');

        if ($opr->status() == Operacion::OP_STATUS_APALANCAOFF || $opr->status() == Operacion::OP_STATUS_STOP_CAPITAL)
            $arr['addButtons'] = '<button class="btn btn-warning btn-block" onclick="resolverApalancamiento();">Crear una nueva Orden de Compra LIMIT</button>';
        else
            $arr['addButtons'] = '<div class="alert alert-danger">No es posible resolver el apalancamiento debido a que el estado de la orden no es valido para la operacion.</div>';

        $this->addView('bot/resolverApalancamiento',$arr);
    }    

    function crearOrdenDeCompra($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Crear Orden de Compra -  Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }
        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['idoperacion'] = $idoperacion;
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));
        $arr['estado'] = $opr->get('strEstado');

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as);    

        $symbol = $opr->get('symbol');

        //Consulta billetera en Binance para ver si se puede recomprar
        $symbolData = $api->getSymbolData($symbol);

        $arr['precioActual'] = $symbolData['price'];
                
        $arr['idoperacion'] = $opr->get('idoperacion');

        if ($opr->status() == Operacion::OP_STATUS_READY)
            $arr['addButtons'] = '<button class="btn btn-warning btn-block" onclick="crearOrdenDeCompra();">Crear una nueva Orden de Compra LIMIT</button>';
        else
            $arr['addButtons'] = '<div class="alert alert-danger">No es posible resolver el apalancamiento debido a que el estado de la orden no es valido para la operacion.</div>';

        $this->addView('bot/crearOrdenDeCompra',$arr);
    }
   
    function resolverVenta($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Resolver Venta Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }
        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['idoperacion'] = $idoperacion;
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));
        $arr['estado'] = $opr->get('strEstado');

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $ordenes = $opr->getOrdenes($enCurso=false);

        $dgA = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dgA->addHeader('ID');
        $dgA->addHeader('Tipo');
        $dgA->addHeader('Unidades',null,null,'right');
        $dgA->addHeader('Precio',null,null,'right');
        $dgA->addHeader('USD',null,null,'right');
        $dgA->addHeader('Estado');
        $dgA->addHeader('Fecha Hora');

        $dgB = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dgB->addHeader('ID');
        $dgB->addHeader('Tipo');
        $dgB->addHeader('Unidades',null,null,'right');
        $dgB->addHeader('Precio',null,null,'right');
        $dgB->addHeader('USD',null,null,'right');
        $dgB->addHeader('Estado');
        $dgB->addHeader('Fecha Hora');

        $totVentas = 0;
        $gananciaUsd = 0;
        $maxCompraNum = 0;
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);

            if (!$rw['completed'] && $rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank">'.$rw['orderId'].'</a>';
        
            $row = array($link,
                         $rw['sideStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd,
                         $rw['statusStr'],
                         $rw['updatedStr']
                        );

            if (!$rw['completed'])
                $dgA->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);
            else
                $dgB->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

            if ($rw['completed'])
            {
                if ($rw['side']==Operacion::SIDE_SELL)
                {
                    $totVentas++;
                    $gananciaUsd += $usd;
                }
                else
                {
                    $gananciaUsd -= $usd;
                }
            }

            //Calculo de parametros propuestos
            if ($rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED && $rw['completed']<1)
            {
                $lastBuyPrice = $rw['price'];
                
                $lastUsdBuyed = ($rw['origQty']*$rw['price']);
                
                $totUnitsBuyed += $rw['origQty'];
                $totUsdBuyed += ($rw['origQty']*$rw['price']);

                if ($rw['compraNum']>$maxCompraNum)
                    $maxCompraNum = $rw['compraNum'];
        
             }


        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as);    

        $symbol = $opr->get('symbol');

        //Consulta billetera en Binance para ver si se puede recomprar
        $symbolData = $api->getSymbolData($symbol);
        $account = $api->account();
        $asset = str_replace($symbolData['quoteAsset'],'',$symbol);
        $unitsFree = '0.00';
        $unitsLocked = '0.00';
        foreach ($account['balances'] as $balances)
        {
            if ($balances['asset'] == $asset)
            {
                $unitsFree = $balances['free'];
                $unitsLocked = $balances['locked'];
            }
            if ($balances['asset'] == $symbolData['quoteAsset'])
            {
                $usdFreeToBuy = $balances['free'];
            }
        }

        $strControlUnitsBuyed = ' - totUnitsBuyed: '.$totUnitsBuyed.' - unitsFree: '.$unitsFree;
        $strControlUsdFreeToBuy = ' - usdFreeToBuy: '.$usdFreeToBuy;
        //Si la cantidad de unidades compradas segun DB es mayor a la cantidad de unidades en API
        //Toma la cantidad de unidades en la API
        if (($totUnitsBuyed*1) > ($unitsFree*1))
            $totUnitsBuyed = $unitsFree;
        
        //Orden para recompra por apalancamiento
        $multiplicador_porc = $opr->get('multiplicador_porc');
        if ($opr->get('multiplicador_porc_inc'))
            $multiplicador_porc = $multiplicador_porc*$maxCompraNum; 

        $newQty = toDec($totUnitsBuyed,($symbolData['qtyDecs']*1));
        if ($maxCompraNum > 1)
            $newUsd = $totUsdBuyed+$totUsdBuyed*($opr->get('real_porc_venta_down')/100);
        else
            $newUsd = $totUsdBuyed+$totUsdBuyed*($opr->get('real_porc_venta_up')/100);
        $newPrice = toDec(($newUsd/$newQty),$symbolData['qtyDecsPrice']);

        $arr['symbolPrice'] = $newPrice;
        //$newQty = toDecDown($totUnitsBuyed,$symbolData['qtyDecs']);
        $arr['qtyUnit'] = toDec($totUnitsBuyed,($symbolData['qtyDecs']*1));





        $arr['ordenesActivas'] = $dgA->get();
        $arr['idoperacion'] = $opr->get('idoperacion');

        if ($opr->status() == Operacion::OP_STATUS_VENTAOFF)
            $arr['addButtons'] = '<button class="btn btn-warning btn-block" onclick="resolverVenta();">Crear una nueva Orden de Venta LIMIT</button>';
        else
            $arr['addButtons'] = '<div class="alert alert-danger">No es posible resolver la venta debido a que el estado de la orden no es valido para la operacion.</div>';

        $this->addView('bot/resolverVenta',$arr);
    }

    function symbolEstadisticas($auth)
    {
        $this->addTitle('Estadisticas Moneda');

        $tck = new Ticker();
        $symbol = strtoupper($_REQUEST['symbol']);
        $arr['symbol'] = $symbol;
        if (!$symbol)
        {
            $this->addError('Se debe especificar el arametro symbol');
            return false;
        }

        $at = $tck->getAnalisisTecnico($symbol,'5m');
        if ($at)
        {
            //debug($at);
            foreach ($at['signal'] as $k=>$v)
                $tendencias['signal_'.$k] = $v;
            foreach ($at as $k=>$v)
                if (strstr($k,'_trend')=='_trend')
                    $tendencias[$k] = $v;
            $arr['data'] .= arrayToTable($tendencias);
        }
        else
        {
            debug($tck->getErrLog());
        }
        

        $arr['hidden'] = '';
    
        $this->addView('bot/estadisticasMoneda',$arr);
    }

    function auditarOrdenes($auth)
    {
        $this->addTitle('Auditar Ordenes');

        $idoperacion = $_REQUEST['idoperacion'];
        $idbotsw = $_REQUEST['idbotsw'];
        $opr = new Operacion($idoperacion);
        $symbol = $opr->get('symbol');
        if (!$symbol)
            $symbol = $_REQUEST['symbol'];
        $check_last = $_REQUEST['check_last'];

        $oprOrders = $opr->getOrdenes($enCurso = false);
        $audit = array();
        $lastComplete = null;
        if (!empty($oprOrders))
        {
            foreach ($oprOrders as $k => $v)
            {
                if ($v['completed'])
                {
                    $lastComplete = $v['updated'];
                    //unset($oprOrders[$k]);
                }
                $auditBot[$v['orderId']] = $v;
            }
        }

        //Buscar ordenes en BotSW
        $bsw = new BotSW();
        $bsw_orders = $bsw->auditOrders();
        if (!empty($bsw_orders))
        {
            foreach ($bsw_orders as $k => $v)
            {
                $auditBot[$v['orderId']] = $v;
            }

        }

        if ($check_last)
            $lastComplete = strToDate($check_last).' 00:00:00';
        elseif (!$lastComplete)
            $lastComplete = date('Y-m-d',strtotime('-7 days')).' 00:00:00';
        $check_last = $lastComplete;

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);  

        //Informacion de la moneda
        if ($symbol)
        {
            $symbolData = $api->getSymbolData($symbol);
            $qtyDecsPrice = $symbolData['qtyDecsPrice'];
            
            //Historico
            if ($_REQUEST['buscar'])
            {
                $ordersHst = $api->orders($symbol); 
                $show = false; 
                foreach ($ordersHst as $k => $v)
                {
                    $v['datetime'] = date('Y-m-d H:i:s',$ordersHst[$k]['updateTime']/1000);

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
                            if ($auditBot[$v['orderId']]['idbotsw'])
                                $v['idbotsw'] = $auditBot[$v['orderId']]['idbotsw'];
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
        $inputs = '<input type="hidden" id="idoperacion" value="'.$idoperacion.'">';

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
            if ($rw['bot'])
            {
                if ($rw['idbotsw'])
                    $row[] = 'Bot SW#'.$rw['idbotsw'];
                else
                    $row[] = 'OK';
            }
            else
            {
                $row[] = 'Falta';
            }
            if ($rw['bot'])
                $row[] = '&nbsp;';
            else
                $row[] = '<input type="checkbox" id="chk_'.$rw['orderId'].'" onclick="refresh();" />';

            $sql='';
            if (!$rw['bot'] && !$rw['idbotsw'])
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
        
        $arr['symbol'] = $symbol;
        if ($idoperacion)
        {
            $arr['symbol_read_only'] = 'READONLY';
            $arr['url_prms'] = 'idoperacion='.$idoperacion;
        }
        elseif ($idbotsw)
        {
            $arr['url_prms'] = 'idbotsw='.$idbotsw;
        }
        $arr['check_last'] = dateToStr($check_last);
        $arr['data'] = $dg->get();
        $arr['data'] .= $inputs;
        $arr['hidden'] = '';
    
        $this->addView('bot/auditarOrdenes',$arr);
    }
    
    
    function __selectOperacion($idoperacion,$baseLink)
    {
        $auth = UsrUsuario::getAuthInstance();
        $opr = new Operacion($idooperacion);
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario').' AND (stop = 0 OR idoperacion = '.$idoperacion.')','symbol');
        $options = array();
        foreach ($ds as $rw)
        {
            if ($rw['idoperacion'] == $idoperacion)
            {
                $symbol = $rw['symbol'];
            }

            $options[$rw['idoperacion']] = $rw['symbol'].' #'.$rw['idoperacion'];
        }

        $html = '
        <div class="dropdown dropright" >
          <a class="btn btn-primary btn-sm dropdown-toggle" href="https://www.binance.com/es/trade/'.$symbol.'" target="_blank" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-expanded="false">
            '.$symbol.'
          </a>
          ';
        if (!empty($options))
        {
            $html .= '
              <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">';
            foreach ($options as $id => $label)
            {
                $class = ($id==$idoperacion?' active ':'');

                $html .= '
                    <a style="font-size:0.7em;" class="dropdown-item btn btn-sm '.$class.'" href="'.$baseLink.$id.'">'.$label.'</a>';
            }
                
            $html .= '
              </div>';
        }
        $html .= '
        </div>';

        return $html;
    }
    

    function ordenesActivas($auth)
    {
        $this->addTitle('Ordenes Activas');

        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }
    
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);

        $prices = $api->prices();

        $opr = new Operacion();
        $ordenes = $opr->getOrdenesActivas();

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->setCaption('Ordenes activas');
        $dg->addHeader('Moneda');
        $dg->addHeader('Posicion');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader('Unidades',null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader('USD',null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');
        $dg->addHeader('Accion',null,null,'center');

        $idoperacion = 0;
        foreach ($ordenes as $rw)
        {
            $symbolPrice = $prices[$rw['symbol']];
            $usd = toDec($rw['origQty']*$rw['price']);

            if ($rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $status = '';
            if (!$rw['completed'] && $rw['status']==Operacion::OR_STATUS_FILLED)
                $status = ' <span class="glyphicon glyphicon-ok" style="font-size: 0.7em;"></span>';
            $porc = 0;
            if ($rw['side']==Operacion::SIDE_SELL || $rw['status']==Operacion::OR_STATUS_FILLED)
                $porc = toDec((($symbolPrice/$rw['price'])-1)*100);

            
            
            $rowClass = 'orden';
            if ($porc>=0 && $rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED)
            {
                $rowClass .= ' para_liquidar';
            }
            $rowClass .= ($rw['side']==Operacion::SIDE_BUY?' side_buy':' side_sell');
            
            
            $btnLiquidar = '&nbsp;';
            if (/*$porc>=1 && */$rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED)
            {
                $btnLiquidar = '<a href="'.Controller::getLink('app','bot','liquidarOrden','id='.$rw['idoperacion'].'&idoo='.$rw['idoperacionorden']).'" class="badge badge-'.($porc>=$rw['porcLimit']?'danger':'light').'">Liquidar Orden</a>';
            }
            $refUSD = toDec(($usd * $porc) / 100);
            $row = array($rw['symbol'].' #'.$rw['idoperacion'],
                         $rw['sideStr'].$status,
                         $rw['updatedStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd,
                         ($porc!=0? '<span class="'.($porc<0?'text-danger':'text-success').'" title="Ganancia USD '.$refUSD.'">'.$porc.'%</span>' : ''),
                         $btnLiquidar
                        );

            

            $dg->addRow($row,$rw['sideClass'].' '.$rowClass,null,null,$id='ord_'.$rw['orderId']);

            if ($rw['completed'])
            {
                if ($rw['side']==Operacion::SIDE_SELL)
                {
                    $totVentas++;
                    $gananciaUsd += $usd;
                }
                else
                {
                    $gananciaUsd -= $usd;
                }
            }

        }

        $arr['ordenesActivas'] = $dg->get();

        $this->addView('bot/ordenesActivas',$arr);

    }
    
    function liquidarOrden($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $idoperacionorden = $_REQUEST['idoo'];
        $this->addTitle('Liquidar Orden - Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }
        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['idoperacion'] = $idoperacion;
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'');
        $arr['porc_venta_up'] = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));
        $arr['estado'] = $opr->get('strEstado');

        if ($opr->autoRestart())
            $autoRestart = '<span class="glyphicon glyphicon-ok"></span>';
        else
            $autoRestart = '<span class="glyphicon glyphicon-ban-circle"></span>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');
        $arr['hidden'] .= Html::getTagInput('idoperacionorden',$idoperacionorden,'hidden');

        $ordenes = $opr->getOrdenes($enCurso=true);

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('ID');
        $dg->addHeader('Tipo');
        $dg->addHeader('Unidades',null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader('USD',null,null,'right');
        $dg->addHeader('Estado');
        $dg->addHeader('Fecha Hora');

        $totVentas = 0;
        $gananciaUsd = 0;
        $maxCompraNum = 0;
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);

            if (!$rw['completed'] && $rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank">'.$rw['orderId'].'</a>';
            $row = array($link,
                         $rw['sideStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd,
                         $rw['statusStr'],
                         $rw['updatedStr']
                        );

            $classRow = '';
            if ($rw['idoperacionorden'] == $idoperacionorden)
            {
                $arr['warningMsg'] = 'Se va a proceder a liquidar la orden correspondiente al OrderID <b>'.$rw['orderId'].'</b>, ejecutando una venta precio de MARKET.<br>'.
                                     'A continuacion se replantearan las ordenes de compra y venta existentes.';
                $classRow = ' table-warning';
            }
            $dg->addRow($row,$rw['sideClass'].$classRow,null,null,$id='ord_'.$rw['orderId']);

            //Calculo de parametros propuestos
            if ($rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED && $rw['completed']<1)
            {
                $lastBuyPrice = $rw['price'];
                
                $lastUsdBuyed = ($rw['origQty']*$rw['price']);
                
                $totUnitsBuyed += $rw['origQty'];
                $totUsdBuyed += ($rw['origQty']*$rw['price']);

                if ($rw['compraNum']>$maxCompraNum)
                    $maxCompraNum = $rw['compraNum'];
        
             }


        }

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $api = new BinanceAPI($ak,$as);    

        $symbol = $opr->get('symbol');

        $arr['ordenesActivas'] = $dg->get();
        $arr['idoperacion'] = $opr->get('idoperacion');

        if ($arr['warningMsg'])
            $arr['addButtons'] = '<button id="btnLiquidarOrden" class="btn btn-warning btn-block" onclick="liquidarOrden();">Liquidar Orden</button>';
        else
            $arr['warningMsg'] = 'No es posible liquidar la orden';

        $this->addView('bot/liquidarOrden',$arr);
    }        
    

    function trade($auth)
    {
        $this->addTitle('Trade');

        if ($_REQUEST['symbol'])
            $symbol = $_REQUEST['symbol'];
        else
            $symbol = 'LUNCFDUSD';

        $arr['symbol'] = $symbol;

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        $fc = new HtmlTableFc();
        
        if (empty($ak) || empty($as))
        {
            $arr['data'] = '<div class="alert alert-danger">No se encuentra registro de asociacion de la cuenta con Binance</div>';
        }
        else
        {
            $api = new BinanceAPI($ak,$as);

            //Informacion de la moneda
            $symbolData = $api->getSymbolData($symbol);
            $qtyDecsPrice  = $symbolData['qtyDecsPrice'];
            $qtyDecsUnits  = $symbolData['qtyDecs'];
            $tokenPrice    = $symbolData['price'];
            $baseAsset     = $symbolData['baseAsset'];
            $quoteAsset    = $symbolData['quoteAsset'];

            $account = $api->account();
            $qtyTokenFree = 0;
            $qtyTokenLocked = 0;
            $qtyTokenTotal = 0;
            if (!empty($account))
            {
                foreach ($account['balances'] as $asset)
                {
                    if ($asset['asset'] == $baseAsset)
                    {
                        $qtyTokenFree = $asset['free'];
                        $qtyTokenLocked = $asset['locked'];
                        $qtyTokenTotal = $asset['free']+$asset['locked'];
                    }
                }
            }

            if ($tokenPrice > 0)
            {
                $data = array('Cantidad de '.$baseAsset);
                if ($qtyTokenFree-$qtyTokenTotal == 0)
                {
                    $data[] = $qtyTokenTotal;
                }
                else
                {
                    $data[] = 'Total: <b>'.$qtyTokenTotal.'</b ><br>'.
                              'Bloqueado: <b>'.($qtyTokenLocked*1).'</b ><br>'.
                              'Disponible: <b>'.($qtyTokenFree*1).'</b ><br>';
                }
                $fc->addRow($data);

                $data = array($baseAsset.' en '.$quoteAsset,toDec($tokenPrice*$qtyTokenTotal));
                $fc->addRow($data);

            }
            
            //Historico de ordenes
            $ordersHst = $api->orders($symbol); 
            $show = false; 
            $orders = array();
            foreach ($ordersHst as $k => $v)
            {
                $v['datetime'] = date('Y-m-d H:i:s',$ordersHst[$k]['time']/1000);
                
                if ($v['datetime']<date('Y-m-d H:i:s',strtotime('-30 days')))
                    continue;

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
                
                $tradeQty = $v['executedQty'];
                if ($tradeQty > 0)
                {
                    $tradeUsd = $v['cummulativeQuoteQty'];
                    $v['qty'] = $v['executedQty'];
                    $v['price'] = toDec(toDec($tradeUsd/$tradeQty,$qtyDecsPrice),$strQtyDecs);
                    array_unshift($orders,$v);
                }
            } 
            $dg = new HtmlTableDg('tbl_orders');
            $dg->setCaption('Ordenes ejecutadas');
            $dg->addHeader('Par');
            $dg->addHeader('Operacion');
            $dg->addHeader('Fecha');
            $dg->addHeader('Precio');
            $dg->addHeader($baseAsset);
            $dg->addHeader($quoteAsset);
            $dg->addHeader('Estado');

            $qtyToken = 0;
            $qtyBase = 0;

            foreach ($orders as $rw)
            {
                //http://www.bisaro.ar/app.bot.verOrden+symbol=EURUSDT&orderId=218477961
                $linkToOrder = '<a href="app.bot.verOrden+symbol='.$symbol.'&orderId='.$rw['orderId'].'" target="_orderView">'.$rw['orderId'].'</a>';
                $row = array();
                $row[] = $symbol.' '.$linkToOrder;
                $row[] = ($rw['side']=='BUY'?'Compra':'Venta');
                $row[] = '<span title="Order ID '.$rw['orderId'].'">'.dateToStr($rw['datetime'],true).'</span>';
                $row[] = $rw['price'];
                $row[] = ($rw['side']=='SELL'?'-':'').toDec($rw['qty'],$qtyDecsUnits);
                $row[] = ($rw['side']=='BUY'?'-':'').toDec($rw['qty']*$rw['price'],$qtyDecsPrice);
                
                if ($rw['status']=='FILLED')
                {
                    if ($rw['side']=='SELL')
                    {
                        $qtyToken -= toDec($rw['qty'],$qtyDecsUnits);
                        $qtyBase += toDec($rw['qty']*$rw['price'],$qtyDecsPrice);                    
                    }
                    else
                    {
                        $qtyToken += toDec($rw['qty'],$qtyDecsUnits);
                        $qtyBase -= toDec($rw['qty']*$rw['price'],$qtyDecsPrice);                    
                    }
                    
                }

                if ($rw['status']=='NEW')
                    $row[] = 'PENDIENTE';
                elseif ($rw['status']=='FILLED')
                    $row[] = 'EJECUTADA';
                else
                    $row[] = $rw['status'];
                
                $class='';
                if ($rw['side']=='BUY')
                    $class='text-success';
                else
                    $class='text-danger';
                $dg->addRow($row,$class,$height='15px');
            }
            $footer = array('Totales','','','',toDec($qtyToken,$qtyDecsUnits),toDec($qtyBase,$qtyDecsPrice),'');
            $dg->addFooter($footer);
            $arr['orders'] = $dg->get();

        }
    
        $arr['lunabusdPrice'] = $tokenPrice;

        if ($qtyToken!=0 or $qtyBase!=0)
        {
            $balance = $qtyToken*$tokenPrice+$qtyBase;
            $fc->addRow(array('Balance en '.$quoteAsset,toDec($balance,$qtyDecsPrice)));
        }
        $arr['info'] = $fc->get();
        $arr['hidden'] = '';
    
        $this->addView('bot/trade',$arr);
    }
    
    
    
}
