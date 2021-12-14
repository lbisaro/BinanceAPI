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

    function __Construct($id=null)
    {
        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'tickers','tickerid');

        if($id)
            $this->load($id);
    }

    function get($field)
    {
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

        if (!$this->data['OK'])
        {
            $err[] = 'Descripcion del error';
        }

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

        // Creando el Id en caso que no este
        if (!$this->data['tickerid'])
        {
            $isNew = true;
            $this->data['tickerid'] = $this->getNewId();
        }

        if (!$this->valid())
        {
            return false;
        }

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

    function addPrices(array $prices)
    {

        $ds = $this->getDataSet();
        $exists = array();
        if (!empty($ds))
        {
            foreach ($ds as $rw)
                $exists[$rw['tickerid']]=$rw;
        }

        //Se resta un minuto a la fecha actual para guardar el precio como cierre del minuto anterior
        $date = date('Y-m-d H:i',strtotime('-1 minute')); 

        //Actualizando tabla tickers
        if (!empty($prices))
        {
            $toIns = '';
            $upds = array();
            foreach ($prices as $tickerid => $price)
            {
                if (!isset($exists[$tickerid])) //Insert
                {
                    $this->newTicker .= $tickerid." ".$date."\n";
                    $toIns .= ($toIns?',':'')."('".$tickerid."','".$date."')";
                }
            }
            file_put_contents($fichero, "\n"."addPrices.2 ".date('H:i:s'),FILE_APPEND);
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO tickers (tickerid,created) VALUES '.$toIns;
                $this->db->query($ins);
            }
            //Actualizando tabla prices
            $toIns='';
            foreach ($prices as $tickerid => $price)
            {
                $toIns .= ($toIns?',':'')."('".$tickerid."',".$price.",'".$date."')";
            }
            
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO prices (tickerid,price,datetime) VALUES '.$toIns;
                $this->db->query($ins);
            }
            file_put_contents($fichero, "\n"."addPrices.3 ".date('H:i:s'),FILE_APPEND);
        }
    }

    function getVariacionDePrecios()
    {
        $dateLimit = date('Y-m-d H:i',strtotime('-25 hours')); //Se buscan registros de poco mas de 1 hora
        $qry = "SELECT * 
                FROM prices 
                WHERE datetime > '".$dateLimit."' 
                ORDER BY datetime"; 
        $ret=array();
        $stmt = $this->db->query($qry);
        $lastDateTime='';
        while ($rw = $stmt->fetch())
        {
            $rw['price'] = (float)$rw['price'];
            $ret['tickers'][$rw['tickerid']]['tickerid']=$rw['tickerid'];
            $ret['tickers'][$rw['tickerid']]['name']=str_replace('USDT','',$rw['tickerid']);
            $ret['tickers'][$rw['tickerid']]['prices'][$rw['datetime']] = $rw['price'];
            $ret['tickers'][$rw['tickerid']]['price'] = $rw['price'];
            $lastDateTime = $rw['datetime'];
            $ret['updated'] = $lastDateTime;
            $ret['updatedStr'] = date('d/m/y h:i',strtotime($lastDateTime));
        }
        $date_1m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 1 minutes'));
        $date_3m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 3 minutes'));
        $date_5m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 5 minutes'));
        $date_15m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 15 minutes'));
        $date_30m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 30 minutes'));
        $date_1h = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 1 hour'));
        $date_6h = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 6 hours'));
        $date_12h = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 12 hours'));
        $date_24h = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 24 hours'));
        if (!empty($ret))
        {
            foreach ($ret['tickers'] as $tickerid => $rw)
            {
                foreach ($rw['prices'] as $datetime => $price)
                {
                    if ($price != 0)
                    {
                        if ($datetime == $date_1m)
                            $ret['tickers'][$tickerid]['perc_1m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_3m)
                            $ret['tickers'][$tickerid]['perc_3m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_5m)
                            $ret['tickers'][$tickerid]['perc_5m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_15m)
                            $ret['tickers'][$tickerid]['perc_15m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_30m)
                            $ret['tickers'][$tickerid]['perc_30m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_1h)
                            $ret['tickers'][$tickerid]['perc_1h']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_6h)
                            $ret['tickers'][$tickerid]['perc_6h']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_12h)
                            $ret['tickers'][$tickerid]['perc_12h']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_24h)
                            $ret['tickers'][$tickerid]['perc_24h']=toDec((($rw['price']/$price)-1)*100);
                    }
                    $price_last = $price;
                }
            }
        }
        return $ret;
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

        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);        

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

    public function getAnalisisTecnico($symbol,$interval='1h')
    {
        $limit     = 40;
        $startTime = null;// Ej: date('Y-m-d H:i',strtotime('-10 days'));
        $endTime   = null;//'Ej: 2021-12-10 10:00';

        if ($startTime)
            $startTime = date('U',strtotime($startTime)).'000';
        if ($endTime)
            $endTime = date('U',strtotime($endTime)).'000';


        $api = new BinanceAPI();        

        try {
            $candlesticks = $api->candlesticks($symbol, $interval, $limit, $startTime, $endTime);
            $i=0;
            foreach ($candlesticks as $timestamp => $candel)
            {
                $data_date[] = date('Y-m-d H:i',($timestamp/1000));
                $data_close[] = (float)$candel['close'];
                $data_volume[] = (float)$candel['volume'];

                $at['candel']['open'] = (float)$candel['open'];
                $at['candel']['high'] = (float)$candel['high'];
                $at['candel']['low'] = (float)$candel['low'];
                $at['candel']['close'] = (float)$candel['close'];
                
                $i++;
            }



            $data_ma24 = trader_ma($data_close, $periods = 24, TRADER_MA_TYPE_SMA);
            $data_macd = trader_macd($data_close, $fastPeriod=12, $slowPeriod=26, $signalPeriod=9 );
            $data_rsi = trader_rsi($data_close, $periods = 14);
            $data_bb = trader_bbands($data_close, $periods = 20,$upper_mult = 2,$lower_mult = 2,TRADER_MA_TYPE_SMA);
            $data_obv = trader_obv($data_close, $data_volume);
            
            for ($j=($limit-5) ; $j<$i ; $j++)
            {
                $at['date'] = $data_date[$j];
                $at['price'] = $data_close[$j];
                $at['signal'] = array();

                $at['ma24'] = $data_ma24[$j];
                $at['ma24_trend'] = '';

                $at['macd_val'] = $data_macd[0][$j];
                $at['macd_sig'] = $data_macd[1][$j];
                $at['macd_diverg'] = $data_macd[2][$j];
                $at['macd_trend'] = '';

                $at['rsi'] = $data_rsi[$j];
                $at['rsi_trend'] = '';

                $at['obv'] = $data_obv[$j];
                $at['obv_trend'] = '';

                $at['bb_up'] = $data_bb[0][$j];
                $at['bb_mid'] = $data_bb[1][$j];
                $at['bb_low'] = $data_bb[2][$j];
                $at['bb_gap'] = toDec((($data_bb[0][$j]/$data_bb[2][$j])-1)*100);
                $at['bb_trend'] = '';

                //Analisis de tendencias sobre ultimos 4 periodos
                if ($j>$start+3)
                {
                    $tl = tendenciaLineal(array($data_rsi[$j-3],
                                                $data_rsi[$j-2],
                                                $data_rsi[$j-1],
                                                $data_rsi[$j] 
                                               ));
                    $at['rsi_trend'] = toDec($tl);

                    $tl = tendenciaLineal(array($data_macd[0][$j-3],
                                                $data_macd[0][$j-2],
                                                $data_macd[0][$j-1],
                                                $data_macd[0][$j] 
                                               ));
                    $at['macd_trend'] = toDec($tl); 

                    $tl = tendenciaLineal(array($data_obv[$j-3],
                                                $data_obv[$j-2],
                                                $data_obv[$j-1],
                                                $data_obv[$j] 
                                               ));
                    $at['obv_trend'] = toDec($tl);

                    $tl = tendenciaLineal(array($data_bb[1][$j-3],
                                                $data_bb[1][$j-2],
                                                $data_bb[1][$j-1],
                                                $data_bb[1][$j] 
                                               ));
                    $at['bb_trend'] = toDec($tl); 

                    $tl = tendenciaLineal(array($data_ma24[$j-3],
                                                $data_ma24[$j-2],
                                                $data_ma24[$j-1],
                                                $data_ma24[$j] 
                                               ));
                    $at['ma24_trend'] = toDec($tl);

                }


            }

            //Señales
            $at['signal']['rsi'] = '';
            $at['signal']['bb'] = '';
            $at['signal']['macd'] = '';

            //RSI
            if ($at['rsi']>50 /*&& $at['rsi_trend'] > 1.2*/)
            {
                $at['signal']['rsi'] = 'C'; //Buy
            }
            elseif ($at['rsi']<50 /*&& $at['rsi_trend'] < -1.2*/)
            {
                $at['signal']['rsi'] = 'V'; //Sell
            }
            elseif ($at['rsi']>88)
            {
                $at['signal']['rsi'] = 'V'; //Sell
            }

            //Bollinger
            if ($at['price']>$at['bb_mid'] /*&& $at['bb_gap'] > 3*/)
            {
                $at['signal']['bb'] = 'C'; //Buy
            }
            elseif ($at['price']<$at['bb_mid'] /*&& $at['bb_gap'] > 3*/)
            {
                $at['signal']['bb'] = 'V'; //Sell
            }

            //MACD
            if ($at['macd_val'] > $at['macd_sig'] /*&& $at['macd_trend'] > 0.5*/)
            {
                $at['signal']['macd'] = 'C'; //Buy
            }
            elseif ($at['macd_val'] < $at['macd_sig'] /*&& $at['macd_trend']< -0.5*/)
            {
                $at['signal']['macd'] = 'V'; //Sell
            }
           
            return $at; 

        } catch (Throwable $e) {
            $this->errLog->add('No fue posible encontrar informacion para la moneda '.$symbol);
            return false;
        }


    }
}