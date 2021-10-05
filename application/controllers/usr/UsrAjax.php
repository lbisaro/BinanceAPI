<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."ControllerAjax.php";
include_once MDL_PATH."usr/UsrUsuario.php";

class UsrAjax extends ControllerAjax
{
    function login()
    {
        $user = $_REQUEST['login_username'];
        $pass = $_REQUEST['login_password'];

        if (!$user || !$pass)
        {
            if (!$user)
        	{
        		$error = 'Debe especificar el nombre de usuario';
        	}
        	if (!$pass)
        	{
        		$error = ($error?$error.' y password':'Debe especificar el password');
        	}
        }
    	elseif (!UsrUsuario::validAuth($user,$pass))
    	{
    		$error = 'No coincide usuario y/o password';
    	}
        elseif (!UsrUsuario::setAuthInstance($user,$pass))
        {
            $error = 'No se pudo instanciar la sesion de usuario';
        }

        if ($auth = UsrUsuario::getAuthInstance())
        {
            if ($auth->get('idperfil') < UsrUsuario::PERFIL_CNS)
                $error = 'La cuenta de usuario se encuentra inhabilitada.';
        }
        if ($error)
        {
            $this->ajxRsp->script("$('#login_msg').removeClass('text-success')");
            $this->ajxRsp->script("$('#login_msg').addClass('text-danger')");
        	$this->ajxRsp->script("$('#login_msg').html('".$error."')");

        }
        else
        {
            $auth->registrarAcceso();
            if (isset($_SESSION['cachedRequest']) && empty($_SESSION['cachedRequest']['post']))
            {
                $mod  = $_SESSION['cachedRequest']['moduleName'];
                $ctrl = $_SESSION['cachedRequest']['controllerName'];
                $act  = $_SESSION['cachedRequest']['actionName'];
                $prm='';
                if (!empty($_SESSION['cachedRequest']['get']))
                    foreach ($_SESSION['cachedRequest']['get'] as $k => $v)
                    {
                        if (!in_array($k, array('mod','ctrl','act')))
                            $prm  .= ($prm?'&':'').$k.'='.$v;
                    }

                unset($_SESSION['cachedRequest']);
            }
            else
            {
                $mod  = 'App';
                $ctrl = 'Cripto';
                $act  = 'home';
                $prm  = 'login=OK';
            }
            $this->ajxRsp->redirect(Controller::getLink($mod,$ctrl,$act,$prm));
            $this->ajxRsp->script("$('#login_msg').removeClass('text-danger')");
            $this->ajxRsp->script("$('#login_msg').addClass('text-success')");
            $this->ajxRsp->script("$('#login_msg').html('Acceso correcto!')");
        }
    }

    /**
    * Funcion ajax para modificar password
    */
    function grabarPassword()
    {
        $arrDatos = $_REQUEST;
        $usr = new UsrUsuario($arrDatos['idusuario']);

        if(!$usr->setNewPassword($arrDatos['password'],$arrDatos['oldpassword']))
        {
            $aErr = $usr->getErrLog();
            $htmlError = '';
            if (!empty($aErr))
            {
                foreach($aErr as $err)
                    $htmlError .= '<p>'.$err.'</p>';
            }
            else
            {
                $htmlError = 'El password no pudo ser establecido.';
            }
            $this->ajxRsp->assign('message-error','innerHTML',$htmlError);
            $this->ajxRsp->script("$('#message-error').show();");
        }
        else
        {
            $this->ajxRsp->alert('El password se a modificado con exito!! Sera redireccionado al Login. Recuerde que su nuevo password es: "'.$arrDatos['password'].'"');
            $this->ajxRsp->redirect(Controller::getLink('Usr','Usr','login'));
        }
    }

    function grabarBinance()
    {
        $arrDatos = $_REQUEST;
        $usr = new UsrUsuario($arrDatos['idusuario']);
        $usr->setConfig('bncak',$arrDatos['api_key']);
        $usr->setConfig('bncas',$arrDatos['api_secret']);

        $this->ajxRsp->script('hideBinanceForm();');
        $this->ajxRsp->alert('Los datos se han registrado con exito!!');
        $this->ajxRsp->redirect(Controller::getLink('Usr','Usr','perfil'));
        
    }

}
?>
