<?php
include_once LIB_PATH."DB.php";
include_once LIB_PATH."ErrorLog.php";
include_once MDL_PATH."Ticker.php";


/** IMPLEMENTACION

$api = mew Exchange();
if ($api->start($symbol,$qtyUsd,$qtyToken))
{
    inicia el proceso en la primera vela descargada


    $api->fwd(); //Avanza al proximo minuto y verifica las ordenes
}
else
{
    $api->getErrLog();
}


*/


class Exchange {

    protected $db;
    protected $errLog;

    protected $openOrders = array();
    protected $pnlOrders = array();
    protected $lastOrderId = 0;
    protected $klines;
    protected $kline;
    protected $comisionExchange = 0.075;

    protected $symbol;
    protected $symbolData;
    protected $qtyUsd;
    protected $qtyToken;
    protected $blockUsd;
    protected $blockToken;

    function __Construct()
    {
        $this->db = DB::getInstance();
        $this->errLog = new ErrorLog();
    }

    function start($symbol,$qtyUsd,$qtyToken)
    {
        $this->symbol   = $symbol;
        $this->qtyUsd   = $qtyUsd;
        $this->qtyToken = $qtyToken;

        if (empty($symbol))
        {
            $this->errLog->add('Se debe especificar un Ticker (Ej.: BTCUSDT)');
            return false;
        }

        if (($qtyUsd + $qtyToken) < 0 || $qtyUsd < 0 || $qtyToken < 0)
        {
            $this->errLog->add('Se debe especificar una cantidad mayor a 0 en la billetera compuesta de una cantidad de USD y/o Token');
            return false;
        }

        $tck = new Ticker($symbol);
        if ($tck->get('tickerid') == $symbol)
        {
            $this->symbolData = $tck->getAllData();
            $this->__loadKlines($symbol);
            
            if (!empty($this->klines))
            {
                $this->kline = current($this->klines);

                return true;
            }
            $this->errLog->add('No existen datos de velas registradas para '.$symbol);
            return false;
            
        }
        $this->errLog->add('No existen datos registrados para '.$symbol);
        return false;
    }

    function getKline()
    {
        return $this->kline;
    }

    function fwd()
    {
        //Revisar y enecutar las ordenes existentes
        $klinePre = $this->kline;
        $klinePost = next($this->klines);
        $this->kline = $klinePost;
        if (empty($klinePost))
        {
            return false; //No hay mas Klines
        }

        if (!empty($this->openOrders))
        {
            foreach ($this->openOrders as $orderId => $order)
            {
                if ($order['status']=='NEW')
                {
                    if ($order['type']=='MARKET')
                    {
                        $price = (($klinePre['high']+$klinePre['low'])/2);
                        $usd = toDec($price * $order['origQty']);

                        if ($order['side']=='BUY')
                        {
                            $this->__executeOrder($orderId,$price);
                        }
                        elseif ($order['side']=='SELL')
                        {
                            $this->__executeOrder($orderId,$price);
                        }
                    }
                    elseif ($order['type']=='LIMIT')
                    {
                        $priceHigh = $klinePost['high'];
                        $priceLow = $klinePre['low'];
                        $usd = toDec($order['price'] * $order['origQty']);
                        
                        if ($order['side']=='BUY' && $order['price']>=$priceLow && $order['price']<=$priceHigh)
                        {
                            $this->__executeOrder($orderId,$price);
                        }
                        elseif ($order['side']=='SELL' && $order['price']>=$priceLow && $order['price']<=$priceHigh)
                        {
                            $this->__executeOrder($orderId,$price);
                        }
                    }
                }

            }
        }
        return true;  

    }

    function openOrders()
    {
        return $this->openOrders;
    }

    function pnlOrders()
    {
        return $this->pnlOrders;
    }

    function orderStatus($symbol,$orderId)
    {
        if (isset($this->openOrders[$orderId]))
            return $this->openOrders[$orderId];
        
        if (isset($this->pnlOrders[$orderId]))
            return $this->pnlOrders[$orderId];

        $order[$orderId]['status']='CANCELLED';
        return $order;
    }

    function getSymbolData($symbol)
    {
        return $this->symbolData;
    }

    function price($symbol)
    {
        return $this->kline['open']; 
    }

    function account()
    {
        $symbolData = $this->getSymbolData($symbol);
        $account['usd']['units'] = $this->qtyUsd;
        $account['usd']['asset'] = $this->symbolData['quote_asset'];
        $account['token']['units'] = toDec($this->qtyToken,$symbolData['qty_decs_units']);
        $account['token']['asset'] = $this->symbolData['base_asset'];

        return $account;
    }

    function buy($symbol, $qty, $price)
    {
        return $this->__addOrder("BUY", $symbol, $qty, $price, 'LIMIT');
    }

