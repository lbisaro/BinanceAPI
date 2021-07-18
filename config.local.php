<?php
/**
 * Archivo de configuración del sistema en entorno local
 */
 
/** Datos referentes al entorno del sistema */
    //define('SERVER_ENTORNO' , 'Produccion');
    define('SERVER_ENTORNO' , 'Test');

    //Se define si se trata de un server WIN (No se requiere en server Linux)
    define('WIN_SERVER',true);


/** Conexion a Sql  */
    define('DB_HOST','localhost');
    define('DB_NAME','cripto');
    define('DB_USER','root');
    define('DB_PASSWORD','');

/** Paths  */
    // Root dir
    define('ROOT_DIR', "C:\\xampp\htdocs\\cripto\\BinanceAPI");
    // Temp dir
    define('TMP_PATH', "C:\\xampp\\tmp");
    // Log dir
    define('LOG_PATH', 'C:\\xampp\\log\\cripto');
    // Base Url
    define('BASE_URL', 'http://localhost/cripto');

?>