<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."Ticker.php";

/**
 * CriptoAjax
 *
 * @package SGi_Controllers
 */
class CriptoAjax extends ControllerAjax
{
    function historico()
    {
        $prms=array();
        $tickerid = $_REQUEST['tickerid'];
        $prms['interval'] = $_REQUEST['interval'];
        $prms['limit'] = $_REQUEST['limit'];
        if ($_REQUEST['ema'])
            $prms['ema'] = $_REQUEST['ema'];
        $this->ajxRsp->setEchoOut(true);
        $tck = new Ticker();

        $ds = $tck->getHistorico($tickerid,$prms);
        echo json_encode($ds);

    }

    function depth()
    {
        $symbol = strtoupper($_REQUEST['asset'].$_REQUEST['assetQuote']);

        $tck = new Ticker();
        $data = $tck->depth($symbol);
        $dg = new HtmlTableDg();
        $dg->addHeader('Price');
        $dg->addHeader('Amount',null,null,'right');
        $dg->addHeader('Ref',null,null,'right');
        $dg->addHeader('Portion',null,null,'right');
        foreach ($data['bids'] as $price=>$rw)
        {
            $dg->addRow(array($price,toDec($rw['amount'],2,'.',','),$rw['ref'],$rw['portion']),'text-success');
        }
        $htmlTables .= '<div class="col">'.$dg->get().'</div>';

        unset($dg);
        $dg = new HtmlTableDg();
        $dg->addHeader('Price');
        $dg->addHeader('Amount',null,null,'right');
        $dg->addHeader('Ref',null,null,'right');
        $dg->addHeader('Portion',null,null,'right');
        foreach ($data['asks'] as $price=>$rw)
        {
            $dg->addRow(array($price,toDec($rw['amount'],2,'.',','),$rw['ref'],$rw['portion']),'text-danger');
        }
        $htmlTables .= '<div class="col">'.$dg->get().'</div>';

        $html = '<div class="container"><div class="row">'.$htmlTables.'</div></div>';

        $this->ajxRsp->assign('resultado','innerHTML',$html);

    }

    function grabarTicker()
    {
        $_REQUEST['tickerid'] = strtoupper($_REQUEST['tickerid']);
        if ($_REQUEST['new_tickerid'])
        {
            $tck = new Ticker();            
            $arrToSet['tickerid'] = $_REQUEST['tickerid'];

            $ds = $tck->getDataSet("tickerid ='".$_REQUEST['tickerid']."'");
            if (!empty($ds))
            {
                $this->ajxRsp->addError('El Ticker '.$_REQUEST['tickerid'].' ya se encuentra registrado');
                return false;
            }
        }
        else
        {
            $tck = new Ticker($_REQUEST['tickerid']);
        }

        $arrToSet['hst_min'] = $_REQUEST['hst_min'];
        $arrToSet['hst_max'] = $_REQUEST['hst_max'];
        $arrToSet['max_drawdown'] = $_REQUEST['max_drawdown'];

        if ($arrToSet['hst_min']<=0)
            $err[] = 'Se debe especificar un Minimo historico mayor a 0';
        
        if ($arrToSet['hst_max']<=$arrToSet['hst_min'])
            $err[] = 'Se debe especificar un Maximo historico mayor al Minimo';
        
        if ($arrToSet['max_drawdown']<6)
            $err[] = 'Se debe especificar un Drawdown Maximo mayor 6.00%';
        elseif ($arrToSet['hst_min']>0 && $arrToSet['hst_max']>0)
        {
            $mdd = (1-($arrToSet['hst_min']/$arrToSet['hst_max']))*100;
            if ($arrToSet['max_drawdown']>toDec($mdd,2))
                $err[] = 'El Drawdown Maximo no puede ser superior a '.toDec($mdd,2).'% de acuerdo a minimo y maximo historico especificado.';
        }
        if (!empty($err))
        {
            $this->ajxRsp->addError($err);
        }
        else
        {
            $tck->set($arrToSet);
            if ($tck->save())
                $this->ajxRsp->redirect(Controller::getLink('app','cripto','verTicker','id='.$tck->get('tickerid')));
            else
                $this->ajxRsp->addError($tck->getErrLog());
        }
    }

