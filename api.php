<?php
include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");
//include_once(LIB_PATH."Mailer.php");
include_once(MDL_PATH."usr/UsrUsuario.php");

Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

//Inicializando rsp['STATUS']
$rsp['STATUS'] = null;
$act = $_REQUEST['act'];

include "api/api_login.php";

if ($act != 'login' && $loginOk)
{
    switch ($act) {
        case 'notificaciones':

            include "api/api_notificaciones.php";
            break;

        default:
            addError('Se debe especificar ACT valido');
            break;
    }
}

//------------------------------------------------------------------
if (!empty($rsp['ERRORES']))
{
    $rsp['error'] = null;
    foreach ($rsp['ERRORES'] as $err)
    {
        $rsp['error'] .= ($rsp['error']?"\n":"").$err;
    }
    unset($rsp['ERRORES']);
    $rsp['STATUS'] = 'ERROR';
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

function addError($error)
{
    GLOBAL $rsp;
    if(!empty($error))
    {
        if (is_array($error))
            foreach ($error as $it)
                $rsp['ERRORES'][] = $it;
        else
            $rsp['ERRORES'][] = $error;
    }
}
?>