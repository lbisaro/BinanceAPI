<?php

/**
 * @package myLibrary
 *
 * @version 1.0
 * @copyright 2008
 * @author Ricardo Riobóo
 * @link www.tanet.com.ar
 */

$DIA_SEMANA_CORTO = array(1=>'Lun',
                          2=>'Mar',
                          3=>'Mie',
                          4=>'Jue',
                          5=>'Vie',
                          6=>'Sab',
                          7=>'Dom');

$DIA_SEMANA_LARGO = array(1=>'Lunes',
                          2=>'Martes',
                          3=>'Miercoles',
                          4=>'Jueves',
                          5=>'Viernes',
                          6=>'Sabado',
                          7=>'Domingo');

$MES_CORTO = array('Ene','Feb','Mar','Abr','May','Jun',
                   'Jul','Ago','Sep','Oct','Nov','Dic');

$MES_LARGO = array('Enero','Febrero','Marzo','Abril','Mayo','Junio',
                   'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');


/* ----------------------------------------------------------- */

    function WinClose()
    {
        echo'<script language="JavaScript">';
        echo'   window.close();';
        echo'</script>';
        exit;
    }

    function WinRedir($url)
    {
        echo'<script>';
        echo'   window.location = "'.$url.'";';
        echo'</script>';
        exit;
    }

    /**
    * Alert
    * hace un alert de java script  de la variable $txt
    */

    function Alert($txt)
    {
    echo '<script language="JavaScript">';
    echo 'alert("'.$txt.'")';
    echo '</script>';

    }

    /**
    * Redir
    * hace un Window.location de java script  de la variable $txt
    */
    function Redir($txt)
    {
    echo '<script language="JavaScript">';
    echo 'window.location ="'.$txt.'"';
    echo '</script>';
    }

    /**
    * Reload
    * hace un Window.location.reload() de java script  de la variable $txt
    */
    function Reload()
    {
    echo '<script language="JavaScript">';
    echo 'window.location.reload()';
    echo '</script>';
    }

    /**
    * pr
    * hace un print_r de un $array
    */
    function pr($array)
    {
        debug($array);
    }

    /**
    * cmp
    * La funcion compara $a y $b
    *
    * Esta funcion se creo para usar la funcion uksort() nativa de PHP.
    */
    function cmp($a, $b)
    {
        $a = preg_replace('@^(a|an|the) @', '', $a);
        $b = preg_replace('@^(a|an|the) @', '', $b);
        return strcasecmp($a, $b);
    }

    /**
     * arrayToTable($array)
     *
     * @return html
     */
    function arrayToTable($array)
    {
        if (is_array($array))
        {
            $echo = "
            <div style=\"border:1px solid #343537;\" >
            <table class=\"arrayToTable\" border=\"1\" style=\"border-collapse: collapse;\">";
            foreach ($array as $k => $v)
            {
                if (is_array($v))
                    $str = arrayToTable($v);
                else
                    $str = $v;
                $echo .= "
                <tr><th width=\"25%\" valign=\"top\" >$k</th><td valign=\"top\">";
                if (is_object($v))
                    $echo .= 'OBJECT';
                elseif ($v)
                    $echo .= $str;
                else
                    $echo .= "&nbsp;";
                $echo .= "</td></tr>";
            }
            $echo .= "
            </table>
            </div>";
        }
        else
            $echo = $array;

        return $echo;

    }

    /**
     * Similar a arrayToTable,
     * pero imprime una tabla tipo dataSet
     * como un datagrid.
     */
    function arrayToTableDg($array,$class='table')
    {
        if (is_array($array))
        {
            foreach ($array as $row)
            {
                foreach ($row as $k => $val)
                {
                    $keys[$k] = $k;
                }
            }

            if ($keys)
            {
                $ths = "
                <tr>
                <th style=\"width:30px;text-align:center;\">K</th>";
                foreach ($keys as $k=>$v)
                    $ths .= "<th>".$k."</th>";
                $ths .= "
                </tr>";

            }

            $echo = "
            <div style=\"border:1px solid #343537;overflow: auto;\" >
            <table class=\"arrayToTable DG\ ".$class." style=\"border-collapse: collapse;\">";
            $echo .= $ths;
            foreach ($array as $k => $row)
            {
                $echo .= "
                <tr>
                <th style=\"text-align:center;\">".$k."</th>";
                foreach ($keys as $key)
                {
                    $v = $row[$key];
                    $echo .= "<td >".($v?$v:"&nbsp;")."</td>";
                }
                $echo .= "
                </tr>";
            }
            $echo .= "
            </table>
            </div>";
        }
        else
        {
            $echo = $array;
        }
        return $echo;
    }



    /**
    *   Formatea una fecha
    *   de BBDD a STR
    *   EJ:
    *   2008-03-10 15:07:15
    *   muestra : 10/03/2008   .-
    *   y con hora muestra 10/03/2008 15:07   .-
    */
    function dateToStr($date, $time = false)
    {
        if (date('U',strtotime($date))>0)
        {
            if(!$time)
                return date('d/m/Y',strtotime ($date));
            else
                return date('d/m/Y H:i',strtotime ($date));
        }
        else
        {
            return '';
        }
    }

    /**
    *   Formatea una fecha
    *   de STR a BBDD
    *   EJ:
    *   dateToStr(10/03/2008);
    *   muestra 2008-10-03   .-
    *   y
    *   dateToStr(10/03/2008 12:25, true);
    *   muestra 2008-10-03 12:25  .-
    */
    function strToDate($str, $time = false)
    {
        if (!$str)
        {
            return null;
        }
        else
        {
            $str = str_replace(array("/","\\","."),'-',$str);

            if (strtotime ($str) < 1)
            {
                return null;
            }
            else
            {
               $str = strtotime ($str);
            }
        }

        if(!$time)
        {
            return date('Y-m-d',$str);
        }
        else
        {
            return date('Y-m-d H:i',$str);
        }
    }

    /**
    *   Formatea una fecha
    *   de STR a BBDD  - Timestamp
    *   EJ:
    *   dateToStr(10/03/2008);
    *   muestra 20081003000000   .-
    *   y
    *   dateToStr(10/03/2008 12:25, true);
    *   muestra 20081003122500  .-
    */
    function strToTimestamp($str, $time = true)
    {
        $str = trim($str);
        if (empty($str))
           return null;

        $str = str_replace(array("/","\\","."),'-',$str);
        if (strtotime ($str)<1)
            return null;
        else
           $str = strtotime ($str);

        if(!$time)
            return date('Ymd',$str);
        else
            return date('YmdHis',$str);
    }

    /**
    * Devuelve una cadena con la cantidad de:
    *  Semanas, Dias, Horas, Minutos y Segundos
    * representada por el valor $time pasado como parametro.
    *
    * @param int $time: Cantidad de segundos. ref.: date('U').
    * @return String xx Semanas, xx Dias, xx Horas, xx Minutos, xx Segundos
    */
    function timeToText($time)
    {
        $weeks  = (int)($time/604800);
        $days   = (int)(($time-$weeks*604800)/86400);
        $hours  = (int)(($time-$weeks*604800-$days*86400)/3600);
        $mins   = (int)(($time-$weeks*604800-$days*86400-$hours*3600)/60);
        $secs   = (int)(($time-$weeks*604800-$days*86400-$hours*3600-$mins*60));

        if ($weeks)
            $txt = $weeks." semanas";
        if ($days)
            $txt .= ($txt?', ':'').$days." dias";
        if ($hours)
            $txt .= ($txt?', ':'').$hours." horas";
        if ($mins)
            $txt .= ($txt?', ':'').$mins." minutos";
        if ($secs)
            $txt .= ($txt?', ':'').$secs." segundos";

        return $txt;
    }

    /**
    * Devuelve true si la fecha y/o hora enviada como parametro es un formato
    * valido para almacenar en base de datos.
    *
    * La funcion es valida para campos MySql del tipo: date, datetime y timestamp.
    *
    */
    function checkDbDateTime($fechaHora)
    {
        $dd = intval(substr($fechaHora,8,2));
        $mm = intval(substr($fechaHora,5,2));
        $yy = intval(substr($fechaHora,0,4));
        $h  = intval(substr($fechaHora,11,2));
        $m  = intval(substr($fechaHora,14,2));
        $s  = intval(substr($fechaHora,17,2));

        if (between($h,0,23) && between($m,0,59) && between($s,0,59) && checkdate($mm,$dd,$yy) )
            return true;
        return false;
    }

    /**
    * Devuelve true si la fecha y/o hora enviada como parametro es un formato
    * valido para almacenar en base de datos.
    *
    * La funcion es valida para campos MySql del tipo: date, datetime y timestamp.
    *
        switch ($mesNr)
        {
            case 1 : $mesLet2 = "Ene";      break;
            case 2 : $mesLet2 = "Feb";      break;
            case 3 : $mesLet2 = "Mar";      break;
            case 4 : $mesLet2 = "Abr";      break;
            case 5 : $mesLet2 = "May";      break;
            case 6 : $mesLet2 = "Jun";      break;
            case 7 : $mesLet2 = "Jul";      break;
            case 8 : $mesLet2 = "Ago";      break;
            case 9 : $mesLet2 = "Sep";      break;
            case 10 : $mesLet2 = "Oct";     break;
            case 11 : $mesLet2 = "Nov";     break;
            case 12 : $mesLet2 = "Dic";     break;
        }


    */
    function checkDbTime($hora)
    {
        $h  = intval(substr($fechaHora,0,2));
        $m  = intval(substr($fechaHora,3,2));
        $s  = intval(substr($fechaHora,6,2));

        if (between($h,0,23) && between($m,0,59) && between($s,0,59) )
            return true;
        return false;
    }

    /**
    *   Formatea una fecha
    *   Hay que pasarle la fecha con el formato AAAA-MM-DD
    *   EJ:
    *   2008-03-10 15:07:15
    *   muestra 10/Mar/2008   .-
    *   20080310150715
    *   muestra 10/Mar/2008   .-
    *   muestra Lun 10/Mar/2008   .-
    */
    function dateFormat($fecha,$formato = 0,$time = false)
    {
        $fecha  = strlen($fecha) ? $fecha : "0000-00-00";

        if ($time)
            $hora   = date('H:i',strtotime($fecha)).' Hs.';
        else
            $hora = null;

        $fecha  = explode("-",date('Y-m-d',strtotime($fecha)));


        $anio   = isset($fecha[0]) && strlen($fecha[0]) ? $fecha[0] : 0;
        $mesNr  = isset($fecha[1]) && strlen($fecha[1]) ? $fecha[1] : 0;
        $diaNr  = isset($fecha[2]) && strlen($fecha[2]) ? $fecha[2] : 0;

        /* Mes en letras */
        switch ($mesNr)
        {
            case 1 : $mesLet = "Enero";     break;
            case 2 : $mesLet = "Febrero";   break;
            case 3 : $mesLet = "Marzo";     break;
            case 4 : $mesLet = "Abril";     break;
            case 5 : $mesLet = "Mayo";      break;
            case 6 : $mesLet = "Junio";     break;
            case 7 : $mesLet = "Julio";     break;
            case 8 : $mesLet = "Agosto";    break;
            case 9 : $mesLet = "Septiembre";    break;
            case 10 : $mesLet = "Octubre";      break;
            case 11 : $mesLet = "Noviembre";    break;
            case 12 : $mesLet = "Diciembre";    break;
        }


        /* Mes en letras 2*/
        switch ($mesNr)
        {
            case 1 : $mesLet2 = "Ene";      break;
            case 2 : $mesLet2 = "Feb";      break;
            case 3 : $mesLet2 = "Mar";      break;
            case 4 : $mesLet2 = "Abr";      break;
            case 5 : $mesLet2 = "May";      break;
            case 6 : $mesLet2 = "Jun";      break;
            case 7 : $mesLet2 = "Jul";      break;
            case 8 : $mesLet2 = "Ago";      break;
            case 9 : $mesLet2 = "Sep";      break;
            case 10 : $mesLet2 = "Oct";     break;
            case 11 : $mesLet2 = "Nov";     break;
            case 12 : $mesLet2 = "Dic";     break;
        }

        /* Dia de la semana en letras */
        $diaSem = date("w",strtotime($fecha[1]."/".$fecha[2]."/".$fecha[0]));

        switch ($diaSem)
        {
            case 0 : $diaLet = "Domingo";   $diaLetCorto = "Dom";    break;
            case 1 : $diaLet = "Lunes";     $diaLetCorto = "Lun";    break;
            case 2 : $diaLet = "Martes";    $diaLetCorto = "Mar";    break;
            case 3 : $diaLet = "Miercoles"; $diaLetCorto = "Mie";    break;
            case 4 : $diaLet = "Jueves";    $diaLetCorto = "Jue";    break;
            case 5 : $diaLet = "Viernes";   $diaLetCorto = "Vie";    break;
            case 6 : $diaLet = "Sabado";    $diaLetCorto = "Sab";    break;
        }

        if(isset($fecha) && $fecha != "0000-00-00")
        {
            switch ($formato)
            {
                case 1 : 
                    $fmt = "$mesLet, $anio";
                    break;
                case 2 : 
                    $fmt = "<b>$diaLet</b>&nbsp;|&nbsp;$diaNr de $mesLet del $anio";
                    break;
                case 3 : 
                    $fmt = "$diaLet $diaNr de $mesLet de $anio";
                    break;
                case 4 : 
                    $fmt = "$diaLet $diaNr de $mesLet";
                    break;
                case 5 : 
                    $fmt = "$diaNr/$mesNr/$anio";
                    break;
                case 6 : 
                    $fmt = substr($diaNr,0,2).".".substr($mesNr,0,2).".".substr($anio,-2);
                    break;
                case 7 : 
                    $fmt = "$mesLet $diaNr, $anio";
                    break;
                case 8 : 
                    $fmt = "$mesLet $diaNr de $anio";
                    break;
                case 9 : 
                    $fmt = "$diaNr de $mesLet de $anio";
                    break;
                case 10 : 
                    $fmt = "$diaLet $diaNr/$mesLet2/$anio";
                    break;
                case 11 : 
                    $fmt = "$diaLet $diaNr/$mesLet2";
                    break;
                case 12 : 
                    $fmt = ( $anio ? "$diaNr.$mesLet2".($anio==date('Y')?"":".$anio") : '' );
                    break;
                case 13 : 
                    $fmt = "$diaNr/$mesNr/$anio $diaLet";
                    break;
                case 14 : 
                    $fmt = "$diaLetCorto $diaNr/$mesLet2/$anio";
                    break;
                default:
                    $fmt = "$diaNr/$mesLet2/$anio";
                    break;
            }
            switch ($mesNr)
            {
                case 1 : $mesLet2 = "Ene";      break;
                case 2 : $mesLet2 = "Feb";      break;
                case 3 : $mesLet2 = "Mar";      break;
                case 4 : $mesLet2 = "Abr";      break;
                case 5 : $mesLet2 = "May";      break;
                case 6 : $mesLet2 = "Jun";      break;
                case 7 : $mesLet2 = "Jul";      break;
                case 8 : $mesLet2 = "Ago";      break;
                case 9 : $mesLet2 = "Sep";      break;
                case 10 : $mesLet2 = "Oct";     break;
                case 11 : $mesLet2 = "Nov";     break;
                case 12 : $mesLet2 = "Dic";     break;
            }
        }
        else
        {
          $fmt = "";
        }

        if ($fmt && $time)
            $fmt .= ' '.$hora;
        return $fmt;
    }

    /**
     * Recibe una cadena de caracteres y la devuelve encriptada.
     */
    function encryptPassword($str)
    {
       return md5(trim($str));
    }

    /**
     * Valida la politica de password
     */
    function validarPoliticaPassword($pass)
    {
        $pass = trim($pass);
        $err='';
        if ((PASS_MIN > 0 && PASS_MAX >0) && (strlen($pass)<PASS_MIN || strlen($pass)>PASS_MAX) )
            $err .= ($err?", ":"")."Debe tener entre ".PASS_MIN." y ".PASS_MAX." caracteres";

        $n=$num=$upper=$lower=0;
        while (ord(substr($pass,$n,1)) && $n < strlen($pass))
        {
            $chr = ord(substr($pass,$n,1));

            if ($chr>=48 && $chr<=57)
                $num++;
            elseif ($chr>=65 && $chr<=90)
                $upper++;
            elseif ($chr>=97 && $chr<=122)
                $lower++;
            else
                $no_alfa++;
            $n++;
        }
        if (PASS_NUM && $num<1)
            $err .= ($err?", ":"")."Debe tener al menos un numero";
        if (PASS_LOWER && $lower<1)
            $err .= ($err?", ":"")."Debe tener al menos una letra minuscula";
        if (PASS_UPPER && $upper<1)
            $err .= ($err?", ":"")."Debe tener al menos una letra mayuscula";
        if (PASS_ALFA && $no_alfa>0)
            $err .= ($err?", ":"")."No debe tener espacios ni caracteres especiales";
        if ($err)
            $err = $err.".";
        return $err;
    }

    /**
     * Devuelve un mensaje con la politica de password establecida
     */
    function getPoliticaPassword()
    {
        $pass_pol_msg = "";

        if (PASS_MIN>0 && PASS_MAX>0)
            $pass_pol_msg .= "<li>Debe tener entre ".PASS_MIN." y ".PASS_MAX." caracteres.</li>";
        if (PASS_UPPER)
            $pass_pol_msg .= "<li>Debe tener al menos una letra mayuscula</li>";
        if (PASS_LOWER)
            $pass_pol_msg .= "<li>Debe tener al menos una letra minuscula</li>";
        if (PASS_NUM)
            $pass_pol_msg .= "<li>Debe tener al menos un numero</li>";
        if (PASS_ALFA)
            $pass_pol_msg .= "<li>No debe tener espacios ni caracteres especiales.</li>";
        if (PASS_EXPIRE>0)
            $pass_pol_msg .= "<li>Debera renovarse cada ".PASS_EXPIRE." dias.</li>";
        if (PASS_REPEAT>0)
            $pass_pol_msg .= "<li>No se deben repetir las ultimas ".PASS_REPEAT." contraseñas utilizadas anteriormente.</li>";

        if ($pass_pol_msg)
            $pass_pol_msg = "<b>Caracteristicas de las contraseñas:</b> <ul>".$pass_pol_msg."</ul>";
        else
            $pass_pol_msg = "No se especificaron caracteristicas de las contraseñas";

        return ($pass_pol_msg);
    }

    /**
     * Devuelve el idperfil del usuario para un permiso en particular
     *
     * El valor ingresado en el parametro $min_idperfil,
     * corresponde al minimo requerido.<br>
     * Cuando el idperfil obtenido sea menor a $min_idperfil,
     * el sistema mostrara un error e interrumpira la
     * ejecucion (exit;).<br>
     * Para consultar el idperfil relacionado con un permiso
     * en particular evitando que sea interrumpida la ejecucion
     * el valor pasado por $min_idperfil deberá ser = 0,
     * y de esta forma el idperfil nunca será menor.
     */
    function check_permiso($idpermiso, $min_idperfil=PERFIL_ADM)
    {
        if ($this->idperfil==PERFIL_ADM)
            return PERFIL_ADM;

        if (!is_array($this->permiso))
            return NULL;

        $idperfil=0;
        foreach($this->permiso as $it)
        {
            if ($it['idpermiso']==$idpermiso)
                $idperfil = $it['idperfil'];
        }
        if ($idperfil < $min_idperfil) {
            print "ERROR CRITICO: <hr>".
                  "Contactese con el administrador del sistema.<hr>".
                  "No tiene permiso de acceso - Id.user: ".$this->idusuario.
                  " - Id.Permiso: ".$idpermiso.
                  " - Id.Perfil: ".$this->idperfil.
                  ($idperfil?" - Id.Perfil.Solicitado: ".$min_idperfil:"").
                  "<hr>";
            exit;
        }
        return $idperfil;
    }

    /**
     * Compara si data >= d1 y data <= d2, y devuelve un dato logico.
     *
     * @param mixed $data -> Dato a comparar
     * @param mixed $d1   -> Rango inferior, incluido.
     * @param mixed $d2   -> Rango superior, incluido.
     * @return bool
     */
    function between($data,$d1,$d2)
    {
        return ($data>=$d1 && $data <=$d2);
    }


    /**
     * es_bisiesto
     *
     * Devuelve un 0 si un año no es Bisiesto, y 1 en caso contrario
     *
     * <b>NOTA: Se puede aprovechar el valor devuelto por esta funcion, para
     * sumarlo a 28 y asi definir la cantidad de dias del mes de Febrero.</b>
     *
     */
    function es_bisiesto($aaaa)
    {
        return (!($aaaa%400) || ($aaaa%100 && !($aaaa%4) ) ? 1:0);
    }

    /**
     * dias_por_mes
     *
     * Devuelve la cantidad de dias que tiene un mes para un año en particular
     *
     */
    function dias_por_mes($mm,$aaaa)
    {
        if ($mm < 1 || $mm >12)
            return 0;
        $dias_por_mes = array(31,28+es_bisiesto($aaaa),31,30,31,30,31,31,30,31,30,31);
        return $dias_por_mes[$mm-1];
    }


    function cuitToDb($cuit)
    {
        $cuit = str_replace('-','',$cuit);


        if (validCuit($cuit))
            return $cuit;

        return null;
    }

    function cuitToStr($cuit)
    {
        if (!strstr($cuit,'-'))
            $cuit = substr($cuit,0,2).'-'.substr($cuit,2,8).'-'.substr($cuit,10,1);

        if (validCuit($cuit))
            return $cuit;

        return null;
    }

    /**  Valida número de CUIT o CUIL
     *   USO: validCuit(<99-99999999-9>)
     *   RETORNA: Lógico
     *
     */
    function validCuit($cuit)
    {
        if (strlen($cuit) == 13)
            $cuit = str_replace('-','',$cuit);

        if (is_numeric($cuit) && strlen($cuit) == 11)
        {
            $lnSuma = (substr($cuit,9,1)*2) +
                      (substr($cuit,8,1)*3) +
                      (substr($cuit,7,1)*4) +
                      (substr($cuit,6,1)*5) +
                      (substr($cuit,5,1)*6) +
                      (substr($cuit,4,1)*7) +
                      (substr($cuit,3,1)*2) +
                      (substr($cuit,2,1)*3) +
                      (substr($cuit,1,1)*4) +
                      (substr($cuit,0,1)*5);

            if (($lnSuma%11) == 0)
                $dv = 0 ;
            else
                $dv = (11 - ($lnSuma%11));

            if ( substr($cuit,10,1) == $dv )
                return true;
        }
        return false;
    }

    function validDNI($dni)
    {
        if ($dni > 1000000 && $dni < 99999999)
            return true;
        return false;
    }

    function textoVertical($txt)
    {
        for ($i=0;$i<strlen($txt);$i++)
        {
            $newTxt .= substr($txt,$i,1).'<br/>';
        }
        return $newTxt;
    }

    function subStrPad($str,$len,$padStr=' ')
    {
        $strNew = str_pad($str,$len,$padStr);
        $strNew = substr($strNew,0,$len);
        return $strNew;
    }

    function strSafe($txt)
    {
        //Este metodo tambien se encuentra en Functions.js

        //no se permite comillas
        $pat="/[\"']+/";

        $txt = preg_replace($pat,'`',$txt);

        //Caracteres permitidos solo : de a-z en min y en mayus, numeros de 0 al 9, espacios y caracteres designados
        //no se permite ningun otro tipo de caracter
        $pat="/[^a-zA-Z0-9 `\#\$\%\(\)\*\+\,\-\.\/\:;=<>@_]+ á|é|í|ó|ú|Á|É|Í|Ó|Ú|ñ|Ñ /";

        $txt = preg_replace($pat,'',$txt);

        return $txt;
    }

    function getRndAlpha($lenght)
    {
        $str = 'abcdefghijklmnopqrstuvwxyz0123456789';
        for ($i = 1 ; $i <= $lenght ; $i++)
        {
            $rndAlpha .= substr($str, rand(0,35),1);
        }
        return $rndAlpha;
    }


    function validarEmail($email)
    {
        if(preg_match("/^[\.a-zA-Z0-9_-]{2,}@[a-zA-Z0-9_-]{2,}\.[a-zA-Z]{2,4}(\.[a-zA-Z]{2,4})?$/", $email))
            return true;
        else
            return false;
    }

    function criticalExit($message)
    {
        $software = null;
        if (defined('SOFTWARE_NAME'))
            $software = SOFTWARE_NAME;
        if (defined('SOFTWARE_VER'))
            $software .= ' v'.SOFTWARE_VER;

        $error = '
        <div style="font-size: 16px; font-family: arial, tahoma, verdana;border:1px solid #dd5555;color:#dd5555;padding:10px;margin:10px;border-radius: 5px;display: block;">
            '.($software?'<h2 style="border-bottom:1px solid #dd5555;">Sistema '.$software.'</h2>':'').'
            <h4>ERROR CRITICO</h4>
            <li style="color:#dd5555;font-size:13px; border: 1px solid #d55; padding: 10px;border-radius: 5px;">
                '.nl2br($message).'
            </li>
            <p style="color:#999999;font-size:12px;">Contacte al administrador del sistema (<a href="mailto:sistemas@tanet.com.ar">sistemas@tanet.com.ar</a>) informando el presente error.</p>
            <code style="color:#999999;font-size:12px;display: block;">

                <b>REQUEST</b><ul>';

        foreach ($_REQUEST as $k=>$v)
            if ($k != 'PHPSESSID')
                $error .='<li style="list-style-type:none;"><div style="display: inline-block;width:100px;">'.$k.
                         ':</div> <div style="display: inline-block;""><b>'.$v.'</b></div></li>';

        $error .='
                </ul>
            </code>';

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        if (!empty($bt))
        {
            $bt = array_reverse($bt);
            $q = count($bt);
            $error .= '<br/><code style="color:#999999;font-size:12px;display: block;">';
            $error .= '<b>BACKTRACE</b><ul>';
            foreach ($bt as $k => $rw)
            {
                $error .= '<li style="list-style-type:none;padding-left: '.($k*15).'px;">';
                $error .= $rw['class'].$rw['type'].$rw['function'].'() - L:'.$rw['line'];
                $error .= '</li>';
            }
            $error .= '</ul></code>';
            addTimeDebug($str);
        }

        $error .='
        </div>';

        die($error);
    }

    function toDec($val,$dec=2,$dSep=".",$mSep="")
    {
        return number_format(round(floatVal($val),$dec),$dec,$dSep,$mSep);
    }

    function toDecDown($val,$dec=2,$dSep=".",$mSep="")
    {
        return number_format(round(floatVal($val),$dec,PHP_ROUND_HALF_DOWN),$dec,$dSep,$mSep);
    }
    function toDecUp($val,$dec=2,$dSep=".",$mSep="")
    {
        return number_format(round(floatVal($val),$dec,PHP_ROUND_HALF_UP),$dec,$dSep,$mSep);
    }

    function num2letras($num, $fem = false, $dec = true, $minQtyDec = 2)
    {
        $matuni[2]  = "dos";
        $matuni[3]  = "tres";
        $matuni[4]  = "cuatro";
        $matuni[5]  = "cinco";
        $matuni[6]  = "seis";
        $matuni[7]  = "siete";
        $matuni[8]  = "ocho";
        $matuni[9]  = "nueve";
        $matuni[10] = "diez";
        $matuni[11] = "once";
        $matuni[12] = "doce";
        $matuni[13] = "trece";
        $matuni[14] = "catorce";
        $matuni[15] = "quince";
        $matuni[16] = "dieciseis";
        $matuni[17] = "diecisiete";
        $matuni[18] = "dieciocho";
        $matuni[19] = "diecinueve";
        $matuni[20] = "veinte";
        $matunisub[2] = "dos";
        $matunisub[3] = "tres";
        $matunisub[4] = "cuatro";
        $matunisub[5] = "quin";
        $matunisub[6] = "seis";
        $matunisub[7] = "sete";
        $matunisub[8] = "ocho";
        $matunisub[9] = "nove";

        $matdec[2] = "veint";
        $matdec[3] = "treinta";
        $matdec[4] = "cuarenta";
        $matdec[5] = "cincuenta";
        $matdec[6] = "sesenta";
        $matdec[7] = "setenta";
        $matdec[8] = "ochenta";
        $matdec[9] = "noventa";
        $matsub[3]  = 'mill';
        $matsub[5]  = 'bill';
        $matsub[7]  = 'mill';
        $matsub[9]  = 'trill';
        $matsub[11] = 'mill';
        $matsub[13] = 'bill';
        $matsub[15] = 'mill';
        $matmil[4]  = 'millones';
        $matmil[6]  = 'billones';
        $matmil[7]  = 'de billones';
        $matmil[8]  = 'millones de billones';
        $matmil[10] = 'trillones';
        $matmil[11] = 'de trillones';
        $matmil[12] = 'millones de trillones';
        $matmil[13] = 'de trillones';
        $matmil[14] = 'billones de trillones';
        $matmil[15] = 'de billones de trillones';
        $matmil[16] = 'millones de billones de trillones';

        $num = trim((string)@$num);
        if ($num[0] == '-')
        {
            $neg = 'menos ';
            $num = substr($num, 1);
        }
        else
        {
            $neg = '';
        }

        while ($num[0] == '0')
            $num = substr($num, 1);

        if ($num[0] < '1' or $num[0] > 9)
            $num = '0' . $num;

        $zeros = true;
        $punt = false;
        $ent = '';
        $fra = '';

        for ($c = 0; $c < strlen($num); $c++)
        {
            $n = $num[$c];
            if (! (strpos(".,'''", $n) === false))
            {
                if ($punt)
                {
                    break;
                }
                else
                {
                    $punt = true;
                    continue;
                }

            }
            elseif (! (strpos('0123456789', $n) === false))
            {
                if ($punt)
                {
                    if ($n != '0')
                        $zeros = false;
                    $fra .= $n;
                }
                else
                {
                    $ent .= $n;
                }
            }
            else
            {
                break;
            }

        }

        $ent = '     ' . $ent;
        if ($dec and $fra and !$zeros)
        {
            $fin = ' con ';
            for ($n = 0; $n < strlen($fra); $n++)
            {
                if (($s = $fra[$n]) == '0')
                    $fin .= ' cero';
                elseif ($s == '1')
                    $fin .= $fem ? ' una' : ' un';
                else
                    $fin .= ' ' . $matuni[$s];
            }
        }
        else
        {
            $fin = '';
        }

        if ((int)$ent === 0)
            return 'Cero ' . $fin;

        $tex = '';
        $sub = 0;
        $mils = 0;
        $neutro = false;
        while ( ($num = substr($ent, -3)) != '   ')
        {
            $ent = substr($ent, 0, -3);
            if (++$sub < 3 and $fem)
            {
                $matuni[1] = 'una';
                $subcent = 'as';
            }
            else
            {
                $matuni[1] = $neutro ? 'un' : 'uno';
                $subcent = 'os';
            }
            $t = '';
            $n2 = substr($num, 1);
            if ($n2 == '00')
            {
            }
            elseif ($n2 < 21)
            {
                $t = ' ' . $matuni[(int)$n2];
            }
            elseif ($n2 < 30)
            {
                $n3 = $num[2];
                if ($n3 != 0)
                    $t = 'i' . $matuni[$n3];
                $n2 = $num[1];
                $t = ' ' . $matdec[$n2] . $t;
            }
            else
            {
                $n3 = $num[2];
                if ($n3 != 0)
                    $t = ' y ' . $matuni[$n3];
                $n2 = $num[1];
                $t = ' ' . $matdec[$n2] . $t;
            }
            $n = $num[0];
            if ($n == 1)
            {
                $t = ' ciento' . $t;
            }
            elseif ($n == 5)
            {
                $t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t;
            }
            elseif ($n != 0)
            {
                $t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t;
            }
            if ($sub == 1)
            {
            }
            elseif (! isset($matsub[$sub]))
            {
                if ($num == 1)
                {
                    $t = ' un mil';
                }
                elseif ($num > 1)
                {
                    $t .= ' mil';
                }
            }
            elseif ($num == 1)
            {
                $t .= ' ' . $matsub[$sub] . '?n';
            }
            elseif ($num > 1)
            {
                $t .= ' ' . $matsub[$sub] . 'ones';
            }
            if ($num == '000')
                $mils ++;
            elseif ($mils != 0)
            {
                if (isset($matmil[$sub]))
                    $t .= ' ' . $matmil[$sub];
                $mils = 0;
            }
            $neutro = true;
            $tex = $t . $tex;
        }

        $qtyDec = (strlen($fra)>$minQtyDec?strlen($fra):$minQtyDec);
        $fin = ' con '.str_pad($fra,$qtyDec,'0').'/100';

        $tex = $neg .' '. trim($tex) .' '. $fin;
        return ucfirst(trim($tex));
    }

    function objectToArray($obj)
    {
        if (!is_object($obj))
        {
            $arr = $obj;
        }
        else
        {
            $arr = get_object_vars($obj);
            if (is_array($arr))
            {
                foreach ($arr as $k => $v)
                {
                    if (is_object($v))
                        $arr[$k] = objectToArray($v);
                    else
                        $arr[$k] = $v;
                }
            }
        }
        return $arr;
    }

    function getMensajeItHtml($texto=null)
    {
        $mensaje = '';
        if ($texto)
        {
            $lines = explode("\n",$texto);
        }
        else
        {
            $msg = new Mensaje();
            $texto = $msg->get();
            $lines = explode("\n",$texto);
        }

        if (!empty($lines))
            foreach ($lines as $line)
                if (substr(trim($line),0,1) != '#' && !empty($line))
                    $mensaje .= ($mensaje ?'<br/>':'').trim($line);

        if (!empty($mensaje))
        {

            $mensaje .= '<br/><br/><i class="info">En caso de ser necesario contacte al administrador del sistema (++54 11 4509-6070 - sistemas@tanet.com.ar)</i>';
            $mensaje .= ' - <i class="info">Puede hacer click sobre el mensaje para ocultarlo.</i>';
            return '<div id="mensaje_it" style="padding: 10px;margin: 0px 10px 10px 10px;border-radius: 10px;border-width: 2px;" class="msgAlert" onclick="$(this).hide();">'.$mensaje.'</div>';
        }
        return null;

    }

