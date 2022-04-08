<?php
include_once (MDL_PATH."NotificacionApp.php");

$usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
$registration_ids[] = $usr->getFCM_token();
$registration_ids[] = 'eMNllRsKTyKLSahgo4CgKI:APA91bG85TMw2rc_Kzak7LXjTqgjzj6EgGs7TPzh6fp9xgwlMDFvvtBdDDbLzb_ixC8RPW4gFkp29mYQyf6-eVHRyGXbS2QWDIM_ZGUp7sOwOijj8TOfx0Bzt1BFF16JXwdTnGvkAdaN';

$title = 'Prueba desde Crontab';
$body = 'Son las '.date('H:i:s');
$result = NotificacionApp::send($title,$body,$registration_ids);
print_r($result);
