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

    function testAPL()
    {
        $fc = new HtmlTableFc();
        

        $test = new Test();
        $prms['multiplicadorCompra']    = $_REQUEST['multiplicadorCompra'];
        $prms['multiplicadorPorc']      = $_REQUEST['multiplicadorPorc'];
        $prms['incremental']            = ($_REQUEST['incremental']?true:false);
        $prms['porcVentaUp']            = $_REQUEST['porcVentaUp'];
        $prms['porcVentaDown']          = $_REQUEST['porcVentaDown'];

        $symbol = $_REQUEST['symbol'];
        $usdInicial = $_REQUEST['usdInicial'];
        $results = $test->testApalancamiento($symbol,$usdInicial,$prms);
        $fc->addRow(array('Saldo Inicial',toDec($results['SaldoInicial'])));
        $fc->addRow(array('Balance',toDec($results['Balance'])));
        $fc->addRow(array('Comisiones',toDec($results['Comisiones'])));
        $fc->addRow(array('Balance Final',toDec($results['BalanceFinal'])));
        $fc->addRow(array('Ganancia','<strong>'.$results['Ganancia'].'%'.'</strong>'));
        $fc->addRow(array('Operaciones',$results['Operaciones']));
        $fc->addRow(array('Apalancamiento Insuficiente',($results['apalancamientoInsuficiente']?'SI':'NO')));
        $fc->addRow(array('Maximo Apalancamiento',$results['maxCompraNum']));

        $dg = new HtmlTableDg();
        if (!empty($results['months']))
        {
            $dg->setCaption('Resultado Mensual');
            $dg->addHeader('Mes');
            $dg->addHeader('USD');
            foreach ($results['months'] as $month => $usd)
            {
                $dg->addRow(array($month,toDec($usd)));
            }
            $this->ajxRsp->assign('months','innerHTML',$dg->get());
        }
        
        unset($dg);
        $dg = new HtmlTableDg();
        if (!empty($results['orders']))
        {
            $dg->setCaption('Ordenes');
            $dg->addHeader('Fecha Hora');
            $dg->addHeader('OP#');
            $dg->addHeader('O/C',null,null,'right');
            $dg->addHeader('O/V',null,null,'right');
            $dg->addHeader('Tokens',null,null,'right');
            $dg->addHeader('Precio',null,null,'right');
            $dg->addHeader('USD',null,null,'right');
            $dg->addHeader('Balance USD',null,null,'right');
            $dg->addHeader('Balance Token',null,null,'right');
            $dg->addHeader('Comisiones',null,null,'right');
            foreach ($results['orders'] as $order)
            {
                $strOp = $order['side'].' #'.($order['side']!='BUY'?$order['operaciones']:$order['compraNum']);
                if ($order['porcCompra'])
                    $strOp .= ' -'.toDec($order['porcCompra']*100).'%';
                $row = array(strToDate($order['datetime'],true),
                             $strOp,
                             $order['ordenCompra'],
                             $order['ordenVenta'],
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
            $this->ajxRsp->assign('ordenes','innerHTML',$dg->get());
        }
        $this->ajxRsp->assign('resultado','innerHTML',$fc->get());

        
    }
}