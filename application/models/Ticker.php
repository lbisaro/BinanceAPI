<?php
include_once LIB_PATH."ModelDB.php";

class Ticker extends ModelDB
{
    protected $query = "SELECT * FROM tickers";

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
file_put_contents($fichero, "\n"."Ticker::addPrices.1 ".date('H:i:s'),FILE_APPEND);
        $ds = $this->getDataSet();
        $exists = array();
        if (!empty($ds))
        {
            foreach ($ds as $rw)
                $exists[$rw['tickerid']]=$rw;
        }

        $date = date('Y-m-d H:i');


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
                else //Update
                {
                    $upds[] = "UPDATE tickers SET updated = '".$date."', price = ".$price.
                              " WHERE tickerid = '".$tickerid."'";
                }
            }
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO tickers (tickerid,price,created) VALUES '.$toIns;
                $this->db->query($ins);
            }
            if (!empty($upds))
            {
                foreach ($upds as $upd)
                {
                    $this->db->query($upd);
                }
            }
file_put_contents($fichero, "\n"."Ticker::addPrices.2 ".date('H:i:s'),FILE_APPEND);
            //Actualizando tabla prices_1m
            $toIns='';
            foreach ($prices as $tickerid => $price)
            {
                $toIns .= ($toIns?',':'')."('".$tickerid."',".$price.",'".$date."')";
            }
file_put_contents($fichero, "\n"."Ticker::addPrices.3 ".date('H:i:s'),FILE_APPEND);            
            if (!empty($toIns))
            {
                $ins = 'INSERT INTO prices_1m (tickerid,price,datetime) VALUES '.$toIns;
                $this->db->query($ins);
            }
file_put_contents($fichero, "\n"."Ticker::addPrices.4 ".date('H:i:s'),FILE_APPEND);
            //Actualizando tabla prices_1h
            
            //Armar las velas con los datos de la tabla prices_1m

        }
    }
}