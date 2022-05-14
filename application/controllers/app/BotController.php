
<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once LIB_PATH."HtmlTableFc.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Operacion.php";
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

        $opr = new Operacion();
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario'),'auto_restart DESC, symbol');
        
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->addHeader('Moneda');
        $dg->addHeader('Capital',null,null,'center');
        $dg->addHeader('Compra inicial',null,null,'center');
        $dg->addHeader('Multiplicadores',null,null,'center');
        $dg->addHeader('Porcentaje de venta',null,null,'center');
        $dg->addHeader('Estado',null,null,'center');
        $dg->addHeader('Recompra Automatica',null,null,'center');

        if (!empty($ds))
        {
            foreach ($ds as $rw)
            {
                $opr->reset();
                $opr->set($rw);
                $link = '<a class="" href="'.Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')).'">'.$opr->get('symbol').'</a>';
                $autoRestart = '<span class="glyphicon glyphicon-'.($opr->autoRestart()?'ok text-success':'ban-circle text-danger').'"></span>';
                $compras = $opr->get('compras');
                if ($compras < 1)
                    $strCompras = '';
                else
                    $strCompras = ' <br/>Compras x '.$compras;
                $data = array($link,
                              $opr->get('capital_usd'),
                              $opr->get('inicio_usd'),
                              'x'.$opr->get('multiplicador_compra').' / '.
                              $opr->get('multiplicador_porc').'% '.
                                         ($opr->get('multiplicador_porc_inc')?' Inc':'').
                                         ($opr->get('multiplicador_porc_auto')?' Auto':''),
                              $opr->get('strPorcVenta'),
                              $opr->get('strEstado').$strCompras,
                              $autoRestart
                              );
                if ($rw['ordenesActivas']>0)
                    $dg->addRow($data);
                else
                    $inactivas[] = $data;
            }
        }
        if (!empty($inactivas))
        {
            foreach ($inactivas as $data)
                $dg->addRow($data,'table-secondary text-secondary');
        }

        $arr['lista'] = $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('bot/operaciones',$arr);
    }

    function crearOperacion($auth)
    {
        $this->addTitle('Crear Operacion');

        $arr['data'] = '';
        $arr['hidden'] = '';

        $arr['PORCENTAJE_VENTA_UP'] = toDec(Operacion::PORCENTAJE_VENTA_UP);
        $arr['PORCENTAJE_VENTA_DOWN'] = toDec(Operacion::PORCENTAJE_VENTA_DOWN);

        $this->addView('bot/crearOperacion',$arr);
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

        $arr['symbol'] = $opr->get('symbol');
        $arr['capital_usd'] = $opr->get('capital_usd');
        $arr['inicio_usd'] = $opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc');
        if ($opr->get('multiplicador_porc_inc'))
            $arr['mpi_checked'] = 'CHECKED';
        if ($opr->get('multiplicador_porc_auto'))
            $arr['mpa_checked'] = 'CHECKED';

        $arr['idoperacion'] = $opr->get('idoperacion');

        $arr['porc_venta_up']    = toDec($opr->get('real_porc_venta_up'));
        $arr['porc_venta_down'] = toDec($opr->get('real_porc_venta_down'));

        $arr['PORCENTAJE_VENTA_UP'] = toDec(Operacion::PORCENTAJE_VENTA_UP);
        $arr['PORCENTAJE_VENTA_DOWN'] = toDec(Operacion::PORCENTAJE_VENTA_DOWN);

        $tck = new Ticker($opr->get('symbol'));
        $arr['show_check_MPAuto'] = 'false';
        if ($tck->get('tickerid') == $opr->get('symbol'))
            $arr['show_check_MPAuto'] = 'true';

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

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);

        $symbolData = $api->getSymbolData($opr->get('symbol'));
        $symbolPrice = $symbolData['price'];

        $link = $this->__selectOperacion($idoperacion,'app.bot.verOperacion+id=');
        $arr['idoperacion'] = $idoperacion;
        $arr['symbolSelector'] = $link;
        $arr['capital_usd'] = 'USD '.$opr->get('capital_usd');
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
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
            if ($status==Operacion::OP_STATUS_APALANCAOFF)
                $arr['estado'] .= '<br/><a class="btn btn-sm btn-warning" href="'.Controller::getLink('app','bot','resolverApalancamiento','id='.$idoperacion).'">Resolver Apalancamiento</a>';
            elseif ($status==Operacion::OP_STATUS_STOP_CAPITAL)
                $arr['estado'] .= '<br/><a class="btn btn-sm btn-info" href="'.Controller::getLink('app','bot','resolverApalancamiento','id='.$idoperacion).'&msg=addCompra">Agregar Apalancamiento</a>';
            
        }
        else
        {
            $arr['estado'] = '<b class="text-danger">FUERA DE REVISION DEL BOT</b>';
        }
        if ($status==Operacion::OP_STATUS_READY)
        {
            $arr['crearOrdenDeCompra_btn'] .= '<br/><a class="btn btn-sm btn-success" href="'.Controller::getLink('app','bot','crearOrdenDeCompra','id='.$idoperacion).'">Crear Nueva Orden de Compra LIMIT</a>';
            $arr['start_btn'] .= '&nbsp;<button class="btn btn-sm btn-success" onclick="startOperacion();">Crear Nueva Orden de Compra MARKET</button>';
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
            $arr['toogleStopText'] = 'Reanudar';
            $arr['toogleStopClass'] = 'success';
        }
        else
        {
            $arr['toogleStopText'] = 'Quitar de revision';
            $arr['toogleStopClass'] = 'danger';
        }

        $ordenes = $opr->getOrdenes($enCurso=true,'price DESC');

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
        $dg->addHeader('Tipo');
        $dg->addHeader('Fecha Hora');
        $dg->addHeader('Unidades',null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader('USD',null,null,'right');
        $dg->addHeader('Ref.',null,null,'right');

        $totVentas = 0;
        $gananciaUsd = 0;
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);

            if ($rw['side']==Operacion::SIDE_BUY)
                $rw['sideStr'] .= ' #'.$rw['compraNum'];

            $link = '<a href="app.bot.verOrden+symbol='.$opr->get('symbol').'&orderId='.$rw['orderId'].'" target="_blank" label="'.$rw['orderId'].'">'.$rw['sideStr'].'</a>';
        
            if (!$rw['completed'] && $rw['status']==Operacion::OR_STATUS_FILLED)
                $link .= ' <span class="glyphicon glyphicon-ok" style="font-size: 0.7em;"></span>';
            
            $row = array($link,
                         $rw['updatedStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd
                        );
            if ($rw['price']>0 && (
                ($rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED)
                || 
                ($rw['side']==Operacion::SIDE_SELL && $rw['status']==Operacion::OR_STATUS_NEW)
                ))
            {
                $porc = toDec((($symbolPrice/$rw['price'])-1)*100);
                $row[] = '<span class="'.($porc<0?'text-danger':'text-success').'">'.$porc.'%</span>';
            }
            else
            {
                $row[] = '&nbsp;';
            }

            $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);

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
        
        if ($opr->status() == Operacion::OP_STATUS_ERROR)
            $arr['addButtons'] = '<a class="btn btn-danger btn-sm" href="app.bot.detenerOperacion+id={{idoperacion}}">Detener</a>';

        $this->addView('bot/verOperacion',$arr);
    }    

    function detenerOperacion($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Detener Operacion #'.$idoperacion);

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

        }

        $arr['ordenesActivas'] = $dgA->get();
        $arr['idoperacion'] = $opr->get('idoperacion');

        if ($opr->status() == Operacion::OP_STATUS_ERROR)
            $arr['addButtons'] = '<button class="btn btn-danger btn-block" onclick="detenerOperacion();">Detener la operacion para finalizarla manualmente en Binance.com</button>';

        $this->addView('bot/detenerOperacion',$arr);
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

        //Diaria 
        $data = $opr->getEstadisticaDiaria();
        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Fecha');
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                if (substr($symbol,-4) == 'USDT' || substr($symbol,-4) == 'USDC' || substr($symbol,-4) == 'BUSD')
                    $strSymbol = substr($symbol,0,-4).'<br>'.substr($symbol,-4);
                $dg->addHeader($strSymbol,null,null,'right');
            }
        }
        $dg->addHeader('Total<br>USD',null,null,'right');

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
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = $data[$curDate][$symbol];
            }
            $row[] = toDec($data[$curDate]['total']);
            $dg->addRow($row);
            $curDate = date('Y-m-d',strtotime($curDate.' - 1 day'));
        }

        $row=array();
        $row[] = 'Total';
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = toDec($data['total'][$symbol]);
            }
        }
        $row[] = 'USD '.toDec($data['total']['total']);
        $dg->addFooter($row,'font-weight-bold');

        $row=array();
        $row[] = 'Promedio diario sobre '.$days.' dia'.($days>1?'s':'');
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = toDec($data['total'][$symbol]/$days);
            }
        }
        if ($days>0)
            $row[] = 'USD '.toDec($data['total']['total']/$days);
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= '<h4 class="text-info">Resultado sobre ventas Diarias</h4>'.$dg->get();


        //Mensual 
        $data = $opr->getEstadisticaMensual();

        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Mes');
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                if (substr($symbol,-4) == 'USDT' || substr($symbol,-4) == 'USDC' || substr($symbol,-4) == 'BUSD')
                    $strSymbol = substr($symbol,0,-4).'<br>'.substr($symbol,-4);
                $dg->addHeader($strSymbol,null,null,'right');
            }
        }
        $dg->addHeader('Total<br>USD',null,null,'right');

        $curDate = date('Y-m');
        $months=0;
        $iniMonth = $data['iniMonth'];
        while ($curDate>=$iniMonth)
        {
            $months++;
            $row=array();
            $row[] = $curDate;
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = $data[$curDate][$symbol];
            }
            $row[] = toDec($data[$curDate]['total']);
            $dg->addRow($row);
            $curDate = date('Y-m',strtotime($curDate.'-01'.' -1 months'));
        }

        $row=array();
        $row[] = 'Total';
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = toDec($data['total'][$symbol]);
            }
        }
        $row[] = 'USD '.toDec($data['total']['total']);
        $dg->addFooter($row,'font-weight-bold');

        $row=array();
        $row[] = 'Promedio mensual sobre '.$months.' mes'.($months>1?'es':'');
        if (!empty($data['symbols']))
        {
            foreach ($data['symbols'] as $symbol)
            {
                $row[] = toDec($data['total'][$symbol]/$months);
            }
        }
        if ($days>0)
            $row[] = 'USD '.toDec($data['total']['total']/$months);
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= '<h4 class="text-info">Resultado sobre ventas Mensuales</h4>'.$dg->get();

        //Historico de operaciones

        $data = $opr->getEstadisticaGeneral();

        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Op#');
        $dg->addHeader('Moneda');
        $dg->addHeader('Ventas',null,null,'center');
        //$dg->addHeader('Compras',null,null,'center');
        //$dg->addHeader('Apalancamientos',null,null,'center');
        $dg->addHeader('Ganancia',null,null,'right');
        $dg->addHeader('Inicio',null,null,'center');
        //$dg->addHeader('Fin',null,null,'center');
        $dg->addHeader('Dias Activo',null,null,'center');
        $dg->addHeader('Promedio Dia',null,null,'right');
        if (!empty($data['operaciones']))
        {
            foreach ($data['operaciones'] as $rw)
            {
                $row = array($rw['idoperacion'],
                             $rw['symbol'],
                             $rw['ventas'],
                             //$rw['compras'],
                             //$rw['apalancamientos'],
                             'USD '.toDec($rw['ganancia_usd']),
                             dateToStr($rw['start'],true).' hs.',
                             //dateToStr($rw['end'],true).' hs.',
                             $rw['days'],
                             'USD '.toDec($rw['avg_usd_day'],2)
                            );
                $dg->addRow($row);
            }
        }


        $row = array('',
             'TOTALES',
             $data['totales']['ventas'],
             //$data['totales']['compras'],
             //$data['totales']['apalancamientos'],
             'USD '.toDec($data['totales']['ganancia_usd']),
             dateToStr($data['totales']['start'],true).' hs.',
             //dateToStr($data['totales']['end'],true).' hs.',
             $data['totales']['days'],
             'USD '.toDec($data['totales']['avg_usd_day'],2)
            );
        $dg->addFooter($row,'font-weight-bold');
        $arr['lista'] .= '<h4 class="text-info">Historico de Operaciones</h4>'.$dg->get();


    
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

        pr($symbol);
        pr($orderId);
        pr($orderStatus);
        pr($orderTradeInfo);

        $arr['data'] = '';
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
            if ($file != '.' && $file != '..' && $file != 'status.log' && $file != 'lock.status')
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

        $idoperacion = $_REQUEST['id'];
        $opr = new Operacion($idoperacion);
        $symbol = $opr->get('symbol');

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
        if (!$lastComplete)
            $lastComplete = '2021-06-01 00:00:00';

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        
        $api = new BinanceAPI($ak,$as);  

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

            $sql='';
            if (!$rw['bot'])
            {
                //Preparando SQL
                $side = ($rw['side']=='BUY'?'0':'1');
                $status = ($rw['status']=='FILLED'?'10':'0');
                if ($rw['status']=='NEW')
                {
                    $sql = "INSERT INTO operacion_orden (idoperacion,side,status,origQty,price,orderId,updated) VALUES ".
                            "(".$idoperacion.",".$side.",".$status.",".$rw['origQty'].",".$rw['price'].",'".$rw['orderId']."','".$rw['datetime']."');<br>";
                }
                else
                {
                    $sql = "INSERT INTO operacion_orden (idoperacion,side,status,origQty,price,orderId,updated,completed,pnlDate) VALUES ".
                            "(".$idoperacion.",".$side.",".$status.",".$rw['origQty'].",".$rw['price'].",'".$rw['orderId']."','".$rw['datetime']."',1,'".date('Y-m-d H:i:s')."');<br>";
                }
            }
            $row[] = $sql;
            $class='';
            if (!$rw['bot'] && $rw['status']=='NEW')
                $class='text-success';
            elseif (!$rw['bot'] && $rw['status']!='NEW')
                $class='text-primary';
            $dg->addRow($row,$class);
        }
        
        $arr['data'] = $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
    
    
    function __selectOperacion($idoperacion,$baseLink)
    {
        $auth = UsrUsuario::getAuthInstance();
        $opr = new Operacion($idooperacion);
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario'),'symbol');
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
        $dg->addHeader('ID');
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
            if ($porc>=2 && $rw['side']==Operacion::SIDE_BUY && $rw['status']==Operacion::OR_STATUS_FILLED)
            {
                $btnLiquidar = '<a href="'.Controller::getLink('app','bot','liquidarOrden','id='.$rw['idoperacion'].'&idoo='.$rw['idoperacionorden']).'" class="badge badge-danger">Liquidar Orden</a>';
            }
            $refUSD = toDec(($usd * $porc) / 100);
            $row = array($rw['symbol'].' #'.$rw['idoperacion'],
                         $rw['orderId'].$status,
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
    

    function lunabusd($auth)
    {
        $this->addTitle('LUNA-BUSD');

        $symbol = 'LUNABUSD';

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
            $qtyDecsPrice = $symbolData['qtyDecsPrice'];
            $qtyDecsUnits = $symbolData['qtyDecs'];
            $lunaBusdPrice = $symbolData['price'];

            $account = $api->account();
            $qtyLunaFree = 0;
            $qtyLunaLocked = 0;
            $qtyLunaTotal = 0;
            if (!empty($account))
            {
                foreach ($account['balances'] as $asset)
                {
                    if ($asset['asset'] == 'LUNA')
                    {
                        $qtyLunaFree = $asset['free'];
                        $qtyLunaLocked = $asset['locked'];
                        $qtyLunaTotal = $asset['free']+$asset['locked'];
                    }
                }
            }

            if ($lunaBusdPrice > 0)
            {
                $data = array('Unidades en Billetera');
                if ($qtyLunaFree-$qtyLunaTotal == 0)
                {
                    $data[] = $qtyLunaTotal;
                }
                else
                {
                    $data[] = 'Total: <b>'.$qtyLunaTotal.'</b ><br>'.
                              'Bloqueado: <b>'.($qtyLunaLocked*1).'</b ><br>'.
                              'Disponible: <b>'.($qtyLunaFree*1).'</b ><br>';
                }
                $fc->addRow($data);

                $data = array('USD',toDec($lunaBusdPrice*$qtyLunaTotal));
                $fc->addRow($data);

            }
            
            //Historico de ordenes
            $ordersHst = $api->orders($symbol); 
            $show = false; 
            $orders = array();
            foreach ($ordersHst as $k => $v)
            {
                $v['datetime'] = date('Y-m-d H:i:s',$ordersHst[$k]['time']/1000);
                
                if ($v['datetime']<'2022-05-12 00:00:00')
                    continue;
                if ($v['status'] == 'CANCELED')
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
                if ($v['datetime'] >= $lastComplete && $v['status']!='CANCELED' && $v['status']!='EXPIRED')
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
                }
                array_unshift($orders,$v);
            } 
            $dg = new HtmlTableDg('tbl_orders');
            $dg->setCaption('Ordenes ejecutadas');
            $dg->addHeader('Fecha');
            $dg->addHeader('Precio');
            $dg->addHeader('Cantidad');
            $dg->addHeader('USD');
            $dg->addHeader('Estado');

            foreach ($orders as $rw)
            {
                $row = array();
                $row[] = '<span title="Order ID '.$rw['orderId'].'">'.dateToStr($rw['datetime'],true).'</span>';
                $row[] = $rw['price'];
                $row[] = toDec($rw['origQty'],$qtyDecsUnits);
                $row[] = toDec($rw['origQty']*$rw['price']);
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
            $arr['orders'] = $dg->get();

        }
    
        $arr['lunabusdPrice'] = $lunaBusdPrice;
        $arr['info'] = $fc->get();
        $arr['hidden'] = '';
    
        $this->addView('bot/lunabusd',$arr);
    }
    
    
    
}
