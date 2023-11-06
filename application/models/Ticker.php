<?php
include_once LIB_PATH."ModelDB.php";
include_once LIB_PATH."trade_functions.php";
include_once MDL_PATH."binance/BinanceAPI.php";

class Ticker extends ModelDB
{
    protected $query = "SELECT *
                        FROM tickers";

    protected $pKey  = 'tickerid';

    protected $newTicker = '';

    public $presetDecs = array();

    function __Construct($id=null)
    {
        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'tickers','tickerid');

        $this->presetDecs['USDT'] = 2;
        $this->presetDecs['BUSD'] = 2;
        $this->presetDecs['USDC'] = 2;
        $this->presetDecs['BNB'] = 4;
        $this->presetDecs['BTC'] = 6;
        $this->presetDecs['ETH'] = 5;

        if($id)
            $this->load($id);
    }

    function get($field)
    {
        if ($field == 'hst_min')
            return toDec($this->data['hst_min'],$this->data['qty_decs_price']);
        if ($field == 'hst_max')
            return toDec($this->data['hst_max'],$this->data['qty_decs_price']);
        return parent::get($field);
    }

    function getLabel($field)
    {
        return parent::getLabel($field);
    }

    function getInput($field,$value=null)
    {
        if (!$value)
            $value = $this->data[$field];

        return parent::getInput($field);
    }

    function validReglasNegocio()
    {
        $err=null;

        // Control de errores
        $this->data['tickerid'] = strtoupper($this->data['tickerid']);

        if (!$this->data['tickerid'])
            $err[] = 'Se debe especificar un Ticker valido'.$this->data['tickerid'];

        // FIN - Control de errores

        if (!empty($err))
        {
            $this->errLog->add($err);
            return false;
        }
        return true;
    }

    function save()
    {
        $err   = 0;
        $isNew = false;
        
        $ds = $this->getDataSet("tickerid = '".$this->data['tickerid']."'");
        if (empty($ds))
            $isNew = true;
        
        if (!$this->valid())
        {
            return false;
        }

        //Verificando el ticker en Binance
        $api = new BinanceAPI(); 

        $symbolData = $api->getSymbolData($this->data['tickerid']);
        $this->data['qty_decs_units'] = $symbolData['qtyDecs'];
        $this->data['qty_decs_price'] = $symbolData['qtyDecsPrice'];
        $this->data['qty_decs_quote'] = $this->presetDecs[$symbolData['quoteAsset']];
        $this->data['quote_asset'] = $symbolData['quoteAsset'];
        $this->data['base_asset'] = $symbolData['baseAsset'];

        //Grabando datos en las tablas de la db
        if ($isNew) // insert
        {
            if (!$this->tableInsert(DB_NAME,'tickers'))
                $err++;
        }
        else       // update
        {
            if (!$this->tableUpdate(DB_NAME,'tickers'))
                $err++;
        }
        if ($err)
            return false;
        return true;
    }

    /**
     * @param: tickerid - Puede ser un solo ID o varios separados por coma
     * @param: prms - Ej.: ema=7,14 para agregar indicadores ema de 7 y 14 periodos
     */
    function getHistorico($tickerid,$prms)
    {
        //Parametros por default
        $interval  = ($prms['interval']?$prms['interval']:'1h');
        $limit     = ($prms['limit']?$prms['limit']:null);
        $startTime = ($prms['startTime']?$prms['startTime']:null);
        $endTime = ($prms['endTime']?$prms['endTime']:null);

        if ($startTime)
            $startTime = date('U',strtotime($startTime)).'000';
        if ($endTime)
            $endTime = date('U',strtotime($endTime)).'000';

        $api = new BinanceAPI();        

        $ids = explode(',',$tickerid);
        $tickerid='';

        foreach ($ids as $id)
        {
            $id = strtoupper($id);
            try {
                $candlesticks = $api->candlesticks($id, $interval, $limit, $startTime, $endTime);
            } catch (Throwable $e) {
                $ret['error'] = 'No fue posible encontrar precios para la moneda '.$id;
            }

            foreach ($candlesticks as $timestamp => $candel)
            {
                if (!isset($ret['base0'][$id]) && (float)$candel['close'])
                {
                    $ret['base0'][$id] = (float)$candel['close'];
                    $perc = toDec(0);
                }
                else
                {
                    $perc =  toDec((((float)$candel['close']/$ret['base0'][$id])-1)*100);
                }
                $prices[$id][] = array('date'=>date('c',($timestamp/1000)),
                                       'price'=> ($candel['open']+$candel['close'])/2,
                                       'high'=> (float)$candel['high'],
                                       'low'=> (float)$candel['low'],
                                       'open'=> (float)$candel['open'],
                                       'close'=> (float)$candel['close'],
                                       'perc'=> $perc);
                $lastUpdate = date('Y-m-d H:i',($timestamp/1000));
            }

            $tickerid .= ($tickerid?',':'')."'".$id."'";
        }

        $ret['getHistorico']=$thickerid;

        if (isset($prms['ema']))
        {
            $ema = explode(',',$prms['ema']);
            foreach ($prices as $tickerid => $tickerPrices)
            {
                foreach ($tickerPrices as $k => $v)
                    $basePrices[] = $v['price'];
                
                $ema0 = trader_ema($basePrices, ($ema[0]));
                $ema1 = trader_ema($basePrices, ($ema[1]));
                foreach ($tickerPrices as $k => $v)
                {
                    if ($ema0[$k])
                        $prices[$tickerid][$k]['ema'.$ema[0]] = $ema0[$k];
                    if ($ema1[$k])
                        $prices[$tickerid][$k]['ema'.$ema[1]] = $ema1[$k];
                }
            }
        }

        if (!empty($prices))
            $ret['prices'] = $prices;

        $ret['updated'] = $lastUpdate;
        $ret['updatedStr'] = date('d/m/y h:i',strtotime($lastUpdate));
        if (isset($ret['updated']))
            $ret['updatedStr'] = date('d/m/y h:i',strtotime($ret['updated']));

        return $ret;
    }

    public function getNewTicker()
    {
        return $this->newTicker;
    } 

    public function getAnalisisTecnico($symbol,$interval='1h',$limit=40)
    {
        $startTime = null;// Ej: date('Y-m-d H:i',strtotime('-10 days'));
        $endTime   = null;//'Ej: 2021-12-10 10:00';

        if ($startTime)
            $startTime = date('U',strtotime($startTime)).'000';
        if ($endTime)
            $endTime = date('U',strtotime($endTime)).'000';


        $api = new BinanceAPI();        

        try {
            $candlesticks = $api->candlesticks($symbol, $interval, $limit, $startTime, $endTime);
        } catch (Throwable $e) {
            $this->errLog->add('No fue posible encontrar informacion para la moneda '.$symbol);
            return false;
        }

        $at = $this->analisisTecnico($candlesticks);

        return $at;
    }

    function analisisTecnico($candlesticks,$prms=array())
    {
        $i=0;
        foreach ($candlesticks as $timestamp => $candel)
        {
            $data_date[] = date('Y-m-d H:i',($timestamp/1000));
            $data_close[] = (float)$candel['close'];
            $data_high[] = (float)$candel['high'];
            $data_low[] = (float)$candel['low'];
            $at['candel']['open'] = (float)$candel['open'];
            $at['candel']['high'] = (float)$candel['high'];
            $at['candel']['low'] = (float)$candel['low'];
            $at['candel']['close'] = (float)$candel['close'];
            $i++;
        }
        $lastPos = $i-1;
        $data_emaFast = trader_ema($data_close, $periods = 7);
        $data_emaSlow = trader_ema($data_close, $periods = 14);
        $data_bb = trader_bbands($data_close, $periods = 20,$upper_mult = 2,$lower_mult = 2,TRADER_MA_TYPE_SMA);
        $data_ma24 = trader_ma($data_close, $periods = 24, TRADER_MA_TYPE_SMA);

        /*
        $data_macd = trader_macd($data_close, $fastPeriod=12, $slowPeriod=26, $signalPeriod=9 );
        $data_rsi = trader_rsi($data_close, $periods = 14);
        $data_adx = trader_adx($data_high,$data_low,$data_close,$timePeriod = 14);
        */
        

        /*
        $at['date'] = $data_date[$lastPos];
        $at['price'] = $data_close[$lastPos];
        $at['signal'] = array();

        $at['macd_val'] = $data_macd[0][$lastPos];
        $at['macd_sig'] = $data_macd[1][$lastPos];
        $at['macd_diverg'] = $data_macd[2][$lastPos];
        $at['macd_trend'] = '';
        $at['macd_vporc'] = '';

        $at['rsi'] = $data_rsi[$lastPos];
        $at['rsi_trend'] = '';
        $at['rsi_vporc'] = '';

        $at['adx'] = $data_adx[$lastPos];
        $at['adx_trend'] = '';
        $at['adx_vporc'] = '';

        $at['bb_trend'] = '';
        $at['bb_vporc'] = '';
        */

        #MA
        $at['ma24'] = $data_ma24[$lastPos];

        #Bollinger Bands
        $at['bb_high'] = $data_bb[0][$lastPos];
        $at['bb_mid'] = $data_bb[1][$lastPos];
        $at['bb_low'] = $data_bb[2][$lastPos];
        //if ($data_bb[2][$lastPos]!=0)
        //    $at['bb_gap'] = toDec((($data_bb[0][$lastPos]/$data_bb[2][$lastPos])-1)*100);
        //else
        //    $at['bb_gap'] = '0.00';

        #EMA Cross
        $at['ema_fast'] = $data_emaFast[$lastPos];
        $at['ema_slow'] = $data_emaSlow[$lastPos];
        $at['ema_cross'] = toDec((($data_emaFast[$lastPos]/$data_emaSlow[$lastPos])-1)*100);

        //Calculo de tendencias lineales
        /*
        $trendItems=4;
        $aux = $this->getLastElementsFromArray($data_rsi,$trendItems);
        $at['rsi_trend'] = tendenciaLineal( $aux );
        $at['rsi_vporc'] = variacionPorcentual( $aux );        
        $aux = $this->getLastElementsFromArray($data_adx,$trendItems);
        $at['adx_trend'] = tendenciaLineal( $aux );
        $at['adx_vporc'] = variacionPorcentual( $aux );
        $aux = $this->getLastElementsFromArray($data_macd[0],$trendItems);
        $at['macd_trend'] = tendenciaLineal( $aux );
        $at['macd_vporc'] = variacionPorcentual( $aux );
        $aux = $this->getLastElementsFromArray($data_bb[1],$trendItems);
        $at['bb_trend'] = tendenciaLineal( $aux );  
        $at['bb_vporc'] = variacionPorcentual( $aux );  
        $aux = $this->getLastElementsFromArray($data_ma24,$trendItems);
        $at['ma24_trend'] = tendenciaLineal( $aux );
        $at['ma24_vporc'] = variacionPorcentual( $aux );
        */
        
        //Señales
        
        

        #RSI
        /*
        $at['signal']['rsi'] = '';
        if ($at['rsi']>50) // && $at['rsi_trend'] > 1.2)
        {
            $at['signal']['rsi'] = 'C'; //Buy
        }
        elseif ($at['rsi']<50) // && $at['rsi_trend'] < -1.2)
        {
            $at['signal']['rsi'] = 'V'; //Sell
        }
        elseif ($at['rsi']>88)
        {
            $at['signal']['rsi'] = 'V'; //Sell
        }
        */

        #Bollinger
        //$at['signal']['bb'] = '';
        //if ($at['price']>$at['bb_mid'] /*&& $at['bb_gap'] > 3*/)
        //{
        //    $at['signal']['bb'] = 'C'; //Buy
        //}
        //elseif ($at['price']<$at['bb_mid'] /*&& $at['bb_gap'] > 3*/)
        //{
        //    $at['signal']['bb'] = 'V'; //Sell
        //}

        //MACD
        //$at['signal']['macd'] = '';
        //if ($at['macd_val'] > $at['macd_sig'] /*&& $at['macd_trend'] > 0.5*/)
        //{
        //    $at['signal']['macd'] = 'C'; //Buy
        //}
        //elseif ($at['macd_val'] < $at['macd_sig'] /*&& $at['macd_trend']< -0.5*/)
        //{
        //    $at['signal']['macd'] = 'V'; //Sell
        //}

        //EMA_CROSS
        //$at['signal']['ema_cross'] = '-';
        //if ($at['ema_cross']>0)
        //    $at['signal']['ema_cross'] = 'C'; //Buy
        //elseif ($at['ema_cross']<0)
        //    $at['signal']['ema_cross'] = 'V'; //Buy
        return $at; 

    }

    function getLastElementsFromArray($array,$elements)
    {
    if (empty($array) || count($array)<$elements)
            return false;

        $aux = array();
        foreach($array as $v)
            $aux[] = $v;
        $array = $aux;

        $end = count($array)-1;
        
        for ($i=$elements-1;$i>=0;$i--)
            $new[] = $array[$end-$i];
        
        return $new;
    }

    private function __depth_get_parameters($price,$qtyDecs)
    {
        $data = array();
        $data['price'] = $price;

        $firstDigit = -1;
        $pointPos = -1;
        for ($i=0;$i<strlen($price);$i++)
        {
            $char = substr($price,$i,1);
            if ($firstDigit==-1 && $char!='0' && $char!='.')
                $firstDigit = $i;
            if ($char=='.')
                $pointPos = $i;

        }
        $data['firstDigit'] = $firstDigit;
        $data['pointPos'] = $pointPos;

        $inc = ($price>10000?3:2);
        for ($i=0;$i<strlen($price);$i++)
        {
            $char = substr($price,$i,1);
            if ($i == $pointPos)
                $inc++; 
            
            if ($char == '.')
                $data['scale'] .= '.';
            elseif ($i == $firstDigit+$inc-1)
                $data['scale'] .= '1';
            else
                $data['scale'] .= '0';

            if ($char == '.')
                $data['bidScaleStart'] .= '.';
            elseif ($i >= $firstDigit+$inc)
                $data['bidScaleStart'] .= '0';
            else
                $data['bidScaleStart'] .= $char;

        }

        $data['scale'] = floatVal($data['scale']);

        $data['askScaleStart'] = toDec($data['bidScaleStart']+$data['scale'],$qtyDecs);
        $data['bidScaleStart'] = toDec($data['bidScaleStart']+0,$qtyDecs);
        $data['scale'] = toDec($data['scale']+0,$qtyDecs);

        return $data;
    }

    function depth($symbol,$scale=null)
    {
        $data = array();

        $api = new BinanceAPI(); 

        $symbolData = $api->getSymbolData($symbol);

        $data['price'] = $symbolData['price'];
        $data['qtyDecsPrice'] = $symbolData['qtyDecsPrice'];

        $dp = $this->__depth_get_parameters($data['price'],$symbolData['qtyDecsPrice']);
        $data['askScaleStart'] = $dp['askScaleStart'];
        $data['bidScaleStart'] = $dp['bidScaleStart'];
        if ($scale)
            $data['scale'] = $scale;
        else
            $data['scale']         = $dp['scale'];

        $data['askMin'] = -1;
        $data['askMax'] = -1;
        $data['bidMin'] = -1;
        $data['bidMax'] = -1;
        $data['usdTotal'] = 0;
        $data['usdBid'] = 0;
        $data['usdAsk'] = 0;
        $data['bids'] = array();
        $data['asks'] = array();


        $rawData = $api->depth($symbol,$limit=5000);

        $bidScale = $data['bidScaleStart'];
        $askScale = $data['askScaleStart'];
        if (!empty($rawData))
        {
            foreach ($rawData['bids'] as $price => $amount)
            {
                if ($data['bidMin'] == -1 || $price < $data['bidMin'])
                    $data['bidMin'] = $price;
                if ($data['bidMax'] == -1 || $price > $data['bidMax'])
                    $data['bidMax'] = $price; 
                $data['usdBid'] += $price*$amount;

                if (floatVal($price)<floatVal($bidScale))
                    $bidScale = toDec(floatVal($bidScale)-floatVal($data['scale']),$data['qtyDecsPrice']);
                $data['bids'][$bidScale]['amount'] += toDec($price * $amount);

            }

            foreach ($rawData['asks'] as  $price => $amount)
            {
                if ($data['askMin'] == -1 || $price < $data['askMin'])
                    $data['askMin'] = $price;
                if ($data['askMax'] == -1 || $price > $data['askMax'])
                    $data['askMax'] = $price;
                $data['usdAsk'] += $price*$amount;

                if (floatVal($price)>floatVal($askScale))
                    $askScale = toDec(floatVal($askScale)+floatVal($data['scale']),$data['qtyDecsPrice']);
                $data['asks'][$askScale]['amount'] += toDec($price * $amount);

            }

            $data['usdTotal'] = $data['usdBid']+$data['usdAsk'];
            foreach ($data['bids'] as $scale => $rw)
            {
                $data['bids'][$scale]['ref'] = toDec((($scale*100)/$data['price'])-100);
                $data['bids'][$scale]['portion'] = toDec((($rw['amount']*100)/$data['usdBid']));
            }
            
            foreach ($data['asks'] as  $scale => $rw)
            {
                $data['asks'][$scale]['ref'] = toDec((($scale*100)/$data['price'])-100);
                $data['asks'][$scale]['portion'] = toDec((($rw['amount']*100)/$data['usdAsk']));
            }

        }

        return $data;             
    }

    function calcularPalancas($precioActual,$qtyPalancas=5)
    {
        if ($qtyPalancas == 5)
        {
            $coefPalanca2 = 0.08;
            $coefPalanca3 = 0.16;
            $coefPalanca4 = 0.32;
            $coefPalanca5 = 0.64;   
        }
        elseif ($qtyPalancas == 4)
        {
            $coefPalanca2 = 0.08;
            $coefPalanca3 = 0.16;
            $coefPalanca4 = 0.64;
        }
        if ($qtyPalancas != 4 && $qtyPalancas != 5)
            CriticalExit('Ticker :: calcularPalancas() - La cantidad de palancas puede ser 4 o 5');

        $min = $this->data['hst_min'];
        $max = $this->data['hst_max'];
        $porcToMin = toDec((1-($min/$precioActual))*100);



        //Control sobre MaxDrawdown
        if ($porcToMin>$this->data['max_drawdown'])
            $porcToMin = $this->data['max_drawdown'];

        if ($porcToMin <= 8) //El precio esta igualando o por debajo del minimo historico
        {
            if ($qtyPalancas == 5)
            {
                $palancas['porc'][1] = 2.00;
                $palancas['porc'][2] = 4.00;
                $palancas['porc'][3] = 6.00;
                $palancas['porc'][4] = 9.00;
                $palancas['porc'][5] = 14.00;
            }
            elseif ($qtyPalancas == 4)
            {
                $palancas['porc'][1] = 2.00;
                $palancas['porc'][2] = 4.00;
                $palancas['porc'][3] = 7.00;
                $palancas['porc'][4] = 10.00;
            }
        }
        else
        {
            $palancas['porc'][1] = 2.00;
            $palancas['porc'][2] = toDec($palancas['porc'][1]+(($porcToMin-$palancas['porc'][1])*$coefPalanca2));
            $palancas['porc'][3] = toDec($palancas['porc'][2]+(($porcToMin-$palancas['porc'][2])*$coefPalanca3));
            $palancas['porc'][4] = toDec($palancas['porc'][3]+(($porcToMin-$palancas['porc'][3])*$coefPalanca4));
            if ($qtyPalancas == 5)
            {
                $palancas['porc'][5] = toDec($palancas['porc'][4]+(($porcToMin-$palancas['porc'][4])*$coefPalanca5));
            }

        }
        foreach ($palancas['porc'] as $k => $v)
        {
            $palancas['price'][$k] = toDec($precioActual - (($precioActual * $v) / 100),$this->data['qty_decs_price']);
        }
        return $palancas;
    }

    function calcularMultiplicadorDePorcentaje($qtyPalancas,$palancaMax)
    {
        $qtyCompras = $qtyPalancas+1;
        
        $multPorc = 10; 
        $porcTotal = $this->__cmp($multPorc,$qtyCompras);
        while($porcTotal>$palancaMax)
        {
            $multPorc = toDec($multPorc-0.1);
//echo "<br>mu: ".$multPorc;
            $porcTotal = $this->__cmp($multPorc,$qtyCompras);
        }
//$porcTotal = $this->__cmp($multPorc,$qtyCompras,$echo=true);
        return toDec($multPorc);
    }

        private function __cmp($mp,$qc,$echo=false)
        {
            $prb = 100;
            $pru = $prb;
//if ($echo) echo "<br>mp: ".$mp;
            $tp = 0;
            for ($i=1;$i<$qc;$i++)
            {
                $pru = $pru - (($pru * $mp*$i) /100);
                $tp = $tp + $mp*$i;
//if ($echo) echo "<br>tp: ".$tp." pru: ".$pru;
            }
            $pf = (($pru/$prb)-1)*100;
//echo "<br>pf: ".$pf;
            return toDec($pf*-1);
        }

    function calcularMultiplicadorDeCompras($qtyPalancas,$capital,$compraInicial)
    {
        $qtyCompras = $qtyPalancas+1;
        
        $multCompras = 2; //multiplicador de compras
        $compraTotal = $this->__cmu($compraInicial,$qtyCompras,$multCompras);
        while($compraTotal>$capital)
        {
            $multCompras = $multCompras-0.1;
            $compraTotal = $this->__cmu($compraInicial,$qtyCompras,$multCompras);
        }
        $compraTotal = $compraTotal*2;
        $multCompras = $multCompras+0.1;
        while($compraTotal>$capital)
        {
            $multCompras = $multCompras-0.01;
            $compraTotal = $this->__cmu($compraInicial,$qtyCompras,$multCompras);
        }
        
        return $multCompras;
    }

    public function getSymbolData($symbol)
    {
        $api = new BinanceAPI();
        $price = $api->price($symbol);
        
        $data['symbol'] = $symbol;
        $data['price'] = $price;
        
        $ds = $this->getDataSet("tickerid = '".$symbol."'");
        if (!empty($ds))
        {
            foreach ($ds as $rw)
            {
                $data['qtyDecs']    = $rw['qty_decs_units'];
                $data['qtyDecsPrice'] = $rw['qty_decs_price'];
                $data['qtyDecsQuote'] = $rw['qty_decs_quote'];
                $data['quoteAsset'] = $rw['quote_asset'];
                $data['baseAsset']  = $rw['base_asset'];                
            }
        }
        elseif ($price>0)
        {
            $exchangeInfo = $this->exchangeInfo($symbol);
            $data['qtyDecs'] = intval($this->numberOfDecimals($exchangeInfo['symbols'][$symbol]['filters'][1]['minQty']));
            $data['qtyDecsPrice'] = intval($this->numberOfDecimals($exchangeInfo['symbols'][$symbol]['filters'][0]['minPrice']));
            $data['qtyDecsQuote'] = $this->presetDecs[$exchangeInfo['symbols'][$symbol]['quoteAsset']];
            $data['quoteAsset'] = $exchangeInfo['symbols'][$symbol]['quoteAsset'];
            $data['baseAsset']  = $exchangeInfo['symbols'][$symbol]['baseAsset'];
        }

        return $data;

    }

        private function __cmu($ci,$qc,$mu)
        {
            $tc = $ci;
            $uc = $ci;
            for ($i=1;$i<$qc;$i++)
            {
                $uc = $uc*$mu;
                $tc = $tc + $uc;
            }
            return $tc;
        }


}