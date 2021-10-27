<?php
/**
 * Archivo de configuración del sistema en entorno local
 */
 
/** Datos referentes al entorno del sistema */
    define('SERVER_ENTORNO' , 'Produccion');
    //efine('SERVER_ENTORNO' , 'Test');

    //Se define si se trata de un server WIN (No se requiere en server Linux)
    //define('WIN_SERVER',true);


/** Conexion a Sql  */
    define('DB_HOST','localhost');
    define('DB_NAME','cripto');
    define('DB_USER','root');
    define('DB_PASSWORD','password');

/** Paths  */
    // Root dir         C:\Dropbox\Cripto\python\cripto_web
    define('ROOT_DIR', "/var/www/html/cripto_web");
    // Temp dir
    define('TMP_PATH', "/tmp");
    // Log dir
    define('LOG_PATH', '/var/log/cripto/');
    // Base Url
    define('BASE_URL', 'http://localhost');

/** STATUS FILE para control de Crontab */
    define('STATUS_FILE',LOG_PATH.'bot/status.log');
?>
