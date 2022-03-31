<?php
include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");
//include_once(LIB_PATH."Mailer.php");
include_once(MDL_PATH."usr/UsrUsuario.php");

Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

$act = $_REQUEST['act'];
$RSID = $_REQUEST['RSID'];

//Iniciando el array de respuesta
$rsp['STATUS'] = null;
 
$auth = new UsrUsuario();
if ($RSID)
{
    $auth->loadByRSID($RSID); 
    if ($auth->get('idusuario') < 1)
    {
        $rsp['ERRORES'][] = 'RSID no valido';
    }
}

if (empty($act))
    $rsp['ERRORES'][] = 'Se debe especificar ACT';
elseif ($act != 'login' && !$RSID)
    $rsp['ERRORES'][] = 'Se debe especificar RSID';

if (empty($rsp['ERRORES']))
{
    switch ($act) {
        case 'login':

            include "api/api_login.php";
            break;

        default:
            $rsp['ERRORES'][] = 'Se debe especificar ACT valido';
            break;
    }
}

//------------------------------------------------------------------
if (!empty($rsp['ERRORES']))
{
    $rsp['STATUS'] = 'ERROR';
    $rsp['ERROR'] = null;
    foreach ($rsp['ERRORES'] as $err)
    {
        $rsp['ERROR'] .= ($rsp['ERROR']?"\n":"").$err;
    }
    unset($rsp['ERRORES']);
}
else
{
    $rsp['STATUS'] = 'OK';
}

if (isset($rsp))
    echo json_encode($rsp,JSON_UNESCAPED_UNICODE);

//------------------------------------------------------------------
Sql::close();
exit();
?>