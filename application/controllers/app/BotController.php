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
        $arr['lista'] = $dg->get();

        $data = $opr->getEstadisticaDiaria();
        unset($dg);
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');

        $dg->addHeader('Fecha');
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
            $dg->addHeader($symbol.'['.$idoperacion.']',null,null,'right');
        $dg->addHeader('Total',null,null,'right');

        $curDate = date('Y-m-d');
        while ($curDate>=$data['iniDate'])
        {
            $row=array();
            $row[] = dateFormat($curDate,14);
            foreach ($data['operaciones'] as $idoperacion=>$symbol)
            {
                $row[] = ($data['data'][$curDate][$idoperacion] ?toDec($data['data'][$curDate][$idoperacion]) : '-');
            }
            $row[] = 'USD '.toDec($data['data'][$curDate]['total']);
            $dg->addRow($row);
            $curDate = date('Y-m-d',strtotime($curDate.' - 1 day'));
        }

        $row=array();
        $row[] = 'Total';
        foreach ($data['operaciones'] as $idoperacion=>$symbol)
        {
            $row[] = toDec($data['data']['total'][$idoperacion]);
        }
        $row[] = 'USD '.toDec($data['data']['total']['total']);
        $dg->addFooter($row,'font-weight-bold');

        $arr['lista'] .= $dg->get();
    
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
    
    
    
    
}
