
<?php
include_once LIB_PATH."DB.php";
include_once MDL_PATH."binance/BinanceAPI.php";


class Test 
{
    protected $db;

    //Fecha de inicio de Klines para descarga de datos
    public $startKlines = '2021-06-01 00:00:00';

    protected $usdInicial = 0.0;
    protected $qtyUsd = 0.0;
    protected $qtyToken = 0.0;

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

        $auth = UsrUsuario::getAuthInstance();
        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');
        $api = new BinanceAPI($ak,$as);

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
            $qry .= " AND datetime > '".$from."' "; 
        if ($to)
            $qry .= " AND datetime < '".$to."' "; 
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
        # Agregar control sobre falta de palanca
        # Agregar control sobre maximo invertido

        $this->usdInicial = $usdInicial;
        $this->qtyUsd = $this->usdInicial;
        $this->qtyToken = 0.0;
        $symbolCsv = "apalancamiento_".$symbol.".csv";

        $multiplicadorCompra = $prms['multiplicadorCompra'];
        $multiplicadorPorc = $prms['multiplicadorPorc']/100;
        $incremental = $prms['incremental'];
        $porcVentaUp = $prms['porcVentaUp']/100;
        $porcVentaDown = $prms['porcVentaDown']/100;

        //$compraInicial = $this->usdInicial*0.056; //Este numero se calcula para lograr 4/5 palancas con la billetera

        //Obtener datos de BinanceAPI
        $this->tokenDecPrice = 3;
        $this->tokenDecUnits = 3;


        $compraNum = 0;
        $maxCompraNum = 0;
        $operaciones = 0;
        $ordenVenta = 0.0;
        $ordenCompra = 0.0;
        $ultimaCompra = 0.0;
        $totalCompra = 0.0;
        $comisiones = 0.0;
        $orders = array();
        $days = array();
        $months = array();
        $apalancamientoInsuficiente = false;
        $acumPorcCompra = 0;


        $qry = "SELECT datetime,open,close,high,low 
                FROM klines_1m 
                WHERE symbol = '".$symbol."' 
                ORDER BY datetime ASC "; //LIMIT 1440
        $stmt = $this->db->query($qry);

        while ($rw = $stmt->fetch())
        {
            $datetime   = $rw['datetime'];
            $open       = $rw['open'];
            $close      = $rw['close'];
            $high       = $rw['high'];
            $low        = $rw['low'];
            $volume     = $rw['volume'];

            $tokenPrice = round($close,$this->tokenDecPrice);
                

            $day = substr($datetime,0,10);
            if (!isset($days[$day]))
            {
                $days[$day]['qtyUsd'] = 0;
                $days[$day]['qtyTokenInUsd'] = 0;
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
                    $acumPorcCompra += $porcCompra;
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
                        $acumPorcCompra += $porcCompra;
                    }
                    else
                    {
                        if (!$apalancamientoInsuficiente)
                            $apalancamientoInsuficiente = true;
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
                        

                        $acumPorcCompra = 0;

                    }
                }
            }
            $days[$day]['qtyUsd'] = toDec($this->qtyUsd);
            $days[$day]['qtyTokenInUsd'] = toDec($this->qtyToken*$tokenPrice);
            $days[$day]['tokenPrice'] = $tokenPrice;

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
        $results['days'] = $days;
        $results['months'] = $months;
        
        return $results;

    }

    function compra($qty,$price)
    {
        $usd = round($qty*$price,2);
        if ($this->qtyUsd-$usd>0)
        {
            $this->qtyToken = toDec($this->qtyToken + $qty,$this->tokenDecUnits);
            $this->qtyUsd -= $usd;
            return $usd;
        }
        return null;
    }

    function venta($qty,$price)
    {
        if (toDec($this->qtyToken,$this->tokenDecUnits)-$qty==0)
        {
            $usd = toDec($qty*$price,2);
            $this->qtyToken = toDec($this->qtyToken - $qty,$this->tokenDecUnits);
            $this->qtyUsd += $usd;
            return $usd;
        }
        return null;
    }

}