    function sell($symbol, $qty, $price)
    {
        return $this->__addOrder("SELL", $symbol, $qty, $price, 'LIMIT');
    }

    function marketBuy($symbol, $qty)
    {
        return $this->__addOrder("BUY", $symbol, $qty, 0, "MARKET");
    }

    function marketSell($symbol, $qty)
    {
        return $this->__addOrder("SELL", $symbol, $qty, 0, "MARKET");
    }

    protected function __addOrder($side, $symbol, $qty, $price, $type)
    {
        $datetime = $this->kline['datetime'];
        $symbolData = $this->getSymbolData($symbol);
        $qty = toDec($qty,$symbolData['qty_decs_units']);
        $price = toDec($price,$symbolData['qty_decs_price']);

        $this->lastOrderId++;
        $orderId = $this->lastOrderId;
        $this->openOrders[$orderId] = array('orderId'=>$orderId,
                                        'side'=>$side,
                                        'symbol'=>$symbol,
                                        'origQty'=>$qty,
                                        'price'=>$price,
                                        'type'=>$type,
                                        'status'=>'NEW',
                                        'datetime'=>$datetime
                                        );
        if ($type == 'MARKET')
        {
            $this->__executeOrder($orderId,$price);
            return $this->pnlOrders[$orderId];
        }
        return $this->openOrders[$orderId];
    }

    function cancelOrder($symbol, $orderId)
    {
        if ($this->openOrders[$orderId]['status'] == 'NEW')
        {
            unset($this->openOrders[$orderId]);
            return true;
        }
        return false;

    }

    protected function __executeOrder($orderId,$price)
    {
        if ($this->openOrders[$orderId]['status'] == 'NEW')
        {
            $datetime = $this->kline['datetime'];
            $this->openOrders[$orderId]['updated'] = $datetime;
            
            $symbolData = $this->getSymbolData($symbol);
            if ($this->openOrders[$orderId]['type']=='MARKET')
            {
                $price = $this->kline['open'];
                $this->openOrders[$orderId]['price'] = $price;
            }

            $this->openOrders[$orderId]['status'] = 'FILLED';
            $this->openOrders[$orderId]['comision'] = toDec(($this->openOrders[$orderId]['price']*$this->openOrders[$orderId]['origQty'])*($this->comisionExchange/100),5);

            $usd = toDec($this->openOrders[$orderId]['origQty']*$this->openOrders[$orderId]['price']);
            if ($this->openOrders[$orderId]['side'] == 'BUY')
            {
                $this->qtyUsd -= toDec($usd);
                $this->qtyToken += toDec($this->openOrders[$orderId]['origQty'],$symbolData['qty_decs_units']);
            } 
            elseif ($this->openOrders[$orderId]['side'] == 'SELL')
            {
                $this->qtyUsd += toDec($usd);
                $this->qtyToken -= toDec($this->openOrders[$orderId]['origQty'],$symbolData['qty_decs_units']);
            } 

            $this->pnlOrders[$orderId] = $this->openOrders[$orderId];
            unset($this->openOrders[$orderId]);

        }
    }



    /**
     * @param $interval => 1m, 1h, 15m
     */
    protected function __loadKlines($symbol,$from=null,$to=null)
    {
        $lote = 100000;
        $continue = true;
        $this->klines = array();
        $lastDatetime = null;
        while ($continue)
        {
            $qry = "SELECT datetime,open,high,low,close 
                    FROM klines_1m 
                    WHERE symbol = '".$symbol."' ";
            if ($from)
                $qry .= " AND datetime >= '".$from."' "; 
            if ($to)
                $qry .= " AND datetime <= '".$to."' "; 
            if ($lastDatetime)
                $qry .= " AND datetime > '".$lastDatetime."' "; 

            $qry .= " ORDER BY datetime ASC "; 
            $qry .= "LIMIT ".$lote;
            
            $stmt = $this->db->query($qry);
            $qtyRecs = 0;
            while ($rw = $stmt->fetch())
            {
                $datetime = substr($rw['datetime'],0,16);
                $this->klines[$datetime]['datetime'] = $datetime;
                $this->klines[$datetime]['close'] = floatval($rw['close']);
                $this->klines[$datetime]['open'] = floatval($rw['open']);
                $this->klines[$datetime]['high'] = floatval($rw['high']);
                $this->klines[$datetime]['low'] = floatval($rw['low']);
                $qtyRecs++;
            }
            $lastDatetime = $datetime;
            if ($qtyRecs < $lote)
                $continue = false;
            $indice += $lote;
        }
    }

    function getKlines()
    {
        return $this->klines;
    }

    public function getErrLog()
    {
        return $this->errLog->get();
    }

}