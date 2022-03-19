<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
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

        $this->ajxRsp->assign('resultado','innerHTML',arrayToTable($data));

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

        $tck->set($arrToSet);
        if ($tck->save())
            $this->ajxRsp->redirect(Controller::getLink('app','cripto','verTicker','id='.$tck->get('tickerid')));
        else
            $this->ajxRsp->addError($tck->getErrLog());

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

                $ref_perc = (($candel['close']/$info['data'][$i]['hst_mid'])-1)*100;
                $info['data'][$i]['ref_perc'] = (float)toDec($ref_perc);
                

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
}