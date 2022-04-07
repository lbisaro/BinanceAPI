<?php
include_once MDL_PATH."usr/UsrUsuario.php";

$arrToSet['FCM_token'] = $_REQUEST['FCM_token'];
$auth->set($arrToSet);
if ($auth->save())
    $rsp['result'] = 'Token registrado';
else
    addError('No fue posible registrar el token');