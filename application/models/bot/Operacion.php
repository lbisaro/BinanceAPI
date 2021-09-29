<?php
include_once LIB_PATH."ModelDB.php";

class Operacion extends ModelDB
{
    protected $query = "SELECT * FROM operacion";

    protected $pKey  = 'idoperacion';

    const SIDE_BUY = 0;
    const SIDE_SELL = 1;

    const STATUS_NEW = 0;
    const STATUS_FILLED = 10;

    const COEF_A = 0.7;
    const COEF_B = 5;

    function __Construct($id=null)
    {
        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'operacion','idoperacion');

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
        if (!$this->data['idoperacion'])
        {
            $isNew = true;
            $this->data['idoperacion'] = $this->getNewId();
        }

        if (!$this->valid())
        {
            return false;
        }

        //Grabando datos en las tablas de la db
        if ($isNew) // insert
        {
            if (!$this->tableInsert(DB_NAME,'operacion'))
                $err++;
        }
        else       // update
        {
            if (!$this->tableUpdate(DB_NAME,'operacion'))
                $err++;
        }
        if ($err)
            return false;
        return true;
    }

    function calcularOperacion($inicio_precio,$inicio_usd,$multiplicador_compra,$multiplicador_porc)
    {
        $arr = array();
        $op['porc'] = 0;
        $op['usd'] = $inicio_usd;
        $op['precio_compra'] = $inicio_precio;
        $op['porc_venta'] = toDec(self::COEF_A*$op['porc']+self::COEF_B,2);
        $op['precio_venta'] = $inicio_precio+toDec($inicio_precio * $op['porc_venta'] / 100,7);
        $op['compra_moneda'] = toDec($op['usd']/$op['precio_compra'],7);
        $arr[] = $op;
        for ($i = 1; $i<6 ; $i++)
        {
            $preOp = $arr[$i-1];

            $op['porc'] = $preOp['porc']-$multiplicador_porc;
            $op['usd'] = $preOp['usd']*$multiplicador_compra;
            $op['precio_compra'] = toDec($inicio_precio+$inicio_precio*$op['porc']/100,7);
            $op['porc_venta'] = toDec(self::COEF_A*$op['porc']+self::COEF_B,2);
            $op['precio_venta'] = $inicio_precio+toDec($inicio_precio * $op['porc_venta'] / 100,7);
            $op['compra_moneda'] = toDec($op['usd']/$op['precio_compra'],7);
            $arr[] = $op;
        } 
        return $arr;



    }
}