<?php
include_once MDL_PATH."usr/UsrUsuario.php";

$FCM_token = $_REQUEST['FCM_token'];
$auth->setConfig('FCM_token',$FCM_token);