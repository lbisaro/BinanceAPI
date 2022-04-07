<?php
include_once MDL_PATH."usr/UsrUsuario.php";

if (!empty($_REQUEST['FCM_token']))
{
    if ($auth->saveFCM_token($_REQUEST['FCM_token']))
        $rsp['result'] = 'Token registrado';
    else
        addError('No fue posible registrar el token');
    
}
else
{
    addError('Se debe especificar un token');
}