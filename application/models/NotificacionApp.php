<?php
include_once(MDL_PATH."usr/UsrUsuario.php");

class NotificacionApp {

    const SUPER_ADMIN_ID = 1;
    
    static public function send($title,$body,$registration_ids)
    {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

        //forzar
        //$registration_ids = array('eMNllRsKTyKLSahgo4CgKI:APA91bG85TMw2rc_Kzak7LXjTqgjzj6EgGs7TPzh6fp9xgwlMDFvvtBdDDbLzb_ixC8RPW4gFkp29mYQyf6-eVHRyGXbS2QWDIM_ZGUp7sOwOijj8TOfx0Bzt1BFF16JXwdTnGvkAdaN');

        $apiKey = FIREBASE_FCM_APIKEY;
        $notification = array('title' => $title, 'body' => $body);
        $notification['color'] = '#278ed8';
        $notification['icon'] = 'cripto_app_icon.png' ;
        //$notification['sound'] = 'mySound';
        //$notification['click_action'] = 'http://bisaro.ar';

        $extraNotificationData = array("message" => $notification, "moredata" => 'dd');
        $fcmNotification = array(
            'registration_ids' => $registration_ids,
            'notification' => $notification, 'data' => $extraNotificationData);
        $headers = array('Authorization:key=' . $apiKey, 'Content-Type: application/json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);
        
        $ret = json_decode($result);
        $ret->status = ($ret->success>0 && $ret->failure==0 ? 'OK' :'ERROR');
        $ret->registration_ids = $registration_ids;
        return $ret;
    }
}