<?php
class Binance
{


    /**
    Binance API Docs
    https://binance-docs.github.io/apidocs/spot/en/#symbol-price-ticker

    URLs
    https://api.binance.com/api/v3/ticker/price
    https://api.binance.com/api/v3/ticker/price?symbol=BTCUSDT
    */
    protected function httpRequest(string $addUrl, array $params = [])
    {
        $url = 'https://api.binance.com/api/'.$addUrl;
        $qs = http_build_query($params); // query string encode the params
        $request = "{$url}?{$qs}"; // create the request URL

        // Crear un flujo
        $opciones = array(
          'http'=>array(
            'method'=>"GET",
            'header'=>"Accepts: application/json\r\n" /*.
                      "X-CMC_PRO_API_KEY: 1fcac892-4207-4ee1-818a-afb2a28e8b2f\r\n"*/
          )
        );

        $contexto = stream_context_create($opciones);

        // Abre el fichero usando las cabeceras HTTP establecidas arriba
        $jsonResponse = file_get_contents($request, false, $contexto);
        $arrayResponse = json_decode($jsonResponse,true);
        return $arrayResponse;
    }

    function price($symbol=null)
    {
        $params=array();
        if ($symbol)
            $params['symbol'] = $symbol;
        $prices = $this->httpRequest('v3/ticker/price',$params);
        $ret = array();
        //Filtrando los que no son contra USDT y los Apalancados (UP y DOWN)
        if (empty($symbol))
        {
            foreach ($prices as $rw)
            {
                if (substr($rw['symbol'],-4)=='USDT' &&
                    substr($rw['symbol'],-8)!='DOWNUSDT' &&
                    substr($rw['symbol'],-6)!='UPUSDT' 
                    )
                    $ret[$rw['symbol']] = $rw['price'];
            }
        }
        else
        {
            $ret[$prices['symbol']] = $prices['price'];
        }

        return $ret;
    }
}