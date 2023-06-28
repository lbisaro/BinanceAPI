<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once LIB_PATH."HtmlTableFc.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."bot/Test.php";

/**
 * TestAjax
 *
 * @package SGi_Controllers
 */
class TestAjax extends ControllerAjax
{
    function updateKlines_1m()
    {
        $symbol = $_REQUEST['symbol'];
        $test = new Test();
        if (!$test->updateKlines_1m($symbol))
        {
            $this->ajxRsp->script('currentSymbol++;');
        }
        $status = $test->getUpdateStatus();

        $log = '<code class="text-secondary">'.date('Y-m-d H:i:s').' - Update '.$symbol.' - CantidaQty: '.$status['qtyKlines'].' - Last: '.$status['last'].'</code><br/>';
        $this->ajxRsp->prepend('log','innerHTML',$log);
        sleep(1);
        $this->ajxRsp->script('update()');

    }

    function testEstrategias()
    {
        $fc = new HtmlTableFc();
        
        $prms['multiplicadorCompra']    = $_REQUEST['multiplicadorCompra'];
        $prms['multiplicadorPorc']      = $_REQUEST['multiplicadorPorc'];
        $prms['incremental']            = ($_REQUEST['incremental']?true:false);
        $prms['porcVentaUp']            = $_REQUEST['porcVentaUp'];
        $prms['porcVentaDown']          = $_REQUEST['porcVentaDown'];
        $prms['grafico']                = ($_REQUEST['mostrar']=='grafico'?true:false);
        $prms['ordenes']                = ($_REQUEST['mostrar']=='ordenes'?true:false);

        $prms['from']                   = $_REQUEST['from'];
        if ($_REQUEST['estrategia'] == 'bot_ars')
            $prms['to']                     = date('Y-m-d H:i');
        else
            $prms['to']                     = date('Y-m-d H:i',strtotime($_REQUEST['from'].' + 90 days'));
        $test = new Test();

        $symbol = $_REQUEST['symbol'];
        $quoteInicial = $_REQUEST['quoteInicial']*1.02;
        $compraInicial = $_REQUEST['compraInicial'];

        $err=0;
        if ($compraInicial<10)
        {
            $this->ajxRsp->addError('La compra inicial debe ser mayor a 10.');
            $err++;
        }
        if ($quoteInicial<$compraInicial)
        {
            $this->ajxRsp->addError('La cantidad de USD en la billetera debe ser mayor a la compra inicial.');
            $err++;
        }
        if ($_REQUEST['estrategia'] && $prms['multiplicadorCompra']<1)
        {
            $this->ajxRsp->addError('El multiplicador de compras debe ser mayor o igual a 1.');
            $err++;
        }        
        if (!$symbol)
        {
            $this->ajxRsp->addError('Se debe seleccionar una moneda.');
            $err++;
        }
        if ($err>0)
            return false;

        if ($_REQUEST['estrategia'] == 'apalancamiento')
        {
            $results = $test->testApalancamiento($symbol,$quoteInicial,$compraInicial,$prms);
        }
        elseif ($_REQUEST['estrategia'] == 'bot_auto')
        {
            $results = $test->testBotAuto($symbol,$quoteInicial,$compraInicial,$prms);
        }
        elseif ($_REQUEST['estrategia'] == 'bot_ars')
        {
            $results = $test->testBotArs($symbol,$quoteInicial,$compraInicial,$prms);
        }
        else
        {
            $this->ajxRsp->addError('Se debe seleccionar una Estrategia.');
            return false;
        }

        //Analisis del pnlInfo
        if (!empty($results['pnlInfo']))
        {
            $totGanancia = 0;
            $totHoras = 0;
            $maxHoras = 0;
            $totCompras = 0;
            $qtyRecs = 0;
            $mapCompras = array();
            for ($q=1;$q <= $results['maxCompraNum'] ; $q++)
                $mapCompras[$q] = 0;

            $monthStart = substr($results['start'],0,7);
            $monthEnd = substr($results['end'],0,7);
            $month = $monthStart;
            while ($month<=$monthEnd)
            {
                $results['months'][$month]['ganancia'] = 0;
                $month = date('Y-m',strtotime($month.'-01 + 1 month'));
            }

            foreach ($results['pnlInfo'] as $pnl)
            {
                //Ganancia Mensual
                $month = substr($pnl['start'],0,7);
                $results['months'][$month]['ganancia'] += $pnl['ganancia'];
                $totGanancia += $pnl['ganancia'];
                $totHoras += $pnl['horas'];
                $totCompras += $pnl['qtyCompras'];
                if ($pnl['horas']>$maxHoras)
                    $maxHoras = $pnl['horas'];
                $mapCompras[$pnl['qtyCompras']]++;
                $qtyRecs++;
            }
            $promHoras = toDec($totHoras/$qtyRecs);
            $promCompras = toDec($totCompras/$qtyRecs);
        }

        $strMapCompras = '';
        foreach ($mapCompras as $qtyCompras => $qtyOp)
            $strMapCompras .= '<div class="mapCompras">Compra '.$qtyCompras.': <b>'.$qtyOp.'</b> ops.</div>';

        $qtyApIns = count($results['apalancamientoInsuficiente']);
        $porcGananciaMensualProm = toDec(((($totGanancia*100)/$results['saldoInicial'])/$results['qtyDays'])*30);
        $fc->addRow(array('Saldo Inicial',toDec($results['saldoInicial']),
                          'Balance',toDec($results['balance']),
                          'Comisiones',toDec($results['comisiones']),
                          'Balance Final',toDec($results['balanceFinal']),
                          'Resultado Balance',toDec(  (($results['balanceFinal']/$results['saldoInicial'])-1)*100  ).'%',
                           ));
        $fc->addRow(array('Ganancia Mensual Promedio','<strong>'.$porcGananciaMensualProm.'%'.'</strong>',
                          'Operaciones',$results['operaciones'],
                          'Promedio de compras',$promCompras,
                          'Apalancamiento Insuficiente',($qtyApIns?$qtyApIns:'No'),
                          'Cantidad de compras Maxima',$results['maxCompraNum']));
        $fc->addRow(array('Promedio de dias por Op.',toDec($promHoras/24,1),
                          'Maximo de dias por Op.',toDec($maxHoras/24,1),
                          'Mapa de cantidad de compras',$strMapCompras));
        
        //$fc->addRow(array(arrayToTableDg($results['pnlInfo'])));
        
        $dg = new HtmlTableDg();
        if (!empty($results['months']))
        {
            $dg->setCaption('Resultado Mensual');
            $dg->addHeader('Mes');
            $dg->addHeader('Ganancia USD',null,null,'right');
            $dg->addHeader('Porcentaje',null,null,'right');
            foreach ($results['months'] as $month => $rw)
            {
                $porcentaje = ($rw['ganancia']*100)/$results['saldoInicial'];
                $dg->addRow(array($month,toDec($rw['ganancia']),toDec($porcentaje).'%'));
            }
            $this->ajxRsp->assign('months','innerHTML',$dg->get());
        }
        
        if ($prms['ordenes'])
        {
            unset($dg);
            $dg = new HtmlTableDg();
            if (!empty($results['orders']))
            {
                $dg->setCaption('Ordenes');
                $dg->addHeader('Fecha Hora');
                $dg->addHeader('OP#');
                $dg->addHeader('Tokens',null,null,'right');
                $dg->addHeader('Precio',null,null,'right');
                $dg->addHeader('USD',null,null,'right');
                $dg->addHeader('Comisiones',null,null,'right');
                foreach ($results['orders'] as $order)
                {
                    $strOp = $order['side'];
                    if ($order['porcCompra'])
                        $strOp .= ' -'.toDec($order['porcCompra']*100).'%';

                    $order['quote'] = toDec($order['price']*$order['origQty']);
                    $row = array(dateToStr($order['datetime'],true).' '.dateToStr($order['updated'],true),
                                 $strOp.' '.$order['status'].' '.$order['type'],
                                 toDec($order['origQty'],$results['tokenDecUnits']),
                                 toDec($order['price'],$results['tokenDecPrice']),
                                 ($order['side']!='SELL'?'-':'').toDec($order['quote'],2),
                                 toDec($order['comision'],2)
                                    );
                    $classRow = '';
                    if ($order['side']=='SELL')
                        $classRow = 'text-danger';
                    elseif ($order['side']=='BUY')
                        $classRow = 'text-success';
                    $dg->addRow($row,$classRow);
                }
                $this->ajxRsp->assign('orderlist','innerHTML',$dg->get());
                //$this->ajxRsp->append('orderlist','innerHTML',arrayToTable($results['openPos']));
            }
        }
        
        if ($prms['grafico'])
        {
            $this->ajxRsp->script("$('#chartdiv').show();");
            $ds['labels'] = array('Fecha','Low','High','Billetera (USD)','Compra','Venta','Ap.Ins.');
            $ds['tickerid'] = $results['symbol'];
            $ds['interval'] = $results['interval'];
            if (!empty($results['hours']))
            {
                $i=0;
                foreach ($results['hours'] as $hour => $rw)
                {
                    $ds['data'][$i]['date'] = $hour;
                    //$ds['data'][$i]['ko'] = (float)toDec($rw['open'],$results['tokenDecPrice']);
                    //$ds['data'][$i]['kc'] = (float)toDec($rw['close'],$results['tokenDecPrice']);
                    $ds['data'][$i]['kl'] = (float)toDec($rw['low'],$results['tokenDecPrice']);
                    $ds['data'][$i]['kh'] = (float)toDec($rw['high'],$results['tokenDecPrice']);
                    //$ds['data'][$i]['bil'] = (float)toDec($rw['qtyQuote']+$rw['qtyTokenInQuote']);
                    if ($rw['buy'])
                        $ds['data'][$i]['buy'] = (float)$rw['buy'];
                    if ($rw['sell'])
                        $ds['data'][$i]['sell'] = (float)$rw['sell'];
                    if ($rw['apins'])
                        $ds['data'][$i]['apins'] = (float)$rw['apins'];

                    //Ordenes de compra y venta                            
                    if ($rw['ov'])
                        $ds['data'][$i]['ov'] = (float)$rw['ov'];
                    for ($j=1;$j<=$results['maxCompraNum'];$j++)
                        if ($rw['oc'.$j])
                            $ds['data'][$i]['oc'.$j] = (float)$rw['oc'.$j];

                    $i++;

                }
                
                $this->ajxRsp->script('maxCompraNum = '.$results['maxCompraNum'].';');
                $this->ajxRsp->script('info = '.json_encode($ds).';');
                $this->ajxRsp->script('daysGraph();');
            }
        }
        else
        {
            $this->ajxRsp->script("$('#chartdiv').hide();");
        }
        
        $this->ajxRsp->assign('resultado','innerHTML',$fc->get());

        
    }

