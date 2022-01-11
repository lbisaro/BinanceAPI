<?php
header('Content-type: text/html; charset='.DEFAULT_CHAR_ENCODING);

$controller = new $controllerName();

if (strtolower($moduleName.$controllerName.$actionName) == strtolower('UsrUsrControllerlogout'))
{
    $controller->logout($auth);
}

if (SERVER_ENTORNO == 'Test')
    $controller->setTitle(SERVER_ENTORNO);
$baseTitle = 'Cripto';
$controller->setTitle($baseTitle);

//https://getbootstrap.com/docs/3.4/customize/

$controller->addLinkCss('noPrint',$media='print');
$controller->addLinkCss('ajax');
$controller->addLinkCss('bootstrap.min');
$controller->addLinkCss('bootstrap.icons');

if (SERVER_ENTORNO == 'Test')
    $controller->addLinkCss('bootstrap.add.test');
else
    $controller->addLinkCss('bootstrap.add');

$controller->addLinkCss('cripto');

$controller->addLinkJs('functions');
$controller->addLinkJs('cripto');
$controller->addLinkJs('ajax');

$controller->addLinkJs('jquery.min');
$controller->addLinkJs('push.min');
$controller->addLinkJs('popper.min');
$controller->addLinkJs('bootstrap.min');

$controller->addLinkCss('jquery.tablesorter');
$controller->addLinkJs('jquery-tablesorter/jquery.tablesorter.min');


$isLogScrn = false;
if (strtolower($moduleName.$controllerName.$actionName) == "usr"."usrcontroller"."login")
    $isLogScrn = true;

if (!$isLogScrn)
{
    $controller->addLinkJs('ajaxSessionTimeout');
}
else
{
    $controller->addLinkCss('cripto_login');
    $controller->addOnloadJs("$('body').css('background','#fff');");
}

$controller->$actionName($auth);

/** Comenzando a escribir el HTML */
$controller->getHeader(($isLogScrn?'usr\login_header':'header'));

if (!$isLogScrn )
{

    $view = new View();
    $view->setTpl('menu');
    if ($auth->get('idperfil') != UsrUsuario::USUARIO_ADM)
        $arr['jsMenuAdmin'] = " $('.menu-admin').remove(); ";
    $arr['title']=str_replace($baseTitle.' - ','',$controller->getTitle());
    echo $view->get($arr);

}

$controller->printContent();


$controller->getFooter(($isLogScrn?'usr\login_footer':'footer'));


?>