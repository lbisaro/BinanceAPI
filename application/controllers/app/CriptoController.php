<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";


/**
 * Controller: AppCriptoController
 * @package SGi_Controllers
 */
class CriptoController extends Controller
{
    
    function home($auth)
    {
        $this->addTitle('Estado de Cuenta Binance');

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        if (empty($ak) || empty($as))
        {
            $arr['data'] = '<div class="alert alert-danger">No se encuentra registro de asociacion de la cuenta con Binance</div>';
        }
        else
        {
            $api = new BinanceAPI($ak,$as);
            $opr = new Operacion();

            $prices = $api->prices();

            $pnlStatus = $opr->getCompradoEnCurso();

            if (!empty($pnlStatus))
            {
                foreach ($pnlStatus as $symbol => $rw)
                {
                    $pnlStatus[$symbol]['actualPrice'] = $prices[$symbol];
                    $pnlStatus[$symbol]['actualUSD'] = $prices[$symbol]*$rw['buyedUnits'];
                    $pnlStatus[$symbol]['perc'] = (($pnlStatus[$symbol]['actualUSD']/$pnlStatus[$symbol]['buyedUSD'])-1)*100;

                }
            }
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
            $dg->addHeader('Moneda');
            $dg->addHeader('Comprado USD',null,null,'right');
            $dg->addHeader('Actual USD',null,null,'right');
            $dg->addHeader('Resultado USD',null,null,'right');
            $dg->addHeader('Resultado %',null,null,'right');
            foreach ($pnlStatus as $symbol => $rw)
            {
                $row = array();
                $row[] = $symbol;
                $row[] = toDec($rw['buyedUSD']);
                $row[] = toDec($rw['actualUSD']);
                $row[] = '<span class="text-'.(($rw['actualUSD']-$rw['buyedUSD'])>0?'success':'danger').'">'.toDec($rw['actualUSD']-$rw['buyedUSD']).'</span>';
                $row[] = '<span class="text-'.($rw['perc']>0?'success':'danger').'">'.toDec($rw['perc']).'%</span>';
                $dg->addRow($row);
                $pnlTotal['buyedUSD'] += $rw['buyedUSD'];
                $pnlTotal['actualUSD'] += $rw['actualUSD'];
                $pnlTotal['resultadoUSD'] += ($rw['actualUSD']-$rw['buyedUSD']);
            }
            if ($pnlTotal['actualUSD'] && $pnlTotal['buyedUSD'])
            {
                $pnlTotal['perc'] = (($pnlTotal['actualUSD']/$pnlTotal['buyedUSD'])-1)*100;
                $pnlTotal['perc'] = '<span class="text-'.($pnlTotal['perc']>0?'success':'danger').'">'.toDec($pnlTotal['perc']).'%</span>';
            }
            $pnlTotal['resultadoUSD'] = '<span class="text-'.($pnlTotal['resultadoUSD']>0?'success':'danger').'">'.toDec($pnlTotal['resultadoUSD']).'</span>';
            
            $dg->addFooter(array('Totales',toDec($pnlTotal['buyedUSD']),toDec($pnlTotal['actualUSD']),$pnlTotal['resultadoUSD'],$pnlTotal['perc']));

            $arr['data'] .= '<h4 class="text-info">Operaciones</h4>'.$dg->get();
            

            $account = $api->account();
            unset($dg);

            $ctrlBnb = 0;
            $ctrlBilletera = 0;
            $porcMinimoUsdEnBnb = 0.5;//%
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
                    $rw['free'] = toDec($rw['free']*$prices[$rw['asset'].'USDT']);
                    $rw['locked'] = toDec($rw['locked']*$prices[$rw['asset'].'USDT']);
                    $balance[$rw['asset']] = $rw;
                    if ($rw['asset'] == 'BNB')
                        $ctrlBnb = $rw['free'];
                }
            }

            $totTotal = 0;
            $totLocked = 0;
            $totFree = 0;
            foreach ($balance as $rw)
            {
                $total = $rw['free']+$rw['locked'];
                if ($total>0)
                {
                    $locked = $rw['locked'];
                    $free = $rw['free'];
                    $row = array();
                    $row[] = ($rw['usd_flag']?'<strong>'.$rw['asset'].'</strong>':$rw['asset']);
                    $row[] = ($total>0?$total:'');
                    $row[] = ($locked>0?$locked:'');
                    $row[] = ($free>0?$free:'');
                    $dg->addRow($row);
        
                    $totTotal += $total;
                    $totLocked += $locked;
                    $totFree += $free;
                }

            }

            $ctrlBilletera = $totTotal;

            $dg->addFooter(array('Totales',$totTotal,$totLocked,$totFree));

            $arr['data'] .= '<h4 class="text-info">Billetera</h4>'.$dg->get();
        }

        if ($ctrlBilletera>0 && (($ctrlBnb*100)/$ctrlBilletera < $porcMinimoUsdEnBnb))
        {
            $arr['alertas'] .= '<div class="alert alert-warning alert-dismissible fade show" role="alert" style="font-size:2em;" >
            <strong>ALERTA!</strong><br>
            El total de BNB expresado en dolares debe ser superior al '.$porcMinimoUsdEnBnb.'% del total de la billetera para contemplar el pago de comisiones.
            <br>
            Actualmente el porcentaje de BNB respecto al total de la billetera es de '.toDec(($ctrlBnb*100)/$ctrlBilletera).'%
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            </div>';
        }
        elseif ($ctrlBilletera>0 && (($ctrlBnb*100)/$ctrlBilletera < ($porcMinimoUsdEnBnb*2)))
        {
            $arr['alertas'] .= '<div class="alert alert-success alert-dismissible fade show" role="alert" >
            Porcentaje de BNB respecto al total de la billetera: '.toDec(($ctrlBnb*100)/$ctrlBilletera).'%
            <br>
            <small class="text-muted">El total de BNB expresado en dolares debe ser superior al '.$porcMinimoUsdEnBnb.'% del total de la billetera para contemplar el pago de comisiones.</small>            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            </div>';
        }
        

        $arr['hidden'] = '';
    
        $this->addView('cripto/home',$arr);
    }
    
    function compararPorcentaje($auth)    
    {
        $this->addTitle('Comparar %');
        $this->addView('cripto/compararPorcentaje',$arr);

    }    

    function operaciones($auth)    
    {
        $this->addTitle('Bot');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');

        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('operaciones',$arr);

    }

    function depth($auth)
    {
        $this->addTitle('Ordenes de Mercado');
        $arr = array();
        $this->addView('cripto/depth',$arr);        
    }
}
