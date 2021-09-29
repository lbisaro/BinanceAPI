<?php

/**
 * Define la pendiente de tendencia de una serie de datos
 * por el metodode minimos cuadrados
 *
 * Metodo de Minimos cuadrados: https://www.youtube.com/watch?v=gUdU6BgnJ2c
 *
 * y = m.x + b 
 * 
 * @param: $y = array(1,2,3,5,4,7,8,9,10);
 */
function tendenciaLineal($y=array())
{
    if (!is_array($y))
        return 0;
    $i = 0;
    $m = 0;
    $sumx  = 0;
    $sumy  = 0;
    $sumxy = 0;
    $sumx2 = 0;
    $n = count ($y);
    foreach ($y as $k => $v)
    {
        $x[$k]=$i;
        $xy[$k] = $x[$k]*$y[$k];
        $x2[$k] = $x[$k]*$x[$k];

        //Sumatorias
        $sumx  += $x[$k];
        $sumy  += $y[$k];
        $sumxy += $xy[$k];
        $sumx2 += $x2[$k];
        
        $i++;
    }

    //Calculo de la pendiente m
    $m = ( $sumxy - ( ( $sumx * $sumy ) / $n ) ) / ( $sumx2 - ( ( $sumx * $sumx ) / $n ) );
    
    return $m; 
}