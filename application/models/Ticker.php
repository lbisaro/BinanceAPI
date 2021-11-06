<?php
include_once LIB_PATH."ModelDB.php";
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
        $startTime = ($prms['endTime']?$prms['endTime']:null);

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
                $candelistics = $api->candlesticks($id, $interval, $limit, $startTime, $endTime);
            } catch (Throwable $e) {
                $ret['error'] = 'No fue posible encontrar precios para la moneda '.$id;
            }

            foreach ($candelistics as $timestamp => $candel)
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
                                       'price'=> (float)$candel['close'],
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

}