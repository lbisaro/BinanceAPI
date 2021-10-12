<?php
include_once LIB_PATH."ModelDB.php";

class Operacion extends ModelDB
{
    protected $query = "SELECT * FROM operacion";

    protected $pKey  = 'idoperacion';

    const SIDE_BUY = 0;
    const SIDE_SELL = 1;

    //Operacion status
    const OP_STATUS_READY       = 10;
    const OP_STATUS_OPEN        = 20;
    const OP_STATUS_RECALCULATE = 30;
    const OP_STATUS_WAITING     = 40;

    //Order status
    const OR_STATUS_NEW = 0;
    const OR_STATUS_FILLED = 10;


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
        if ($field=='strEstado')
            return $this->getTipoStatus($this->status());
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

        if (!$this->data['symbol'])
            $err[] = 'Se debe especificar un Symbol';
        if ($this->data['inicio_usd']<=0)
            $err[] = 'Se debe especificar un importe de compra inicial en USD';
        if ($this->data['multiplicador_compra']<1 || $this->data['multiplicador_compra']>2.5 )
            $err[] = 'Se debe especificar un multiplicador de compra entre 1 y 2.5';
        if ($this->data['multiplicador_porc']<1 || $this->data['multiplicador_porc']>20 )
            $err[] = 'Se debe especificar un multiplicador de porcentaje entre 1 y 20';

        if (!$this->data['idusuario'])
        {
            $auth = UsrUsuario::getAuthInstance();
            $this->data['idusuario'] = $auth->get('idusuario');
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

    function getTipoStatus($id='ALL')
    {
        $arr[self::OP_STATUS_READY]         = 'Completa';
        $arr[self::OP_STATUS_OPEN]          = 'Abierta';
        $arr[self::OP_STATUS_RECALCULATE]   = 'Esperando recalculo';
        $arr[self::OP_STATUS_WAITING]       = 'Esperando completar orden';

        if ($id=='ALL')
            return $arr;
        elseif (isset($arr[$id]))
            return $arr[$id];
        return 'Desconocido'.($id?' ['.$id.']':'');
    }

    function getTipoStatusOr($id='ALL')
    {
        $arr[self::OR_STATUS_FILLED]      = 'Ejecutada';
        $arr[self::OR_STATUS_NEW]         = 'Nueva';

        if ($id=='ALL')
            return $arr;
        elseif (isset($arr[$id]))
            return $arr[$id];
        return 'Desconocido'.($id?' ['.$id.']':'');
    }

    function status()
    {
        if (!$this->data['idoperacion'])
            return false;
        $qry = 'SELECT * 
                FROM operacion_orden 
                WHERE idoperacion = '.$this->data['idoperacion'];
        $stmt = $this->db->query($qry);
        $ready = true;
        $openBuy = 0;
        $openSell = 0;

        $qty = 0;
        while ($rw = $stmt->fetch())
        {
            $qty++;
            if ($rw['status'] == self::OR_STATUS_NEW)
                $ready = false;
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_NEW)
                $openBuy++;
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_NEW)
                $openSell++;
        }

        if ($ready)
            return self::OP_STATUS_READY; // No hay ordenes abiertas
        elseif ($openBuy==0 && $qty==1)
            return self::OP_STATUS_RECALCULATE; //Recien inicia la compra
        elseif ($openBuy==1 && $qty==1)
            return self::OP_STATUS_WAITING;
        elseif ($openBuy==1 && $openSell==1)
            return self::OP_STATUS_OPEN;
        elseif (($openBuy==0 && $openSell==1) || ($openBuy==1 && $openSell==0))
            return self::OP_STATUS_RECALCULATE;
        
