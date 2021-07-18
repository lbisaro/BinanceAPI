<?php
header('Content-type: text/html; charset='.DEFAULT_CHAR_ENCODING);

$controller = new $controllerName();

if (strtolower($moduleName.$controllerName.$actionName) == strtolower('UsrUsrControllerlogout'))
{
    $controller->logout($auth);
}

if (SERVER_ENTORNO == 'Test')
    $controller->setTitle(SERVER_ENTORNO);

//https://getbootstrap.com/docs/3.4/customize/

$controller->addLinkCss('noPrint',$media='print');
$controller->addLinkCss('ajax');
$controller->addLinkCss('bootstrap.min');
$controller->addLinkCss('bootstrap.icons');

$controller->addLinkCss('bootstrap.add');
$controller->addLinkCss('covid');

$controller->addLinkJs('functions');
$controller->addLinkJs('covid');
$controller->addLinkJs('ajax');

$controller->addLinkJs('jquery.min');
$controller->addLinkJs('push.min');
$controller->addLinkJs('popper.min');
$controller->addLinkJs('bootstrap.min');


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
    if (SERVER_ENTORNO == 'Test')
    {
        //$controller->addOnloadJs("$('body').css('background','#555');");
        $controller->addOnloadJs("$('form').css('background','#fff');");
    }
}

$controller->$actionName($auth);

/** Comenzando a escribir el HTML */
$controller->getHeader(($isLogScrn?'usr\login_header':'header'));

if (!$isLogScrn )
{

    $view = new View();
    $view->setTpl('menu');
    echo $view->get();

}

$controller->printContent();


$controller->getFooter(($isLogScrn?'usr\login_footer':'footer'));


?>