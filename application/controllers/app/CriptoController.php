<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";


/**
 * Controller: AppCriptoController
 * @package SGi_Controllers
 */
class CriptoController extends Controller
{
    
    function home($auth)
    {
        $this->addTitle('Home');

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        if (empty($ak) || empty($as))
        {
            $arr['data'] = '<div class="alert alert-danger">No se encuentra registro de asociacion de la cuenta con Binance</div>';
        }
        else
        {
            $api = new BinanceAPI($ak,$as);
            $account = $api->account();
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
            $dg->addHeader('Asset');
            $dg->addHeader('Total',null,null,'center');
            $dg->addHeader('Bloqueado',null,null,'center');
            $dg->addHeader('Disponible',null,null,'center');
            $balance=array();
            foreach ($account['balances'] as $rw)
            {
                if ((substr($rw['asset'],0,3)=='USD' || substr($rw['asset'],-3)=='USD') && ($rw['free'] > 0 || $rw['locked'] > 0 ) )
                {
                    $rw['free'] = toDec($rw['free'],2);
                    $rw['locked'] = toDec($rw['locked'],2);
                    $rw['usd_flag'] = true;
                    $balance[$rw['asset']] = $rw;
                }
            }
            foreach ($account['balances'] as $rw)
            {
                if (!isset($balance[$rw['asset']]) && ( $rw['free'] > 0 || $rw['locked'] > 0 ) )
                {
                    $rw['free'] = $rw['free']*1;
                    $rw['locked'] = $rw['locked']*1;
                    $balance[$rw['asset']] = $rw;
                }
            }

            foreach ($balance as $rw)
            {
                $row = array();
                $row[] = ($rw['usd_flag']?'<strong>'.$rw['asset'].'</strong>':$rw['asset']);
                $row[] = $rw['free']+$rw['locked'];
                $row[] = $rw['locked'];
                $row[] = $rw['free'];
                $dg->addRow($row);
            }

            $arr['data'] = $dg->get();
        }
        

        $arr['hidden'] = '';
    
        $this->addView('cripto/home',$arr);
    }
    
    

    function variacionPrecio($auth)
    {
        $this->addTitle('Precios');
        $this->addView('variacionPrecio',$arr);
    }
    
    function compararPorcentaje($auth)    
    {
        $this->addTitle('Comparar');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');

        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('compararPorcentaje',$arr);

    }    

    function operaciones($auth)    
    {
        $this->addTitle('Operaciones');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');

        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('operaciones',$arr);

    }
}