    function testAT()
    {
        $fc = new HtmlTableFc();
        

        $test = new Test();
        $prms['multiplicadorCompra']    = $_REQUEST['multiplicadorCompra'];
        $prms['multiplicadorPorc']      = $_REQUEST['multiplicadorPorc'];
        $prms['incremental']            = ($_REQUEST['incremental']?true:false);
        $prms['porcVentaUp']            = $_REQUEST['porcVentaUp'];
        $prms['porcVentaDown']          = $_REQUEST['porcVentaDown'];
        $prms['grafico']                = ($_REQUEST['grafico']=='SI'?true:false);
        $prms['ordenes']                = ($_REQUEST['ordenes']=='SI'?true:false);
           

        $symbol = $_REQUEST['symbol'];
        $quoteInicial = $_REQUEST['quoteInicial'];
        $compraInicial = $_REQUEST['compraInicial'];

        $err=0;
        /*
        if ($compraInicial<10)
        {
            $this->ajxRsp->addError('La compra inicial debe ser mayor a 10.');
            $err++;
        }
        if ($quoteInicial<$compraInicial)
        {
            $this->ajxRsp->addError('La cantidad de USD en la billetera debe ser mayor a la compra inicial.');
            $err++;
        }
        if ($prms['multiplicadorCompra']<1)
        {
            $this->ajxRsp->addError('El multiplicador de compras debe ser mayor o igual a 1.');
            $err++;
        }        
        if (!$symbol)
        {
            $this->ajxRsp->addError('Se debe seleccionar una moneda.');
            $err++;
        }
        if ($err>0)
            return false;
        */
        if ($_REQUEST['estrategia'] == 'estandar')
        {
            $results = $test->testAT($symbol,$quoteInicial,$compraInicial,$prms);
        }
        else
        {
            $this->ajxRsp->addError('Se debe seleccionar una Estrategia.');
            return false;
        }

        //$gananciaMensualPromedio = toDec(($results['Ganancia']/$results['qtyDays'])*30);
        //$fc->addRow(array('Saldo Inicial',toDec($results['SaldoInicial']),
        //                  'Balance',toDec($results['Balance']),
        //                  'Comisiones',toDec($results['Comisiones']),
        //                  'Balance Final',toDec($results['BalanceFinal'])));
        //$fc->addRow(array('Ganancia Mensual Promedio','<strong>'.$gananciaMensualPromedio.'%'.'</strong>',
        //                  'Operaciones',$results['Operaciones'],
        //                  'Apalancamiento Insuficiente',count($results['apalancamientoInsuficiente']),
        //                  'Maximo Apalancamiento',$results['maxCompraNum']));

        $dg = new HtmlTableDg();
        if (!empty($results['months']))
        {
            $dg->setCaption('Resultado Mensual');
            $dg->addHeader('Mes');
            $dg->addHeader('Ganancia USD',null,null,'right');
            $dg->addHeader('Porcentaje',null,null,'right');
            foreach ($results['months'] as $month => $rw)
            {
                $porcentaje = ($rw['ganancia']*100)/$results['SaldoInicial'];
                $dg->addRow(array($month,toDec($rw['ganancia']),toDec($porcentaje).'%'));
            }
            $this->ajxRsp->assign('months','innerHTML',$dg->get());
        }
        
        if ($prms['ordenes'])
        {
            unset($dg);
            $dg = new HtmlTableDg();
            if (!empty($results['orders']))
            {
                $dg->setCaption('Ordenes');
                $dg->addHeader('Fecha Hora');
                $dg->addHeader('OP#');
                $dg->addHeader('Tokens',null,null,'right');
                $dg->addHeader('Precio',null,null,'right');
                $dg->addHeader('USD',null,null,'right');
                $dg->addHeader('Balance USD',null,null,'right');
                $dg->addHeader('Balance Token',null,null,'right');
                $dg->addHeader('Comisiones',null,null,'right');
                foreach ($results['orders'] as $order)
                {
                    $strOp = $order['side'].' '.$order['orderId'].' #'.($order['side']!='BUY'?$order['operaciones']:$order['compraNum']);
                    if ($order['porcCompra'])
                        $strOp .= ' -'.toDec($order['porcCompra']*100).'%';
                    $row = array(strToDate($order['datetime'],true),
                                 $strOp,
                                 toDec($order['origQty'],$results['tokenDecUnits']),
                                 toDec($order['price'],$results['tokenDecPrice']),
                                 ($order['side']=='BUY'?'-':'').toDec($order['quote'],2),
                                 toDec($order['qtyQuote'],2),
                                 toDec($order['qtyToken'],$results['tokenDecUnits']),
                                 toDec($order['comision'],2)
                                    );
                    $classRow = 'text-success';
                    if ($order['side']=='SELL')
                        $classRow = 'text-danger';
                    $dg->addRow($row,$classRow);
                }
                $this->ajxRsp->assign('orderlist','innerHTML',$dg->get());
                //$this->ajxRsp->append('orderlist','innerHTML',arrayToTable($results['openPos']));
            }
        }
        
        if ($prms['grafico'])
        {
            $this->ajxRsp->script("$('#chartdiv').show();");
            $ds[] = array('Fecha','Precio '.$symbol,'EMA Slow','EMA Fast','BB High','BB Mid','BB Low');
            if (!empty($results['hours']))
            {
                foreach ($results['hours'] as $hour => $rw)
                {
                    $ds[] = array($hour,
                                  toDec($rw['tokenPrice'],$results['tokenDecPrice']),
                                  toDec($rw['at']['ema_slow'],$results['tokenDecPrice']),
                                  toDec($rw['at']['ema_fast'],$results['tokenDecPrice']),
                                  toDec($rw['at']['bb_high'],$results['tokenDecPrice']),
                                  toDec($rw['at']['bb_mid'],$results['tokenDecPrice']),
                                  toDec($rw['at']['bb_low'],$results['tokenDecPrice'])
                              );
                }
                
                $this->ajxRsp->script('info = '.json_encode($ds).';');
                $this->ajxRsp->script('daysGraph();');
            }
        }
        else
        {
            $this->ajxRsp->script("$('#chartdiv').hide();");
        }
        
        $this->ajxRsp->assign('resultado','innerHTML',$fc->get());

        
    }
}