function debug($data)
{
    if (!is_string($data))
    {
        $str = '<pre style="width:100%;">';
        $str .= print_r($data,true);
        $str .= '</pre><br/>';
    }
    else
    {
        $str .= $data.'<br/>';
    }
    addTimeDebug($str);
}

function addTimeDebug($str)
{
    if (empty($_SESSION['timeDebug']))
        $_SESSION['timeDebug'][] = array('time'=>date('U'),'str'=>'TIME_DEBUG_START');

    $q=count($_SESSION['timeDebug']);
    $_SESSION['timeDebug'][]=array('time'=>date('U'),'str'=>$str);
}

function resetTimeDebug()
{
    if (isset($_SESSION['timeDebug']))
        unset($_SESSION['timeDebug']);
}

function addTimeDebugBacktrace()
{
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    
    if (!empty($bt))
    {
        $bt = array_reverse($bt);
        $q = count($bt);
        $str = 'BACKTRACE';
        foreach ($bt as $k => $rw)
        {
            if ($rw['function'] != 'addTimeDebugBacktrace')
            {
                $tab = '       '.str_pad(' ',(($k+1)*3),"  ");
                $str .= "<br/>".($tab).$rw['class'].$rw['type'].$rw['function'].'() - L:'.$rw['line'];
            }
        }
        addTimeDebug($str);
    }
}

