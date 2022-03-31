<?php
include_once MDL_PATH."usr/UsrUsuario.php";

$user = $_REQUEST['login_username'];
$pass = $_REQUEST['login_password'];

if (!$user || !$pass)
{
    if (!$user)
    {
        addError('Debe especificar el nombre de usuario');
    }
    if (!$pass)
    {
        addError('Debe especificar el password');
    }
}
elseif (!UsrUsuario::validAuth($user,$pass))
{
    addError('No coincide usuario y/o password');
}
elseif (!UsrUsuario::setAuthInstance($user,$pass))
{
    addError('No se pudo instanciar la sesion de usuario');
}

if ($auth = UsrUsuario::getAuthInstance())
{
    if ($auth->get('idperfil') < UsrUsuario::PERFIL_CNS)
        addError('La cuenta de usuario se encuentra inhabilitada.');
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
