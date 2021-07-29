<?php
include_once LIB_PATH."ModelDB.php";

class Ticker extends ModelDB
{
    protected $query = "SELECT *, 
                            (SELECT price FROM prices_1m WHERE prices_1m.tickerid = tickers.tickerid ORDER BY datetime DESC limit 1) price,
                            (SELECT datetime FROM prices_1m WHERE prices_1m.tickerid = tickers.tickerid ORDER BY datetime DESC limit 1) updated
                        FROM tickers";

    protected $pKey  = 'tickerid';

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
$fichero = ROOT_DIR.'/log.txt';
file_put_contents($fichero, "\n"."addPrices.0 ".date('H:i:s'),FILE_APPEND);

        $ds = $this->getDataSet();
        $exists = array();
        if (!empty($ds))
        {
            foreach ($ds as $rw)
                $exists[$rw['tickerid']]=$rw;
        }
file_put_contents($fichero, "\n"."addPrices.1 ".date('H:i:s'),FILE_APPEND);

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
                    $toIns .= ($toIns?',':'')."('".$tickerid."','".$date."')";
                }
            }
            file_put_contents($fichero, "\n"."addPrices.2 ".date('H:i:s'),FILE_APPEND);
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO tickers (tickerid,created) VALUES '.$toIns;
                $this->db->query($ins);
            }
            //Actualizando tabla prices_1m
            $toIns='';
            foreach ($prices as $tickerid => $price)
            {
                $toIns .= ($toIns?',':'')."('".$tickerid."',".$price.",'".$date."')";
            }
            
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO prices_1m (tickerid,price,datetime) VALUES '.$toIns;
                $this->db->query($ins);
            }
            file_put_contents($fichero, "\n"."addPrices.3 ".date('H:i:s'),FILE_APPEND);
        }
    }

    function getVariacionDePrecios()
    {
        $dateLimit = date('Y-m-d H:i',strtotime('-70 minutes')); //Se buscan registros de poco mas de 1 hora
        $qry = "SELECT * FROM prices_1m WHERE datetime > '".$dateLimit."' ORDER BY datetime"; 
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
        $ids = explode(',',$tickerid);
        $tickerid='';
        foreach ($ids as $id)
            $tickerid .= ($tickerid?',':'')."'".$id."'";
        $qry = "SELECT * 
                FROM prices_1m 
                WHERE tickerid in (".$tickerid.")
                ORDER BY datetime"; 
        $ret['getHistorico']=$thickerid;
        $stmt = $this->db->query($qry);

        while ($rw = $stmt->fetch())
        {
            if (!isset($ret['base0'][$rw['tickerid']]))
            {
                $ret['base0'][$rw['tickerid']] = $rw['price'];
                $perc = toDec(0);
            }
            else
            {
                $perc =  toDec((($rw['price']/$ret['base0'][$rw['tickerid']])-1)*100);
            }
            $prices[$rw['tickerid']][] = array('date'=>date('c',strToTime($rw['datetime'])),
                                               'price'=> (float)$rw['price'],
                                               'perc'=> $perc);
            $lastUpdate = $rw['datetime'];     
        }

        if (isset($prms['ema']))
        {
            $ema = explode(',',$prms['ema']);
            foreach ($prices as $tickerid => $tickerPrices)
            {
                foreach ($tickerPrices as $k => $v)
                    $basePrices[] = $v['price'];
                
                $ema0 = trader_ema($basePrices, ($ema[0]*60));
                $ema1 = trader_ema($basePrices, ($ema[1]*60));
                foreach ($tickerPrices as $k => $v)
                {
                    $prices[$tickerid][$k]['ema'.$ema[0]] = ($ema0[$k]?$ema0[$k]:$ret['base0'][$tickerid]);
                    $prices[$tickerid][$k]['ema'.$ema[1]] = ($ema1[$k]?$ema1[$k]:$ret['base0'][$tickerid]);
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

}