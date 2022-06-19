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
    
    function estadoDeCuenta($auth)
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

            //Compras
            $prices = $api->prices();

            $pnlStatus = $opr->getCompradoEnCurso();

            if (!empty($pnlStatus))
            {
                foreach ($pnlStatus as $symbol => $rw)
                {
                    $pnlStatus[$symbol]['actualPrice'] = $prices[$symbol];
                    $pnlStatus[$symbol]['actualUSD'] = $prices[$symbol]*$rw['buyedUnits'];
                    $pnlStatus[$symbol]['buyedUnits'] = $rw['buyedUnits'];
                    $pnlStatus[$symbol]['perc'] = (($pnlStatus[$symbol]['actualUSD']/$pnlStatus[$symbol]['buyedUSD'])-1)*100;
                    $pnlStatus[$symbol]['stop'] = $rw['stop'];

                }
            }
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
            $dg->addHeader('Moneda');
            $dg->addHeader('Comprado USD',null,null,'right');
            $dg->addHeader('Comprado Token',null,null,'right');
            $dg->addHeader('Actual USD',null,null,'right');
            $dg->addHeader('Resultado USD',null,null,'right');
            $dg->addHeader('Resultado %',null,null,'right');
            foreach ($pnlStatus as $symbol => $rw)
            {
                $row = array();
                $symbolClass = ($rw['stop']?'secondary':'info');
                $row[] = '<span class="text-'.$symbolClass.'">'.$symbol.'</span>';
                $row[] = toDec($rw['buyedUSD']);
                $row[] = $rw['buyedUnits'];
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
            
            $dg->addFooter(array('Totales',toDec($pnlTotal['buyedUSD']),'&nbsp',toDec($pnlTotal['actualUSD']),$pnlTotal['resultadoUSD'],$pnlTotal['perc']));

            $arr['tab_compras'] = $dg->get();
            
            
            //Billetera
            $account = $api->account();
            unset($dg);

            $ctrlBnb = 0;
            $ctrlBilletera = 0;
            $porcMinimoUsdEnBnb = 0.25;//%
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
            $dg->addHeader('Asset');
            $dg->addHeader('Total',null,null,'center');
            $dg->addHeader('Bloqueado',null,null,'center');
            $dg->addHeader('Disponible',null,null,'center');
            $balance=array();
            foreach ($account['balances'] as $rw)
            {
                if ((substr($rw['asset'],0,3)=='USD' || substr($rw['asset'],-3)=='USD' ) && ($rw['free'] > 0 || $rw['locked'] > 0 ) )
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
                    $ticker = $rw['asset'].'USDT';
                    if (!isset($prices[$ticker]))
                        $ticker = $rw['asset'].'BUSD';
                    if (!isset($prices[$ticker]))
                        $ticker = $rw['asset'].'USDC';

                    $rw['free'] = toDec($rw['free']*$prices[$ticker]);
                    $rw['locked'] = toDec($rw['locked']*$prices[$ticker]);
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

            $arr['tab_billetera'] = $dg->get();


            //Gestion del capital
            unset($dg);
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
            $dg->addHeader('Operacion');
            $dg->addHeader('Capital<br>USD',null,null,'right');
            $dg->addHeader('Comprado<br>USD',null,null,'right');
            $dg->addHeader('Bloqueado<br>USD',null,null,'right');
            $dg->addHeader('Remanente<br>USD',null,null,'right');
            $dg->addHeader('Venta<br>%',null,null,'right');

            $gdc = $opr->gestionDelCapital();
            
            $autoRestartOffIcon = '<span class="badge badge-danger"><span class="glyphicon glyphicon-ban-circle"></span></span> ';
            $autoRestartOnIcon = '';//'<span class="badge badge-success"><span class="glyphicon glyphicon-ok"></span></span>';
            
            $total = array();
            $total['capital'] = 0;
            $total['comprado'] = 0;
            $total['bloqueado'] = 0;
            $total['remanente'] = 0;
            if (!empty($gdc))
            {
                foreach ($gdc as $idoperacion=>$rw)
                {
                    $symbolStr = '<span class="text-'.($rw['auto_restart']?'primary':'secondary').'">'.$rw['symbol'].'</span>'.' <small>[#'.$rw['idoperacion'].']</small>';
                    $row = array($symbolStr,
                                 toDec($rw['capital']),
                                 toDec($rw['comprado']),
                                 toDec($rw['bloqueado']),
                                 toDec($rw['remanente']),
                                 toDec($rw['porc_venta'])
                                 );
                    $dg->addRow($row);
                    $total['capital'] += $rw['capital'];
                    $total['comprado'] += $rw['comprado'];
                    $total['bloqueado'] += $rw['bloqueado'];
                    $total['remanente'] += $rw['remanente'];
                }
            }

            $row = array('TOTAL',
                         toDec($total['capital']),
                         toDec($total['comprado']),
                         toDec($total['bloqueado']),
                         toDec($total['remanente']),
                         '&nbsp;'
                        );
            $dg->addFooter($row);

            $freeUSD=0;
            foreach ($balance as $asset=>$rw)
            {
                if ((substr($asset,0,3)=='USD' || substr($asset,-3)=='USD') )
                {
                    $freeUSD += $rw['free'];
                }
            }

            $capitalFree = toDec($freeUSD-$total['remanente']);
            $htmlResultado = '<h4 class="text-info">Analisis sobre la gestion del capital</h4>';
            $htmlResultado .= '<p>Capital disponible: <b>USD '.toDec($freeUSD).'</b></p>';
            $htmlResultado .= '<p>Remanente para ordenes de compra: <b>USD '.toDec($total['remanente']).'</b></p>';
            $htmlResultado .= '<p>Capital libre para operaciones: <b class="'.($capitalFree<0?'text-danger':'text-success').'">USD '.$capitalFree.'</b></p>';

            $arr['tab_capitalDisponible'] = $dg->get();
            $arr['tab_capitalDisponible_analisis'] = $htmlResultado;



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
    
        $this->addView('cripto/estadoDeCuenta',$arr);
    }
    
    function compararPorcentaje($auth)    
    {
        $this->addTitle('Comparar %');
        $this->addView('cripto/compararPorcentaje',$arr);

    }    

    function depth($auth)
    {
        $this->addTitle('Ordenes de Mercado');
        $arr = array();
        $this->addView('cripto/depth',$arr);        
    }

    function tickers($auth)
    {
        $this->addTitle('Tickers');
    
        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }
    
        $tck = new Ticker();
        $ds = $tck->getDataSet('','tickerid');

        $dg = new HtmlTableDg();
        $dg->addHeader('Ticker');
        $dg->addHeader('Min.Hst',null,null,'right');
        $dg->addHeader('Max.Hst',null,null,'right');
        $dg->addHeader('Max.Drawdown',null,null,'right');

        if (!empty($ds))
        {
            foreach ($ds as $rw)
            {
                $tck->reset();
                $tck->set($rw);
                $link = '<a href="'.Controller::getLink('app','cripto','verTicker','id='.$rw['tickerid']).'">'.$rw['tickerid'].'</a>';
                $row = array($link,
                             $tck->get('hst_min'),
                             $tck->get('hst_max'),
                             $tck->get('max_drawdown').'%');
                $dg->addRow($row);
            }
        }

    
        $arr['data'] = $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('cripto/tickers',$arr);
    }

    function verTicker($auth)
    {
        $this->addTitle('Ver Ticker');
        
        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }
    
        $tck = new Ticker($_REQUEST['id']);
        if ($_REQUEST['id']  && $tck->get('tickerid')!=$_REQUEST['id'])
        {
            $this->addError('Se debe especificar un ID valido.');
            return null;
        }

        $arr['tickerid'] = $tck->get('tickerid');
        $arr['hst_min'] = $tck->get('hst_min');
        $arr['hst_max'] = $tck->get('hst_max');
        $arr['max_drawdown'] = $tck->get('max_drawdown');
    
        $arr['data'] = '';
    
        $this->addView('cripto/tickerVer',$arr);
    }

    function editarTicker($auth)
    {
        if ($_REQUEST['id'])
            $this->addTitle('Editar Ticker');
        else
            $this->addTitle('Crear Ticker');
    
        if (!$auth->isAdmin())
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return null;
        }
    
        $tck = new Ticker($_REQUEST['id']);
        if ($_REQUEST['id']  && $tck->get('tickerid')!=$_REQUEST['id'])
        {
            $this->addError('Se debe especificar un ID valido.');
            return null;
        }
        
        if (!$_REQUEST['id'])
            $arr['hidden'] = Html::getTagInput('new_tickerid','new','hidden');
        else
            $arr['readonly'] = 'READONLY';

        $arr['tickerid'] = $tck->get('tickerid');
        $arr['hst_min'] = $tck->get('hst_min');
        $arr['hst_max'] = $tck->get('hst_max');
        $arr['max_drawdown'] = $tck->get('max_drawdown');
    
        $arr['data'] = '';
    
        $this->addView('cripto/tickerEditar',$arr);
    }


    function checkVentas($auth)
    {
        $this->addTitle('Check Ventas');
        
        if ($auth->get('idusuario') == 1)
        {
            $tickers['ARUSDT']['sellPrice'] = 8.44;
            $tickers['ARUSDT']['sellQty'] = 62.76;
            $tickers['AVAXUSDT']['sellPrice'] = 14.84;
            $tickers['AVAXUSDT']['sellQty'] = 12.58;
            $tickers['DOTUSDT']['sellPrice'] = 6.47;
            $tickers['DOTUSDT']['sellQty'] = 40.62;
            $tickers['GLMRUSDT']['sellPrice'] = 0.8389;
            $tickers['GLMRUSDT']['sellQty'] = 148.3;
            $tickers['GRTUSDT']['sellPrice'] = 0.0981;
            $tickers['GRTUSDT']['sellQty'] = 2406;
            $tickers['LINKUSDT']['sellPrice'] = 5.41;
            $tickers['LINKUSDT']['sellQty'] = 65.12;
            $tickers['MANAUSDT']['sellPrice'] = 0.7602;
            $tickers['MANAUSDT']['sellQty'] = 853.0;
            $tickers['MATICUSDT']['sellPrice'] = 0.413;
            $tickers['MATICUSDT']['sellQty'] = 1673.4;
            $tickers['NEARUSDT']['sellPrice'] = 3.159;
            $tickers['NEARUSDT']['sellQty'] = 84.9;
            $tickers['OCEANUSDT']['sellPrice'] = 0.1837;
            $tickers['OCEANUSDT']['sellQty'] = 3023;
            $tickers['SOLUSDT']['sellPrice'] = 26.64;
            $tickers['SOLUSDT']['sellQty'] = 5.67;
            $tickers['THETAUSDT']['sellPrice'] = 0.962;
            $tickers['THETAUSDT']['sellQty'] = 266.6;
            $tickers['TRXUSDT']['sellPrice'] = 0.05447;
            $tickers['TRXUSDT']['sellQty'] = 20940;
        }
        elseif ($auth->get('idusuario') == 2) 
        {
            $tickers['ARUSDT']['sellPrice'] = 8.575;
            $tickers['ARUSDT']['sellQty'] = 141.61;
            $tickers['AVAXUSDT']['sellPrice'] = 14.94;
            $tickers['AVAXUSDT']['sellQty'] = 61.17;
            $tickers['MATICUSDT']['sellPrice'] = 0.415;
            $tickers['MATICUSDT']['sellQty'] = 3699.9;
            $tickers['THETAUSDT']['sellPrice'] = 0.986;
            $tickers['THETAUSDT']['sellQty'] = 2347.8;
        }

        $api = new BinanceAPI();
        $prices = $api->prices();
        
        foreach ($tickers as $ticker => $rw)
        {
            $tickers[$ticker]['price'] = $prices[$ticker];
            $tickers[$ticker]['sellUSD'] = toDec($rw['sellPrice']*$rw['sellQty']);
            $tickers[$ticker]['porc'] = toDec((($prices[$ticker]/$rw['sellPrice'])-1)*100);   
        }

        $dg = new HtmlTableDg();
        $dg->addHeader('Ticker');
        $dg->addHeader('OP Venta');
        $dg->addHeader('USD Venta');
        $dg->addHeader('Cambio');
        $dg->addHeader('USD Actual');
        $totUSDVenta = 0;
        $totUSDActual = 0;
        foreach ($tickers as $ticker => $rw)
        {
            $usdActual = toDec($rw['price']*$rw['sellQty']);
            $row = array($ticker,
                         $rw['sellPrice'].' x '.$rw['sellQty'],
                         $rw['sellUSD'],
                         '<span class="text-'.($rw['porc']>0?'success':'danger').'">'.$rw['porc'].'%</span>',
                         $usdActual
                        );
            $totUSDVenta += $rw['sellUSD'];
            $totUSDActual += $usdActual;
            $dg->addRow($row);
        }
        $dg->addFooter(array('Totales','',toDec($totUSDVenta),'',toDec($totUSDActual)),'table-secondary');


    
        $arr['data'] = $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
    
    

    
}
