<?php
chdir('../');
include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");
include_once(MDL_PATH."usr/UsrUsuario.php");

$ip = file_get_contents('https://wgetip.com');
$rsp = @file_get_contents('http://bisaro.ar/receive_ip.php?ip='.$ip);
if ($rsp)
{
    echo "\n".$rsp;
    echo "\nIP registrada: ".$ip;
}
else
{
    Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

    include_once (MDL_PATH."NotificacionApp.php");
    $usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
    $registration_ids[] = $usr->getFCM_token();
    
    $title = 'ALERTA!';
    $body = 'bisaro.ar - Fuera de servicio'."\n".$lockFileText;
    $result = NotificacionApp::send($title,$body,$registration_ids);
    
}
