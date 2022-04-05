<?php
include_once MDL_PATH."usr/UsrUsuario.php";
include_once MDL_PATH."bot/Operacion.php";

$folder = LOG_PATH.'bot/';
$lastUpdate = file_get_contents($folder.'status.log');
$rsp['lastUpdate'] = substr($lastUpdate,0,19);

$checkDatetime = date('Y-m-d H:i:s',strtotime('- 3 minutes'));

$rsp['alert'] = false;
if ($lastUpdate < $checkDatetime)
    $rsp['alert'] = true;


//$prms['qtyHours'] = 12;
//$log = Operacion::getLog($prms);
//$rsp['log'] = $log;
