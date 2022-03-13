<?php
include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");
//include_once(LIB_PATH."Mailer.php");
include_once(MDL_PATH."usr/UsrUsuario.php");
Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

$hostname = shell_exec('hostname');

$parametro = (isset($argv[1])?$argv[1]:"");

$htmlStyle =  '<style>';
$htmlStyle .= 'table {font-family: arial; width: 100%; font-size: 11px; border: 1px solid #555;border-collapse: collapse;} ';
$htmlStyle .= 'table tr th {border: 1px solid #555;background-color: #fffdd5; text-align: left;padding: 3px 2px;} ';
$htmlStyle .= 'table tr td {border: 1px solid #555;text-align: left;padding: 2px;} ';
$htmlStyle .= 'table caption {color: #3333aa; text-align: left; font-size: 14px; padding: 10px 2px;} ';
$htmlStyle .= 'h3 {font-family: arial; color:#33aa33;} ';
$htmlStyle .= '#reporte{font-family: arial; color: #555;} ';
$htmlStyle .= '.rojo{font-family: arial; color: #aa3333;} ';
$htmlStyle .= '</style>';

//--------------------------------------------------------------------------------------------------------------------


switch ($parametro) {
    case 'apalancamiento':
    
        include_once MDL_PATH."bot/Operacion.php";

        $lockFile = file_get_contents(LOCK_FILE);
        if (!empty($lockFile))
        {
            $msg = 'Error - Bot Apalancamiento Bloqueado desde '.$lockFile;
            Operacion::logBot($msg);
        }
        else
        {
            file_put_contents(LOCK_FILE, $procStart);
            include "crontab/crontab_apalancamiento.php";
            
            /*
            crontab_apalancamiento_post.php es para ejecutar cuestiones almacenadas 
            en la tabla operacion_post gestionada desde Operacion.php
            Operacion::tipoAccionesPost($key)
            Operacion::getAccionesPost()
            Operacion::addAccionesPost($idoperacion,$accion,$params)
            */
            //include "crontab/crontab_apalancamiento_post.php";
        }
        file_put_contents(LOCK_FILE, '');
        break;
    
    case 'arbitrajeTriangular':
        include "crontab/crontab_arbitrajeTriangular.php";
        break;
    
    case 'scalper':
        include "crontab/crontab_scalper.php";
        break;
    
    case 'getprices_binance':
        include "crontab/crontab_getprices_binance.php";
        break;
    
    case 'mailer':
        include "crontab/crontab_mailer.php";
        break;
    
    default:
        break;
}


//--------------------------------------------------------------------------------------------------------------------
Sql::close();
exit();
?>