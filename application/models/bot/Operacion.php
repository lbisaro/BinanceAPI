<?php
include_once LIB_PATH."ModelDB.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."Ticker.php";

class Operacion extends ModelDB
{
    protected $query = "SELECT operacion.*,
                               tickers.*, 
                               ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 AND status = 10 AND side = 0) 
                                compras,                               
                                ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 AND status = 10 AND side = 1) 
                                ventas,
                                ( SELECT count(idoperacionorden) 
                                   FROM operacion_orden 
                                  WHERE operacion.idoperacion = operacion_orden.idoperacion
                                    AND completed = 0 ) 
                                ordenesActivas 
                        FROM operacion
                        LEFT JOIN tickers ON operacion.symbol = tickers.tickerid ";

    protected $pKey  = 'idoperacion';

    public $binStatus;

    public $presetDecs = array();

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
    const OP_STATUS_COMPRAOFF    = 60;
    const OP_STATUS_COMPLETED    = 90;

    //Destino del profit
    const OP_DESTINO_PROFIT_QUOTE = 0;
    const OP_DESTINO_PROFIT_BASE = 1;

    //Order status
    const OR_STATUS_NEW = 0;
    const OR_STATUS_FILLED = 10;

    //Tipos de operaciones
    const OP_TIPO_APL = 0;
    const OP_TIPO_APLCRZ = 1;
    const OP_TIPO_APLSHRT = 2;

    const PORCENTAJE_VENTA_UP = 2;
    const PORCENTAJE_VENTA_DOWN = 1.75;



    function __Construct($id=null)
    {
        if (!is_dir(LOG_PATH.'bot'))
            mkdir(LOG_PATH.'bot');

        parent::__Construct();

        $tck = new Ticker();
        $this->presetDecs = $tck->presetDecs;

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
            return $this->data['porc_venta_up'];
        }
        if ($field == 'real_porc_venta_down')
        {
            return $this->data['porc_venta_down'];
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
        if ($field == 'capital_asset')
        {
            if ($this->data['destino_profit']==self::OP_DESTINO_PROFIT_QUOTE)
                return $this->data['quote_asset'];

            return $this->data['base_asset'];
        }
        if ($field == 'qty_decs_capital')
        {
            if ($this->data['destino_profit']==self::OP_DESTINO_PROFIT_QUOTE)
                return $this->data['qty_decs_quote'];

            return $this->data['qty_decs_units'];
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
        if ($this->data['capital_usd'] < $this->data['inicio_usd'])
            $err[] = 'El capital destinado a la operacion debe ser mayor o igual a la compra inicial';
        if ($this->data['tipo'] == self::OP_TIPO_APL)
        {
            if ($this->data['inicio_usd']<11)
                $err[] = 'Se debe especificar un importe de compra inicial mayor o igual a 11.00 USD';
            if ($this->data['multiplicador_compra']<1 || $this->data['multiplicador_compra']>2.5 )
                $err[] = 'Se debe especificar un multiplicador de compra entre 1 y 2.5';
            if ($this->data['multiplicador_porc']<0.5 || $this->data['multiplicador_porc']>10 )
                $err[] = 'Se debe especificar un multiplicador de porcentaje entre 0.5 y 10';
            if ($this->data['porc_venta_up']<0.5 || $this->data['porc_venta_up']>30 )
                $err[] = 'Se debe especificar un porcentaje de venta inicial entre 0.5 y 30';
            if ($this->data['porc_venta_down']<0.5 || $this->data['porc_venta_down']>100 )
                $err[] = 'Se debe especificar un porcentaje de venta palanca entre 0.5 y 100';
            
        }

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
        $strEsperandoConfirmar = 'compra';
        if ($this->data['tipo'] == self::OP_TIPO_APLSHRT)
            $strEsperandoConfirmar = 'venta';

        $arr[self::OP_STATUS_ERROR]          = 'Error';
        $arr[self::OP_STATUS_READY]          = 'Lista para iniciar';
        $arr[self::OP_STATUS_OPEN]           = 'Abierta - Esperando confirmar '.$strEsperandoConfirmar;
        $arr[self::OP_STATUS_APALANCAOFF]    = 'En curso - Apalancamiento insuficiente';
        $arr[self::OP_STATUS_STOP_CAPITAL]   = 'En curso - Stop por Limite de Capital';
        $arr[self::OP_STATUS_WAITING]        = 'En curso';
        $arr[self::OP_STATUS_VENTAOFF]       = 'En curso - Sin orden de venta';
        $arr[self::OP_STATUS_COMPRAOFF]      = 'En curso - Sin orden de compra';
        $arr[self::OP_STATUS_COMPLETED]      = 'Completa';

        if ($id=='ALL')
            return $arr;
        elseif ($id==0)
            return self::OP_STATUS_ERROR;
        elseif (isset($arr[$id]))
            return $arr[$id];
        return 'Desconocido'.($id?' ['.$id.']':'');
    }


    function getTipoOperacion($id='ALL',$nombreCorto=false)
    {
        $id=$id*1;
        if (!$id)
            $id = self::OP_TIPO_APL;
        if ($nombreCorto)
        {
            $arr[self::OP_TIPO_APL]              = 'APL';
            $arr[self::OP_TIPO_APLCRZ]           = 'LONG';
            $arr[self::OP_TIPO_APLSHRT]          = 'SHORT';
        }
        else
        {
            $arr[self::OP_TIPO_APL]              = 'Apalancamiento';
            $arr[self::OP_TIPO_APLCRZ]           = 'Martingala LONG';
            $arr[self::OP_TIPO_APLSHRT]          = 'Martingala SHORT';
        }

        if (isset($arr[$id]))
            return $arr[$id];
        elseif ($id=='ALL')
            return $arr;
        
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
                $totalSelled += ($rw['origQty']);
                $lastBaseSelled = ($rw['origQty']);
                $closedSell++;
            }
        }

        //Prepara un binario para definir el estado
        $bin = ($openBuy>0?'1':'0');
        $bin .= ($closedBuy>0?'1':'0');
        $bin .= ($openSell>0?'1':'0');
        $bin .= ($closedSell>0?'1':'0');

        if ($this->data['tipo'] != self::OP_TIPO_APLSHRT)
        {
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
        }
        else
        {    //ob cb os cs
            $arr['0000'] = self::OP_STATUS_READY;
            $arr['0100'] = self::OP_STATUS_ERROR;
            $arr['1000'] = self::OP_STATUS_ERROR;
            $arr['1100'] = self::OP_STATUS_ERROR;
            $arr['0001'] = self::OP_STATUS_ERROR;
            $arr['0101'] = self::OP_STATUS_COMPLETED;
            $arr['1001'] = self::OP_STATUS_APALANCAOFF;
            $arr['1101'] = self::OP_STATUS_APALANCAOFF;
            $arr['0010'] = self::OP_STATUS_OPEN;
            $arr['0110'] = self::OP_STATUS_ERROR;
            $arr['1010'] = self::OP_STATUS_ERROR;
            $arr['1110'] = self::OP_STATUS_ERROR;
            $arr['0011'] = self::OP_STATUS_COMPRAOFF; 
            $arr['0111'] = self::OP_STATUS_ERROR;
            $arr['1011'] = self::OP_STATUS_WAITING;
            $arr['1111'] = self::OP_STATUS_ERROR;
        }


        //Control sobre APALANCAMIENTO INSUFICIENTE vs LIMITE DE CAPITAL
        if ($this->data['capital_usd']>0)
        {
            if ($this->data['tipo'] != self::OP_TIPO_APLSHRT)
            {
                $totalBuyed = toDec($totalBuyed);
                $nextBuy = $lastUsdBuyed*$this->data['multiplicador_compra'];
                if (($totalBuyed+$nextBuy)>$this->data['capital_usd'])
                     $arr['0110'] = self::OP_STATUS_STOP_CAPITAL;
            }
            else
            {
                $totalSelled = toDec($totalSelled);
                $nextBuy = $lastBaseSelled*$this->data['multiplicador_compra'];
                if (($totalBuyed+$nextBuy)>$this->data['capital_usd'])
                     $arr['1001'] = self::OP_STATUS_STOP_CAPITAL;

            }
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
        if ($this->data['stop'])
            return;
        
        //No fue posible crear la orden de venta luego de confirmar la compra
        if ($this->data['tipo'] != self::OP_TIPO_APLSHRT && $this->binStatus == '0100') 
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
                self::logBot('u:'.$this->data['idusuario'].' o:'.$lastBuy['idoperacion'].' s:'.$this->data['symbol'].' '.$msg,$echo=false);
            }
        }

        //No fue posible crear la orden de compra luego de confirmar la venta
        if ($this->data['tipo'] == self::OP_TIPO_APLSHRT && $this->binStatus == '0001') 
        {
            $ordenes = $this->getOrdenes($enCurso=true);
            foreach ($ordenes as $rw)
            {
                if ($rw['side']==self::SIDE_SELL && $rw['status']==self::OR_STATUS_FILLED)
                    $lastSell = $rw;
            }
            if (!empty($lastSell))
            {
                $qry = "UPDATE operacion_orden SET status = '".self::OR_STATUS_NEW."' 
                         WHERE idoperacion = ".$lastSell['idoperacion']." 
                           AND idoperacionorden = ".$lastSell['idoperacionorden'];
                $this->db->query($qry);
                $msg = ' Warning - trySolve '.$this->binStatus.' - orderId: '.$lastBuy['orderId'];
                self::logBot('u:'.$this->data['idusuario'].' o:'.$lastBuy['idoperacion'].' s:'.$this->data['symbol'].' '.$msg,$echo=false);
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
                $this->errLog->add('No es posible iniciar la operacion - El reinicio automatico se encuentra bloqueada.');
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

        //Orden para compra/venta inicial
        $startOp = 'compra';
        if ($this->data['tipo'] == self::OP_TIPO_APLSHRT)
            $startOp = 'venta';

        if ($startOp == 'compra')
        {
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
                /*
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
                */

                return true;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                $this->errLog->add($e->getMessage());
                return false;
            }
            
        }
        else //Venta
        {
            $qty = $this->data['inicio_usd'];
            $qty = toDec($qty,$data['qtyDecs']);
            try {
                $order = $api->marketSell($symbol, $qty);
                $opr[1]['idoperacion']  = $this->data['idoperacion'];
                $opr[1]['side']         = self::SIDE_SELL;
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
                $msg = ' START ORDER Sell -> Qty:'.$qty.' Price: MARKET';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                

                //Actualizar el multiplicador de porcentaje si esta seteado en AUTO, y si la moneda esta seteada en Ticker
                /*
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
                */
                return true;
            } catch (Throwable $e) {
                $msg = $e->getMessage();
                echo "\n".$msg;
        
                $this->errLog->add($e->getMessage());
                return false;
            }
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

    function getOrdenes($enCurso=true,$order=null)
    {
        if (!$this->data['idoperacion'])
            return false;
        $qry = "SELECT operacion.symbol, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE operacion_orden.idoperacion = ".$this->data['idoperacion'];
        
        if ($enCurso)
            $qry .= ' AND completed = 0'; 
        
        if ($order)
            $qry .= ' ORDER BY '.$order;
        else
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
            if ($rw['side']==self::SIDE_SELL && !$rw['completed'])
            {
                $ventaNum++;
                $rw['ventaNum'] = $ventaNum;
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

    function toogleStop()
    {
        if ($this->data['stop'])
            $this->data['stop'] = 0;
        else
            $this->data['stop'] = 1;
        $upd = "UPDATE operacion SET stop = '".($this->data['stop']?'1':'0')."' WHERE idoperacion = ".$this->data['idoperacion'];
        $this->db->query($upd);
        return $this->data['stop'];
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

    function getPnlDiario($idoperacion=null)
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry = "SELECT operacion.symbol,
                       pnlDate,
                       side, 
                       price,
                       origQty,
                       tickers.base_asset,
                       tickers.quote_asset,
                       tickers.qty_decs_units as base_decs,
                       tickers.qty_decs_quote as quote_decs
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion 
                LEFT JOIN tickers ON tickers.tickerid = operacion.symbol
                WHERE operacion.idusuario = ".$idusuario." AND operacion_orden.completed = 1 AND year(pnlDate)>=".date('Y')." AND month(pnlDate)>=".date('m');
        if ($idoperacion)
            $qry .= ' AND idoperacion = '.$idoperacion;
        $stmt = $this->db->query($qry);
        $data=array();
        $data['assets'] = array();
        $data['assets_decs'] = array();
        $data['iniDate'] = date('Y-m-d');
        $data['total'] = array();
        while ($rw = $stmt->fetch())
        {
            $dia = substr($rw['pnlDate'],0,10);

            if ($rw['side']==self::SIDE_BUY)
            {
                $rw['base']  = $rw['origQty'];
                $rw['quote'] = (-$rw['origQty'])*$rw['price'];
            }
            else
            {
                $rw['base']  = -$rw['origQty'];
                $rw['quote'] = $rw['origQty']*$rw['price'];
            }


            //Calculo de Base
            $data['assets'][$rw['base_asset']] = $rw['base_asset'];
            if (isset($this->presetDecs[$rw['base_asset']]))
            {
                $data['assets_decs'][$rw['base_asset']] = $this->presetDecs[$rw['base_asset']]; 
            }
            else
            {
                $data['assets_decs'][$rw['base_asset']] = $rw['base_decs'];
            }
            if (!isset($data[$dia][$rw['base_asset']]))
                $data[$dia][$rw['base_asset']] = 0;
            $data[$dia][$rw['base_asset']] += $rw['base'];
            
            if (!isset($data['total'][$rw['base_asset']]))
                $data['total'][$rw['base_asset']]=0;
            $data['total'][$rw['base_asset']] += $rw['base'];



            //Calculo de Quote
            $data['assets'][$rw['quote_asset']] = $rw['quote_asset'];
            if (isset($this->presetDecs[$rw['quote_asset']]))
            {
                $data['assets_decs'][$rw['quote_asset']] = $this->presetDecs[$rw['quote_asset']]; 
            }
            else
            {
                $decs = ($rw['quote_decs']>$rw['base_decs'] ? $rw['quote_decs'] : $rw['base_decs']);
                $data['assets_decs'][$rw['quote_asset']] = ($decs>$data['assets_decs'][$rw['quote_asset']]?$decs:$data['assets_decs'][$rw['quote_asset']]);
            }
            if (!isset($data[$dia][$rw['quote_asset']]))
                $data[$dia][$rw['quote_asset']] = 0;
            $data[$dia][$rw['quote_asset']] += $rw['quote'];
            
            if (!isset($data['total'][$rw['quote_asset']]))
                $data['total'][$rw['quote_asset']]=0;
            $data['total'][$rw['quote_asset']] += $rw['quote'];




            if ($dia < $data['iniDate'])
                $data['iniDate'] = $dia;
        }

        //Eliminando datos aproximados a 0
        $assetsToDelete = array();
        foreach ($data['total'] as $asset => $profit)
            if (toDec($profit,$data['assets_decs'][$asset]) == 0)
                $assetsToDelete[] = $asset;

        if (!empty($assetsToDelete))
        {
            foreach ($assetsToDelete as $asset)
            {
                unset($data['total'][$asset]);
                unset($data['assets'][$asset]);
                unset($data['assets_decs'][$asset]);
            }
        }
        
        return $data;
    }

    function getPnlMensual($idoperacion=null)
    {
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry = "SELECT operacion.symbol,
                       pnlDate,
                       side, 
                       price,
                       origQty,
                       tickers.base_asset,
                       tickers.quote_asset,
                       tickers.qty_decs_units as base_decs,
                       tickers.qty_decs_quote as quote_decs
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion 
                LEFT JOIN tickers ON tickers.tickerid = operacion.symbol
                WHERE operacion.idusuario = ".$idusuario." AND operacion_orden.completed = 1";
        if ($idoperacion)
            $qry .= ' AND idoperacion = '.$idoperacion;
        $stmt = $this->db->query($qry);
        $data=array();
        $data['assets'] = array();
        $data['assets_decs'] = array();
        $data['iniMonth'] = date('Y-m');
        $data['total'] = array();
        while ($rw = $stmt->fetch())
        {
            $mes = substr($rw['pnlDate'],0,7);
            if ($rw['side']==self::SIDE_BUY)
            {
                $rw['base']  = $rw['origQty'];
                $rw['quote'] = (-$rw['origQty'])*$rw['price'];
            }
            else
            {
                $rw['base']  = -$rw['origQty'];
                $rw['quote'] = $rw['origQty']*$rw['price'];
            }


            //Calculo de Base
            $data['assets'][$rw['base_asset']] = $rw['base_asset'];
            if (isset($this->presetDecs[$rw['base_asset']]))
            {
                $data['assets_decs'][$rw['base_asset']] = $this->presetDecs[$rw['base_asset']]; 
            }
            else
            {
                $data['assets_decs'][$rw['base_asset']] = $rw['base_decs'];
            }
            if (!isset($data[$mes][$rw['base_asset']]))
                $data[$mes][$rw['base_asset']] = 0;
            $data[$mes][$rw['base_asset']] += $rw['base'];
            
            if (!isset($data['total'][$rw['base_asset']]))
                $data['total'][$rw['base_asset']]=0;
            $data['total'][$rw['base_asset']] += $rw['base'];



            //Calculo de Quote
            $data['assets'][$rw['quote_asset']] = $rw['quote_asset'];
            if (isset($this->presetDecs[$rw['quote_asset']]))
            {
                $data['assets_decs'][$rw['quote_asset']] = $this->presetDecs[$rw['quote_asset']]; 
            }
            else
            {
                $decs = ($rw['quote_decs']>$rw['base_decs'] ? $rw['quote_decs'] : $rw['base_decs']);
                $data['assets_decs'][$rw['quote_asset']] = ($decs>$data['assets_decs'][$rw['quote_asset']]?$decs:$data['assets_decs'][$rw['quote_asset']]);
            }
            if (!isset($data[$mes][$rw['quote_asset']]))
                $data[$mes][$rw['quote_asset']] = 0;
            $data[$mes][$rw['quote_asset']] += $rw['quote'];
            
            if (!isset($data['total'][$rw['quote_asset']]))
                $data['total'][$rw['quote_asset']]=0;
            $data['total'][$rw['quote_asset']] += $rw['quote'];
            
            if ($mes < $data['iniMonth'])
                $data['iniMonth'] = $mes;
        }

        //Eliminando datos aproximados a 0
        $assetsToDelete = array();
        foreach ($data['total'] as $asset => $profit)
            if (toDec($profit,$data['assets_decs'][$asset]) == 0)
                $assetsToDelete[] = $asset;

        if (!empty($assetsToDelete))
        {
            foreach ($assetsToDelete as $asset)
            {
                unset($data['total'][$asset]);
                unset($data['assets'][$asset]);
                unset($data['assets_decs'][$asset]);
            }
        }

        return $data;
    }

    function getPnlOperacion($idoperacion=null)
    {
        $api = new BinanceAPI();
        $prices = $api->prices();

        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $qry = "SELECT operacion.*,
                       updated,
                       pnlDate,
                       side, 
                       price,
                       origQty,
                       tickers.base_asset,
                       tickers.quote_asset,
                       tickers.qty_decs_units as base_decs,
                       tickers.qty_decs_quote as quote_decs
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion = operacion_orden.idoperacion 
                LEFT JOIN tickers ON tickers.tickerid = operacion.symbol
                WHERE operacion.idusuario = ".$idusuario." AND operacion_orden.completed = 1 ";
        if ($idoperacion)
            $qry .= ' AND operacion.idoperacion = '.$idoperacion.' ';
        else
            $qry .= ' AND operacion.stop<1 ';

        $qry .= ' ORDER BY operacion_orden.pnlDate, operacion_orden.updated';
        $stmt = $this->db->query($qry);
        $data=array();
        while ($rw = $stmt->fetch())
        {
            if (!isset($data[$rw['idoperacion']]))
            {

                $data[$rw['idoperacion']]['symbol'] = $rw['symbol'];
                $data[$rw['idoperacion']]['capital'] = $rw['capital_usd'];
                if ($rw['tipo']!=self::OP_TIPO_APLSHRT)
                {
                    $data[$rw['idoperacion']]['capital_asset'] = $rw['quote_asset'];
                    $data[$rw['idoperacion']]['capital_decs'] = $rw['quote_decs'];
                }
                else
                {
                    $data[$rw['idoperacion']]['capital_asset'] = $rw['base_asset'];
                    $data[$rw['idoperacion']]['capital_decs'] = $rw['base_decs'];
                }
                $data[$rw['idoperacion']]['inicio'] = $rw['updated'];
                $data[$rw['idoperacion']]['strTipo'] = $this->getTipoOperacion($rw['tipo'],$nombreCorto=true);
                $data[$rw['idoperacion']]['destino_profit'] = $rw['destino_profit'];
                $data[$rw['idoperacion']]['asset_profit'] = ($rw['destino_profit']==self::OP_DESTINO_PROFIT_QUOTE?$rw['quote_asset']:$rw['base_asset']);
                $data[$rw['idoperacion']]['base'] = 0;
                $data[$rw['idoperacion']]['quote'] = 0;
                $data[$rw['idoperacion']]['base_decs'] = $rw['base_decs'];
                $data[$rw['idoperacion']]['quote_decs'] = $rw['quote_decs'];
                $data[$rw['idoperacion']]['base_asset'] = $rw['base_asset'];
                $data[$rw['idoperacion']]['quote_asset'] = $rw['quote_asset'];

                
            }
            if ($rw['side']==self::SIDE_BUY)
            {
                $data[$rw['idoperacion']]['base']  += $rw['origQty'];
                $data[$rw['idoperacion']]['quote'] += (-$rw['origQty'])*$rw['price'];
            }
            else
            {
                $data[$rw['idoperacion']]['base']  += -$rw['origQty'];
                $data[$rw['idoperacion']]['quote'] += $rw['origQty']*$rw['price'];
            }
        }

        //Eliminando datos aproximados a 0
        foreach ($data as $id => $rw)
        {
            if (toDec($rw['base'],$rw['base_decs']) == 0)
                $data[$id]['base'] = 0;
            if (toDec($rw['quote'],$rw['quote_decs']) == 0)
                $data[$id]['quote'] = 0;

            $data[$id]['realCapital'] = $rw['capital'];
            if ($rw['strTipo']=='LONG' && $rw['destino_profit'] != Operacion::OP_DESTINO_PROFIT_QUOTE)
                $data[$id]['realCapital'] = $rw['capital']/$prices[$rw['symbol']];
            elseif ($rw['strTipo']=='SHORT' && $rw['destino_profit'] == Operacion::OP_DESTINO_PROFIT_QUOTE)
                $data[$id]['realCapital'] = $rw['capital']*$prices[$rw['symbol']];

            if ($rw['destino_profit']==self::OP_DESTINO_PROFIT_QUOTE)
            {
                $data[$id]['realQuote'] = $data[$id]['quote'];
                if ($data[$id]['base'] != 0)
                    $data[$id]['realQuote'] += $data[$id]['base']*$prices[$rw['symbol']];
                $data[$id]['porc_ganancia'] = toDec(($data[$id]['realQuote']/$data[$id]['realCapital'])*100);
            }
            else
            {
                $data[$id]['realBase'] = $data[$id]['base'];
                if ($data[$id]['quote'] != 0)
                    $data[$id]['realBase'] += $data[$id]['quote']/$prices[$rw['symbol']];
                 $data[$id]['porc_ganancia'] = toDec(($data[$id]['realBase']/$data[$id]['realCapital'])*100);
            }
        }
        
        if ($idoperacion)
            return $data[$idoperacion];

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
            $data[$rw['fecha']][$rw['symbol']] = $rw['USD'];
            if (!isset($data['total'][$rw['symbol']]))
                $data['total'][$rw['symbol']]=0;
            $data['total'][$rw['symbol']] += $rw['USD'];
            if (!isset($data['total']['total']))
                $data['total']['total']=0;
            $data['total']['total'] += $rw['USD'];
            if (!isset($data[$rw['fecha']]['total']))
                $data[$rw['fecha']]['total']=0;
            $data[$rw['fecha']]['total'] += $rw['USD'];
            
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
        if (!is_file($logFile))
        {
            file_put_contents($logFile, "\n"."START");
            chmod($logFile, 0777);
        }
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

        $qry = "SELECT operacion.symbol,operacion.stop, operacion_orden.* 
                FROM operacion_orden 
                LEFT JOIN operacion ON operacion.idoperacion =operacion_orden.idoperacion
                WHERE idusuario = ".$auth->get('idusuario')." 
                  AND status = ".self::OR_STATUS_FILLED."  
                  AND completed = 0 
                ORDER BY stop,symbol, operacion_orden.updated";
        $stmt = $this->db->query($qry);
        $data = array();

        while ($rw = $stmt->fetch())
        {
            if (!isset($data[$rw['symbol']]))
            {
                $data[$rw['symbol']]['buyedUSD'] = 0;
                $data[$rw['symbol']]['buyedUnits'] = 0;
                $data[$rw['symbol']]['stop'] = $rw['stop'];
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

        $qry = "SELECT operacion.symbol,operacion.porc_venta_up,operacion.porc_venta_down, operacion_orden.* 
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
            $rw['porcLimit'] = ($rw['compraNum']==1?$rw['porc_venta_up']:$rw['porc_venta_down']);
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

    function liquidarOrden($idoperacionorden,$recomprar=false)
    {
        if (!$this->data['idoperacion'] || !$idoperacionorden)
            return false;

        if ($this->data['tipo'] == self::OP_TIPO_APLSHRT)
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
            if ($recomprar)
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
                    $aOpr['idoperacion']  = $this->data['idoperacion'];
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


    function liquidarOp($params=array())
    {
        if (!$this->data['idoperacion'])
            return false;

        if ($this->isShort())
        {
            $this->errLog->add('El tipo de Operacion no se puede liquidar');
            return false;
        }

        //Apagando el Bot
        $upd = "UPDATE operacion SET stop = '1' WHERE idoperacion = ".$this->data['idoperacion'];
        $this->db->query($upd);


        $qry = 'SELECT * FROM operacion_orden 
                 WHERE idoperacion = '.$this->data['idoperacion'].
                  ' AND completed = 0';
        $stmt = $this->db->query($qry);
        $usr = new UsrUsuario($this->data['idusuario']);
        $ak = $usr->getConfig('bncak');
        $as = $usr->getConfig('bncas');
        $api = new BinanceAPI($ak,$as); 
        $symbol = $this->data['symbol'];
        $idusuario = $this->data['idusuario'];
        $qtyBase = 0;
        $qtyQuote = 0;
        $qtyOrdenesALiquidar = 0;

        $msg = 'LIQUIDAR_OPERACION';
        self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

        while ($rw = $stmt->fetch())
        {
            if ($rw['status'] == self::OR_STATUS_FILLED)
            {
                $qtyOrdenesALiquidar++;
                if ($rw['side'] == self::SIDE_BUY)
                {
                    $qtyBase += $rw['origQty'];
                    $qtyQuote -= $rw['origQty'] * $rw['price'];
                }
                else
                {
                    $qtyBase -= $rw['origQty'];
                    $qtyQuote += $rw['origQty'] * $rw['price'];
                }
            }
            else
            {
                $ordersToCancel[$rw['idoperacionorden']] = $rw['orderId'];
            }
        }

        if ($qtyOrdenesALiquidar==0)
        {
            $this->errLog->add('No existen ordenes a liquidar');
            return false;
        }

        //Cancelar Ordenes Abiertas
        if (!empty($ordersToCancel))
        {
            foreach ($ordersToCancel as $idoperacionorden => $orderId)
            {
                try {
                    $msg = 'LIQUIDAR_OPERACION CANCELAR ORDEN '.$orderId;
                    self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                    $api->cancel($symbol, $orderId);
                    $this->deleteOrder($orderId);
                } catch (Throwable $e) {
                    $msg = 'No fue posible cancelar la orden ID# '.$orderId;
                    self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                    $this->errLog->add($msg);
                    return false;
                }
            }
            while (!empty($ordersToCancel))
            {
                sleep(1);               
                foreach ($ordersToCancel as $idoperacionorden => $orderId)
                {
                    $msg = 'LIQUIDAR_OPERACION Verificando ordenes a cancelar';
                    self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                    $orderStatus = $api->orderStatus($symbol,$orderId);
                    if (empty($orderStatus) || $orderStatus['status']=='CANCELED' || $orderStatus['status']=='EXPIRED')
                    {
                        $msg = 'LIQUIDAR_OPERACION Orden cancelada OK ID# '.$orderId;
                        self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                        unset($ordersToCancel[$idoperacionorden]);
                    }
                }
            }
        }

         
        if ($this->isLong()) //Vender qtyBase comprado
        {   
            try {
                $qty = toDec($qtyBase,$this->data['qty_decs_units']);
                $msg = 'LIQUIDAR_OPERACION START ORDER Sell -> Qty:'.$qty.' Price: MARKET - Liquidar Orden';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                $order = $api->marketSell($symbol, $qtyBase);
                $op['idoperacion']  = $this->data['idoperacion'];
                $op['side']         = self::SIDE_SELL;
                $op['origQty']      = $qty;
                $op['price']        = 0;
                $op['orderId']      = $order['orderId'];
                $this->insertOrden($op);

            } catch (Throwable $e) {
                $msg = ' START ORDER Sell -> Qty:'.$qty.' Price: MARKET - Liquidar Orden - CANCELADO';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                $msg = $e->getMessage();
                $this->errLog->add($e->getMessage());
                return false;
            }

        }
        /** Verificar el caso de liquidar una operacion SHORT
        else //Comprar qtyQuote vendido
        {
            try {
                $actualPrice = $api->price($symbol);
                $qty = toDec($actualPrice/(-$qtyBase),$this->data['qty_decs_quote']);
                $msg = ' START ORDER Buy -> Qty:'.$qty.' Price: MARKET - Liquidar Orden';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                $order = $api->marketBuy($symbol, $qtyQuote);
                $op['idoperacion']  = $this->data['idoperacion'];
                $op['side']         = self::SIDE_BUY;
                $op['origQty']      = $qty;
                $op['price']        = 0;
                $op['orderId']      = $order['orderId'];
                $this->insertOrden($op);

            } catch (Throwable $e) {
                $msg = ' START ORDER Buy -> Qty:'.$qty.' Price: MARKET - Liquidar Orden - CANCELADO';
                self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
                $msg = $e->getMessage();
                $this->errLog->add($e->getMessage());
                return false;
            }

        }
        */

        //Apagando el Bot
        $upd = "UPDATE operacion SET stop = '0' WHERE idoperacion = ".$this->data['idoperacion'];
        $this->db->query($upd);

        
        if ($params['autoRestartOff'])
        {
            $upd = "UPDATE operacion SET auto_restart = '0' WHERE idoperacion = ".$this->data['idoperacion'];
            $this->db->query($upd);
        }

        $msg = 'LIQUIDAR_OPERACION - Finalizado';
        self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

        return true;
    }

    function gestionDelCapital()
    {
        $data = array();
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $oprs = $this->getDataSet('idusuario = '.$idusuario.' AND stop<1','auto_restart DESC,tipo,symbol,idoperacion');
        if (!empty($oprs))
        {
            foreach ($oprs as $op)
            {
                $this->reset();
                $this->set($op);
                $data[$op['idoperacion']]['idoperacion']      = $op['idoperacion'];
                $data[$op['idoperacion']]['symbol']           = $op['symbol'];
                $data[$op['idoperacion']]['capital']          = $op['capital_usd'];
                $data[$op['idoperacion']]['auto_restart']     = $op['auto_restart'];
                $data[$op['idoperacion']]['stop']             = $op['stop'];
                $data[$op['idoperacion']]['qty_decs_units']   = $op['qty_decs_units'];
                $data[$op['idoperacion']]['qty_decs_quote']   = $op['qty_decs_quote'];
                $data[$op['idoperacion']]['quote_asset']      = $op['quote_asset'];
                $data[$op['idoperacion']]['base_asset']       = $op['base_asset'];
              
                $data[$op['idoperacion']]['qty_decs_capital'] = $this->get('qty_decs_capital');
                $data[$op['idoperacion']]['capital_asset']    = $this->get('capital_asset');

                $data[$op['idoperacion']]['is_short']    = $this->isShort();
                $data[$op['idoperacion']]['is_long']     = $this->isLong();
                $data[$op['idoperacion']]['strTipo']     = $this->getTipoOperacion($this->data['tipo'],$nombreCorto=true);

            }
        }

        $oact = $this->getOrdenesActivas();
        if (!empty($oact))
        {
            foreach ($oact as $orden)
            {
                if ($data[$orden['idoperacion']]['is_long'])
                {
                    $usd = $orden['price']*$orden['origQty'];
                    if ($orden['side']==self::SIDE_SELL)
                        $usd = $usd*(-1);
                }
                else
                {
                    $usd = $orden['origQty'];
                    if ($orden['side']==self::SIDE_BUY)
                        $usd = $usd*(-1);
                }


                if (!isset($data[$orden['idoperacion']]['comprado']))
                    $data[$orden['idoperacion']]['comprado']=0;
                if (!isset($data[$orden['idoperacion']]['vendido']))
                    $data[$orden['idoperacion']]['vendido']=0;
                if (!isset($data[$orden['idoperacion']]['en_venta']))
                    $data[$orden['idoperacion']]['en_venta']=0;
                if (!isset($data[$orden['idoperacion']]['en_compra']))
                    $data[$orden['idoperacion']]['en_compra']=0;

                if ($orden['side']==self::SIDE_SELL)
                {
                    if ($orden['status'] == self::OR_STATUS_FILLED)
                        $data[$orden['idoperacion']]['vendido'] += $usd;
                    if ($orden['status'] == self::OR_STATUS_NEW) 
                        $data[$orden['idoperacion']]['en_venta'] += $usd;
                }

                if ($orden['side']==self::SIDE_BUY)
                {
                    if ($orden['status'] == self::OR_STATUS_FILLED)
                        $data[$orden['idoperacion']]['comprado'] += $usd;
                    if ($orden['status'] == self::OR_STATUS_NEW) 
                        $data[$orden['idoperacion']]['en_compra'] += $usd;
                }


            }
        }

        foreach ($data as $idoperacion=>$op)
        {
            if ($op['is_long'])
            {
                if ($data[$idoperacion]['capital'] == 0 || $data[$idoperacion]['capital']<($data[$idoperacion]['comprado']+$data[$idoperacion]['bloqueado']))
                    $data[$idoperacion]['capital'] = $data[$idoperacion]['comprado']+$data[$idoperacion]['en_compra'];
                $data[$idoperacion]['remanente'] = $data[$idoperacion]['capital']-$data[$idoperacion]['comprado']-$data[$idoperacion]['en_compra'];

                $data[$idoperacion]['cierre'] = '';
                if ($data[$idoperacion]['en_venta'] != 0 && $data[$idoperacion]['comprado'] != 0)
                    $data[$idoperacion]['porc_cierre'] = toDec(((($data[$idoperacion]['en_venta']/$data[$idoperacion]['comprado'])-1)*100));
            }
            if ($op['is_short'])
            {
                if ($data[$idoperacion]['capital'] == 0 || $data[$idoperacion]['capital']<($data[$idoperacion]['vendido']+$data[$idoperacion]['en_venta']))
                    $data[$idoperacion]['capital'] = $data[$idoperacion]['vendido']+$data[$idoperacion]['en_venta'];
                $data[$idoperacion]['remanente'] = $data[$idoperacion]['capital']-$data[$idoperacion]['vendido']-$data[$idoperacion]['en_venta'];

                $data[$idoperacion]['porc_cierre'] = '';
                if ($data[$idoperacion]['en_venta'] != 0 && $data[$idoperacion]['comprado'] != 0)
                    $data[$idoperacion]['porc_cierre'] = toDec(((($data[$idoperacion]['en_venta']/$data[$idoperacion]['comprado'])-1)*100));
            }


        }

        return $data;
    }

    function getOperacionesPorTipo($idusuario,$tipo)
    {
        if (!$tipo)
            $tipo = '0';
        $ds = $this->getDataset("idusuario = ".$idusuario." AND operacion.tipo = '".$tipo."'");
        if (!is_array($ds))
            return array();
        return $ds;
    }

    function getAllSymbols()
    {
        $qry = "SELECT DISTINCT operacion.symbol,
                                tickers.base_asset,
                                tickers.quote_asset, 
                                tickers.qty_decs_units, 
                                tickers.qty_decs_price 
                FROM operacion 
                LEFT JOIN tickers ON tickers.tickerid = operacion.symbol";
        $stmt = $this->db->query($qry);
        
        $symbols = array();
        while ($rw = $stmt->fetch())
        {
            $symbols[$rw['symbol']] = $rw;
        }
        return $symbols;
    }

    function getDecs($asset)
    {
        $decs = 8;
        $asset = strtoupper($asset);
        if (isset($this->presetDecs[$asset]))
            $decs = $this->presetDecs[$asset];
        return $decs;
    }   

    function isShort()
    {
        return ($this->data['tipo'] == self::OP_TIPO_APLSHRT);
    }

    function isLong()
    {
        return ($this->data['tipo'] != self::OP_TIPO_APLSHRT);
    }

    function loadStartData()
    {
        $idoperacion = $this->data['idoperacion'];
        if (!$idoperacion)
            return null;
        $qry = "SELECT min(updated) as start FROM operacion_orden WHERE idoperacion = '".$idoperacion."'";
        $stmt = $this->db->query($qry);
        $data = $stmt->fetch();
        if (!$data['start'])
            return null;
        
        $data['start'] = substr($data['start'],0,16); //Se quitan los segundos
        $startTime = date('U',strtotime($data['start'])).'000';
        
        $data['base_asset'] = $this->data['base_asset'];
        $data['quote_asset'] = $this->data['quote_asset'];

        $data['base_start_in_usd'] = 0;
        $data['quote_start_in_usd'] = 0;
        $usdAssets = array('USDT','BUSD','USDC');
        if (in_array($data['quote_asset'], $usdAssets))
            $data['quote_start_in_usd'] = 1;
        if (in_array($data['base_asset'], $usdAssets))
            $data['base_start_in_usd'] = 1;        
        

        $api = new BinanceAPI();
        if (!$data['base_start_in_usd'])
        {
            $strSymbol = $data['base_asset'].($data['base_asset']!='USDT'?'USDT':'BUSD');
            $klines = $api->candlesticks($strSymbol, $interval = "1m", $limit = 1, $startTime );
            $data['base_start_in_usd'] = $klines[$startTime]['close'];
        }
        if (!$data['quote_start_in_usd'])
        {
            $strSymbol = $data['quote_asset'].($data['quote_asset']!='USDT'?'USDT':'BUSD');
            $klines = $api->candlesticks($strSymbol, $interval = "1m", $limit = 1, $startTime );
            $data['quote_start_in_usd'] = $klines[$startTime]['close'];
        }

        $upd = "UPDATE operacion SET start = '".$data['start']."',".
                                   " base_start_in_usd = '".($data['base_start_in_usd']?$data['base_start_in_usd']:'0')."', ".
                                   " base_start_in_usd = '".($data['base_start_in_usd']?$data['base_start_in_usd']:'0')."' ".
                " WHERE idoperacion = ".$idoperacion;
        $this->db->query($upd);        

    }

    function apagarBot($params)
    {
        $ordenesActivas = $this->getOrdenes($enCurso=true);
        $symbol = $this->data['symbol'];

        $operacionSet = " stop = '1' ";
        if ($params['autoRestartOff'])
            $operacionSet .= ", auto_restart = '0' ";
            
        $upd = "UPDATE operacion SET ".$operacionSet." WHERE idoperacion = ".$this->data['idoperacion'];
        $this->db->query($upd);

        $strParams = '';
        foreach ($params as $k=>$v)
            if ($v)
                $strParams .= ' '.$k;
        $msg = 'STOP_BOT'.$strParams;
        self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

        if ($params['delOrdenesActivas'])
        {
            $del = 'DELETE FROM operacion_orden 
                          WHERE idoperacion = '.$this->data['idoperacion'].'
                            AND completed = 0';
            $this->db->query($del);
        }
        if ($params['delOrdenesBinance'])
        {
            if (!empty($ordenesActivas))
            {
                $auth = UsrUsuario::getAuthInstance();
                $ak = $auth->getConfig('bncak');
                $as = $auth->getConfig('bncas');
                $api = new BinanceAPI($ak,$as);
                try {
                    foreach ($ordenesActivas as $orden)
                    {
                        if ($orden['status'] != Operacion::OR_STATUS_FILLED)
                        {
                            $api->cancel($symbol, $orden['orderId']);

                            $msg = 'STOP_BOT Cancelar Orden Binance id#'.$orden['orderId'];
                            self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);

                        }
                    }
                } catch (Throwable $e) {
                    $errorAlCancelarOrden = true;
                }
            }
        }
        $msg = 'STOP_BOT';
        self::logBot('u:'.$idusuario.' o:'.$this->data['idoperacion'].' s:'.$symbol.' '.$msg,$echo=false);
        return $this->data['stop'];
    }
}