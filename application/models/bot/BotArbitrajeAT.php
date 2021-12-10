<?php
include_once LIB_PATH."ModelDB.php";
include_once MDL_PATH."binance/BinanceAPI.php";

class BotArbitrajeAT extends ModelDB
{
    protected $query = "SELECT * FROM bot_at";

    protected $pKey  = 'idbotat';

    //Costo por comisiones 
    public $comision = (0.075/100) ;
    
    //Porcentaje minimo por operacion, libre de comisiones
    public $porcentajeMinimo = 0.049;

    protected $ak;
    protected $as;

    protected $api;

    function __Construct($ak,$as)
    {
        $this->ak = $ak;
        $this->as = $as;

        $this->api = new BinanceAPI($this->ak,$this->as);

        parent::__Construct();

        //($db,$tabl,$id)
        $this->addTable(DB_NAME,'bot_at','idbotat');


    }

    function get($field)
    {
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

        if (!$this->data['OK'])
        {
            $err[] = 'Descripcion del error';
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
        if (!$this->data['idbotat'])
        {
            $isNew = true;
            $this->data['idbotat'] = $this->getNewId();
        }

        if (!$this->valid())
        {
            return false;
        }

        //Grabando datos en las tablas de la db
        if ($isNew) // insert
        {
            if (!$this->tableInsert(DB_NAME,'bot_at'))
                $err++;
        }
        else       // update
        {
            if (!$this->tableUpdate(DB_NAME,'bot_at'))
                $err++;
        }
        if ($err)
            return false;
        return true;
    }

    public function readTokens($tokenUSD,$tokenBase)
    {

        $prices = $this->api->bookPrices();

        $tokens = array();

        $symbol = $tokenBase.$tokenUSD;
        $key = $symbol;
        $tokens[$key][$symbol];
        
        foreach ($prices as $symbol => $rw)
        {
            if ((float)$rw['bid'] > 0)
            {

                if ($symbol == $tokenBase.$tokenUSD)
                {
                    $key = $symbol;   
                    $tokens[$key][$symbol]['bidPrice'] = (float)$rw['bid'];
                    $tokens[$key][$symbol]['bidQty'] = (float)$rw['bids'];
                    $tokens[$key][$symbol]['askPrice'] = (float)$rw['ask'];                
                    $tokens[$key][$symbol]['askQty'] = (float)$rw['asks'];
                }
                elseif (substr($symbol,-(strlen($tokenUSD)))==$tokenUSD)
                {
                    $key = str_replace($tokenUSD,'',$symbol);   
                    $tokens[$key][$symbol]['bidPrice'] = (float)$rw['bid'];
                    $tokens[$key][$symbol]['bidQty'] = (float)$rw['bids'];
                    $tokens[$key][$symbol]['askPrice'] = (float)$rw['ask'];
                    $tokens[$key][$symbol]['askQty'] = (float)$rw['asks'];
                }
                elseif (substr($symbol,-(strlen($tokenBase)))==$tokenBase)
                {
                    $key = str_replace($tokenBase,'',$symbol);   
                    $tokens[$key][$symbol]['bidPrice'] = (float)$rw['bid'];
                    $tokens[$key][$symbol]['bidQty'] = (float)$rw['bids'];
                    $tokens[$key][$symbol]['askPrice'] = (float)$rw['ask'];
                    $tokens[$key][$symbol]['askQty'] = (float)$rw['asks'];
                }
            }

        }
        //debug($tokens);

        $tokenBaseUSD['bidPrice'] = (float)$tokens[$tokenBase.$tokenUSD][$tokenBase.$tokenUSD]['bidPrice'];
        $tokenBaseUSD['askPrice'] = (float)$tokens[$tokenBase.$tokenUSD][$tokenBase.$tokenUSD]['askPrice'];

        foreach ($tokens as $token => $rw)
        {
            if ($token != $tokenBase.$tokenUSD) //Filtra los tokens
            {
                if (count($rw) == 2) //Filtrando los tokens que tienen los 2 pares (Base y USD)
                {
                    $importe = 1000;

                    //Prueba de operacion Token->Base->USD
                    $via = 'Token->Base->USD';

                    //Compra Token con USD
                    $qToken = $importe/$rw[$token.$tokenUSD]['askPrice'];
                    //Venta de Token a Base
                    $qBase = $qToken*$rw[$token.$tokenBase]['bidPrice'];
                    //Venta de Base a USD
                    $qUsd = $qBase*$tokenBaseUSD['bidPrice'];
                    //Descuento de comisiones
                    $qUsd = $qUsd-($importe * ($this->comision*3) );

                    $cambioPerc = toDec(($qUsd/$importe)-1,4);

                    //Si no resulta la primera via, se prueba la via invertida
                    //if ($cambioPerc<0)
                    //{
                    //
                    //    //Prueba de operacion Token->Base->USD
                    //    $via = 'Base->Token->USD';
                    //
                    //    //Compra Base con USD
                    //    $qBase = $importe/$tokenBaseUSD['askPrice'];
                    //    //Compra Token con Base
                    //    $qToken = $qBase/$rw[$token.$tokenBase]['askPrice'];
                    //    //Venta de Base a USD
                    //    $qUsd = $qToken*$rw[$token.$tokenUSD]['bidPrice'];
                    //    //Descuento de comisiones
                    //    //$qUsd = $qUsd-($importe * ($this->comision*3) );
                    //
                    //    $cambioPerc = toDec(($qUsd/$importe)-1);
                    //}

                    if ($cambioPerc > $this->porcentajeMinimo )
                    {
                        $tokens[$token]['cambioPerc'] = $cambioPerc;
                        $tokens[$token]['via'] = $via;

                        if (!in_array($tokenBase.$tokenUSD,$symbolsToCheck))
                            $symbolsToCheck[] = $tokenBase.$tokenUSD;
                        if (!in_array($token.$tokenUSD,$symbolsToCheck))
                            $symbolsToCheck[] = $token.$tokenUSD;
                        if (!in_array($token.$tokenBase,$symbolsToCheck))
                            $symbolsToCheck[] = $token.$tokenBase;

                     }
                    else
                    {
                        unset($tokens[$token]);
                    }
                    
                }
                else
                {
                    unset($tokens[$token]);
                }
            }
        }

        //Agregando info sobre cada token para ejecutar ordenes
        if (!empty($symbolsToCheck))
        {
            $symbolInfo = $this->symbolInfo($symbolsToCheck);
            foreach ($tokens as $token => $rw)
            {
                $tokens[$token][$tokenBase.$tokenUSD] = $tokens[$tokenBase.$tokenUSD][$tokenBase.$tokenUSD];
                        
                $tokens[$token]['info'][$token.$tokenBase] = $symbolInfo[$token.$tokenBase];
                $tokens[$token]['info'][$token.$tokenUSD] = $symbolInfo[$token.$tokenUSD];
                $tokens[$token]['info'][$tokenBase.$tokenUSD] = $symbolInfo[$tokenBase.$tokenUSD];
            }
        }
        unset($tokens[$tokenBase.$tokenUSD]);
        return $tokens;
    }

    public function symbolInfo($symbols)
    {
        $exchangeInfo = $this->api->exchangeInfo($symbols);
        $data = array();
        if (!empty($exchangeInfo))
        {
            foreach ($exchangeInfo['symbols'] as $symbol => $rw) 
            {
                $data[$symbol]['baseAsset']  = $rw['baseAsset'];
                $data[$symbol]['quoteAsset'] = $rw['quoteAsset'];
                
                $data[$symbol]['qtyDecsBase'] = $this->api->numberOfDecimals($rw['filters'][2]['minQty']);
                $data[$symbol]['qtyDecsQuote'] = $this->api->numberOfDecimals($rw['filters'][0]['minPrice']);

                if ($rw['isSpotTradingAllowed'] && $rw['status']=='TRADING' && in_array('SPOT', $rw['permissions']))
                    $data[$symbol]['canTrade'] = true;
                else
                    $data[$symbol]['canTrade'] = false;
            }
        }
        return $data;
    }

    function buy($symbol,$qty,$price)
    {
        try {
            print_r("\n".'BUY '.$symbol.' q:'.$qty);
            $order = $this->api->buy($symbol,$qty,$price);
            $orderId = $order['orderId'];
            $orderStatus = $this->api->orderStatus($symbol,$orderId);
            $check = 0;
            while ($orderStatus['status'] != 'FILLED')
            {
                sleep(1);
                $check++;
                $orderStatus = $this->api->orderStatus($symbol,$orderId);
                echo " -> ";
                if ($check>10)
                {
                    $this->errLog->add('COMPRA CANCELADA');
                    echo ' COMPRA CANCELADA ';
                    $this->api->cancel($symbol, $orderId);
                    return false;
                }
            }
            echo "COMPRA OK";
            return $orderStatus;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->errLog->add($e->getMessage());
            return false;
        }
    }

    function sell($symbol,$qty,$price)
    {
        try {
            print_r("\n".'SELL '.$symbol.' q:'.$qty);
            $order = $this->api->marketSell($symbol,$qty,$price);

            $orderId = $order['orderId'];
            $orderStatus = $this->api->orderStatus($symbol,$orderId);
            $check = 0;
            while ($orderStatus['status'] != 'FILLED')
            {
                sleep(1);
                $check++;
                $orderStatus = $this->api->orderStatus($symbol,$orderId);
                echo " -> ";
                if ($check>10)
                {
                    $this->errLog->add('VENTA CANCELADA');
                    echo ' VENTA CANCELADA ';
                    $this->api->cancel($symbol, $orderId);
                    return false;
                }
            }
            echo "VENTA OK";
            return $order;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $this->errLog->add($e->getMessage());
            return false;
        }
    }

    function orderStatus($symbol,$orderId)
    {
        return $this->api->orderTradeInfo($symbol,$orderId);
    }
}