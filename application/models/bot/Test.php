
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
    protected $comisionBinance = 0.075 ;

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
        $months = array();
        $apalancamientoInsuficiente = array();
        $acumPorcCompra = 0;


        $qry = "SELECT datetime,open,close,high,low 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' ";
        //$qry .= " AND datetime > '2021-07-01 00:00:00' ";
        //$qry .= " AND datetime < '2021-08-01 00:00:00' ";
        $qry .= " ORDER BY datetime ASC "; //LIMIT 1440
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
            if ($prms['at'])
            {
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
            }
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
            }
            $month = substr($datetime,0,7);
            if (!isset($months[$month]))
            {
                $months[$month]['ganancia'] = 0;
            }
                
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
                                      'qty'=>$qty,
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
                                      'qty'=>$qty,
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
                                          'qty'=>$qty,
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
                                        'qty'=>$qty,
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
                        $months[$month]['ganancia'] += $usd-$totalCompra;
                        
                        $ultimaCompra = 0.0;
                        $ordenCompra = 0.0;
                        $ordenVenta = 0.0;
                        $totalCompra = 0.0;
                        $compraNum = 0;
                        $operaciones++;
                        $comision = $usd * ($this->comisionBinance / 100);
                        $comisiones += $comision;
                        $orders[] = array('datetime'=>$datetime,
                                          'side'=>'SELL',
                                          'ordenCompra'=>$ordenCompra,
                                          'ordenVenta'=>$ordenVenta,
                                          'qty'=>$qty,
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

            //Analisis tecnico de 1 hora
            $hours[$hour]['at'] = $aTec['signal']['ema_cross'];


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
        $results['months'] = $months;
        $results['qtyDays'] = count($days);
        
        return $results;

    }

    function testGrid($symbol,$usdInicial,$compraInicial,$prms)
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
        $openPos = array();
        $ultimaCompra = 0.0;
        $ordenCompra = 0.0;
        $comisiones = 0.0;
        $orders = array();
        $hours = array();
        $months = array();
        $apalancamientoInsuficiente = array();
        
        $klines_1h = array();

        $qry = "SELECT datetime,open,close,high,low,volume 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' ";
        //$qry .= " AND datetime > '2021-07-01 00:00:00' ";
        //$qry .= " AND datetime < '2021-08-01 00:00:00' ";
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
            if ($prms['at'])
            {
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
            }
            $atBuySignal = true;
            if ($prms['at'] && $aTec['signal']['ema_cross']=='V')
                $atBuySignal = false;            

            $tokenPrice = round($close,$this->tokenDecPrice);
            
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
            }
            $month = substr($datetime,0,7);
            if (!isset($months[$month]))
            {
                $months[$month]['ganancia'] = 0;
            }

            if (count($openPos)==0) //Si no hay posiciones abiertas, inicia compra
            {
                $price = $close;
                $usd = round($compraInicial,2);
                $qty = round($usd/$price,$this->tokenDecUnits);
                if ($usd = $this->compra($qty,$price))
                {
                    $ultimaCompra = $usd;
                    $compraNum++;
                    
                    $comision = $usd * ($this->comisionBinance / 100);
                    $comisiones += $comision;
            
                    $porcCompra = ($multiplicadorPorc * ($incremental?$compraNum:1));
                    
                    $ordenCompra = round($price * (1 - $porcCompra ) ,$this->tokenDecPrice);
                    $ordenVenta  = round($price * (1 + $porcVentaUp ) ,$this->tokenDecPrice);
                    
                    $orderId = $this->newOrderId();
                    $openPos[$orderId] = array('qty'=>$qty,
                                               'buyPrice'=>$price,
                                               'sellPrice'=>$ordenVenta
                                               );

                    $orders[] = array('datetime'=>$datetime,
                                      'side'=>'BUY',
                                      'operacion'=>$operacion,
                                      'qty'=>$qty,
                                      'price'=>$price,
                                      'usd'=>$usd,
                                      'qtyUsd'=>$this->qtyUsd,
                                      'qtyToken'=>$this->qtyToken,
                                      'compraNum'=>$compraNum,
                                      'operaciones'=>$operaciones,
                                      'comision'=>$comision,
                                      'orderId'=>$orderId
                                      );
                    $hours[$hour]['buy'] = $price;
                }
                else
                {
                    $aiKey = str_replace('.','_',$price);
                    if (!isset($apalancamientoInsuficiente[$aiKey]))
                    {
                        $apalancamientoInsuficiente[$aiKey]=$datetime;
                        $orders[] = array('datetime'=>$datetime,
                                      'side'=>'AP_INS',
                                      'operacion'=>$operacion,
                                      'qty'=>$qty,
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
            else
            {
                if ($atBuySignal && $ordenCompra<$high && $ordenCompra>$low) //Ejecuta orden de compra
                {

                    foreach ($openPos as $rw)
                        $ultimaCompraAbierta = toDec($rw['buyPrice']*$rw['qty']);
                    $price = round($ordenCompra,$this->tokenDecPrice);
                    $usd = round($ultimaCompraAbierta * $multiplicadorCompra ,2);
                    $qty = round($usd/$price,$this->tokenDecUnits);
                    if ($usd = $this->compra($qty,$price))
                    {
                        $ultimaCompra = $usd;
                        $compraNum++;
                        if ($compraNum>$maxCompraNum)
                            $maxCompraNum = $compraNum;
                        $comision = $usd * ($this->comisionBinance / 100);
                        $comisiones += $comision;

                        if ($compraNum>2)
                            $dynPorcVentaDown = $porcVentaDown*($compraNum-2)*(1+($multiplicadorPorc*0.45));
                        else
                            $dynPorcVentaDown = $porcVentaDown;
            
                        $porcCompra = ($multiplicadorPorc * ($incremental?$compraNum:1));
                        $ordenCompra = round($price * (1 - $porcCompra ) ,$this->tokenDecPrice);
                        $usdAVender  = round($usd * (1 + $dynPorcVentaDown ),2);
                        $ordenVenta  = round($usdAVender/$qty,$this->tokenDecPrice);
                        
                        $orderId = $this->newOrderId();
                        $openPos[$orderId] = array('qty'=>$qty,
                                                   'buyPrice'=>$price,
                                                   'sellPrice'=>$ordenVenta
                                                   );

                        $orders[] = array('datetime'=>$datetime,
                                          'side'=>'BUY',
                                          'operacion'=>$operacion,
                                          'qty'=>$qty,
                                          'price'=>$price,
                                          'usd'=>$usd,
                                          'qtyUsd'=>$this->qtyUsd,
                                          'qtyToken'=>$this->qtyToken,
                                          'compraNum'=>$compraNum,
                                          'operaciones'=>$operaciones,
                                          'comision'=>$comision,
                                          'orderId'=>$orderId
                                          );
                        $hours[$hour]['buy'] = $price;
                    }
                    else
                    {
                        $aiKey = str_replace('.','_',$price);
                        if (!isset($apalancamientoInsuficiente[$aiKey]))
                        {
                            $apalancamientoInsuficiente[$aiKey]=$datetime;
                            $orders[] = array('datetime'=>$datetime,
                                          'side'=>'AP_INS',
                                          'operacion'=>$operacion,
                                          'qty'=>$qty,
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
                //Baja la orden de compra si esta en señal de venta
                if (!$atBuySignal && $ordenCompra>($open) )
                {
                    $ordenCompra = $close;
                }

                $posToDelete=array();
                foreach ($openPos as $orderId => $rw) //Revisa las posiciones abiertas para ver si se debe vender algo
                {
                    if ($rw['sellPrice']<$high && $rw['sellPrice']>$low) //Ejecuta orden de venta
                    {
                        $qty = round($rw['qty'],$this->tokenDecUnits);
                        $price = round($rw['sellPrice'],$this->tokenDecPrice);
                        if ($usd = $this->venta($qty,$price))
                        {
                            $compro=false;
                            $resultadoVenta = toDec(($rw['sellPrice']*$rw['qty'])-($rw['buyPrice']*$rw['qty']));
                            $months[$month]['ganancia'] += $resultadoVenta;
                            
                            $ordenCompra = $rw['buyPrice'];
                            $ordenVenta = 0.0;
                            $compraNum --;
                            $operaciones++;
                            $comision = $usd * ($this->comisionBinance / 100);
                            $comisiones += $comision;

                            $posToDelete[]=$orderId;

                            $orders[] = array('datetime'=>$datetime,
                                              'side'=>'SELL',
                                              'qty'=>$qty,
                                              'price'=>$price,
                                              'usd'=>$usd,
                                              'qtyUsd'=>$this->qtyUsd,
                                              'qtyToken'=>$this->qtyToken,
                                              'compraNum'=>$compraNum,
                                              'operaciones'=>$operaciones,
                                              'comision'=>$comision,
                                              'orderId'=>$orderId
                                              );
                            $hours[$hour]['sell'] = $price;
                        }
                    }
                }
            }
            $hours[$hour]['qtyUsd'] = toDec($this->qtyUsd);
            $hours[$hour]['qtyTokenInUsd'] = toDec($this->qtyToken*$tokenPrice);
            $hours[$hour]['tokenPrice'] = $tokenPrice;

            //Analisis tecnico de 1 hora
            $hours[$hour]['at'] = $aTec['signal']['ema_cross'];

            $hours[$hour]['nuevaOC'] = $ordenCompra;

            if (!empty($posToDelete))
            {
                foreach ($posToDelete as $orderId)
                    unset($openPos[$orderId]);
            }


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
        $results['months'] = $months;
        $results['openPos'] = $openPos;
        $results['qtyDays'] = count($days);
                
        return $results;

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
            $month = substr($datetime,0,7);
            if (!isset($months[$month]))
            {
                $months[$month]['ganancia'] = 0;
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
        $results['months'] = $months;
        $results['openPos'] = $openPos;
        $results['qtyDays'] = count($days);
                
        return $results;

    }

    function testBot2($symbol,$capital,$compraInicial,$prms)
    {
        $this->capital = $capital;
        $this->qtyUsd = $this->capital;
        $this->compraInicial = $compraInicial;
        $this->qtyToken = 0.0;
        $this->totalComprado = 0.0;

        $ventas = 0;

        $porcVentaUp = ($prms['porcVentaUp']/100);
        $porcVentaDown = ($prms['porcVentaDown']/100);
   
        $tck = new Ticker();
        $api = new Exchange();

        $bot['orders'] = array(); //Ordenes en curso
        
        if ($api->start($symbol,$this->qtyUsd,$this->qtyToken))
        {
            $fwd = true;
            while ($fwd)
            {
                $apiOrders = $api->openOrders();
                $completeBuy=0;
                $completeSell=0;
                $data=array();

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
                            
                            //Calculando PNL y estadisticas
                            $pnlQtyBuy = 0;
                            $pnlOprStart = null;
                            $pnlOprEnd = null;
                            $pnlGanancia = 0;
                            $pnlComision = 0;
                            foreach ($bot['orders'] as $o)
                            {
                                if ($o['side']=='BUY')
                                {
                                    $pnlQtyBuy++;
                                    $pnlGanancia -= toDec($o['origQty']*$o['price']);
                                    $pnlComision += $o['comision'];
                                }

                                if (!isset($pnlOprStart))
                                {
                                    $pnlOprStart = $o['created'];
                                }

                                if ($o['side']=='SELL')
                                {
                                    $pnlOprEnd = $o['updated'];
                                    $pnlGanancia += toDec($o['origQty']*$o['price']);
                                    $pnlComision += $o['comision'];
                                }
                                
                            }
                            $horas = diferenciaFechasEnHoras($pnlOprStart,$pnlOprEnd);
                            $this->pnlInfo[] = array('start'=>$pnlOprStart,
                                                     'end'=>$pnlOprEnd,
                                                     'ganancia'=>$pnlGanancia,
                                                     'comisiones'=>$pnlComision,
                                                     'horas'=>$horas,
                                                     'qtyCompras'=>$pnlQtyBuy
                                                     );
                            

                            //Deja la operacion lista para el reinicio
                            $bot['orders'] = array();
                            //EN PRODUCCION, COMPLETAR LA OPERACION Y AGREGAR A PNL
                            $ventas++;
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
                    $symbolData = $api->getSymbolData($symbol);
                    $qtyDecsUnits = $symbolData['qty_decs_units'];
                    $qtyDecsPrice = $symbolData['qty_decs_price'];

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
                $fwd = $api->fwd(); //Avanza al proximo minuto y verifica las ordenes
                if ($ventas>100)
                    break;
            }
            $account = $api->account();
            $account['usd']['usd'] = toDec($account['usd']['units']);
            $account['token']['usd'] = toDec($account['token']['units']*$ultimoPrecio,);
            $result['pnlInfo'] = $this->pnlInfo;
            $result['account'] = $account;
            $result['botOrders'] = $bot['orders'];
            $result['openOrders'] = $api->openOrders();
            $result['pnlOrders'] = $api->pnlOrders();
            return $result;

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


}