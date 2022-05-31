<?php

$rootFolder = dirname(__FILE__);
include_once($rootFolder."/config.local.php");
ini_set('memory_limit', '256M');

/**
 * Archivo de configuración del sistema
 */

/**
* Estableciendo zona horaria
*/
date_default_timezone_set('America/Argentina/Buenos_Aires');

/** Datos referentes al Software */

    define('SOFTWARE_NAME',"Cripto");
    define('SOFTWARE_VER',"1.0");
    

/** Gestino de errores PHP */
    if (SERVER_ENTORNO == 'Test')
    {
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        ini_set('display_errors','Yes');

        set_error_handler(function ($errno, $errstr) {
           return strpos($errstr, 'Declaration of') === 0;
        }, E_WARNING);

    }
    else
    {
        error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING & ~E_STRICT);
        ini_set('display_errors','Yes');        
    }

/** Paths  */
    
    // MVC - Models
    define('MDL_PATH', ROOT_DIR.'/application/models/');
    // MVC - Views
    define('VIEW_PATH', ROOT_DIR.'/application/views/');

    // MVC - Controllers
    define('CTRL_PATH', ROOT_DIR.'/application/controllers/');
    // MVC - Controllers Ajax
    define('AJX_PATH', CTRL_PATH);
    

    // Python Path
    define('PY_PATH',ROOT_DIR.'/python/');

    // Bibliotecas base
    define('LIB_PATH','library/');
    // Hojas de estilo
    define('CSS_PATH','public/styles/');
    // Scripts
    define('SCR_PATH','public/scripts/');
    // Imagenes
    define('IMG_PATH','public/images/');
    
    
/** CHARSET */

    // General
    // define ('DEFAULT_CHAR_ENCODING', 'iso-8859-1');
    // define ('DEFAULT_CHAR_DB_ENCODING', 'latin1');
    define ('DEFAULT_CHAR_ENCODING', 'UTF-8');
    define ('DEFAULT_CHAR_DB_ENCODING', 'utf8');

/** STATUS FILE para control de Crontab */
    define('LOCK_FILE',LOG_PATH.'bot/lock.status');
    define('STATUS_FILE',LOG_PATH.'bot/status.log');

    define('STATUS_FILE_AT',LOG_PATH.'bot/statusAT.log');

    define('STATUS_FILE_SCLPR',LOG_PATH.'/bot/statusScalper.log');
?>
