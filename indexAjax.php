<?php
header('Content-Type: text/html; charset='.DEFAULT_CHAR_ENCODING);
if ($_REQUEST['encode'])
{
    foreach ($_REQUEST as $k => $v)
    {
        if ($k != 'sid' && $k != 'ajaxIdToChange' && $k != 'ajaxPhpScript' && $k != 'encode')
        {
            $_REQUEST[$k] = base64_decode($v);
        }
    }
}
foreach ($_REQUEST as $k => $v)
    if (is_string($v))
        $_REQUEST[$k] = utf8_decode($v);


$ajaxController = new $controllerName();
$ajaxController->$actionName();
?>