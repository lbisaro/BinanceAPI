<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once MDL_PATH."usr/UsrUsuario.php";

/**
 * UsrController
 *
 * @package SGi_Controllers
 */
class UsrController extends Controller
{

    function login($auth)
    {
        $this->addTitle(SOFTWARE_NAME.' - Abrir sesion');

        $this->setFocus('login_username');

        $arr['titulo'] = 'Abrir sesión de sistema';

        $arr['input_username']   = Html::getTagInput('login_username',null,null,array('AUTOCOMPLETE'=>'OFF'));
        $arr['input_password']   = Html::getTagInput('login_password',null,'password');

        $this->addView('usr\login',$arr);
    }

    function logout($auth)
    {
        session_destroy();
        UsrUsuario::killAuthInstance();
        header ("Location: .");
        exit;
    }


    function perfil($auth)
    {
        $this->addTitle('Cuenta');

        $arr['idusuario'] = $auth->get('idusuario');
        $arr['ayn'] = $auth->get('ayn');
        $arr['username'] = $auth->get('username');
        $arr['mail'] = $auth->get('mail');

        if ($auth->getConfig('bncak'))
            $arr['binanceBtn'] = '<button type="button" class="btn btn-danger btn-sm" onclick="cancelarBinance();">Cancelar asociacion con la API</button>';
        else
            $arr['binanceBtn'] = '<button type="button" class="btn btn-primary btn-sm" onclick="showBinanceForm();">Asociar API a la cuenta</button>';

        if ($FCM_token = $auth->get('FCM_token'))
            $arr['FCM_token'] = $FCM_token;
        else
            $arr['FCM_token'] = 'Sin especificar';
    
        $this->addView('usr/perfil',$arr);
    }
}
?>
