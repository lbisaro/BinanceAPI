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

        $this->ajxRsp->script('update()');

    }

    function testEstrategias()
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
        $prms['at']                     = ($_REQUEST['at']=='SI'?true:false);
            

        $symbol = $_REQUEST['symbol'];
        $usdInicial = $_REQUEST['usdInicial'];
        $compraInicial = $_REQUEST['compraInicial'];

        $err=0;
        if ($compraInicial<10)
        {
            $this->ajxRsp->addError('La compra inicial debe ser mayor a 10.');
            $err++;
        }
        if ($usdInicial<$compraInicial)
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

        if ($_REQUEST['estrategia'] == 'apalancamiento')
        {
            $results = $test->testApalancamiento($symbol,$usdInicial,$compraInicial,$prms);
        }
        elseif ($_REQUEST['estrategia'] == 'grid')
        {
            $results = $test->testGrid($symbol,$usdInicial,$compraInicial,$prms);
        }
        else
        {
            $this->ajxRsp->addError('Se debe seleccionar una Estrategia.');
            return false;
        }

        $gananciaMensualPromedio = toDec(($results['Ganancia']/$results['qtyDays'])*30);
        $fc->addRow(array('Saldo Inicial',toDec($results['SaldoInicial']),
                          'Balance',toDec($results['Balance']),
                          'Comisiones',toDec($results['Comisiones']),
                          'Balance Final',toDec($results['BalanceFinal'])));
        $fc->addRow(array('Ganancia Mensual Promedio','<strong>'.$gananciaMensualPromedio.'%'.'</strong>',
                          'Operaciones',$results['Operaciones'],
                          'Apalancamiento Insuficiente',count($results['apalancamientoInsuficiente']),
                          'Maximo Apalancamiento',$results['maxCompraNum']));

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
                                 toDec($order['qty'],$results['tokenDecUnits']),
                                 toDec($order['price'],$results['tokenDecPrice']),
                                 ($order['side']=='BUY'?'-':'').toDec($order['usd'],2),
                                 toDec($order['qtyUsd'],2),
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
            $ds[] = array('Fecha','Billetera (USD)','Total USD',$symbol.' (USD)','Precio '.$symbol,'Compra','Venta','AT Compra','AT Venta','Ap.Ins.');
            if (!empty($results['hours']))
            {
                foreach ($results['hours'] as $hour => $rw)
                {
                    if ($rw['at']  == 'C')
                        $at_compra  = toDec($rw['tokenPrice']*1.01,$results['tokenDecPrice']);
                    else
                        $at_compra  = null;

                    if ($rw['at']  == 'V')
                        $at_venta  = toDec($rw['tokenPrice']*0.99,$results['tokenDecPrice']);
                    else
                        $at_venta  = null;

                    $ds[] = array($hour,
                                  toDec($rw['qtyUsd']+$rw['qtyTokenInUsd']),
                                  toDec($rw['qtyUsd']),
                                  toDec($rw['qtyTokenInUsd']),
                                  toDec($rw['tokenPrice'],$results['tokenDecPrice']),
                                  ($rw['buy'] ? toDec($rw['buy']) : null),
                                  ($rw['sell'] ? toDec($rw['sell']) : null),
                                  $at_compra,
                                  $at_venta,
                                  $rw['apins']
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
        $usdInicial = $_REQUEST['usdInicial'];
        $compraInicial = $_REQUEST['compraInicial'];

        $err=0;
        /*
        if ($compraInicial<10)
        {
            $this->ajxRsp->addError('La compra inicial debe ser mayor a 10.');
            $err++;
        }
        if ($usdInicial<$compraInicial)
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
            $results = $test->testAT($symbol,$usdInicial,$compraInicial,$prms);
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
                                 toDec($order['qty'],$results['tokenDecUnits']),
                                 toDec($order['price'],$results['tokenDecPrice']),
                                 ($order['side']=='BUY'?'-':'').toDec($order['usd'],2),
                                 toDec($order['qtyUsd'],2),
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