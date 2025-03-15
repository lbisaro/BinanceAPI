<?php
include_once LIB_PATH."ModelDB.php";

include_once MDL_PATH."Ticker.php";

class BotSW extends ModelDB
{
    protected $query = "SELECT * FROM bot_sw";

    protected $pKey  = 'idbotsw';

    const ESTADO_ONLINE     = 10;
    const ESTADO_STANDBY    = 20;
    const ESTADO_ERROR      = 30;
    const ESTADO_STOPPED    = 90;

    const SIDE_BUY          = 0;
    const SIDE_SELL         = 1; 

    function __Construct($id=null)
    {
        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'bot_sw','idbotsw');

        if($id)
            $this->load($id);

    }

    function get($field)
    {
        if ($field == 'strEstado')
            return $this->getTipoEstado($this->data['estado']);

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

        if (!$this->data['estado'])
            $this->data['estado'] = self::ESTADO_STANDBY;

        if (!$this->data['idbotsw'] && $this->data['estado'] == self::ESTADO_STANDBY)
            $this->data['estado_msg'] = 'No se encontraron monedas para operar';
        
        if (!$this->data['idusuario'])
        {
            $auth = UsrUsuario::getAuthInstance();
            $this->data['idusuario'] = $auth->get('idusuario');
        }

        // Control de errores

        if (!$this->data['titulo'])
            $err[] = 'Se debe especificar un Titulo';
        if (!$this->data['symbol_estable'])
            $err[] = 'Se debe especificar una StableCoin para operar';
        if (!$this->data['symbol_reserva'])
            $err[] = 'Se debe especificar una StableCoin para reserva';
        if ($this->data['symbol_estable'] == $this->data['symbol_reserva'])
            $err[] = 'Las StableCoin Operar y para Reserva deben ser diferentes';


        // FIN - Control de errores

        if (!empty($err))
        {
            $this->errLog->add($err);
            return false;
        }
        return true;
    }

    function saveNew()
    {
        if (!$this->valid())
        {
            return false;
        }

        // Creando el Id en caso que no este
        if (!$this->data['idbotsw'])
        {
            $isNew = true;
            $this->data['idbotsw'] = $this->getNewId();
        }
        if (!$this->tableInsert(DB_NAME,'bot_sw'))
            $err++;

        if ($err)
        {
            $this->errLog->add('Erro al insertar el registro en la base de datos');
            return false;
        }
        return true;
    }

    function save()
    {
        $err   = 0;
        $isNew = false;

        // Creando el Id en caso que no este
        if (!$this->data['idbotsw'])
        {
            $isNew = true;
            CriticalExit('BotSW::save() :: No es posible grabar los datos sin un ID - Implementar saveNew()');
        }

        if (!$this->valid())
        {
            return false;
        }

        //Grabando datos en las tablas de la db
        if ($isNew) // insert
        {
            if (!$this->tableInsert(DB_NAME,'bot_sw'))
                $err++;
        }
        else       // update
        {
            if (!$this->tableUpdate(DB_NAME,'bot_sw'))
                $err++;
        }
        if ($err)
            return false;
        return true;
    }

    function getTipoEstado($id)
    {
        $arr[self::ESTADO_ONLINE]  = 'Encendido';
        $arr[self::ESTADO_STANDBY] = 'En espera';
        $arr[self::ESTADO_ERROR]   = 'Error';
        $arr[self::ESTADO_STOPPED] = 'Detenido';

        if (isset($arr[$id]))
            return $arr[$id];

        return 'Desconocido';
    }

    function getTipoEstadoClass($id)
    {
        $arr[self::ESTADO_ONLINE]  = 'text-primary';
        $arr[self::ESTADO_STANDBY] = 'text-info';
        $arr[self::ESTADO_ERROR]   = 'text-danger';
        $arr[self::ESTADO_STOPPED] = 'text-secondary';

        if (isset($arr[$id]))
            return $arr[$id];

        return '';
    }

    function isStop()
    {
        if ($this->data['estado'] == self::ESTADO_STOPPED)
            return true;
        return false;
    }

    function isOnline()
    {
        if ($this->data['estado'] == self::ESTADO_ONLINE)
            return true;
        return false;
    }

    function isStandby()
    {
        if ($this->data['estado'] == self::ESTADO_STANDBY)
            return true;
        return false;
    }

    function getOpenOrders()
    {
        $openOrders = unserialize($this->data['open_orders']);
        if (empty($openOrders))
            return array();
        
        return $openOrders;

    }


    function auditOrders()
    {
        $ordenes = array();
        $qry = "SELECT * FROM bot_sw_orden_log";
        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            if ($rw['side'] == self::SIDE_BUY)
            {
                $rw['base_qty'] = $rw['origQty'];
                $rw['quote_qty'] = -($rw['origQty']*$rw['price']);
            }
            else  
            {
                $rw['base_qty'] = -$rw['origQty'];
                $rw['quote_qty'] = $rw['origQty']*$rw['price'];
            }
            unset($rw['origQty']);
            $ordenes[] = $rw;
        }

        return $ordenes; 
    }


    function getOrdenes()
    {
        $ordenes = array();
        $qry = "SELECT * FROM bot_sw_orden_log WHERE idbotsw = ".$this->data['idbotsw'];
        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            if ($rw['side'] == self::SIDE_BUY)
            {
                $rw['base_qty'] = $rw['origQty'];
                $rw['quote_qty'] = -($rw['origQty']*$rw['price']);
            }
            else  
            {
                $rw['base_qty'] = -$rw['origQty'];
                $rw['quote_qty'] = $rw['origQty']*$rw['price'];
            }
            unset($rw['origQty']);
            $ordenes[] = $rw;
        }

        return $ordenes; 
    }

    function getOrdersFull()
    {
        $idbotsw = $this->data['idbotsw'];
        if (!$idbotsw)
            return null;
        
        $ordenes = array();
        $qry = "SELECT * FROM bot_sw_orden_log WHERE idbotsw = ".$idbotsw;
        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            $rw['symbol'] = $rw['base_asset'].$rw['quote_asset'];

            if ($rw['side'] == self::SIDE_BUY)
            {
                $rw['base_qty'] = $rw['origQty'];
                $rw['quote_qty'] = -($rw['origQty']*$rw['price']);
            }
            else  
            {
                $rw['base_qty'] = -$rw['origQty'];
                $rw['quote_qty'] = $rw['origQty']*$rw['price'];
            }
            unset($rw['origQty']);
            $ordenes[] = $rw;
        }

        return $ordenes; 
    }

    function getOrdenesResumen()
    {
        $ordenes = $this->getOrdenes();
        $resumen = array();
        if (!empty($ordenes))
        {
            foreach ($ordenes as $rw)
            {
                if (!isset($resumen[$rw['base_asset']]))
                    $resumen[$rw['base_asset']] = 0;
                if (!isset($resumen[$rw['quote_asset']]))
                    $resumen[$rw['quote_asset']] = 0;

                $resumen[$rw['base_asset']] += $rw['base_qty'];
                $resumen[$rw['quote_asset']] += $rw['quote_qty'];
            }
        }
        return $resumen;
    }

    function getPosiciones($prices)
    {
        if (!$this->data['idbotsw'])
        {
            CriticalExit('BotSW::getPosiciones() :: Se debe especificar un ID valido');
        }
        $posiciones = array();

        $capital = $this->getCapital();
        $or = $this->getOrdenesResumen();

        //Se hace una precarga de monedas para organizar el orden en el que se muestran
        $precharge = array('USDT','FDUSD','USDC','BTC','ETH','BNB');
        foreach ($precharge as $asset)
            $posiciones[$asset]=array();

        if (!empty($capital))
        {
            $totInUSD = 0;
            foreach ($capital as $asset => $rw)
            {
                $posiciones[$asset]['cap'] = $rw;

                $posiciones[$asset]['pos']['qty'] = $rw['qty'] + $or[$asset];
                if ($asset == $this->data['symbol_estable'] || $asset == $this->data['symbol_reserva'])
                    $posiciones[$asset]['pos']['price'] = 1;
                else
                    $posiciones[$asset]['pos']['price'] = $prices[$asset.$this->data['symbol_estable']];
                $posiciones[$asset]['pos']['inUSD'] = toDec($posiciones[$asset]['pos']['qty'] * $posiciones[$asset]['pos']['price']);

                $totInUSD += $posiciones[$asset]['pos']['inUSD'];
            }
            $totInUSD = toDec($totInUSD);

            foreach ($posiciones as $asset => $rw)
            {
                $posiciones[$asset]['pos']['part'] = toDec(($rw['pos']['inUSD']/$totInUSD)*100);

                $posiciones[$asset]['dif']['part'] = toDec($posiciones[$asset]['pos']['part'] - $posiciones[$asset]['cap']['part']);
                $posiciones[$asset]['dif']['qty']  = $posiciones[$asset]['pos']['qty'] - $posiciones[$asset]['cap']['qty'];
                $posiciones[$asset]['dif']['inUSD']  = toDec($posiciones[$asset]['pos']['inUSD'] - $posiciones[$asset]['cap']['inUSD']);
            }
        }
        foreach ($precharge as $asset)
            if ($posiciones[$asset]['pos']['part']==0)
                unset($posiciones[$asset]);

        return $posiciones;

    }

    function getCapital()
    {
        if (!$this->data['idbotsw'])
        {
            CriticalExit('BotSW::getCapital() :: Se debe especificar un ID valido');
        }
        $capital = array();
        //Se hace una precarga de monedas para organizar el orden en el que se muestran
        $precharge = array('USDT','FDUSD','USDC','BTC','ETH','BNB');
        foreach ($precharge as $asset)
            $capital[$asset]=array();

        $qry = "SELECT * FROM bot_sw_capital_log WHERE idbotsw = ".$this->data['idbotsw'];
        $stmt = $this->db->query($qry);
        $totInUSD = 0;
        while ($rw = $stmt->fetch())
        {
            if (!isset($capital[$rw['symbol']]))
            {
                $capital[$rw['symbol']]['qty'] = 0;
                $capital[$rw['symbol']]['inUSD'] = 0;
                $capital[$rw['symbol']]['price'] = 0;
                $capital[$rw['symbol']]['part'] = 0;
            }
            $capital[$rw['symbol']]['qty'] += $rw['qty'];
            $capital[$rw['symbol']]['inUSD'] += $rw['qty']*$rw['price'];
            $totInUSD += $rw['qty']*$rw['price'];
        }

        if (!isset($capital[$this->data['symbol_estable']]))
        {
            $capital[$this->data['symbol_estable']]['qty'] = 0;
            $capital[$this->data['symbol_estable']]['inUSD'] = 0;
            $capital[$this->data['symbol_estable']]['price'] = 1;
            $capital[$this->data['symbol_estable']]['part'] = 0;
        }

        if (!isset($capital[$this->data['symbol_reserva']]))
        {
            $capital[$this->data['symbol_reserva']]['qty'] = 0;
            $capital[$this->data['symbol_reserva']]['inUSD'] = 0;
            $capital[$this->data['symbol_reserva']]['price'] = 1;
            $capital[$this->data['symbol_reserva']]['part'] = 0;
        }

        $totInUSD = toDec($totInUSD);
        if ($totInUSD > 0)
        {
            $remove = array();
            foreach ($capital as $asset=>$rw)
            {
                if ($capital[$asset]['qty']>0 || $asset == $this->data['symbol_estable'])
                {
                    $capital[$asset]['inUSD'] = toDec($capital[$asset]['inUSD']);
                    if ($capital[$asset]['qty'] != 0)
                        $capital[$asset]['price'] = $capital[$asset]['inUSD']/$capital[$asset]['qty'];
                    if ($totInUSD != 0)
                        $capital[$asset]['part'] = toDec(($capital[$asset]['inUSD']/$totInUSD)*100);
                }
                else
                {
                    $remove[] = $asset;
                }
            }
            foreach ($remove as $asset)
                unset($capital[$asset]);
            
        }
        else
        {
            $capital = array();
        }

        foreach ($precharge as $asset)
            if ($capital[$asset]['qty']==0 && $asset != $this->data['symbol_estable'])
                unset($capital[$asset]);
        return $capital;

    }

    function getAvgPrice()
    {
        if (!$this->data['idbotsw'])
        {
            CriticalExit('BotSW::getAvgPrice() :: Se debe especificar un ID valido');
        }
        $posiciones = array();

        $data = array();

        //Analisis del capital
        $capital = $this->getCapital();
        if (!empty($capital))
        {
            foreach ($capital as $asset => $rw)
            {
                if ($rw['qty']>0)
                {
                    if ($asset == $this->data['symbol_estable'])
                        $rw['price'] = 1;
                    $data[$asset][] = array('qty'=>$rw['qty'],
                                            'inUSD'=>-($rw['qty']*$rw['price']),
                                            'price'=>$rw['price'],
                                            );
                    
                }
            }
        }

        //Analisando trades
        $or = $this->getOrdersFull();
        if (!empty($or))
        {
            foreach ($or as $rw)
            {
                $asset = $rw['base_asset'];
                if ($asset == $this->data['symbol_estable'])
                    $rw['price'] = 1;
                $qty = ($rw['side'] == self::SIDE_BUY ? $rw['base_qty'] : -$rw['base_qty']);
                $data[$asset][] = array('qty'=>$qty,
                                        'inUSD'=>-($qty*$rw['price']),
                                        'price'=>$rw['price'],
                                        );
            }
        }        

        //Calculando AVG Price
        $avg = array();
        foreach ($data as $asset => $rwAsset) 
        {
            $qty = 0;
            $inUSD = 0;
            foreach ($rwAsset as $rw)
            {
                $qty += $rw['qty'];
                $inUSD += $rw['inUSD'];
            }
            $avg[$asset]['price'] = -($inUSD)/$qty;
            $avg[$asset]['qty'] = $qty;
            $avg[$asset]['inUSD'] = $inUSD;

        }

        return $avg;

        //Se hace una precarga de monedas para organizar el orden en el que se muestran
        $precharge = array('USDT','FDUSD','USDC','BTC','ETH','BNB');
        foreach ($precharge as $asset)
            $posiciones[$asset]=array();

        if (!empty($capital))
        {
            $totInUSD = 0;
            foreach ($capital as $asset => $rw)
            {
                $posiciones[$asset]['cap'] = $rw;

                $posiciones[$asset]['pos']['qty'] = $rw['qty'] + $or[$asset];
                if ($asset == $this->data['symbol_estable'] || $asset == $this->data['symbol_reserva'])
                    $posiciones[$asset]['pos']['price'] = 1;
                else
                    $posiciones[$asset]['pos']['price'] = $prices[$asset.$this->data['symbol_estable']];
                $posiciones[$asset]['pos']['inUSD'] = toDec($posiciones[$asset]['pos']['qty'] * $posiciones[$asset]['pos']['price']);

                $totInUSD += $posiciones[$asset]['pos']['inUSD'];
            }
            $totInUSD = toDec($totInUSD);

            foreach ($posiciones as $asset => $rw)
            {
                $posiciones[$asset]['pos']['part'] = toDec(($rw['pos']['inUSD']/$totInUSD)*100);

                $posiciones[$asset]['dif']['part'] = toDec($posiciones[$asset]['pos']['part'] - $posiciones[$asset]['cap']['part']);
                $posiciones[$asset]['dif']['qty']  = $posiciones[$asset]['pos']['qty'] - $posiciones[$asset]['cap']['qty'];
                $posiciones[$asset]['dif']['inUSD']  = toDec($posiciones[$asset]['pos']['inUSD'] - $posiciones[$asset]['cap']['inUSD']);
            }
        }
        foreach ($precharge as $asset)
            if ($posiciones[$asset]['pos']['part']==0)
                unset($posiciones[$asset]);

        return $posiciones;
    }

    function asignarCapital($asset,$qty,$price)
    {
        if (!$this->data['idbotsw'])
        {
            $isNew = true;
            CriticalExit('BotSW::asignarCapital() :: No es posible asignar capital sin un ID - Implementar saveNew()');
        }
        $capital = $this->getCapital();

        $preQty = $capital[$asset]['qty'];
        $difQty = $qty+$preQty;

        $ai = $this->getAssetsInfo(array($asset));
        $qty = toDecDown($qty,$ai[$asset]['qtyDecsUnits']);
        $price = toDecDown($price,$ai[$asset]['qtyDecsPrice']);
        $preQty = toDec($preQty,$ai[$asset]['qtyDecsUnits']);

        if ($difQty<0)
        {
            $this->errLog->add('El capital total no puede quedar en un valor menor a 0. El capital actual es de '.$preQty);
            return false;
        }


        $ins = "INSERT INTO bot_sw_capital_log (idbotsw,symbol,qty,price) VALUES (".
               $this->data['idbotsw'].",".
               "'".$asset."',".
               "'".$qty."',".
               "'".$price."'".
               " )";
        $this->db->exec($ins);
        return true;

    }

    function setStatus($newStatus)
    {
        $estadoActual = $this->data['estado'];
        if ($newStatus == 'STOP')
        {
            $this->data['estado'] = self::ESTADO_STOPPED;
        }
        elseif ($newStatus == 'START')
        {
            if (empty($this->getCapital()))
            {
                $this->errLog->add('No es posible iniciar el Bot sin asignar Capital');
                return false;
            }
            $this->data['estado'] = self::ESTADO_ONLINE;
        }
        elseif ($newStatus == 'STANDBY')
        {
            $this->data['estado'] = self::ESTADO_STANDBY;
        }

        if ($this->data['estado'] != $estadoActual)
        {
            $this->save();
            return true;
        }
        $this->errLog->add('No fue posible realizar el cambio de estado del Bot');
        return false;

    }

    function getAssetsInfo(array $assets)
    {
        $tck = new Ticker();
        $buscaInfo = false;
        if (is_array($assets))
        {
            $where = '';
            foreach ($assets as $asset)
            {
                if ($asset != 'USDT')
                {
                    $where .= ($where?',':'')."'".$asset."USDT'";
                    $info[$asset] = array();
                    $buscaInfo = true;
                }
                else
                {
                    $info[$asset]['qtyDecsUnits'] = 2;
                    $info[$asset]['qtyDecsPrice'] = 2;
                }
            }
            $where = "tickerid in (".$where.")";
            
        }
        else
        {
            if ($asset != 'USDT')
            {
                $where = "tickerid = '".$asset."'";
                $info[$asset] = array();
                $buscaInfo = true;
            }
            else
            {
                $info[$asset]['qtyDecsUnits'] = 2;
                $info[$asset]['qtyDecsPrice'] = 2;
            }
        }

        if ($buscaInfo)
        {
            $ds = $tck->getDataset($where);
            if (!empty($ds))
            {
                foreach ($ds as $rw)
                {
                    $info[$rw['base_asset']]['qtyDecsUnits'] = $rw['qty_decs_units'];
                    $info[$rw['base_asset']]['qtyDecsPrice'] = $rw['qty_decs_price'];            
                }
            }
        }
        if (!empty($info))
        {
            foreach ($info as $asset => $rw)
            {
                if (empty($rw))
                {
                    //Hay que agregar un Ticker porque no existe info previa
                    $tck->reset();
                    $tck->set(array('tickerid'=>$asset.'USDT'));
                    if ($tck->save())
                    {
                        $info[$asset]['qtyDecsUnits'] = $tck->get('qty_decs_units');
                        $info[$asset]['qtyDecsPrice'] = $tck->get('qty_decs_price');  
                    }
                }
            }        
        }
        

        return $info;
    }

    function separateSymbol($symbol)
    {
        $capital = $this->getCapital();
        $assets = array();
        $ret = array();
        if (!empty($capital))
        {
            foreach ($capital as $k => $rw)
            {
                $assets[] = $k;
                $assets2[] = $k;
            }

            foreach ($assets as $a1)
            {
                foreach ($assets2 as $a2)
                {
                    if ($symbol == $a1.$a2)
                    {
                        $ret['base'] = $a1;
                        $ret['quote'] = $a2;
                    }
                }
            }
        }
        return $ret;
    }


    function getAssets()
    {
        if (!$this->data['idbotsw'])
        {
            CriticalExit('BotSW::getSymbols() :: se debe especificar un ID valido');
        }

        $capital = $this->getCapital();
        $assets = array();
        if (!empty($capital))
        {
            foreach ($capital as $asset => $rw)
            {
                if ($asset != $this->data['symbol_estable'] && $asset != $this->data['symbol_reserva'])
                {
                    $assets[] = $asset;
                }
            }
        }
        return $assets;
    }

    function getActivos()
    {
        $auth = UsrUsuario::getAuthInstance();
        $ds = $this->getDataset('idusuario = '.$auth->get('idusuario'),'estado');
        $bots = array();
        $tmpBot = new BotSW();
        if (!empty($ds))
        {
            foreach ($ds as $k=>$rw)
            {
                $bots[$rw['idbotsw']] = $rw;
                $bots[$rw['idbotsw']]['strEstado'] = $tmpBot->getTipoEstado($rw['estado']);
                $bots[$rw['idbotsw']]['strEstadoClass'] = $tmpBot->getTipoEstadoClass($rw['estado']);
                $bots[$rw['idbotsw']]['strEstables'] = $rw['symbol_estable'].'-'.$rw['symbol_reserva'];
                $bots[$rw['idbotsw']]['strMonedas'] = '';
                $bots[$rw['idbotsw']]['capital'] = 0.0;

                $tmpBot->reset();
                $tmpBot->load($rw['idbotsw']);
                $capital = $tmpBot->getCapital();
                if (!empty($capital))
                {
                    foreach ($capital as $symbol => $rw1)
                    {
                        if ($symbol != $rw['symbol_estable'] && $symbol != $rw['symbol_reserva'])
                            $bots[$rw['idbotsw']]['strMonedas'] .= $symbol.' ';
                        $bots[$rw['idbotsw']]['capital'] += $rw1['inUSD'];
                    }
                }

            }
        }
        return $bots;
    }

    function getSymbolsForTrade()
    {
        if (!$this->data['idbotsw'])
        {
            CriticalExit('BotSW::getSymbolsForTrade() :: se debe especificar un ID valido');
        }

        $symbol_estable = $this->get('symbol_estable');
        $symbol_reserva = $this->get('symbol_reserva');
        $assets = $this->getAssets();
        
        $symbols = array();
        foreach ($assets as $asset)
            if ($asset && $asset != $symbol_estable)
                $symbols[$asset.$symbol_estable] = array('base'=>$asset,'quote'=>$symbol_estable);
        //foreach ($assets as $asset)
        //    if ($asset && $asset != $symbol_reserva)
        //    $symbols[$asset.$symbol_reserva] = array('base'=>$asset,'quote'=>$symbol_reserva);

        //$symbols[$symbol_reserva.$symbol_estable] = array('base'=>$symbol_reserva,'quote'=>$symbol_estable);

        return $symbols;
    }

    function addOrder($datetime,$baseAsset,$quoteAsset,$side,$origQty,$price,$orderId)
    {
        $idbotsw = $this->data['idbotsw'];
        $ins = "INSERT INTO bot_sw_orden_log (idbotsw,datetime,base_asset,quote_asset,side,origQty,price,orderId ) VALUES (
               ".$idbotsw.",
               '".$datetime."',
               '".$baseAsset."',
               '".$quoteAsset."',
               ".$side.",
               ".$origQty.",
               ".$price.",
               '".$orderId."')";
        $this->db->query($ins);

    }
}