<?php
session_start();
$moduleName     = (isset($_REQUEST['mod'])) ? $_REQUEST['mod'] : NULL;
$controllerName = (isset($_REQUEST['ctrl'])) ? $_REQUEST['ctrl'] : NULL;
$actionName     = (isset($_REQUEST['act'])) ? $_REQUEST['act'] : NULL;

include_once("config.php");

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."Sql.php");

include_once(CTRL_PATH."_lib/SessionTimeoutAjax.php");

include_once(MDL_PATH."usr/UsrUsuario.php");

resetTimeDebug();

Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

//Esta accion se implementa para que se mantenga la compatibilidad cuando se accede desde el SGS.
if (!empty($_SESSION['SSN_idusuario']) && !$_SESSION['Auth'])
{
    //Se replica lo realizado por UsrUsuario::setAuthInstance(user,pass)
    $usrTmp = new UsrUsuario($_SESSION['SSN_idusuario']);
    $_SESSION['Auth'] = $usrTmp->get();
    $_SESSION['SSN_idusuario'] = $usrTmp->get('idusuario');
}

$auth = UsrUsuario::getAuthInstance();

//Registra en el log uso del sistema por Usuario
if ($auth)
    $auth->saveLog();



if (strtolower($moduleName.$controllerName.$actionName) != strtolower('UsrUsrAjaxlogin'))
{
    if (!$auth && !empty($moduleName) && !empty($controllerName) && !empty($actionName) )
    {
        /**
        Si se intenta acceder a una URL sin existir una sesion de usuario abierta
        Se cachea la info de la URL, y lurgo la procesa Usr.UsrAjax.login.
        */
        $_SESSION['cachedRequest']['moduleName'] = $moduleName;
        $_SESSION['cachedRequest']['controllerName'] = $controllerName;
        $_SESSION['cachedRequest']['actionName'] = $actionName;
        $_SESSION['cachedRequest']['get'] = $_GET;
        $_SESSION['cachedRequest']['post'] = $_POST;
    }

    if (!$auth || empty($moduleName) || empty($controllerName) || empty($actionName))
    {
        $moduleName     = "Usr";
        $controllerName = "Usr";
        $actionName     = "login";
    }
    else
    {
        SessionTimeoutAjax::restart();
    }
}

if (substr($controllerName,strlen($controllerName)-3,3) == 'Pdf')
{
    $addIndex = 'indexPdf.php';
}
elseif (substr($controllerName,strlen($controllerName)-3,3) == 'Xls')
{
    $addIndex = 'indexXls.php';
}
elseif (substr($controllerName,strlen($controllerName)-3,3) == 'Txt')
{
    $addIndex = 'indexTxt.php';
}
elseif (substr($controllerName,strlen($controllerName)-4,4) == 'Ajax')
{
    $addIndex = 'indexAjax.php';
}
else
{
    $addIndex = 'indexHtml.php';
    $controllerName = $controllerName.'Controller';
}


if (!$moduleName)
    echo '<div class="error">Error en URL: Se debe especificar el parametro correspondiente al Modulo.</div>';
if (!$controllerName)
    echo '<div class="error">Error en URL: Se debe especificar el parametro correspondiente al Controlador.</div>';
if (!$actionName)
    echo '<div class="error">Error en URL: Se debe especificar el parametro correspondiente al Metodo.</div>';


$moduleName = strtolower($moduleName);
$controllerName = ucfirst($controllerName);

$controllerFile = CTRL_PATH.$moduleName.'/'.$controllerName.'.php';

if(!is_file($controllerFile))
    exit('<hr/>El controlador <b>'.$controllerFile.'</b> no existe');

include_once($controllerFile);

if (!is_callable(array($controllerName, $actionName)))
    exit('<hr/>No es posible ejecutar '.$moduleName.'.'.$controllerName . '-><b>' . $actionName . '</b>');

require($addIndex);

Sql::close();

//Solo muestra codigo si se establecio alguna info mediante addTimeDebug($str) o addTimeDebugBacktrace() 
echo getTimeDebug();

?>
