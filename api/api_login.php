<?php
include_once MDL_PATH."usr/UsrUsuario.php";

$user = $_REQUEST['login_username'];
$pass = $_REQUEST['login_password'];

if (!$user || !$pass)
{
    if (!$user)
    {
        $rsp['ERROR'][] = 'Debe especificar el nombre de usuario';
    }
    if (!$pass)
    {
        $rsp['ERROR'][] = ($error?$error.' y password':'Debe especificar el password');
    }
}
elseif (!UsrUsuario::validAuth($user,$pass))
{
    $rsp['ERROR'][] = 'No coincide usuario y/o password';
}
elseif (!UsrUsuario::setAuthInstance($user,$pass))
{
    $rsp['ERROR'][] = 'No se pudo instanciar la sesion de usuario';
}

if ($auth = UsrUsuario::getAuthInstance())
{
    if ($auth->get('idperfil') < UsrUsuario::PERFIL_CNS)
        $rsp['ERROR'][] = 'La cuenta de usuario se encuentra inhabilitada.';
}
if (empty($rsp['ERROR']))
{
    $RSID = getRndAlpha(20);
    $auth->setRSID($RSID);
    $auth->registrarAcceso();

    $rsp['idusuario'] = $auth->get('idusuario');
    $rsp['username']  = $auth->get('username');
    $rsp['ayn']       = $auth->get('ayn');
    $rsp['mail']      = $auth->get('mail');
    $rsp['RSID']      = $auth->get('RSID');
    $rsp['admin']     = $auth->isAdmin();
    
}
