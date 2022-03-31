<?php
include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");
//include_once(LIB_PATH."Mailer.php");
include_once(MDL_PATH."usr/UsrUsuario.php");

Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

$act = $_REQUEST['act'];
$RSID = $_REQUEST['RSID'];

$auth = new UsrUsuario();
if ($RSID)
{
    $auth->loadByRSID($RSID); 
    if ($auth->get('idusuario') < 1)
    {
        addError('RSID no valido');
    }
}

if (empty($act))
    addError('Se debe especificar ACT');
elseif ($act != 'login' && !$RSID)
    addError('Se debe especificar RSID');

if (empty($rsp['ERRORES']))
{
    switch ($act) {
        case 'login':

            include "api/api_login.php";
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
    header('X-PHP-Response-Code: '.500, true, 500);
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