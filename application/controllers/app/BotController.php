<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
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
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario'),'symbol');
        
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->addHeader('Moneda');
        $dg->addHeader('Cantidad de USD compra inicial',null,null,'center');
        $dg->addHeader('Multiplicador Compras',null,null,'center');
        $dg->addHeader('Multiplicador Porcentajes',null,null,'center');
        $dg->addHeader('Estado',null,null,'center');
        $dg->addHeader('Recompra Automatica',null,null,'center');

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
                          $opr->get('inicio_usd'),
                          $opr->get('multiplicador_compra'),
                          $opr->get('multiplicador_porc').
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':''),
                          $opr->get('strEstado').$strCompras,
                          $autoRestart
                          );
            $dg->addRow($data);
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
        $arr['inicio_usd'] = $opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc');
        if ($opr->get('multiplicador_porc_inc'))
            $arr['mpi_selected_1'] = 'SELECTED';
        else
            $arr['mpi_selected_0'] = 'SELECTED';
        $arr['idoperacion'] = $opr->get('idoperacion');

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

        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['idoperacion'] = $idoperacion;
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%'.
                                     ($opr->get('multiplicador_porc_inc')?' Incremental':'');
        $arr['estado'] = $opr->get('strEstado');
        $status = $opr->status();
        if ($status==Operacion::OP_STATUS_APALANCAOFF)
            $arr['estado'] .= '<br/><a class="btn btn-sm btn-warning" href="'.Controller::getLink('app','bot','resolverApalancamiento','id='.$idoperacion).'">Resolver Apalancamiento</a>';

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
        $arr['ordenesCompletas'] = $dgB->get();

        $arr['est_totVentas'] = $totVentas;
        $arr['est_gananciaUsd'] = toDec($gananciaUsd,2);
        //$gananciaPorc = ((($opr->get('inicio_usd')+$gananciaUsd) / $opr->get('inicio_usd')) -1) * 100;
        //$arr['est_gananciaPorc'] = toDec($gananciaPorc,2).'%';

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

        $data = $opr->getEstadisticaGeneral();
        //debug($data);

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
        $dg->addHeader('Promedio Ganancia Diaria',null,null,'right');
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
        $arr['lista'] = '<h4 class="text-info">Historico de Operaciones</h4>'.$dg->get();

        //Revision de estadisticas Diaria y Mensual

        $data = $opr->getEstadisticaDiaria();

        //Diaria 
        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Fecha');
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            if (substr($symbol,-4) == 'USDT' || substr($symbol,-4) == 'USDC' || substr($symbol,-4) == 'BUSD')
                $strSymbol = substr($symbol,0,-4).'<br>'.substr($symbol,-4);
            $dg->addHeader('<span title="Operacion #'.$idoperacion.'">'.$strSymbol.'<span>',null,null,'right');
        }
        $dg->addHeader('Total',null,null,'right');

        $curDate = date('Y-m-d');
        $days=0;
        while ($curDate>=date('Y-m-').'01')
        {
            $days++;
            $row=array();
            $row[] = dateToStr($curDate);
            foreach ($data['operaciones'] as $idoperacion=>$symbol)
            {
                $row[] = ($data['data']['d'][$curDate][$idoperacion] ?toDec($data['data']['d'][$curDate][$idoperacion]) : '-');
            }
            $row[] = 'USD '.toDec($data['data']['d'][$curDate]['total']);
            $dg->addRow($row);
            $curDate = date('Y-m-d',strtotime($curDate.' - 1 day'));
        }

        $row=array();
        $row[] = 'Total';
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            $row[] = toDec($data['data']['d']['total'][$idoperacion]);
        }
        $row[] = 'USD '.toDec($data['data']['d']['total']['total']);
        $dg->addFooter($row,'font-weight-bold');

        $row=array();
        $row[] = 'Promedio diario';
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            $row[] = toDec($data['data']['d']['total'][$idoperacion]/$days);
        }
        $row[] = 'USD '.toDec($data['data']['d']['total']['total']/$days);
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= '<h4 class="text-info">Resultado sobre ventas Diarias</h4>'.$dg->get();



        //Mensual 
        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Mes');
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            if (substr($symbol,-4) == 'USDT' || substr($symbol,-4) == 'USDC' || substr($symbol,-4) == 'BUSD')
                $strSymbol = substr($symbol,0,-4).'<br>'.substr($symbol,-4);
            $dg->addHeader('<span title="Operacion #'.$idoperacion.'">'.$strSymbol.'<span>',null,null,'right');
        }
        $dg->addHeader('Total',null,null,'right');

        $curMonth = date('Y-m');
        $month=0;
        while ($curMonth>=date('Y-m',strtotime($data['iniDate'])))
        {
            $month++;
            $row=array();
            $row[] = $curMonth;
            foreach ($data['operaciones'] as $idoperacion=>$symbol)
            {
                $row[] = ($data['data']['m'][$curMonth][$idoperacion] ?toDec($data['data']['m'][$curMonth][$idoperacion]) : '-');
            }
            $row[] = 'USD '.toDec($data['data']['m'][$curMonth]['total']);
            $dg->addRow($row);
            $curMonth = date('Y-m',strtotime($curMonth.' - 1 month'));
        }

        $row=array();
        $row[] = 'Total';
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            $row[] = toDec($data['data']['m']['total'][$idoperacion]);
        }
        $row[] = 'USD '.toDec($data['data']['m']['total']['total']);
        $dg->addFooter($row,'font-weight-bold');


        $arr['lista'] .= '<h4 class="text-info">Resultado sobre ventas Mensuales</h4>'.$dg->get();
    
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

        $folder = LOG_PATH.'bot/';
        $logFiles=array();
        $errorFiles=array();

        $scandir = scandir($folder,SCANDIR_SORT_DESCENDING);
        foreach ($scandir as $file)
        {
            if ($file != '.' && $file != '..' && $file != 'status.log')
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
                $arr['idusuario_options'] .= '<option value="'.$rw['idusuario'].'">'. $rw['ayn'].'</option>';
        }                 
            
        
        $arr['status'] = nl2br($status);
        
        $arr['firstFile'] = $firstFile;
    
        $this->addView('bot/verLogs',$arr);
    }
    
    function resolverApalancamiento($auth)
    {
        $idoperacion = $_REQUEST['id'];
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

        if ($opr->status() == Operacion::OP_STATUS_APALANCAOFF)
            $arr['addButtons'] = '<button class="btn btn-warning btn-block" onclick="resolverApalancamiento();">Crear una nueva Orden de Compra LIMIT</button>';
        else
            $arr['addButtons'] = '<div class="alert alert-danger">No es posible resolver el apalancamiento debido a que el estado de la orden no es valido para la operacion.</div>';

        $this->addView('bot/resolverApalancamiento',$arr);
    }
    
    
}
