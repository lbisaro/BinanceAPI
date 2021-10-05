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

        $auth = UsrUsuario::getAuthInstance();
        $this->data['idusuario'] = $auth->get('idusuario');



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
        $arr[self::OR_STATUS_FILLED]      = 'Completa';
        $arr[self::OR_STATUS_NEW]         = 'Abierta';

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

        $dbOpenOrders = array();
        $qty = 0;
        while ($rw = $stmt->fetch())
        {
            $qty++;
            if ($rw['complete']<1)
                $ready = false;
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_NEW)
                $openBuy++;
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_NEW)
                $openSell++;
            if ($rw['status'] == self::OR_STATUS_NEW)
                $dbOpenOrders[$rw['orderId']] = $rw;
        }

        if ($ready)
            return self::OP_STATUS_READY;
        elseif ($openBuy==0 && $qty==1)
            return self::OP_STATUS_RECALCULATE;
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
        if ($this->status() == self::OP_STATUS_READY)
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
        $auth = UsrUsuario::getAuthInstance();
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
        return true;
        
    }

    function getOrdenes($notCompleted=false)
    {
        if (!$this->data['idoperacion'])
            return false;
        $qry = "SELECT operacion.symbol, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE operacion_orden.idoperacion = ".$this->data['idoperacion'];
        if ($notCompleted)
            $qry .= ' AND completed = 0'; 

        $stmt = $this->db->query($qry);

        $ds = array();
        while ($rw = $stmt->fetch())
        {
            $rw['sideStr'] = ($rw['side']==self::SIDE_BUY ? 'Compra' : 'Venta');
            $rw['sideClass'] = ($rw['side']==self::SIDE_BUY ? 'text-success' : 'text-danger');
            $rw['statusStr'] = $this->getTipoStatusOr($rw['status']);
            $ds[$rw['idoperacionorden']] = $rw;
        }
        return $ds;
    }

    function matchOrdenesEnBinance($orderId=null)
    {
        $ordenesPendientes=0;
        $ordenes = $this->getOrdenes();
        if (!empty($ordenes))
        {
            $auth = UsrUsuario::getAuthInstance();
            $ak = $auth->getConfig('bncak');
            $as = $auth->getConfig('bncas');
            $api = new BinanceAPI($ak,$as);   
            $first = true;     
            foreach ($ordenes as $k=>$orden)
            {
                if (!isset($symbol))
                    $symbol = $orden['symbol'];
                if (!$orden['completed'])
                {
                    $ordenesPendientes++;
                    $bncOrder = $api->orderTradeInfo($orden['symbol'],$orden['orderId']);
                    if (!empty($bncOrder))
                    {
                        $price = 0;
                        foreach ($bncOrder as $rw)
                            $price += $rw['price'];
                        $price = toDec($price/count($bncOrder),7);
                        $ordenes[$k]['price'] = $price;
                        $upd = 'UPDATE operacion_orden 
                                   SET status = '.self::OR_STATUS_FILLED.',
                                       price = '.$price.',
                                       completed = 1
                                 WHERE orderId = '.$orden['orderId'];
                        $this->db->query($upd);
                        $ordenesPendientes--;
                    }
                }
            }
            if (count($ordenes)==1 && $ordenesPendientes==0)
            {
                //Luego de crear y completar la primer orden de compra
                //Crear las de venta y recompra por apalancamiento
            
                $data = $api->getSymbolData($symbol);
        
                $usd = $this->data['inicio_usd'];
                $qty = reset($ordenes)['origQty']*1;
                $price = reset($ordenes)['price'];
                //Orden para recompra por apalancamiento
                $newUsd = $usd*$this->data['multiplicador_compra'];
                $newPrice = toDec($price - ( $price * $this->data['multiplicador_porc'] / 100 ),$data['qtyDecsPrice']);
                $newQty = toDec(($newUsd/$newPrice),($data['qtyDecs']*1));
                $order = $api->buy($symbol, $newQty, $newPrice);
                $opr[1]['idoperacion']  = $this->data['idoperacion'];
                $opr[1]['side']         = self::SIDE_BUY;
                $opr[1]['origQty']      = $newQty;
                $opr[1]['price']        = $newPrice;
                $opr[1]['orderId']      = $order['orderId'];

         
                //Orden para venta
                $newPrice = toDec($data['price'] * 1.03,$data['qtyDecsPrice']);
                $order = $api->sell($symbol, $qty, $newPrice);
                $opr[2]['idoperacion']  = $this->data['idoperacion'];
                $opr[2]['side']         = self::SIDE_SELL;
                $opr[2]['origQty']      = $qty;
                $opr[2]['price']        = $newPrice;
                $opr[2]['orderId']      = $order['orderId'];
                
                
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
                
                $resp['opr'] = $opr;
            }
        }

        $resp['ordenesPendientes'] = $ordenesPendientes;
        return $resp;
    }

    //function calcularOperacion($inicio_precio,$inicio_usd,$multiplicador_compra,$multiplicador_porc)
    //{
    //    $arr = array();
    //    $op['porc'] = 0;
    //    $op['usd'] = $inicio_usd;
    //    $op['precio_compra'] = $inicio_precio;
    //    $op['porc_venta'] = toDec(self::COEF_A*$op['porc']+self::COEF_B,2);
    //    $op['precio_venta'] = $inicio_precio+toDec($inicio_precio * $op['porc_venta'] / 100,7);
    //    $op['compra_moneda'] = toDec($op['usd']/$op['precio_compra'],7);
    //    $arr[] = $op;
    //    for ($i = 1; $i<6 ; $i++)
    //    {
    //        $preOp = $arr[$i-1];
    //
    //        $op['porc'] = $preOp['porc']-$multiplicador_porc;
    //        $op['usd'] = $preOp['usd']*$multiplicador_compra;
    //        $op['precio_compra'] = toDec($inicio_precio+$inicio_precio*$op['porc']/100,7);
    //        $op['porc_venta'] = toDec(self::COEF_A*$op['porc']+self::COEF_B,2);
    //        $op['precio_venta'] = $inicio_precio+toDec($inicio_precio * $op['porc_venta'] / 100,7);
    //        $op['compra_moneda'] = toDec($op['usd']/$op['precio_compra'],7);
    //        $arr[] = $op;
    //    } 
    //    return $arr;
    //}
}