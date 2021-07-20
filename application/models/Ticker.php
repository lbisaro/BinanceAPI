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
                    $toIns .= ($toIns?',':'')."('".$tickerid."',".$price.",'".$date."')";
                }
            }
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO tickers (tickerid,price,created) VALUES '.$toIns;
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
        }
    }

    function getVariacionDePrecios()
    {
        $dateLimit = date('Y-m-d H:i',strtotime('-1 hour'));
        $qry = "SELECT * FROM prices_1m WHERE datetime > '".$dateLimit."' ORDER BY datetime"; 
        debug($qry);
        $ret=array();
        $stmt = $this->db->query($qry);
        $lastDateTime='';
        while ($rw = $stmt->fetch())
        {
            $ret[$rw['tickerid']]['tickerid']=$rw['tickerid'];
            $ret[$rw['tickerid']]['name']=str_replace('USDT','',$rw['tickerid']);
            $ret[$rw['tickerid']]['prices'][$rw['datetime']] = $rw['price'];
            $ret[$rw['tickerid']]['price'] = $rw['price'];
            $lastDateTime = $rw['datetime'];
        }

        if (!empty($ret))
        {
            foreach ($ret as $tickerid => $rw)
            {
                reset($rw['prices']);
                $price_1h = current($rw['prices']);
                    
                foreach ($rw['prices'] as $datetime => $price)
                {
                    $date_1m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 1 minutes'));
                    $date_3m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 3 minutes'));
                    $date_5m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 5 minutes'));
                    $date_15m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 15 minutes'));
                    $date_30m = Date('Y-m-d H:i:s', strtotime($lastDateTime.' - 30 minutes'));
                    if ($price != 0)
                    {
                        if ($datetime == $date_1m)
                            $ret[$tickerid]['perc_1m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_3m)
                            $ret[$tickerid]['perc_3m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_5m)
                            $ret[$tickerid]['perc_5m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_15m)
                            $ret[$tickerid]['perc_15m']=toDec((($rw['price']/$price)-1)*100);
                        if ($datetime == $date_30m)
                            $ret[$tickerid]['perc_30m']=toDec((($rw['price']/$price)-1)*100);
                    }
                    $price_last = $price;
                }
                if ($price_1h != 0)
                    $ret[$tickerid]['perc_1h']=toDec((($price_last/$price_1h)-1)*100);
            }
        }
        return $ret;
    }
}