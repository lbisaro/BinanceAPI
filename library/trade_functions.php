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
function tendenciaLineal($real=array())
{
    if (!is_array($real) || count($real)<2)
        return 0;

    $i = 0;
    $m = 0;
    $sumx  = 0;
    $sumy  = 0;
    $sumxy = 0;
    $sumx2 = 0;
    $n = count($real);

    //Transforma los elementos del array X en porcentaje
    for ($i=0;$i<count($real);$i++)
        $x[$i] = ($i/(count($real)-1) ) * 100;

    //Transforma los elementos del array Y en porcentaje
    $tot=0;
    foreach ($real as $v)
        $tot += $v;
    $ref = $tot/$n;
    
    $end = end($real);
    for ($i=0;$i<count($real);$i++)
    {
        if ($ref!=0)
            $y[$i] = ( ($real[$i]-$ref) / $ref ) * 100;
        else
            $y[$i] = 0;
    }

    foreach ($y as $k => $v)
    {
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
    
    //formula ecuacion lineal y = m.x + b

    /** Calculo del angulo de la pendiente */
    //Calculo de lados
    $ladoX = count($y);
    $ladoY = $ladoX*$m - $y[0];
    //Calculo del angulo
    $angulo = atan($ladoY/$ladoX);
    $angulo = rad2deg($angulo);

    return toDec($angulo,2); 
}


/** 
 * Compara la variacion porcentual entre el primer y ultimo elemento de un array
 */
function variacionPorcentual($real=array())
{
    foreach ($real as $k => $v)
    {
        if (!isset($iStart))
            $iStart = $k;
        $iEnd = $k;
    }
    if ($real[$iStart]!=0)
        return toDec((($real[$iEnd]/$real[$iStart])-1)*100);
    else
        return toDec(0);
}
