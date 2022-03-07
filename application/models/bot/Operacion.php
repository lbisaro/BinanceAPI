<?php
include_once LIB_PATH."ModelDB.php";
include_once MDL_PATH."binance/BinanceAPI.php";

class Operacion extends ModelDB
{
    protected $query = "SELECT operacion.*,
                               ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 AND status = 10 AND side = 0) 
                                compras  
                        FROM operacion";

    protected $pKey  = 'idoperacion';

    public $binStatus;

    const SIDE_BUY = 0;
    const SIDE_SELL = 1;

    //Operacion status
    const OP_STATUS_ERROR       = 1;
    const OP_STATUS_READY       = 10;
    const OP_STATUS_OPEN        = 20;
    const OP_STATUS_APALANCAOFF = 30;
    const OP_STATUS_WAITING     = 40;
    const OP_STATUS_VENTAOFF    = 50;
    const OP_STATUS_COMPLETED   = 90;

    //Order status
    const OR_STATUS_NEW = 0;
    const OR_STATUS_FILLED = 10;


    const PORCENTAJE_VENTA_UP = 2;
    const PORCENTAJE_VENTA_DOWN = 1.75;


    function __Construct($id=null)
    {
        if (!is_dir(LOG_PATH.'bot'))
            mkdir(LOG_PATH.'bot');

        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'operacion','idoperacion');

        if($id)
            $this->load($id);
    }

    function get($field)
    {
        if ($field=='strEstado')
        {
            $bin='';
            $status = $this->status();
            if ($status == self::OP_STATUS_ERROR)
                $bin = ' [Ref.:'.$this->binStatus.']';

            return $this->getTipoStatus($status).$bin;
        }
        if ($field == 'real_porc_venta_up')
        {
            if ($this->data['porc_venta_up'] > 1)
                return $this->data['porc_venta_up'];
            else
                return self::PORCENTAJE_VENTA_UP;
        }
        if ($field == 'real_porc_venta_down')
        {
            if ($this->data['porc_venta_down'] > 1)
                return $this->data['porc_venta_down'];
            else
                return self::PORCENTAJE_VENTA_DOWN;
        }
        if ($field == 'strPorcVenta')
        {
            if ($this->data['porc_venta_up'] == 0 && $this->data['porc_venta_down'] == 0 )
            {
                return 'Default';
            }
            elseif ($this->data['porc_venta_up'] == 0 && $this->data['porc_venta_down'] != 0 )
            {
                return 'Default/'.self::PORCENTAJE_VENTA_DOWN;
            }
            elseif ($this->data['porc_venta_up'] != 0 && $this->data['porc_venta_down'] == 0 )
            {
                return self::PORCENTAJE_VENTA_UP.'/Default';
            }
            else
            {
                return $this->data['porc_venta_up'].'/'.$this->data['porc_venta_down'];
            }
        }
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
        if ($this->data['multiplicador_porc']<1 || $this->data['multiplicador_porc']>10 )
            $err[] = 'Se debe especificar un multiplicador de porcentaje entre 1 y 10';

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
        $arr[self::OP_STATUS_ERROR]         = 'Error';
        $arr[self::OP_STATUS_READY]         = 'Lista para iniciar';
        $arr[self::OP_STATUS_OPEN]          = 'Abierta - Esperando confirmar compra';
        $arr[self::OP_STATUS_APALANCAOFF]   = 'En curso - Apalancamiento insuficiente';
        $arr[self::OP_STATUS_WAITING]       = 'En curso';
        $arr[self::OP_STATUS_VENTAOFF]      = 'En curso - Sin orden de venta';
        $arr[self::OP_STATUS_COMPLETED]     = 'Completa';

        if ($id=='ALL')
            return $arr;
        elseif ($id==0)
            return self::OP_STATUS_ERROR;
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
                WHERE idoperacion = '.$this->data['idoperacion'].' AND completed = 0';
        $stmt = $this->db->query($qry);
        
        $ready = false;
        $openBuy = 0;
        $openSell = 0;
        $closedBuy = 0;
        $closedSell = 0;

        $qty = 0;
        while ($rw = $stmt->fetch())
        {
            $qty++;
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_NEW)
                $openBuy++;
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_FILLED)
                $closedBuy++;
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_NEW)
                $openSell++;
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_FILLED)
                $closedSell++;
        }

        //Prepara un binario para definir el estado
        $bin = ($openBuy>0?'1':'0');
        $bin .= ($closedBuy>0?'1':'0');
        $bin .= ($openSell>0?'1':'0');
        $bin .= ($closedSell>0?'1':'0');

        $arr['0000'] = self::OP_STATUS_READY;
        $arr['0001'] = self::OP_STATUS_ERROR;
        $arr['0010'] = self::OP_STATUS_ERROR;
        $arr['0011'] = self::OP_STATUS_ERROR;
        $arr['0100'] = self::OP_STATUS_ERROR;
        $arr['0101'] = self::OP_STATUS_COMPLETED;
        $arr['0110'] = self::OP_STATUS_APALANCAOFF;
        $arr['0111'] = self::OP_STATUS_ERROR;
        $arr['1000'] = self::OP_STATUS_OPEN;
        $arr['1001'] = self::OP_STATUS_ERROR;
        $arr['1010'] = self::OP_STATUS_ERROR;
        $arr['1011'] = self::OP_STATUS_ERROR;
        $arr['1100'] = self::OP_STATUS_VENTAOFF; 
        $arr['1101'] = self::OP_STATUS_ERROR;
        $arr['1110'] = self::OP_STATUS_WAITING;
        $arr['1111'] = self::OP_STATUS_ERROR;

        $this->binStatus = $bin;

        if ($arr[$bin] == self::OP_STATUS_ERROR)
            $this->trySolveError();

        if (isset($arr[$bin]))
            return $arr[$bin];

        return self::OP_STATUS_ERROR;
    }

    function trySolveError()
    {
        if ($this->binStatus == '0100') //No fue posible crear la orden de venta luego de confirmar la compra
        {
            $ordenes = $this->getOrdenes($enCurso=true);
            foreach ($ordenes as $rw)
            {
                if ($rw['side']==self::SIDE_BUY && $rw['status']==self::OR_STATUS_FILLED)
                    $lastBuy = $rw;
            }
            if (!empty($lastBuy))
            {
                $qry = "UPDATE operacion_orden SET status = '".self::OR_STATUS_NEW."' 
                         WHERE idoperacion = ".$lastBuy['idoperacion']." 
                           AND idoperacionorden = ".$lastBuy['idoperacionorden'];
                $this->db->query($qry);
                $msg = ' trySolveError '.$this->binStatus.' - orderId: '.$lastBuy['orderId'];
                self::logBot('u:'.$this->data['idusuario'].' o:'.$lastBuy['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
            }
        }

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
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);        
    
        try {
            $data = $api->getSymbolData($symbol);
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->errLog->add($e->getMessage());
            return false;
        }
        if (empty($data))
        {
            $this->errLog->add('No fue posible encontrar informacion sobre la moneda '.$symbol);
            return false;            
        }

        //Orden para compra inicial
        $usd = $this->data['inicio_usd'];
        $qty = toDec($usd/$data['price'],$data['qtyDecs']);
        try {
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
            $msg = ' START ORDER Buy -> Qty:'.$qty.' Price: MARKET';
            self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

            return true;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->errLog->add($e->getMessage());
            return false;
        }
       
    }

    function restart()
    {
        if ($this->data['idoperacion'])
        {
            $this->complete();
            $this->start();  
        }
    }

    function complete()
    {
        if ($this->status() == self::OP_STATUS_COMPLETED)
        {
            $pnlDate = date('Y-m-d H:i:s');
            $upd = "UPDATE operacion_orden SET completed = 1 , pnlDate = '".$pnlDate."'
                    WHERE idoperacion = ".$this->data['idoperacion']." AND completed = 0";
            $this->db->query($upd);      
            $msg = ' COMPLETE ORDER';
            self::logBot('u:'.$this->data['idusuario'].' o:'.$this->data['idoperacion'].' s:'.$this->data['symbol'].' '.$msg,$echo=false);

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
        $qry .= ' ORDER BY completed, updated';
        $stmt = $this->db->query($qry);

        $ds = array();
        $compraNum = 0;
        while ($rw = $stmt->fetch())
        {
            $rw['sideStr'] = ($rw['side']==self::SIDE_BUY ? 'Compra' : 'Venta');
            $rw['sideClass'] = ($rw['side']==self::SIDE_BUY ? 'text-success' : 'text-danger');
            $rw['statusStr'] = $this->getTipoStatusOr($rw['status']);
            $rw['updatedStr'] = dateToStr($rw['updated'],true);
            if ($rw['side']==self::SIDE_BUY && !$rw['completed'])
            {
                $compraNum++;
                $rw['compraNum'] = $compraNum;
            }
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

    function updateOrder($orderId,$price,$origQty)
    {
        if ($this->data['idoperacion'])
        {
            $upd = "UPDATE operacion_orden SET price = ".$price.", origQty = ".$origQty.", updated = '".date('Y-m-d H:i:s')."', status = ".self::OR_STATUS_FILLED." 
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

    function autoRestartOff()
    {
        $this->data['auto_restart'] = 0;
        $this->save();
    }

    function getEstadisticaGeneral()
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry ="SELECT operacion.*, operacion_orden.side, count(operacion_orden.idoperacionorden) qty, sum(operacion_orden.origQty*operacion_orden.price*-1) usd
                   FROM operacion_orden
                   LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
                   WHERE idusuario = ".$idusuario." AND operacion_orden.completed >0 AND operacion_orden.side = 0 
                   GROUP BY idoperacion
               UNION ALL
               SELECT operacion.*, operacion_orden.side, count(operacion_orden.idoperacionorden) qty, sum(operacion_orden.origQty*operacion_orden.price) usd
                   FROM operacion_orden
                   LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
                   WHERE idusuario = ".$idusuario." AND operacion_orden.completed >0 AND operacion_orden.side = 1
                   GROUP BY idoperacion";
        $stmt = $this->db->query($qry);
        $data=array();
        while ($rw = $stmt->fetch())
        {
            if (!isset($data['operaciones'][$rw['idoperacion']]))
            {
                $data['operaciones'][$rw['idoperacion']]['idoperacion'] = $rw['idoperacion'];
                $data['operaciones'][$rw['idoperacion']]['symbol'] = $rw['symbol'];
                $data['operaciones'][$rw['idoperacion']]['inicio_usd'] = $rw['inicio_usd'];
                $data['operaciones'][$rw['idoperacion']]['multiplicador_compra'] = $rw['multiplicador_compra'];
                $data['operaciones'][$rw['idoperacion']]['multiplicador_porc'] = $rw['multiplicador_porc'];
                $data['operaciones'][$rw['idoperacion']]['ventas'] = 0;
                $data['operaciones'][$rw['idoperacion']]['compras'] = 0;
                $data['operaciones'][$rw['idoperacion']]['apalancamientos'] = 0;
                //$data['operaciones'][$rw['idoperacion']]['apalancamientos_promedio'] = 0;
                $data['operaciones'][$rw['idoperacion']]['ganancia_usd'] = 0;
            }

            if (!isset($data['totales']))
            {
                $data['totales']['ventas'] = 0;
                $data['totales']['compras'] = 0;
                $data['totales']['apalancamientos'] = 0;
                //$data['totales']['apalancamientos_promedio'] = 0;
                $data['totales']['ganancia_usd'] = 0;
            }

            $data['operaciones'][$rw['idoperacion']]['ganancia_usd'] += $rw['usd'];
            $data['totales']['ganancia_usd'] += $rw['usd'];

            if ($rw['side']==self::SIDE_SELL)
            {
                $data['operaciones'][$rw['idoperacion']]['ventas'] += $rw['qty'];
                $data['totales']['ventas'] += $rw['qty'];
            }
            else
            {
                $data['operaciones'][$rw['idoperacion']]['compras'] += $rw['qty'];
                $data['totales']['compras'] += $rw['qty'];
            }

        }

        $data['totales']['apalancamientos'] = $data['totales']['compras']-$data['totales']['ventas'];
        //$data['totales']['apalancamientos_promedio'] = toDec($data['totales']['compras']/$data['totales']['ventas'],2);
        if (!empty($data['operaciones']))
        {
            foreach ($data['operaciones'] as $k => $rw)
            {
                $data['operaciones'][$k]['apalancamientos'] = $rw['compras']-$rw['ventas'];
                //$data['operaciones'][$k]['apalancamientos_promedio'] = toDec($rw['compras']/$rw['ventas'],2);
            }
        }

        $data['totales']['start'] = date('Y-m-d H:i:s');
        $data['totales']['end'] = '0000-00-00 00:00:00';
        $qry = "SELECT operacion_orden.idoperacion, auto_restart, min(updated) as first_update, max(updated) as last_update 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
                WHERE idusuario = ".$idusuario." AND completed > 0 
                GROUP BY operacion_orden.idoperacion";
        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            if ($rw['auto_restart']>0)
                $rw['last_update'] = date('Y-m-d H:i:s');
            $data['operaciones'][$rw['idoperacion']]['start'] = $rw['first_update'];
            $data['operaciones'][$rw['idoperacion']]['end'] = $rw['last_update'];
            $data['operaciones'][$rw['idoperacion']]['days'] = 0;

            if ($rw['first_update']<$data['totales']['start'])
                $data['totales']['start'] = $rw['first_update'];
            if ($rw['last_update']>$data['totales']['end'])
                $data['totales']['end'] = $rw['last_update'];
        }
        if (!empty($data['operaciones']))
        {
            foreach ($data['operaciones'] as $k => $rw)
            {
                $data['operaciones'][$k]['days'] = diferenciaFechas($rw['start'],$rw['end']);
                $data['operaciones'][$k]['avg_usd_day'] = toDec($rw['ganancia_usd']/$data['operaciones'][$k]['days'],2);
            }
        }

        $data['totales']['days'] = diferenciaFechas($data['totales']['start'],$data['totales']['end']);
        $data['totales']['avg_usd_day'] = toDec($data['totales']['ganancia_usd']/$data['totales']['days'],2);



        return $data;
    }

    function getEstadisticaDiaria()
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry ="SELECT operacion.*, operacion_orden.*
                   FROM operacion_orden
                   LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
                   WHERE idusuario = ".$idusuario." AND operacion_orden.completed >0 
                   ORDER BY operacion_orden.idoperacion,updated,side";
        $stmt = $this->db->query($qry);
        $data=array();
        $totalCompras=0;
        $data['iniDate'] = date('Y-m-d',strtotime('+ 1 year'));
        while ($rw = $stmt->fetch())
        {
            if (!isset($data['operaciones'][$rw['idoperacion']]))
            {
                $data['operaciones'][$rw['idoperacion']] = $rw['symbol'];
            }

            $dayKey = date('Y-m-d',strtotime($rw['pnlDate']));
            $monthKey = date('Y-m',strtotime($rw['pnlDate']));

            if ($data['iniDate'] > $dayKey)
                $data['iniDate'] = $dayKey;

            if ($rw['side']==self::SIDE_BUY) //Cierra operacion y la guarda en la fecha
            {
                $totalCompras -= toDec($rw['origQty']*$rw['price']);
            }
            else if ($rw['side']==self::SIDE_SELL) //Cierra operacion y la guarda en la fecha
            {
                $usd = toDec($rw['origQty']*$rw['price']);
                $usd = $usd+$totalCompras;
                if (date('m',strtotime($rw['pnlDate']))==date('m'))
                {
                    $data['data']['d'][$dayKey]['total'] += $usd;
                    $data['data']['d']['total']['total'] += $usd;
                    $data['data']['d'][$dayKey][$rw['idoperacion']] += $usd;
                    $data['data']['d']['total'][$rw['idoperacion']] += $usd;
                }
            
                $data['data']['m'][$monthKey]['total'] += $usd;
                $data['data']['m']['total']['total'] += $usd;
                $data['data']['m'][$monthKey][$rw['idoperacion']] += $usd;
                $data['data']['m']['total'][$rw['idoperacion']] += $usd;

                $totalCompras = 0;

            }
            
        }
        if (!empty($data['data']['d']))
            ksort($data['data']['d']);
        if (!empty($data['data']['m']))
            ksort($data['data']['m']);

        return $data;
    }

    //LOG del Crontab BOT
    static function logBot($msg,$echo=true)
    {
        $msg = "\n".date('H:i:s').' '.$msg;

        //if (strstr(strtolower($msg),'error'))
        //{
        //    $logFile = LOG_PATH.'bot/bot_error_'.date('Ymd').'.log';
        //    file_put_contents($logFile, $msg,FILE_APPEND);
        //}        
        
        $logFile = LOG_PATH.'bot/bot_'.date('Ymd').'.log';
        file_put_contents($logFile, $msg,FILE_APPEND);
        if ($echo)  
            echo $msg; 
    }

    function delete()
    {
        $idoperacion = $this->data['idoperacion'];
        if (!empty($idoperacion))
        {
            $ordenes = $this->getOrdenes($enCurso=false);
            if (empty($ordenes))
            {
                $del = "DELETE FROM operacion WHERE idoperacion = '".$idoperacion."'";
                $this->db->query($del);                
            }
        }
    }

    function detener()
    {
        if ($this->data['idoperacion'])
        {
            if ($this->status() == self::OP_STATUS_ERROR)
            {
                $this->autoRestartOff();
                $del = 'DELETE FROM operacion_orden 
                              WHERE idoperacion = '.$this->data['idoperacion'].'
                                AND completed = 0';
                $this->db->query($del);
                return true;
            }
            else
            {
                $this->errLog->add('Para detener una Operacion, la misma debe estar en estado de Error.');
            }
        }
    }

    function getCompradoEnCurso()
    {
        $auth = UsrUsuario::getAuthInstance();

        $qry = "SELECT operacion.symbol, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE idusuario = ".$auth->get('idusuario')." 
                  AND status = ".self::OR_STATUS_FILLED."  
                  AND completed = 0 
                ORDER BY symbol, operacion_orden.updated";
        $stmt = $this->db->query($qry);
        $data = array();

        while ($rw = $stmt->fetch())
        {
            if (!isset($data[$rw['symbol']]))
            {
                $data[$rw['symbol']]['buyedUSD'] = 0;
                $data[$rw['symbol']]['buyedUnits'] = 0;
            }
            $data[$rw['symbol']]['buyedUSD'] += ($rw['origQty']*$rw['price']);
            $data[$rw['symbol']]['buyedUnits'] += $rw['origQty'];
        }
        return $data;
    }

    function getOrdenesActivas()
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');

        $qry = "SELECT operacion.symbol, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE idusuario = ".$idusuario;
        $qry .= ' AND completed = 0'; 
        $qry .= ' ORDER BY symbol, price DESC';
        $stmt = $this->db->query($qry);

        $ds = array();
        $idoperacion = 0;
        while ($rw = $stmt->fetch())
        {
            if ($idoperacion != $rw['idoperacion'])
            {
                $idoperacion = $rw['idoperacion'];
                $compraNum = 0;
            }
            $rw['sideStr'] = ($rw['side']==self::SIDE_BUY ? 'Compra' : 'Venta');
            $rw['sideClass'] = ($rw['side']==self::SIDE_BUY ? 'text-success' : 'text-danger');
            $rw['statusStr'] = $this->getTipoStatusOr($rw['status']);
            $rw['updatedStr'] = dateToStr($rw['updated'],true);
            if ($rw['side']==self::SIDE_BUY)
            {
                $compraNum++;
                $rw['compraNum'] = $compraNum;
            }
            $ds[$rw['idoperacionorden']] = $rw;
        }
        return $ds;
    }
}