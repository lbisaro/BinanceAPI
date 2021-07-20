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
        $ds = $this->getDataSet("datetime > '".$dateLimit."'");
        $ret=array();
        if (!empty($ds)))
        {
            foreach ($ds as $rw)
            {
                $ret[$rw['tickerid']]['tickerid']=$rw['tickerid'];
                $ret[$rw['tickerid']]['name']=str_replace('USDT','',$rw['tickerid']);
                $ret[$rw['tickerid']]['prices'][] = array('dt'=>$rw['datetime'],
                                                          'price'=>$rw['price']);
            }

            pr($ret);
        }
        return $ret;
    }
}