        return false;
    }

    function canStart()
    {
        if ($this->status() == self::OP_STATUS_READY && $this->data['auto_restart'])
            return true;
        return false;
    }

    function start()
    {
        if (!$this->data['idoperacion'])
            return false;
        if (!$this->canStart())
            return false;

        $symbol = $this->data['symbol'];
        if ($this->data['idusuario'])
        {
            $auth = new UsrUsuario($this->data['idusuario']);
        }
        else
        {
            $auth = UsrUsuario::getAuthInstance();
        }
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);        
        $data = $api->getSymbolData($symbol);

        //Orden para compra inicial
        $usd = $this->data['inicio_usd'];
        $qty = toDec($usd/$data['price'],$data['qtyDecs']);
        $order = $api->marketBuy($symbol, $qty);
        $opr[1]['idoperacion']  = $this->data['idoperacion'];
        $opr[1]['side']         = self::SIDE_BUY;
        $opr[1]['origQty']      = $qty;
        $opr[1]['price']        = 0;
        $opr[1]['orderId']      = $order['orderId'];

        $ins='';
        foreach ($opr as $op)
            $ins .= ($ins?',':'')." (".$op['idoperacion'].",".
                                    "".$op['side'].",".
                                    "".$op['origQty'].",".
                                    "".$op['price'].",".
                                    "'".$op['orderId']."' ".
                                    ") ";
        $ins = 'INSERT INTO operacion_orden (idoperacion,side,origQty,price,orderId) VALUES '.$ins;
        $this->db->query($ins);

        $this->data['auto_restart'] = 1;
        $this->save();
        return true;
        
    }

    function restart()
    {
        if ($this->data['idoperacion'])
        {
            $upd = "UPDATE operacion_orden SET completed = 1 
                    WHERE idoperacion = ".$this->data['idoperacion']."";
            $this->db->query($upd);      

            $this->start();
            
        }
    }

    function getOrdenes($enCurso=true)
    {
        if (!$this->data['idoperacion'])
            return false;
        $qry = "SELECT operacion.symbol, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE operacion_orden.idoperacion = ".$this->data['idoperacion'];
        if ($enCurso)
            $qry .= ' AND completed = 0'; 
        $qry .= ' ORDER BY completed, idoperacionorden';
        $stmt = $this->db->query($qry);

        $ds = array();
        while ($rw = $stmt->fetch())
        {
            $rw['sideStr'] = ($rw['side']==self::SIDE_BUY ? 'Compra' : 'Venta');
            $rw['sideClass'] = ($rw['side']==self::SIDE_BUY ? 'text-success' : 'text-danger');
            $rw['statusStr'] = $this->getTipoStatusOr($rw['status']);
            $rw['updatedStr'] = dateToStr($rw['updated'],true);
            $ds[$rw['idoperacionorden']] = $rw;
        }
        return $ds;
    }

    function deleteOrder($orderId)
    {
        if ($this->data['idoperacion'])
        {
            $del = "DELETE FROM operacion_orden 
                    WHERE idoperacion = ".$this->data['idoperacion']." 
                    AND orderId = '".$orderId."'";
            $this->db->query($del);
        }
                        
    }

    function completeOrder($orderId,$price)
    {
        if ($this->data['idoperacion'])
        {
            $upd = "UPDATE operacion_orden SET price = ".$price.", status = ".self::OR_STATUS_FILLED." 
                    WHERE idoperacion = ".$this->data['idoperacion']." 
                    AND orderId = '".$orderId."'";
            $this->db->query($upd);

        }
                        
    }

    function insertOrden($op)
    {
        $ins = " (".$op['idoperacion'].",".
                 "".$op['side'].",".
                 "".$op['origQty'].",".
                 "".$op['price'].",".
                 "'".$op['orderId']."' ".
                 ") ";
        $ins = 'INSERT INTO operacion_orden (idoperacion,side,origQty,price,orderId) VALUES '.$ins;
        $this->db->query($ins);
    }

    function getUsuariosActivos()
    {
        $qry = 'SELECT DISTINCT idusuario FROM operacion';
        $stmt = $this->db->query($qry);
        $usuarios = array();
        while ($rw = $stmt->fetch())
        {
            $usuarios[$rw['idusuario']] = $rw['idusuario'];
        }
        return $usuarios;
    }

    function autoRestart()
    {
        if ($this->data['auto_restart'])
            return true;
        return false;
    }

    function toogleAutoRestart()
    {
        if ($this->data['auto_restart'])
            $this->data['auto_restart'] = 0;
        else
            $this->data['auto_restart'] = 1;
        $this->save();
        return $this->data['auto_restart'];
    }
}