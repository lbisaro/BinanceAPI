<?php
include_once (MDL_PATH."NotificacionApp.php");

$usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
$registration_ids[] = $usr->getFCM_token();
$forcedToken = 'fjM2sgYUTxKd-T7CIQtN7T:APA91bGwjNOZYGJKsjSv5VlMzuAghJ1zq_06vin7QI-fZWUgR-f9DW0HPUA3ZKrQJ-MxS3vwcm-q1CmpssvvTiv0VUfKUvl32o_DcIwZncnDH1bI6k8HwN-B99DYQWqiuV-QvBhY3auh';
if (!in_array($forcedToken, $registration_ids))
    $registration_ids[] = $forcedToken;

$title = 'Prueba desde Crontab';
$body = 'Son las '.date('H:i:s');
$result = NotificacionApp::send($title,$body,$registration_ids);
print_r($result);
