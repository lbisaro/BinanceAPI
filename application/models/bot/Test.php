<?php
include_once LIB_PATH."DB.php";
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Exchange.php";


class Test 
{
    protected $db;

    //Fecha de inicio de Klines para descarga de datos
    public $startKlines = '2021-06-01 00:00:00';

    protected $usdInicial = 0.0;
    protected $billetera = 0.0;
    protected $qtyUsd = 0.0;
    protected $qtyToken = 0.0;
    protected $totalComprado = 0.0;
    protected $pnlInfo = array();
    protected $comisionBinance = 0.0;//0.075 ;

    protected $updateStatus = array();

    function __Construct()
    {
        $this->db = DB::getInstance();
    }

    function getSymbolsToUpdate()
    {
        $qry = "SELECT DISTINCT symbol 
                FROM klines_1m";

        $stmt = $this->db->query($qry);
        $symbols = array();
        while ($rw = $stmt->fetch())
        {
            $symbols[$rw['symbol']] = $rw['symbol'];
        }

        $qry = "SELECT DISTINCT symbol 
                FROM operacion";

        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            $symbols[$rw['symbol']] = $rw['symbol'];
        }


        return $symbols;
    }

    function updateKlines_1m($symbol='ALL')
    {

        $api = new BinanceAPI();

        $qry = "SELECT max(datetime) maxDatetime
                FROM klines_1m
                WHERE symbol = '".$symbol."'";

        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            $maxDatetime = $rw['maxDatetime'];
        }
        $endTime = null;
        if (!$maxDatetime)
            $startTime = date('U',strtotime($this->startKlines)).'000';
        else
            $startTime = date('U',strtotime($maxDatetime.' + 1 minute')).'000';


        $lastKline = '1969-01-01 00:00:00';

        $interval = "1m";
        $limit = '1000';
        $lote = 0;
        $this->updateStatus=array();

        while ($lote <= 10 )
        {
            $ins = '';
            $this->updateStatus['lote'] = $lote;
            $this->updateStatus['start'] = date('Y-m-d H:i:s',$startTime/1000);
            $klines = $api->candlesticks($symbol, $interval, $limit, $startTime, $endTime);
            if (!empty($klines))
            {
                $this->updateStatus['qtyKlines'] = count($klines);
                foreach ($klines as $timestamp => $kline)
                {
                    $kline['datetime'] = date('Y-m-d H:i',($timestamp/1000)).':00';
                    if (strtodate($kline['datetime']) >= strtodate($this->$startKlines))
                    {
                        
                        $ins .= ($ins?' , ':'')." ('".$symbol."', 
                            '".$kline['datetime']."', 
                            '".$kline['open']."', 
                            '".$kline['close']."', 
                            '".$kline['high']."', 
                            '".$kline['low']."', 
                            '".toDec($kline['volume'],3)."'
                            )";
                    }
                    $lastKline = $kline['datetime'];
                    $this->updateStatus['last'] = $lastKline;
                }
                
            }
            if ($ins)
            {
                $ins = "INSERT INTO klines_1m (symbol, datetime, open,close,high,low,volume) VALUES ".$ins;
                $this->db->query($ins);
            }
            if (count($klines) < 1000)
                return false;
            $lote++;
            
            $startTime = date('U',strtotime($lastKline.' +1 minute ')).'000';
        }

        return true;
    }

    function getUpdateStatus()
    {
        return $this->updateStatus;
    }

    /**
     * @param $interval => 1m, 1h
     */
    function getKlines($symbol,$interval='1m',$from=null,$to=null)
    {
        $qry = "SELECT datetime,open,close,high,low,volume 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' ";
        if ($from)
            $qry .= " AND datetime >= '".$from."' "; 
        if ($to)
            $qry .= " AND datetime <= '".$to."' "; 
        $qry .= " ORDER BY datetime ASC "; //LIMIT 1440

        $stmt = $this->db->query($qry);
        $klines = array();
        while ($rw = $stmt->fetch())
        {
            if ($interval == '1m')
            {
                $klines[$rw['datetime']] = $rw;
            }
            else
            {
                $min = substr($rw['datetime'],14,2);
                if ($interval == '1h')
                {
                    $key = substr($rw['datetime'],0,13).':00';
                }
                elseif ($interval == '15m')
                {   
                    $aux = array('00,15,30,45');
                    $key = substr($rw['datetime'],0,13);
                    if ($min < '15')
                        $key .= ':00';
                    elseif ($min < '30')
                        $key .= ':15';
                    elseif ($min < '45')
                        $key .= ':30';
                    else
                        $key .= ':45';
                }

                $klines[$key]['datetime'] = $rw['datetime'];
                if (!isset($klines[$key]['open'])) 
                    $klines[$key]['open'] = $rw['open'];
                if ($rw['high'] > $klines[$key]['high'] || !isset($klines[$key]['high']))
                    $klines[$key]['high'] = $rw['high'];
                 if ($rw['low'] < $klines[$key]['low'] || !isset($klines[$key]['low']))
                    $klines[$key]['low'] = $rw['low'];
                $klines[$key]['close'] = $rw['close'];
                $klines[$key]['volume'] += $rw['volume'];
            }
        }
        return $klines;
    }

    function testAT($symbol,$usdInicial,$compraInicial,$prms)
    {
        $tck = new Ticker();
        $aTec = array(); //Analisis tecnico

        $this->usdInicial = $usdInicial;
        $this->qtyUsd = $this->usdInicial;
        $this->qtyToken = 0.0;
        $this->totalComprado = 0.0;

        $multiplicadorCompra = $prms['multiplicadorCompra'];
        $multiplicadorPorc = $prms['multiplicadorPorc']/100;
        $incremental = $prms['incremental'];
        $porcVentaUp = $prms['porcVentaUp']/100;
        $porcVentaDown = $prms['porcVentaDown']/100;

        //Obtener datos de BinanceAPI
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as); 

        $symbolData = $api->getSymbolData($symbol);
        $this->tokenDecPrice = $symbolData['qtyDecsPrice'];
        $this->tokenDecUnits = $symbolData['qtyDecs'];

        $klines_1h = array();

        $qry = "SELECT datetime,open,close,high,low,volume 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' ";
        $qry .= " AND datetime > '2021-06-01 00:00:00' ";
        $qry .= " AND datetime < '2021-07-01 00:00:00' ";
        $qry .= " ORDER BY datetime ASC"; // LIMIT 28800

        $stmt = $this->db->query($qry);
        while ($rw = $stmt->fetch())
        {
            $datetime   = $rw['datetime'];
            $open       = $rw['open'];
            $close      = $rw['close'];
            $high       = $rw['high'];
            $low        = $rw['low'];
            $volume     = $rw['volume'];

            //Armando analisis tecnico 1h
            $hh = substr($datetime,0,13).':00';
            if (!isset($klines_1h[$hh]))
            {
                $klines_1h[$hh]['open']       = $rw['open'];
                $klines_1h[$hh]['close']      = $rw['close'];
                $klines_1h[$hh]['high']       = $rw['high'];
                $klines_1h[$hh]['low']        = $rw['low'];
                $klines_1h[$hh]['volume']     = $rw['volume'];
            }
            $klines_1h[$hh]['close']      = $rw['close'];
            if ($rw['high'] > $klines_1h[$hh]['high'])
                $klines_1h[$hh]['high']   = $rw['high'];
            if ($rw['low'] < $klines_1h[$hh]['low'])
                $klines_1h[$hh]['low']    = $rw['low'];
            $klines_1h[$hh]['volume']    += $rw['volume'];

            //Luego de 30 horas, y solo si esta la hora completa, hace el analisis tecnico
            if (count($klines_1h)>30 && substr($datetime,14,2)=='59')
            {
                $candlesticks = array();
                
                for ($i=30;$i>=0;$i--)
                {
                    $atKey = date('Y-m-d H:',strtotime($hh.' -'.$i.' hours')).'00'; 
                    $candlesticks[$atKey] = $klines_1h[$atKey];
                }
                $aTec = $tck->analisisTecnico($candlesticks);
            }
        

            //$tokenPrice = round( (($close+$open)/2),$this->tokenDecPrice);
            $tokenPrice = round($close,$this->tokenDecPrice);
            
            $day = substr($datetime,0,10);
            if (!isset($days[$day]))
            {
                $days[$day] = $day;
            }

            $hour = substr($datetime,0,13).':00';
            if (!isset($hours[$hour]))
            {
                $hours[$hour]['qtyUsd'] = 0;
                $hours[$hour]['qtyTokenInUsd'] = 0;
            }

            $hours[$hour]['qtyUsd'] = toDec($this->qtyUsd);
            $hours[$hour]['qtyTokenInUsd'] = toDec($this->qtyToken*$tokenPrice);
            $hours[$hour]['tokenPrice'] = $tokenPrice;

            //Analisis tecnico de 1 hora
            $hours[$hour]['at'] = $aTec;
        }




        $balance = toDec($this->qtyUsd + $this->qtyToken * $close,2);
        $comisiones = toDec($comisiones,2);
        $balanceFinal = toDec($balance - $comisiones,2);
        $porcentajeGanancia = toDec((($balanceFinal-$this->usdInicial)*100)/$this->usdInicial,2);
        $results['SaldoInicial'] = $usdInicial;
        $results['Balance'] =       $balance;
        $results['Comisiones'] =    $comisiones;
        $results['BalanceFinal'] = $balanceFinal;
        $results['Ganancia'] =      $porcentajeGanancia;
        $results['Operaciones'] =   $operaciones;
        $results['tokenDecPrice'] = $this->tokenDecPrice;
        $results['tokenDecUnits'] = $this->tokenDecUnits;
        $results['apalancamientoInsuficiente'] = $apalancamientoInsuficiente;
        $results['maxCompraNum'] = $maxCompraNum;
        $results['orders'] = $orders;
        $results['hours'] = $hours;
        $results['openPos'] = $openPos;
        $results['qtyDays'] = count($days);
                
        return $results;

    }

    function testApalancamiento($symbol,$usdInicial,$compraInicial,$prms)
    {
        $tck = new Ticker();
        $aTec = array(); //Analisis tecnico

        $this->usdInicial = $usdInicial;
        $this->billetera = $usdInicial;
        $this->qtyUsd = $this->usdInicial;
        $this->qtyToken = 0.0;

        $multiplicadorCompra = $prms['multiplicadorCompra'];
        $multiplicadorPorc = $prms['multiplicadorPorc']/100;
        $incremental = $prms['incremental'];
        $porcVentaUp = $prms['porcVentaUp']/100;
        $porcVentaDown = $prms['porcVentaDown']/100;


        if (isset($prms['from']))
            $from = $prms['from'];
        else
            $from = '2021-06-01 00:00:00';
        if (isset($prms['to']))
            $to = $prms['to'];
        else
            $to = '2021-08-31 23:59:00';


        //Obtener datos de BinanceAPI
        $auth = UsrUsuario::getAuthInstance();
        $idusuario = $auth->get('idusuario');
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as); 

        $symbolData = $api->getSymbolData($symbol);
        $this->tokenDecPrice = $symbolData['qtyDecsPrice'];
        $this->tokenDecUnits = $symbolData['qtyDecs'];


        $compraNum = 0;
        $maxCompraNum = 0;
        $operaciones = 0;
        $ordenVenta = 0.0;
        $ordenCompra = 0.0;
        $ultimaCompra = 0.0;
        $totalCompra = 0.0;
        $comisiones = 0.0;
        $orders = array();
        $hours = array();
        $apalancamientoInsuficiente = array();
        $acumPorcCompra = 0;


        $qry = "SELECT datetime,open,close,high,low 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' ";
        if ($from)
            $qry .= " AND datetime >= '".$from."' ";
        if ($to)
            $qry .= " AND datetime <= '".$to."' ";
        $qry .= " ORDER BY datetime ASC "; //LIMIT 1440
        $stmt = $this->db->query($qry);

        while ($rw = $stmt->fetch())
        {
            if (!isset($results['start']))
                $results['start'] = $rw['datetime'];
            $results['end'] = $rw['datetime'];

            $datetime   = $rw['datetime'];
            $open       = $rw['open'];
            $close      = $rw['close'];
            $high       = $rw['high'];
            $low        = $rw['low'];
            $volume     = $rw['volume'];

            /*
            $atBuySignal = true;
            if ($prms['at'] && $aTec['signal']['ema_cross']=='V')
                $atBuySignal = false;            
            */                

            $tokenPrice = round($close,$this->tokenDecPrice);

            $day = substr($datetime,0,10);
            if (!isset($days[$day]))
            {
                $days[$day] = $day;
            }

            //$hour = substr($datetime,0,13).':00';
            $hour = substr($datetime,0,10);
            if (!isset($hours[$hour]))
            {
                $hours[$hour]['qtyUsd'] = 0;
                $hours[$hour]['qtyTokenInUsd'] = 0;
 
                $hours[$hour]['open'] = $open;
                $hours[$hour]['close'] = $close;
                $hours[$hour]['high'] = $high;
                $hours[$hour]['low'] = $low;     
            }

            $hours[$hour]['close'] = $close;
            if ($hours[$hour]['high'] < $high)
                $hours[$hour]['high'] = $high;
            if ($hours[$hour]['low'] > $low)
                $hours[$hour]['low'] = $low;     

            if ($compraNum == 0)
            {

                $price = $close;
                $usd = round($compraInicial,2);
                $qty = round($usd/$price,$this->tokenDecUnits);
                if ($usd = $this->compra($qty,$price))
                {
                    $ultimaCompra = $usd;
                    $compraNum++;
                    $totalCompra += $usd;
                    $comision = $usd * ($this->comisionBinance / 100);
                    $comisiones += $comision;
            
                    $porcCompra = ($multiplicadorPorc * ($incremental?$compraNum:1));
                    $ordenCompra = round($price * (1 - $porcCompra ) ,$this->tokenDecPrice);
                    $ordenVenta  = round($price * (1 + $porcVentaUp ) ,$this->tokenDecPrice);
                    
                    $orders[] = array('datetime'=>$datetime,
                                      'side'=>'BUY',
                                      'ordenCompra'=>$ordenCompra,
                                      'ordenVenta'=>$ordenVenta,
                                      'operacion'=>$operacion,
                                      'origQty'=>$qty,
                                      'price'=>$price,
                                      'usd'=>$usd,
                                      'qtyUsd'=>$this->qtyUsd,
                                      'qtyToken'=>$this->qtyToken,
                                      'compraNum'=>$compraNum,
                                      'operaciones'=>$operaciones,
                                      'comision'=>$comision,
                                      );
                    $hours[$hour]['buy'] = $price;
                    $acumPorcCompra += $porcCompra;
                }
                else
                {
                    $aiKey = str_replace('.','_',$price);
                    if (!isset($apalancamientoInsuficiente[$aiKey]))
                    {
                        $hours[$hour]['apins'] = $price;
                        $apalancamientoInsuficiente[$aiKey]=$datetime;
                        $orders[] = array('datetime'=>$datetime,
                                      'side'=>'AP_INS',
                                      'operacion'=>$operacion,
                                      'origQty'=>$qty,
                                      'price'=>$price,
                                      'usd'=>$price*$qty,
                                      'qtyUsd'=>$this->qtyUsd,
                                      'qtyToken'=>$this->qtyToken,
                                      'compraNum'=>$compraNum,
                                      'operaciones'=>$operaciones,
                                      'comision'=>0,
                                      'orderId'=>''
                                      );

                    }
                }

            }
            elseif ($ordenCompra>0 || $ordenVenta>0)
            {
                if ($ordenCompra<$high && $ordenCompra>$low) #Ejecuta orden de compra
                {
                    $price = round($ordenCompra,$this->tokenDecPrice);
                    $usd = round($ultimaCompra * $multiplicadorCompra,2);
                    $qty = round($usd/$price,$this->tokenDecUnits);
                    if ($usd = $this->compra($qty,$price))
                    {
                        $ultimaCompra = $usd;
                        $compraNum++;
                        if ($compraNum>$maxCompraNum)
                            $maxCompraNum = $compraNum;
                        $totalCompra += $usd;
                        $comision = $usd * ($this->comisionBinance / 100);
                        $comisiones += $comision;
            
                        $porcCompra = ($multiplicadorPorc * ($incremental?$compraNum:1));
                        $ordenCompra = round($price * (1 - $porcCompra ) ,$this->tokenDecPrice);
                        $usdAVender  = round($totalCompra * (1 + $porcVentaDown ),2);
                        $ordenVenta  = round($usdAVender/$this->qtyToken,$this->tokenDecPrice);
                        $orders[] = array('datetime'=>$datetime,
                                          'side'=>'BUY',
                                          'ordenCompra'=>$ordenCompra,
                                          'ordenVenta'=>$ordenVenta,
                                          'operacion'=>$operacion,
                                          'porcCompra' =>$acumPorcCompra,
                                          'origQty'=>$qty,
                                          'price'=>$price,
                                          'usd'=>$usd,
                                          'qtyUsd'=>$this->qtyUsd,
                                          'qtyToken'=>$this->qtyToken,
                                          'compraNum'=>$compraNum,
                                          'operaciones'=>$operaciones,
                                          'comision'=>$comision,
                                          );
                        $hours[$hour]['buy'] = $price;
                        $acumPorcCompra += $porcCompra;
                    }
                    else
                    {
                        $aiKey = str_replace('.','_',$price);
                        if (!isset($apalancamientoInsuficiente[$aiKey]))
                        {
                            $hours[$hour]['apins'] = $price;
                            $apalancamientoInsuficiente[$aiKey]=$datetime;
                            $orders[] = array('datetime'=>$datetime,
                                        'side'=>'AP_INS',
                                        'operacion'=>$operacion,
                                        'origQty'=>$qty,
                                        'price'=>$price,
                                        'usd'=>$price*$qty,
                                        'qtyUsd'=>$this->qtyUsd,
                                        'qtyToken'=>$this->qtyToken,
                                        'compraNum'=>$compraNum,
                                        'operaciones'=>$operaciones,
                                        'comision'=>0,
                                        'orderId'=>''
                                        );
                        }
                    }
                }

                if ($ordenVenta<$high && $ordenVenta>$low) #Ejecuta orden de venta
                {
                    $qty = round($this->qtyToken,$this->tokenDecUnits);
                    $price = round($ordenVenta,$this->tokenDecPrice);
                    if ($usd = $this->venta($qty,$price))
                    {
                        $ultimaCompra = 0.0;
                        $ordenCompra = 0.0;
                        $ordenVenta = 0.0;
                        $totalCompra = 0.0;
                        $compraNum = 0;
                        $operaciones++;
                        $comision = $usd * ($this->comisionBinance / 100);
                        $comisiones += $comision;
                        $orders[] = array('datetime'=>$datetime,
                                          'updated'=>$datetime,
                                          'side'=>'SELL',
                                          'ordenCompra'=>$ordenCompra,
                                          'ordenVenta'=>$ordenVenta,
                                          'origQty'=>$qty,
                                          'price'=>$price,
                                          'usd'=>$usd,
                                          'qtyUsd'=>$this->qtyUsd,
                                          'qtyToken'=>$this->qtyToken,
                                          'compraNum'=>$compraNum,
                                          'operaciones'=>$operaciones,
                                          'comision'=>$comision,
                                          );

                        $hours[$hour]['sell'] = $price;
                        

                        $acumPorcCompra = 0;

                    }
                }
            }
            $hours[$hour]['qtyUsd'] = toDec($this->qtyUsd);
            $hours[$hour]['qtyTokenInUsd'] = toDec($this->qtyToken*$tokenPrice);
            $hours[$hour]['tokenPrice'] = $tokenPrice;

        }

        $this->calcularPnl($orders);

        $balance = toDec($this->qtyUsd + $this->qtyToken * $close,2);
        $comisiones = toDec($comisiones,2);
        $balanceFinal = toDec($balance - $comisiones,2);
        $porcentajeGanancia = toDec((($balanceFinal-$this->usdInicial)*100)/$this->usdInicial,2);
        $results['saldoInicial'] = $usdInicial;
        $results['symbol'] = $symbol;
        $results['interval'] = '1h';
        $results['balance'] =       $balance;
        $results['comisiones'] =    $comisiones;
        $results['balanceFinal'] = $balanceFinal;
        $results['ganancia'] =      $porcentajeGanancia;
        $results['operaciones'] =   $operaciones;
        $results['tokenDecPrice'] = $this->tokenDecPrice;
        $results['tokenDecUnits'] = $this->tokenDecUnits;
        $results['apalancamientoInsuficiente'] = $apalancamientoInsuficiente;
        $results['maxCompraNum'] = $maxCompraNum;
        $results['orders'] = $orders;
        $results['hours'] = $hours;
        $results['qtyDays'] = count($days);
        $results['pnlInfo'] = $this->pnlInfo;
        
        return $results;

    }

    function testBotAuto($symbol,$capital,$compraInicial,$prms)
    {
        $this->capital = $capital;
        $this->qtyUsd = $this->capital;
        $this->compraInicial = $compraInicial;
        $this->qtyToken = 0.0;
        $this->totalComprado = 0.0;
        $days = array();

        $porcVentaUp = ($prms['porcVentaUp']/100);
        $porcVentaDown = ($prms['porcVentaDown']/100);

        $maxCompraNum=0;

        if (isset($prms['from']))
            $from = $prms['from'];
        else
            $from = '2021-06-01 00:00:00';
        if (isset($prms['to']))
            $to = $prms['to'];
        else
            $to = '2021-08-31 23:59:00';
   
        $tck = new Ticker($symbol);
        $api = new Exchange();
        
        $bot['orders'] = array(); //Ordenes en curso
        
        if ($api->start($symbol,$this->qtyUsd,$this->qtyToken,$from,$to))
        {
            $symbolData = $api->getSymbolData($symbol);
            $qtyDecsUnits = $symbolData['qty_decs_units'];
            $qtyDecsPrice = $symbolData['qty_decs_price'];

            $fwd = true;
            while ($fwd)
            {
                $apiOrders = $api->openOrders();
                $completeBuy=0;
                $completeSell=0;
                $data=array();

                $kline = $api->getKline();
                $datetime = $kline['datetime'];

                $day = substr($datetime,0,10);
                if (!isset($days[$day]))
                {
                    $days[$day] = $day;
                }

                $hour = substr($datetime,0,13).':00';
                //$hour = substr($datetime,0,10);
                if (!isset($hours[$hour]))
                {
                    $hours[$hour]['qtyUsd'] = 0;
                    $hours[$hour]['qtyTokenInUsd'] = 0;

                    $hours[$hour]['open'] = $kline['open'];
                    $hours[$hour]['close'] = $kline['close'];
                    $hours[$hour]['high'] = $kline['high'];
                    $hours[$hour]['low'] = $kline['low'];
                }

                $hours[$hour]['close'] = $kline['close'];
                if ($hours[$hour]['high'] < $kline['high'])
                    $hours[$hour]['high'] = $kline['high'];
                if ($hours[$hour]['low'] > $kline['low'])
                    $hours[$hour]['low'] = $kline['low'];

                if (!isset($results['start']))
                    $results['start'] = $datetime;
                $results['end'] = $datetime;
            

                if (!empty($bot['orders']))
                {
                    foreach ($bot['orders'] as $orderId=>$order)
                    {
                        //Filtra las ordenes que se encuentran abiertas
                        if ($order['status']=='NEW')
                        {
                            $strSide = $order['side'];
                            $data[$strSide][$orderId]['orderId']=$orderId;
                            
                            //Si no existe la orden abierta en Binance y si en la DB, hay que tomar accion
                            if (!isset($apiOrders[$orderId]))
                            {
                                //Busca info en el Exchange sobre la orden 
                                $orderStatus = $api->orderStatus($symbol,$orderId);
                                
                                //Si la orden se completo
                                if (!empty($orderStatus) && $orderStatus['status']=='FILLED')
                                {
                                    $data['update'] = true;
                                    $data['actualizar'] = $strSide;
                                    $data[$strSide][$orderId]=$orderStatus;
                                    if ($order['side']=='BUY')
                                        $hours[$hour]['buy'] = $orderStatus['price'];
                                    else
                                        $hours[$hour]['sell'] = $orderStatus['price'];
                                }
                                elseif (!empty($orderStatus) && $orderStatus['status']=='CANCELED')
                                {
                                    $data['canceled'][$orderId] = $strSide;
                                }
                                else
                                {
                                    $data['unknown'][$orderId] = $strSide;  
                                }
                            }
                        }
                    }

                    //Control sobre ordenes en estado desconocido en Binance
                    $ordenDesconocidaEnBinance = false;
                    if (!empty($data['unknown']))
                    {
                        foreach ($data['unknown'] as $orderId => $strSide)
                        {
                            $ordenDesconocidaEnBinance = true;
                            $msg = 'Error - ORDEN DE '.strtoupper($strSide).' DESCONOCIDA EN EXCHANGE (orderId = '.$orderId.')';
                            debug($msg);
                        }
                    }
                    if ($ordenDesconocidaEnBinance)
                        return false;

                    //Control sobre ordenes eliminadas en Binance
                    $ordenEliminadaEnBinance = false;
                    if (!empty($data['canceled']))
                    {
                        foreach ($data['canceled'] as $orderId => $strSide)
                        {
                            $ordenEliminadaEnBinance = true;
                            $msg = ' ORDEN DE '.strtoupper($strSide).' CANCELADA EN EXCHANGE (orderId = '.$orderId.')';
                            debug($msg);
                        }
                    }
                    if ($ordenEliminadaEnBinance)
                        return false;

                    //Actualizar operacion y ordenes
                    if ($data['update'])
                    {
                        //La operacion ejecuto una compra
                        if ($data['actualizar'] == 'BUY') 
                        {
                            if (!empty($data['SELL']) )
                            {
                                foreach ($data['SELL'] as $orderId => $rw)
                                {
                                    unset($bot['orders'][$orderId]);
                                    $api->cancelOrder($symbol, $orderId);

                                    //EN PRODUCCION, ESPERAR A CONFIRMAR LA CANCELACION PARA PODER CREAR LA NUEVA VENTA
                                }
                            }

                            //Actualiza el estado de la compra ejecutada
                            if (!empty($data['BUY']))
                            {
                                foreach ($data['BUY'] as $orderId => $rw)
                                {
                                    $bot['orders'][$orderId] = $api->orderStatus($symbol,$orderId);                                    
                                }
                            }

                            // Crea nueva Venta
                            $buyedUnits = 0;
                            $buyedUsd = 0;
                            foreach ($bot['orders'] as $order)
                            {
                                if ($order['status']=='FILLED' && $order['side']=='BUY')
                                {
                                    $buyedUnits += $order['origQty'];
                                    $buyedUsd += ($order['origQty']*$order['price']);
                                }
                            }
                            $qty = toDec($buyedUnits,$qtyDecsUnits);
                            $usd = toDec($buyedUsd * (1+$porcVentaDown));
                            $price = toDec($usd/$qty,$qtyDecsPrice);
                            $newOrder = $api->sell($symbol, $qty, $price);
                            $bot['orders'][$newOrder['orderId']] = $newOrder;   

                        }
                        //La operacion se vendio y debe finalizar
                        elseif ($data['actualizar'] == 'SELL') 
                        {
                            //Actualiza el estado de la compra ejecutada
                            if (!empty($data['SELL']))
                            {
                                foreach ($data['SELL'] as $orderId => $rw)
                                {
                                    $bot['orders'][$orderId] = $api->orderStatus($symbol,$orderId);
                                }
                            }
                            //Eliminar las ordenes de compra abiertas
                            if (!empty($data['BUY']))
                            {
                                foreach ($data['BUY'] as $orderId => $rw)
                                {
                                    $bot['orders'][$orderId] = $api->orderStatus($symbol,$orderId);   
                                    unset($bot['orders'][$orderId]);
                                    $api->cancelOrder($symbol, $orderId);

                                    //EN PRODUCCION, ESPERAR A CONFIRMAR LA CANCELACION PARA PODER CREAR LA NUEVA VENTA

                                }
                            }

                            //Deja la operacion lista para el reinicio
                            $bot['orders'] = array();
                            //EN PRODUCCION, COMPLETAR LA OPERACION Y AGREGAR A PNL

                        }
                    }
                }

                //Reiniciando la operacion si no hay ordenes activas
                /**
                    Si hubo una venta, el proceso de restart se ejecuta al minuto siguiente 
                    para dar tiempo a que se cancelen todas las ordenes 
                */
                else
                {
                    

                     
                    $precioActual = $api->price($symbol);

                    $opr['palancas'] = $tck->calcularPalancas($precioActual);
                    $palancaMax = end($opr['palancas']['porc']);
                    $qtyPalancas = count($opr['palancas']['porc']);
                    $opr['mutiplicador_compra'] = $tck->calcularMultiplicadorDeCompras($qtyPalancas,$this->capital,$this->compraInicial);
                    
                    //Armando ordenes para nueva operacion
                    if (!empty($opr['palancas']['price']))
                    {
                        $qty = toDec($compraInicial/$precioActual,$qtyDecsUnits);
                        $usd = toDec($qty*$precioActual);
                        $newOrder = $api->marketBuy($symbol, $qty);
                        $hours[$hour]['buy'] = $newOrder['price'];
                        while ($newOrder['status']!='FILLED')
                        {
                            sleep(1);
                            $newOrder = $api->orderStatus($symbol,$newOrder['orderId']);
                        }
                        $bot['orders'][$order['orderId']] = $newOrder;
                        foreach ($opr['palancas']['price'] as $compraNum => $price)
                        {
                            $price = toDec($price,$qtyDecsPrice);
                            $usd = $usd * $opr['mutiplicador_compra'];
                            $qty = toDec($usd/$price,$qtyDecsUnits);
                            $newOrder = $api->buy($symbol, $qty, $price);
                            $bot['orders'][$newOrder['orderId']] = $newOrder;
                        } 
                        
                        //Orden de venta de la compra inicial
                        $buyedUnits = 0;
                        $buyedUsd = 0;
                        foreach ($bot['orders'] as $order)
                        {
                            if ($order['status']=='FILLED' && $order['side']=='BUY')
                            {
                                $buyedUnits += $order['origQty'];
                                $buyedUsd += ($order['origQty']*$order['price']);
                            }
                        }
                        $qty = toDec($buyedUnits,$qtyDecsUnits);
                        $usd = toDec($buyedUsd * (1+$porcVentaUp));
                        $price = toDec($usd/$qty,$qtyDecsPrice);
                        $newOrder = $api->sell($symbol, $qty, $price);
                        $bot['orders'][$newOrder['orderId']] = $newOrder;                        
                        
                    }
                }
                $ultimoPrecio = $api->price($symbol);
                $account = $api->account();
                $account['usd']['usd'] = toDec($account['usd']['units']);
                $account['token']['usd'] = toDec($account['token']['units']*$ultimoPrecio);
                $hours[$hour]['qtyUsd'] = $account['usd']['usd'];
                $hours[$hour]['qtyTokenInUsd'] = $account['token']['usd'];

                $oo = $bot['orders'];
                if (!empty($oo))
                {
                    $ocnum=1;
                    foreach ($oo as $o)
                    {
                        if ($o['side']=='BUY')
                        {
                            if ($o['status']=='NEW')
                                $hours[$hour]['oc'.$ocnum] = $o['price'];
                            else
                                $hours[$hour]['oc'.$ocnum] = null;
                            if ($ocnum>$maxCompraNum)
                                $maxCompraNum = $ocnum;
                            $ocnum++;
                        }

                        if ($o['side']=='SELL' && $o['status']=='NEW')
                        {
                            $hours[$hour]['ov'] = $o['price'];
                        }

                    }

                }

                $fwd = $api->fwd(); //Avanza al proximo minuto y verifica las ordenes

            }

            //RESULTADOS ANALIZADOS PARA INCORPORAR
            $account = $api->account();
            $account['usd']['usd'] = toDec($account['usd']['units']);
            $account['token']['usd'] = toDec($account['token']['units']*$ultimoPrecio);
            $account['total']['usd'] = $account['usd']['usd'] + $account['token']['usd'];
            
            $results['account'] = $account;
            $results['botOrders'] = $bot['orders'];
            $results['openOrders'] = $api->openOrders();
            $results['pnlOrders'] = $api->pnlOrders();

            $usdComprado = 0;
            $qtyComprado = 0;
            if (!empty($results['botOrders']))
            foreach ($results['botOrders'] as $o)
            {
                $usdComprado += toDec($o['origQty']*$o['price']);
                $qtyComprado += $o['origQty'];
            }
            if ($qtyComprado>0)
            {
                $results['statusComprado']['comprado'] = $usdComprado;
                $results['statusComprado']['actual'] = toDec($qtyComprado*$ultimoPrecio);
                $results['statusComprado']['porcentaje'] = toDec((($results['statusComprado']['actual']/$results['statusComprado']['comprado'])-1)*100)."%";

            }

            $this->calcularPnl($api->pnlOrders());                            
            $comisiones=0;
            foreach ($this->pnlInfo as $pnl)
            {
                $comisiones += $pnl['comisiones'];

            }

                            
            //RESULTADOS COMPATIBLES CON BackTesting
            $balance = toDec($account['usd']['usd'] + $result['statusComprado']['comprado'] ,2);
            $comisiones = toDec($comisiones,2);
            $balanceFinal = toDec($balance - $comisiones,2);
            $porcentajeGanancia = toDec((($balanceFinal-$this->capital)*100)/$this->capital,2);
            $results['symbol'] = $symbol;
            $results['interval'] = '1h';
            $results['saldoInicial'] = $this->capital;
            $results['balance'] =       $balance;
            $results['comisiones'] =    $comisiones;
            $results['balanceFinal'] = $balanceFinal;
            $results['ganancia'] =      $porcentajeGanancia;
            $results['operaciones'] =   count($this->pnlInfo);
            $results['tokenDecPrice'] = $symbolData['qty_decs_price'];
            $results['tokenDecUnits'] = $symbolData['qty_decs_units'];
            $results['apalancamientoInsuficiente'] = array();
            $results['maxCompraNum'] = $maxCompraNum;
            $results['orders'] = $api->pnlOrders();
            $results['hours'] = $hours;
            $results['openPos'] = $api->openOrders();
            $results['qtyDays'] = count($days);
            $results['pnlInfo'] = $this->pnlInfo;

            return $results;

        }
        else
        {
            debug($api->getErrLog());
            return false;
        }
    }

    function compra($qty,$price)
    {
        $usd = round($qty*$price,2);
        if ($this->qtyUsd-$usd>=0 && $this->totalComprado+$usd<=$this->billetera)
        {
            $this->qtyToken = toDec($this->qtyToken + $qty,$this->tokenDecUnits);
            $this->qtyUsd -= $usd;
            $this->totalComprado += $usd;
            return $usd;
        }
        return null;
    }

    function venta($qty,$price)
    {
        if (toDec($this->qtyToken,$this->tokenDecUnits)-$qty>=0)
        {
            $usd = toDec($qty*$price,2);
            $this->qtyToken = toDec($this->qtyToken - $qty,$this->tokenDecUnits);
            $this->qtyUsd += $usd;
            $this->totalComprado = 0.0;
            return $usd;
        }
        return null;
    }

    function newOrderId()
    {
        return date('U').'_'.rand(1000,9999);
    }

    function calcularPnl($orders)
    {
        if (!empty($orders))
        {

            $pnlQtyBuy = 0;
            $pnlOprStart = null;
            $pnlOprEnd = null;
            $pnlGanancia = 0;
            $pnlComision = 0;
            foreach ($orders as $o)
            {

                if ($o['side']=='BUY')
                {
                    $pnlQtyBuy++;
                    $pnlGanancia -= toDec($o['origQty']*$o['price']);
                    $pnlComision += $o['comision'];
                }

                if (!$pnlOprStart)
                    $pnlOprStart = $o['datetime'];

                if ($o['side']=='SELL')
                {
                    $pnlOprEnd = $o['updated'];
                    $pnlGanancia += toDec($o['origQty']*$o['price']);
                    $pnlComision += $o['comision'];

                    $horas = diferenciaFechasEnHoras($pnlOprStart,$pnlOprEnd);
                    $this->pnlInfo[] = array('start'=>$pnlOprStart,
                                             'end'=>$pnlOprEnd,
                                             'ganancia'=>toDec($pnlGanancia),
                                             'comisiones'=>$pnlComision,
                                             'horas'=>$horas,
                                             'qtyCompras'=>$pnlQtyBuy
                                             );        

                    $pnlQtyBuy = 0;
                    $pnlOprStart = null;
                    $pnlOprEnd = null;
                    $pnlGanancia = 0;
                    $pnlComision = 0;
                }
            }
        }
    }

}