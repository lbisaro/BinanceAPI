<?php
include_once LIB_PATH."ControllerAjax.php";

/**
 * SessionTimeout
 *
 * Control de timeout de sesiones mediante Ajax
 *
 * Para el funcionamiento se debe linkear al archivo public\scripts\ajaxSessionTimeout.js
 *
 * Para obtener la cantidad de segundos tanto en PHP como en javascript,
 * se utilizan las siguientes funciones:
 *
 * @package myLibrary
 */
class SessionTimeoutAjax extends ControllerAjax
{

	static public function restart($U=null)
	{
		if (isset($U))
			$U = Date('U');
		
		if (isset($_SESSION['SessionTimeout']))
			$_SESSION['SessionTimeout']['startAt'] = $U;

		if ($_REQUEST['restart'] || $_REQUEST['act'] != 'getStatus')
			$_SESSION['SessionTimeout']['lastRestart'] = $U;
	}

	public function getStatus()
	{
		$this->ajxRsp->setEchoOut(true);
		if ($_REQUEST['restart'])
			self::restart($_REQUEST['U']);

		echo $_SESSION['SessionTimeout']['lastRestart'];
	}
}
?>