    function readTicker()
    {
        $this->ajxRsp->setEchoOut(true);

        $symbol = $_REQUEST['tickerid'];
        $interval='1d';
        $limit=(52*7);
        $startTime = null;
        $endTime = null;


        $tck = new Ticker($symbol);  
        
        $api = new BinanceAPI();        

        try {
            $candlesticks = $api->candlesticks($symbol, $interval, $limit, $startTime, $endTime);
        } catch (Throwable $e) {
            $this->errLog->add('No fue posible encontrar informacion para la moneda '.$symbol);
            return false;
        }

        $info = array();

        //Labels
        $i=0;
        $info['labels'] = array('Fecha','Open','High','Low','Close');
        $info['tickerid'] = $symbol;
        $info['interval'] = $interval;
        
        if (!empty($candlesticks))
        {
            foreach ($candlesticks as $timestamp => $candel)
            {
                $data_close[$i] = $candel['close'];

                $date = date('Y-m-d',($timestamp/1000));
                
                $info['data'][$i]['date'] = $date;
                $info['data'][$i]['open'] = (float)$candel['open'];
                $info['data'][$i]['high'] = (float)$candel['high'];
                $info['data'][$i]['low'] = (float)$candel['low'];
                $info['data'][$i]['close'] = (float)$candel['close'];
                $info['data'][$i]['hst_min'] = $tck->get('hst_min');
                $info['data'][$i]['hst_max'] = $tck->get('hst_max');

                $info['data'][$i]['hst_mid'] = ($info['data'][$i]['hst_min']+$info['data'][$i]['hst_max'])/2;
                $info['data'][$i]['hst_ter_t'] = $info['data'][$i]['hst_max'] - (($info['data'][$i]['hst_max']-$info['data'][$i]['hst_min'])/3);
                $info['data'][$i]['hst_ter_d'] = $info['data'][$i]['hst_min'] + (($info['data'][$i]['hst_max']-$info['data'][$i]['hst_min'])/3);

                $ref_perc = (($candel['close']/$info['data'][$i]['hst_min'])-1)*100;
                $info['data'][$i]['ref_perc'] = (float)toDec($ref_perc);

                //Calculo de palancas
                $palancas = $tck->calcularPalancas($candel['close']);
                foreach ($palancas['price'] as $num => $price)
                    $info['data'][$i]['pal'.$num] = $price;
                

                $i++;
            }
            
            $data_bb = trader_bbands($data_close, $periods = 20,$upper_mult = 2,$lower_mult = 2,TRADER_MA_TYPE_SMA);
            if (!empty($data_bb))
            {
                foreach ($data_bb[0] as $k => $v)
                    $info['data'][$k]['bb_h'] = (float)$v;
                foreach ($data_bb[1] as $k => $v)
                    $info['data'][$k]['bb_m'] = (float)$v;
                foreach ($data_bb[2] as $k => $v)
                    $info['data'][$k]['bb_l'] = (float)$v;
                
            }

        }



        echo json_encode($info);
    }

    function obtenerParametrosActuales()
    {
        $tickerid = $_REQUEST['tickerid'];
        $capital_usd = $_REQUEST['capital_usd'];
        $inicio_usd = $_REQUEST['inicio_usd'];
        $tck = new Ticker($tickerid);

        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as); 
        $symbolData = $api->getSymbolData($tickerid);
        $palancas = $tck->calcularPalancas($symbolData['price']);
        $qtyPalancas = count($palancas['porc']);
        $ret['palancas'] = $palancas; 
        $ret['multCompras'] = $tck->calcularMultiplicadorDeCompras($qtyPalancas,$capital_usd,$inicio_usd);
        $ret['multPorc'] = $tck->calcularMultiplicadorDePorcentaje($qtyPalancas,end($palancas['porc']));
        $ret['symbolData'] = $symbolData;

        $html = '<div class="container">';
        $html .= '<h4>Palancas</h4>';
        foreach ($palancas['porc'] as $k => $porc)
            $html .= '<span>P#'.$k.': <b>'.$porc.'%</b> P:'.$palancas['price'][$k].'</span>&nbsp;&nbsp;&nbsp;';
        
        $html .= '<h4>Multiplicador de compras</h4>';
        $html .= '<p><b>'.$ret['multCompras'].'</b></p>';
        
        $html .= '<h4>Multiplicador de porcentaje</h4>';
        $html .= '<p><b>'.$ret['multPorc'].'</b></p>';
        
        $html .= '</div>';

        $this->ajxRsp->assign('oprTable','innerHTML',$html);
        //$this->ajxRsp->script("console.log('multCompras: ".$ret['multCompras']."');");

    }
}