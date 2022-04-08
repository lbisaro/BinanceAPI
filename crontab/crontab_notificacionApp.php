<?php
include_once (MDL_PATH."NotificacionApp.php");

$usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
$registration_ids[] = $usr->getFCM_token();

$title = 'Prueba desde Crontab';
$body = 'Son las '.date('H:i:s');
$result = NotificacionApp::send($title,$body,$registration_ids);
print_r($result);
