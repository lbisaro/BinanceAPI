<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once LIB_PATH."HtmlTableFc.php";
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
        elseif (!$auth->isActive()) 
        {
            $arr['data'] = '<div class="alert alert-danger">Cuenta bloqueada por error en API-KEY de la cuenta con Binance</div>';
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
            $dg->addHeader(' ','text-secondary',null,'center');
            $dg->addHeader('Total',null,null,'center');
            $dg->addHeader(' ','text-secondary',null,'center');
            $dg->addHeader('Bloqueado',null,null,'center');
            $dg->addHeader(' ','text-secondary',null,'center');
            $dg->addHeader('Disponible ',null,null,'center');
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
            
            $whereIn = '';
            foreach ($account['balances'] as $rw)
            {
                if (!isset($balance[$rw['asset']]) && ( $rw['free'] > 0 || $rw['locked'] > 0 ) )
                {
                    $whereIn .= ($whereIn?',':'').'"'.$rw['asset'].'USDT'.'"'; 

                    $ticker = $rw['asset'].'USDT';
                    if (!isset($prices[$ticker]))
                        $ticker = $rw['asset'].'BUSD';
                    if (!isset($prices[$ticker]))
                        $ticker = $rw['asset'].'USDC';

                    $rw['freeT'] = $rw['free']*1;
                    $rw['lockedT'] = $rw['locked']*1;
                    $rw['free'] = toDec($rw['free']*$prices[$ticker]);
                    $rw['locked'] = toDec($rw['locked']*$prices[$ticker]);
                    $rw['qty_decs'] = 2;
                    $balance[$rw['asset']] = $rw;
                    if ($rw['asset'] == 'BNB')
                        $ctrlBnb = $rw['free'];
                }
            }

            //Buscando decimales de los tickers
            $tck = new Ticker();
            $tckDS = $tck->getDataSet('tickerid in('.$whereIn.')');
            foreach($tckDS as $k => $v)
            {
                $balance[$v['base_asset']]['qty_decs'] = $v['qty_decs_units'];
            }

            $totTotal = 0;
            $totLocked = 0;
            $totFree = 0;
            $resumen = array();
            $resumen['USD'] = 0;
            $resumen['Alt1'] = 0;
            $resumen['Alt2'] = 0;

            foreach ($balance as $rw)
            {
                $total = $rw['free']+$rw['locked'];
                $totalT = $rw['freeT']+$rw['lockedT'];

                if ($total>0)
                {
                    $locked = $rw['locked'];
                    $lockedT = $rw['lockedT'];
                    $free = $rw['free'];
                    $freeT = $rw['freeT'];
                    $row = array();
                    $row[] = ($rw['usd_flag']?'<strong>'.$rw['asset'].'</strong>':$rw['asset']);
                    $row[] = ($totalT>0?'<small>'.toDec($totalT,$rw['qty_decs']).'</small>':'');
                    $row[] = ($total>0?$total:'');
                    $row[] = ($lockedT>0?'<small>'.toDec($lockedT,$rw['qty_decs']).'</small>':'');
                    $row[] = ($locked>0?$locked:'');
                    $row[] = ($freeT>0?'<small>'.toDec($freeT,$rw['qty_decs']).'</small>':'');
                    $row[] = ($free>0?$free:'');
                    $dg->addRow($row);
        
                    $totTotal += $total;
                    $totLocked += $locked;
                    $totFree += $free;

                    if (in_array($rw['asset'], array('USDT','BUSD','USDC')))
                        $resumen['USD'] += $total;
                    elseif (in_array($rw['asset'], array('BTC','BNB','ETH')))
                        $resumen['Alt1'] += $total;
                    else
                        $resumen['Alt2'] += $total;
                }
            }

            $ctrlBilletera = $totTotal;

            $dg->addFooter(array('Total','',$totTotal,'','','',''));

            $fc = new HtmlTableFc();
            $fc->setCaption('Resumen de distribucion');
            $fc->addRow(array('Estable Coins','USD '.toDec($resumen['USD']),toDec(($resumen['USD']/$totTotal)*100).'%'));
            $fc->addRow(array('BTC + ETC + BNB','USD '.toDec($resumen['Alt1']),toDec(($resumen['Alt1']/$totTotal)*100).'%'));
            $fc->addRow(array('Alt Coins','USD '.toDec($resumen['Alt2']),toDec(($resumen['Alt2']/$totTotal)*100).'%'));


            $arr['tab_billetera'] = $dg->get().$fc->get();
            $arr['totalUSD'] = $totTotal;


            //Gestion del capital 
            $gdc = $opr->gestionDelCapital();

            unset($dg);
            $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
            $dg->addHeader('Operacion');
            $dg->addHeader('Capital',null,null,'right');
            $dg->addHeader('Ejecutado',null,null,'right');
            $dg->addHeader('Bloqueado',null,null,'right');
            $dg->addHeader('Remanente',null,null,'right');
            $remanente = array();
            if (!empty($gdc))
            {
                //LONG
                foreach ($gdc as $idoperacion=>$rw)
                {
                    if ($rw['is_long'])
                    {

                        $symbolStr = $rw['symbol'].' <span class="text-success">'.$rw['strTipo'].'</span>'.' <small>[#'.$rw['idoperacion'].']</small>';
                        $row = array($symbolStr,
                                     $rw['capital_asset'].' '.toDec($rw['capital'],$rw['qty_decs_capital']),
                                     $rw['quote_asset'].' '.toDec($rw['comprado'],$rw['qty_decs_quote']),
                                     $rw['quote_asset'].' '.toDec($rw['en_compra'],$rw['qty_decs_quote']),
                                     $rw['quote_asset'].' '.toDec($rw['remanente'],$rw['qty_decs_quote'])
                                     );
                        $dg->addRow($row);
                        $remanente[$rw['quote_asset']]['importe'] += $rw['remanente'];
                        $remanente[$rw['quote_asset']]['decs'] = $rw['qty_decs_quote'];
                    }
                }

                //SHORT
                foreach ($gdc as $idoperacion=>$rw)
                {
                    if ($rw['is_short'])
                    {
                        $symbolStr = $rw['symbol'].' <span class="text-danger">'.$rw['strTipo'].'</span>'.' <small>[#'.$rw['idoperacion'].']</small>';
                        $row = array($symbolStr,
                                     $rw['capital_asset'].' '.toDec($rw['capital'],$rw['qty_decs_capital']),
                                     $rw['base_asset'].' '.toDec($rw['vendido'],$rw['qty_decs_units']),
                                     $rw['base_asset'].' '.toDec($rw['en_venta'],$rw['qty_decs_units']),
                                     $rw['base_asset'].' '.toDec($rw['remanente'],$rw['qty_decs_units'])
                                     );
                        $dg->addRow($row);
                        $remanente[$rw['base_asset']]['importe'] += $rw['remanente'];
                        $remanente[$rw['base_asset']]['decs'] = $rw['qty_decs_units'];

                    }
                }
            }

            $arr['tab_capitalDisponible'] .= $dg->get();

            if (!empty($remanente))
            {
                foreach ($account['balances'] as $rw)
                    if(isset($remanente[$rw['asset']]))
                        $remanente[$rw['asset']]['free'] = $rw['free'];
                unset($dg);
                $dg = new HtmlTableDg(null,null,'table table-hover table-striped table-borderless');
                $dg->addHeader('Token');
                $dg->addHeader('Disponible');
                $dg->addHeader('Remanente');
                $dg->addHeader('Libre para operar');
                foreach ($remanente as $asset => $rw)
                {
                    $row = array($asset,
                                 toDec($rw['free'],$rw['decs']),
                                 toDec($rw['importe'],$rw['decs']),
                                 toDec($rw['free']-$rw['importe'],$rw['decs'])
                                );
                    $dg->addRow($row);
                }
                $arr['tab_capitalDisponible_analisis'] .= '<h4 class="text-info">Analisis sobre la gestion del capital</h4>'.$dg->get();
            }



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

        $activeTab = $auth->getConfig('cripto.estadoDeCuenta.tab');
        if ($activeTab)
            $arr['activeTab'] = $activeTab;
        else
            $arr['activeTab'] = 'compras';
    
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
        elseif ($auth->get('idusuario') == 7) 
        {
            $tickers['DOTUSDT']['sellPrice'] = 7.22;
            $tickers['DOTUSDT']['sellQty'] = 6.44;
            $tickers['MATICUSDT']['sellPrice'] = 0.361;
            $tickers['MATICUSDT']['sellQty'] = 66;
            $tickers['THETABUSD']['sellPrice'] = 1.211;
            $tickers['THETABUSD']['sellQty'] = 137;
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