function getTimeDebug()
{
    if (empty($_SESSION['timeDebug']))
        return false;

    addTimeDebug('TIME_DEBUG_END');

    $html = '
        <div id="debugModal" class="timeDebugContainer modal" data-focus="true" >
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">DEBUG</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">';
    foreach ($_SESSION['timeDebug'] as $k => $rw)
    {
        $time = $rw['time'];
        $str  = $rw['str'];
        if (!isset($lastTime))
            $lastTime = $time;
        
        $dif = $time-$lastTime;
        
        $html .= str_pad('+'.$dif,4,' ',STR_PAD_LEFT).' | '.$str."<br>";
    }

    $html .= '</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <script>
    $(window).on("load", function() {
            $("#debugModal").modal("show");
        });
    </script>';
    unset($_SESSION['timeDebug']);
    return $html;
}

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags); 
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}

function detectUTF8($str)
{
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $str);
}

function utf8ToLatin1($str)
{
    if (detectUTF8($str))
    {
        $str=str_replace("\xE2\x82\xAC","&euro;",$str);
        $str=iconv("UTF-8","ISO-8859-1//TRANSLIT",$str);
        $str=str_replace("&euro;","\x80",$str);
    }

    return $str;
}

function is_array_assoc(array $arr)
{
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function diferenciaFechas($fecha_inicial,$fecha_final)
{
    $dias = (strtotime($fecha_inicial)-strtotime($fecha_final))/86400;
    $dias = abs($dias); 
    //$dias = floor($dias);
    $dias = toDec($dias,1);
    //$dias = strtotime($fecha_final, $fecha_inicial)/86400/3600;
    return $dias;
}

?>