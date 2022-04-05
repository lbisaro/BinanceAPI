<?php
include_once LIB_PATH."ModelDB.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."Ticker.php";

class Operacion extends ModelDB
{
    protected $query = "SELECT operacion.*,
                               ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 AND status = 10 AND side = 0) 
                                compras,
                                ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 ) 
                                ordenesActivas 
                        FROM operacion";

    protected $pKey  = 'idoperacion';

    public $binStatus;

    const SIDE_BUY = 0;
    const SIDE_SELL = 1;

    //Operacion status
    const OP_STATUS_ERROR        = 1;
    const OP_STATUS_READY        = 10;
    const OP_STATUS_OPEN         = 20;
    const OP_STATUS_APALANCAOFF  = 30;
    const OP_STATUS_STOP_CAPITAL = 31;
    const OP_STATUS_WAITING      = 40;
    const OP_STATUS_VENTAOFF     = 50;
    const OP_STATUS_COMPLETED    = 90;

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
        if ($this->data['inicio_usd']<11)
            $err[] = 'Se debe especificar un importe de compra inicial mayor o igual a 11.00 USD';
        if ($this->data['capital_usd'] < $this->data['inicio_usd'])
            $err[] = 'El capital destinado a la operacion debe ser mayor o igual a la compra inicial';
        if ($this->data['multiplicador_compra']<1 || $this->data['multiplicador_compra']>2.5 )
            $err[] = 'Se debe especificar un multiplicador de compra entre 1 y 2.5';
        if ($this->data['multiplicador_porc']<0.5 || $this->data['multiplicador_porc']>10 )
            $err[] = 'Se debe especificar un multiplicador de porcentaje entre 0.5 y 10';
        if ($this->data['porc_venta_up']<1 || $this->data['porc_venta_up']>30 )
            $err[] = 'Se debe especificar un porcentaje de venta inicial entre 1 y 30';
        if ($this->data['porc_venta_down']<1 || $this->data['porc_venta_down']>30 )
            $err[] = 'Se debe especificar un porcentaje de venta palanca entre 1 y 300';

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
        $arr[self::OP_STATUS_ERROR]          = 'Error';
        $arr[self::OP_STATUS_READY]          = 'Lista para iniciar';
        $arr[self::OP_STATUS_OPEN]           = 'Abierta - Esperando confirmar compra';
        $arr[self::OP_STATUS_APALANCAOFF]    = 'En curso - Apalancamiento insuficiente';
        $arr[self::OP_STATUS_STOP_CAPITAL]   = 'En curso - Stop por Limite de Capital';
        $arr[self::OP_STATUS_WAITING]        = 'En curso';
        $arr[self::OP_STATUS_VENTAOFF]       = 'En curso - Sin orden de venta';
        $arr[self::OP_STATUS_COMPLETED]      = 'Completa';

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
        $totalBuyed = 0;
        $lastUsdBuyed = 0; 
        $lastBuyPrice = 0;

        $qty = 0;
        while ($rw = $stmt->fetch())
        {
            $qty++;
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_NEW)
            {
                $openBuy++;
            }
            if ($rw['side'] == self::SIDE_BUY && $rw['status'] == self::OR_STATUS_FILLED)
            {
                $totalBuyed += ($rw['origQty']*$rw['price']);
                $lastUsdBuyed = ($rw['origQty']*$rw['price']);
                $closedBuy++;
            }
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_NEW)
            {
                $openSell++;
            }
            if ($rw['side'] == self::SIDE_SELL && $rw['status'] == self::OR_STATUS_FILLED)
            {
                $closedSell++;
            }
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
        $arr['0111'] = self::OP_STATUS_APALANCAOFF;
        $arr['1000'] = self::OP_STATUS_OPEN;
        $arr['1001'] = self::OP_STATUS_ERROR;
        $arr['1010'] = self::OP_STATUS_ERROR;
        $arr['1011'] = self::OP_STATUS_ERROR;
        $arr['1100'] = self::OP_STATUS_VENTAOFF; 
        $arr['1101'] = self::OP_STATUS_ERROR;
        $arr['1110'] = self::OP_STATUS_WAITING;
        $arr['1111'] = self::OP_STATUS_ERROR;


        //Control sobre APALANCAMIENTO INSUFICIENTE vs LIMITE DE CAPITAL
        if ($this->data['capital_usd']>0)
        {
            $totalBuyed = toDec($totalBuyed);
            $nextBuy = $lastUsdBuyed*$this->data['multiplicador_compra'];
            if (($totalBuyed+$nextBuy)>$this->data['capital_usd'])
                 $arr['0110'] = self::OP_STATUS_STOP_CAPITAL;
            
        }


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
                $msg = ' Warning - trySolve '.$this->binStatus.' - orderId: '.$lastBuy['orderId'];
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
        {
            if (!$this->data['auto_restart'])
                $this->errLog->add('No es posible iniciar la operacion - La recompra automatica se encuentra bloqueada.');
            else
                $this->errLog->add('No es posible iniciar la operacion');
            return false;
        }

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
            

            //Actualizar el multiplicador de porcentaje si esta seteado en AUTO, y si la moneda esta seteada en Ticker
            if ($this->data['multiplicador_porc_auto'])
            {
                $tck = new Ticker($symbol);
                $allData['symbol'] = $tck->get('tickerid');
                if ($tck->get('tickerid') == $symbol)
                {
                    $symbolData = $api->getSymbolData($symbol);
                    $palancas = $tck->calcularPalancas($symbolData['price']);
                    $qtyPalancas = count($palancas['porc']);
                    $multPorc = $tck->calcularMultiplicadorDePorcentaje($qtyPalancas,end($palancas['porc']));
                    $this->data['multiplicador_porc'] = toDec($multPorc);
                    $this->save();
                    $msg = ' Update Mult.Porc = '.$multPorc;
                    self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                }
            }

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
        $upd = "UPDATE operacion SET auto_restart = '".($this->data['auto_restart']?'1':'0')."' WHERE idoperacion = ".$this->data['idoperacion'];
        $this->db->query($upd);
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
                if ($data['operaciones'][$k]['days']!=0)
                    $data['operaciones'][$k]['avg_usd_day'] = toDec($rw['ganancia_usd']/$data['operaciones'][$k]['days'],2);
            }
        }

        $data['totales']['days'] = diferenciaFechas($data['totales']['start'],$data['totales']['end']);
        if ($data['totales']['days']!=0)
            $data['totales']['avg_usd_day'] = toDec($data['totales']['ganancia_usd']/$data['totales']['days'],2);



        return $data;
    }    

    function getEstadisticaDiaria()
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry ="SELECT operacion.symbol, 
                      date_format(pnlDate, '%Y-%m-%d') AS fecha, 
                      sum((origQty*price* IF(side=0, -1, 1))) as USD
               FROM operacion_orden
               LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
               WHERE idusuario = ".$idusuario." AND operacion_orden.completed > 0 AND month(pnlDate) = ".date('m')."
               GROUP BY operacion.symbol,date_format(pnlDate, '%Y-%m-%d')
               ORDER BY operacion.symbol,date_format(pnlDate, '%Y-%m-%d')";
        $stmt = $this->db->query($qry);
        $data=array();
        $data['symbols'] = array();
        $data['iniDate'] = date('Y-m-d');
        while ($rw = $stmt->fetch())
        {
            $data['symbols'][$rw['symbol']] = $rw['symbol'];
            $data[$rw['fecha']][$rw['symbol']] = toDec($rw['USD']);
            if (!isset($data['total'][$rw['symbol']]))
                $data['total'][$rw['symbol']]=0;
            $data['total'][$rw['symbol']] += toDec($rw['USD']);
            if (!isset($data['total']['total']))
                $data['total']['total']=0;
            $data['total']['total'] += toDec($rw['USD']);
            if (!isset($data[$rw['fecha']]['total']))
                $data[$rw['fecha']]['total']=0;
            $data[$rw['fecha']]['total'] += toDec($rw['USD']);
            
            if ($rw['fecha'] < $data['iniDate'])
                $data['iniDate'] = $rw['fecha'];
        }

        return $data;
    }

    function getEstadisticaMensual()
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry ="SELECT operacion.symbol, 
                      date_format(pnlDate, '%Y-%m') AS mes, 
                      sum((origQty*price* IF(side=0, -1, 1))) as USD
               FROM operacion_orden
               LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion
               WHERE idusuario = ".$idusuario." AND operacion_orden.completed > 0 
               GROUP BY operacion.symbol,date_format(pnlDate, '%Y-%m')
               ORDER BY operacion.symbol,date_format(pnlDate, '%Y-%m')";
        $stmt = $this->db->query($qry);
        $data=array();
        $data['symbols'] = array();
        $data['iniMonth'] = date('Y-m');
        while ($rw = $stmt->fetch())
        {
            $data['symbols'][$rw['symbol']] = $rw['symbol'];
            $data[$rw['mes']][$rw['symbol']] = toDec($rw['USD']);
            if (!isset($data['total'][$rw['symbol']]))
                $data['total'][$rw['symbol']]=0;
            $data['total'][$rw['symbol']] += toDec($rw['USD']);
            if (!isset($data['total']['total']))
                $data['total']['total']=0;
            $data['total']['total'] += toDec($rw['USD']);
            if (!isset($data[$rw['mes']]['total']))
                $data[$rw['mes']]['total']=0;
            $data[$rw['mes']]['total'] += toDec($rw['USD']);
            
            if ($rw['mes'] < $data['iniMonth'])
                $data['iniMonth'] = $rw['mes'];
        }

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

    static function getLog($prms=array())
    {
        $folder = LOG_PATH.'bot/';
        $logFiles=array();
        $log=array();

        //Cargando $prmspor Default
        if (!isset($prms['qtyDays']))
            $prms['qtyDays'] = 2; //Cantidad de dias
        if (!isset($prms['qtyHours']))
            $prms['qtyHours'] = 24; //Cantidad de horas
        if (!isset($prms['idusuario']))
            $prms['idusuario'] = null;


        $limitDatetime = date('Y-m-d H:is',strtotime('- '.$prms['qtyHours'].' hours'));        
        //Obteniendo archivos
        $scandir = scandir($folder,SCANDIR_SORT_DESCENDING);
        $cnt=0;
        foreach ($scandir as $file)
        {
            if ($file != '.' && $file != '..' && $file != 'status.log' && $file != 'lock.status')
            {
                $logFiles[] = $file;
                $cnt++;
            }
            if ($cnt>=$prms['qtyDays'])
                break;
        }

        if (!empty($logFiles))
        {
            rsort($logFiles);
            foreach ($logFiles as $file)
            {
                $kDate = substr($file,4,4).'-'.substr($file,8,2).'-'.substr($file,10,2);
                $content = '';
                $archivo = fopen($folder.$file,'r');
                $lin=0;
                while ($linea = fgets($archivo)) 
                {
                    $kDateTime = $kDate.' '.substr($linea,0,8);
                    $text = substr($linea,9);
                    $text = str_replace("\n", "", $text);

                    $type='log';
                    if (strstr(strtolower($linea),'error'))
                        $type='error';
                    if (strstr(strtolower($linea),'warning'))
                        $type='warning';

                    /*
                    $show = true;
                    if ($prms['idusuario'] && !strpos($linea,' u:'.$prms['idusuario'].' ') )
                        $show = false;
                    if ($prms['idoperacion'] && !strpos($linea,' o:'.$prms['idoperacion'].' ') )
                        $show = false;
                    if ($prms['symbol'] && !strpos($linea,' s:'.$prms['symbol'].' ') )
                        $show = false;

                    if ($show)
                        $content = $linea.$salto.$content; 
                        */
                    if ($text && $kDateTime > $limitDatetime)
                    {
                        array_unshift($log, array('datetime'=>$kDateTime,
                                       'type'=>$type,
                                       'text'=> $text ));
                        
                        
                    }
                }
            }
        }

        return $log;
    }

    static function cleanLog()
    {
        $folder = LOG_PATH.'bot/';
        $logFiles=array();
        $errorFiles=array();

        $scandir = scandir($folder,SCANDIR_SORT_DESCENDING);
        foreach ($scandir as $file)
        {
            if (substr($file,0,4) == 'bot_' && $file < 'bot_'.date('Ymd',strtotime('-1 month')).'.log')
                unlink($folder.$file);
        }
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
            if ($rw['side']==Operacion::SIDE_BUY)
            {
                $data[$rw['symbol']]['buyedUSD'] += ($rw['origQty']*$rw['price']);
                $data[$rw['symbol']]['buyedUnits'] += $rw['origQty'];
            }
            else
            {
                $data[$rw['symbol']]['buyedUSD'] -= ($rw['origQty']*$rw['price']);
                $data[$rw['symbol']]['buyedUnits'] -= $rw['origQty'];
            }
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

    static public function readLockFile()
    {
        $lockFileText = file_get_contents(LOCK_FILE);
        return $lockFileText;
    }

    static public function isLockedProcess()
    {
        $lockFileText = file_get_contents(LOCK_FILE);
        return ($lockFileText?true:false);
    }

    static public function lockProcess($proc='NO_PROC')
    {
        $lockFileText = file_get_contents(LOCK_FILE);
        $lockFileTime = substr($lockFileText,0,19);
        if (empty($lockFileText) || $lockFileTime < date('Y-m-d H:i:s',strtoTime('- 4 minutes')))
        {
            file_put_contents(LOCK_FILE, date('Y-m-d H:i:s').' '.$proc);
            chmod(LOCK_FILE, 666);
            return true;

        }
        return false;

    }

    static public function unlockProcess()
    {
        $lockFileText = file_get_contents(LOCK_FILE);
        file_put_contents(LOCK_FILE, '');
        chmod(LOCK_FILE, 666);
    }

    function liquidarOrden($idoperacionorden)
    {
        if (!$this->data['idoperacion'] || !$idoperacionorden)
            return false;

        $qry = 'SELECT * FROM operacion_orden 
                 WHERE idoperacion = '.$this->data['idoperacion'].
                  ' AND idoperacionorden = '.$idoperacionorden;
        $stmt = $this->db->query($qry);
        $rw = $stmt->fetch();
        if (!empty($rw) && $rw['idoperacionorden'] == $idoperacionorden)
        {

            $usr = new UsrUsuario($this->data['idusuario']);
            $ak = $usr->getConfig('bncak');
            $as = $usr->getConfig('bncas');
            $api = new BinanceAPI($ak,$as); 

            $ordenALiquidar = $rw;
            $ret['orderIdBuy'] = $rw['orderId'];
            $symbol = $this->get('symbol');
            $qty = $rw['origQty'];
            $idusuario = $this->data['idusuario'];
            try {
                $order = $api->marketSell($symbol, $qty);
                $op['idoperacion']  = $this->data['idoperacion'];
                $op['side']         = self::SIDE_SELL;
                $op['origQty']      = $qty;
                $op['price']        = 0;
                $op['orderId']      = $order['orderId'];
                $ret['orderIdSell'] = $order['orderId'];
                $this->insertOrden($op);
                $msg = ' START ORDER Sell -> Qty:'.$qty.' Price: MARKET - Liquidar Orden';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $this->errLog->add($e->getMessage());
                return false;
            }

            sleep(2);
            $orderStatus = $api->orderStatus($symbol,$order['orderId']);
            while ($orderStatus['status'] != 'FILLED')
            {
                sleep(2);
                $orderStatus = $api->orderStatus($symbol,$order['orderId']);
            }

            //Actualizando estado de la orden de venta
            $tradeInfo = $api->orderTradeInfo($symbol,$order['orderId']);
            $tradeQty = 0;
            $tradeUsd = 0;
            if (!empty($tradeInfo))
            {
                foreach($tradeInfo as $tii)
                {
                    $tradeQty += $tii['qty'];
                    $tradeUsd += $tii['quoteQty'];
                }
                $tradePrice = toDec($tradeUsd/$tradeQty,8);
                $this->updateOrder($order['orderId'],$tradePrice,$tradeQty);
                $msg = 'Liquidar Operacion Update Venta';
                self::logBot('u:'.$this->data['idusuario'].' o:'.$this->data['idoperacion'].' s:'.$this->data['symbol'].' '.$msg,$echo=false);
            }

            //Actualizando pnlDate y completed
            $pnlDate = date('Y-m-d H:i:s');
            $orderIdWhereIn = "('".$ret['orderIdSell']."','".$ret['orderIdBuy']."')";
            $upd = "UPDATE operacion_orden SET completed = 1 , pnlDate = '".$pnlDate."'
                    WHERE idoperacion = ".$this->data['idoperacion']." AND orderId IN ".$orderIdWhereIn." ";
            $this->db->query($upd);      
            $msg = ' COMPLETE ORDER - Liquidar Orden';
            self::logBot('u:'.$this->data['idusuario'].' o:'.$this->data['idoperacion'].' s:'.$this->data['symbol'].' '.$msg,$echo=false);

            //Creando una nueva orden de compra y venta, solo si ha ordenes de compra pendientes de venta
            $ordenesActivas = $this->getOrdenes();
            if (!empty($ordenesActivas))
            {
                //Creando una nueva orden de compra en el valor de la orden liquidada

                $newQty = $ordenALiquidar['origQty'];
                $newPrice = $ordenALiquidar['price'];
                $newUsd = toDec($newQty*$newPrice);
                $msg = ' Buy -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newUsd).' - Recompra por liquidacion';
                self::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);

                try {
                    $limitOrder = $api->buy($symbol, $newQty, $newPrice);
                    $aOpr['idoperacion']  = $this->data['idoperacion'];
                    $aOpr['side']         = self::SIDE_BUY;
                    $aOpr['origQty']      = $newQty;
                    $aOpr['price']        = $newPrice;
                    $aOpr['orderId']      = $limitOrder['orderId'];
                    $this->insertOrden($aOpr);               
                } catch (Throwable $e) {
                    $msg = "Error: " . $e->getMessage().' - Recompra por liquidacion';
                    self::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
                }

                //Creando orden de venta
                
                //Consulta billetera en Binance para ver si se puede recomprar
                $symbolData = $api->getSymbolData($symbol);
                $account = $api->account();
                $asset = str_replace($symbolData['quoteAsset'],'',$symbol);
                $unitsFree = '0.00';
                $unitsLocked = '0.00';
                foreach ($account['balances'] as $balances)
                {
                    if ($balances['asset'] == $asset)
                    {
                        $unitsFree = $balances['free'];
                        $unitsLocked = $balances['locked'];
                    }
                    if ($balances['asset'] == $symbolData['quoteAsset'])
                    {
                        $usdFreeToBuy = $balances['free'];
                    }
                }

                //Obteniendo datos de ordenes anteriores
                $dbOrders = $this->getOrdenes();

                $totUsdBuyed=0;
                $totUnitsBuyed=0;
                $maxCompraNum=1;
                foreach ($dbOrders as $order)
                {
                    if ($order['side']==self::SIDE_BUY && $order['status'] ==self::OR_STATUS_FILLED)
                    {
                       
                        $totUnitsBuyed += $order['origQty'];
                        $totUsdBuyed += ($order['origQty']*$order['price']);
                        if ($order['compraNum']>$maxCompraNum)
                            $maxCompraNum = $order['compraNum'];

                    }
                }

                $strControlUnitsBuyed = ' - totUnitsBuyed: '.$totUnitsBuyed.' - unitsFree: '.$unitsFree;

                //Si la cantidad de unidades compradas segun DB es mayor a la cantidad de unidades en API
                //Toma la cantidad de unidades en la API
                if (($totUnitsBuyed*1) > ($unitsFree*1))
                {
                    $msg = ' WARNING '.$strControlUnitsBuyed;
                    self::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
                    $totUnitsBuyed = $unitsFree;
                }
                
                //Orden para venta
                if ($maxCompraNum==1) 
                    $porcentaje = $this->get('real_porc_venta_up');
                else
                    $porcentaje = $this->get('real_porc_venta_down');

                $newUsd = $totUsdBuyed * (1+($porcentaje/100));
                $newPrice = toDec(($newUsd / $totUnitsBuyed),$symbolData['qtyDecsPrice']);
                $newQty = toDecDown($totUnitsBuyed,$symbolData['qtyDecs']);

                $msg = ' Sell -> Qty:'.$newQty.' Price:'.$newPrice.' USD:'.toDec($newPrice*$newQty).' +'.$porcentaje.'% - Liquidar Orden';
                self::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);

                try {
                    $limitOrder = $api->sell($symbol, $newQty, $newPrice);
                    $aOpr['idoperacion']  = $idoperacion;
                    $aOpr['side']         = self::SIDE_SELL;
                    $aOpr['origQty']      = $newQty;
                    $aOpr['price']        = $newPrice;
                    $aOpr['orderId']      = $limitOrder['orderId'];
                    $this->insertOrden($aOpr); 
                } catch (Throwable $e) {
                    $msg = "Error: " . $e->getMessage().$strControlUnitsBuyed;
                    self::logBot('u:'.$idusuario.' o:'.$idoperacion.' s:'.$symbol.' '.$msg,$echo=false);
                }
            }
            
            return true;
        }
        return false;
    }

    function gestionDelCapital()
    {
        $data = array();
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $oprs = $this->getDataSet('idusuario = '.$idusuario,'auto_restart DESC,symbol,idoperacion');
        foreach ($oprs as $op)
        {
            $data[$op['idoperacion']]['idoperacion']  = $op['idoperacion'];
            $data[$op['idoperacion']]['symbol']       = $op['symbol'];
            $data[$op['idoperacion']]['capital']      = $op['capital_usd'];
            $data[$op['idoperacion']]['auto_restart'] = $op['auto_restart'];

        }

        $oact = $this->getOrdenesActivas();
        foreach ($oact as $orden)
        {
            $usd = toDec($orden['price']*$orden['origQty']);
            if ($orden['side']==self::SIDE_SELL)
                $usd = $usd*(-1);

            if (!isset($data[$orden['idoperacion']]['comprado']))
                $data[$orden['idoperacion']]['comprado']=0;
            if (!isset($data[$orden['idoperacion']]['en_venta']))
                $data[$orden['idoperacion']]['en_venta']=0;
            if (!isset($data[$orden['idoperacion']]['bloqueado']))
                $data[$orden['idoperacion']]['bloqueado']=0;

            if ($orden['status'] == self::OR_STATUS_FILLED)
                $data[$orden['idoperacion']]['comprado'] += $usd;
            if ($orden['status'] == self::OR_STATUS_NEW && $orden['side']==self::SIDE_BUY) 
                $data[$orden['idoperacion']]['bloqueado'] += $usd;
            if ($orden['status'] == self::OR_STATUS_NEW && $orden['side']==self::SIDE_SELL) 
                $data[$orden['idoperacion']]['en_venta'] -= $usd;


        }

        foreach ($oprs as $op)
        {
            $idoperacion = $op['idoperacion'];
            if ($data[$idoperacion]['comprado']==0 && $data[$idoperacion]['bloueado']==0 && $data[$idoperacion]['en_venta']==0)
            {
                unset($data[$idoperacion]);
            }
            else
            {
                if ($data[$idoperacion]['capital'] == 0 || $data[$idoperacion]['capital']<($data[$idoperacion]['comprado']+$data[$idoperacion]['bloqueado']))
                    $data[$idoperacion]['capital'] = $data[$idoperacion]['comprado']+$data[$idoperacion]['bloqueado'];
                $data[$idoperacion]['remanente'] = $data[$idoperacion]['capital']-$data[$idoperacion]['comprado']-$data[$idoperacion]['bloqueado'];

                $data[$idoperacion]['porc_venta'] = '';
                if ($data[$idoperacion]['en_venta'] != 0 && $data[$idoperacion]['comprado'] != 0)
                    $data[$idoperacion]['porc_venta'] = toDec(((($data[$idoperacion]['en_venta']/$data[$idoperacion]['comprado'])-1)*100));
            }

        }

        return $data;
    }
}