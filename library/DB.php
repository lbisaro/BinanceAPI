<?php
include_once "functions.php";

class DB
{
    private static $instance = null;
    private static $dbLink = null;
    private static $dbList = null;
    private static $tableList = null;

    protected $tablesCache = array();
    protected $dbName;

    function __Construct()
    {
        if (empty(Sql::$db_link))
            DB::criticalExit('DB::__Construct() - No existe conexion con el Servidor de Bases de Datos');
        self::$dbLink = Sql::$db_link;
    }
/**
    public static function Connect($dbhost,$dbuser,$dbpassword,$dbname = '')
    {
        self::$dbLink = mysqli_connect( $dbhost, $dbuser, $dbpassword.'--' ) or DB::criticalExit( 'Error: No se pudo conectar al servidor MySql <br/>'.mysqli_connect_error() );

        self::exec("SET character_set_client = '".DEFAULT_CHAR_DB_ENCODING."';");
        self::exec("SET character_set_results = '".DEFAULT_CHAR_DB_ENCODING."';");
        self::exec("SET character_set_connection = '".DEFAULT_CHAR_DB_ENCODING."';");
        self::exec("SET character_set_server = '".DEFAULT_CHAR_DB_ENCODING."';");
        if ($dbname)
            self::selectDb($dbname);

        $db_list = mysqli_query(self::$dbLink,"SHOW DATABASES");
        self::$dbList = null;
        self::$tableList = null;

        while ($rw = mysqli_fetch_array($db_list))
        {
            self::$dbList[$rw['Database']] = null;
            $table_list = mysqli_query(self::$dbLink,'SHOW TABLES FROM '.$rw['Database']);
            while ($rwT = mysqli_fetch_array($table_list))
            {
                self::$dbList[$rw['Database']]['tables'][$rwT[0]] = null;
                self::$tableList[$rwT[0]] .= (self::$tableList[$rwT[0]]?',':'').$rw['Database'];
            }
        }
        return self::$dbLink;
    }
*/
	public static function getInstance()
	{
        if( self::$instance == null )
			self::$instance = new self();

        self::$instance->dbName = DB_NAME;

		return self::$instance;
	}

    public static function getTableInfo($db,$table)
    {
        $fields=array();

        //Si la tabla esta en cache devuelve la estructura
        if (is_array(self::$instance->tablesCache[$db.'.'.$table]))
            return self::$instance->tablesCache[$db.'.'.$table];

        $qry = "DESCRIBE ".$db.'.'.$table;
        if ($stmt = self::$instance->query($qry))
        {
            while ($rw = $stmt->fetch())
            {
                /**
                * Convierte $rw['Type'] en un array con lo que se encuentre
                * antes del primer parentesis abierto, lo que esta entre
                * parentesis, y lo que se encuentre luego del parentesis cerrado.
                *
                * int(3)unsigned => lo convierte en:
                * $arr[1]='int'
                * $arr[2]='3'
                * $arr[3]='unsigned'
                */
                $field = array();
                preg_match( "/([^\(]+)\(([\d]+)\)?(.+)?/", $rw['Type'], $rval );

                $field['type'] = ( $rval[1]?$rval[1]:$rw['Type'] );
                $field['len'] = $rval[2];
                if ( $rval[3] )
                    $field[trim( $rval[3] )] = 1;

                if (strtoupper($rw['Null'])=='NO')
                    $field['null'] = 'NO';
                else
                    $field['null'] = 'YES';

                $field['key'] = $rw['Key'];

                if (substr($rw['Field'],0,2) == 'id')
                    $field['label'] = 'id'.ucfirst(substr($rw['Field'],2));
                else
                    $field['label'] = str_replace("_"," ",ucfirst($rw['Field']));

                $fields[$rw['Field']] = $field;
            }

            //Guarda la info de la tabla en el cache
            self::$instance->tablesCache[$db.'.'.$table] = $fields;
        }
        return $fields;
    }

    public static function selectDb($dbName)
    {
        mysqli_select_db(self::$dbLink,$dbName) or DB::criticalExit( 'Error: No se pudo seleccionar la base de datos: <b>'.$dbName.'</b><br/>'.mysqli_error(self::$dbLink) );
    }

    public static function query($qry)
    {
        if (empty($qry))
            DB::criticalExit("DB::query() - Atención! Fallo en consulta - La consulta no puede ser una cadena vacia.");

        $rs = mysqli_query(self::$dbLink,$qry)
            or DB::criticalExit("DB::query() - Atención! Fallo en consulta - Error número: [".mysqli_errno(self::$dbLink)."] Descripcion: ".mysqli_error(self::$dbLink)." - Contacte al administrador y comuníquele el número del error. Referencia: ".$qry.".");

        if (!$rs)
            return null;

        $stmt = new DBStatement($rs);
        return $stmt;

    }

    public static function exec($qry)
    {        
        if (empty($qry))
            DB::criticalExit("DB::exec() - Atención! Fallo en consulta - La consulta no puede ser una cadena vacia.");

        $rs = mysqli_query(self::$dbLink,$qry)
            or DB::criticalExit("DB::exec() - Atención! Fallo en consulta - Error número: [".mysqli_errno(self::$dbLink)."] Descripcion: ".mysqli_error(self::$dbLink)." - Contacte al administrador y comuníquele el número del error. Referencia: ".$qry.".");

        return $rs;

    }

    public function getInsertId()
    {
        return mysqli_insert_id(self::$dbLink);
    }


    public static function close()
    {
        if (self::$dbLink)
            @mysqly_close(self::$dbLink);
        self::$instance = null;

    }

    public static function dump()
    {
        if (self::$instance)
            pr(self::$instance->tablesCache);
        pr(self::$tableList);
        pr(self::$dbList);

    }

    public static function criticalExit($message)
    {
        $error = '
        <div style="font-size: 16px; font-family: arial, tahoma, verdana;border:1px solid #dd5555;color:#dd5555;padding:10px;margin:10px;border-radius: 5px;display: block;">
            <h4>DB.php - ERROR CRITICO</h4>
            <li style="color:#dd5555;font-size:13px; border: 1px solid #d55; padding: 10px;border-radius: 5px;">
                '.nl2br($message).'
            </li>
            <p style="color:#999999;font-size:12px;">Contacte al administrador del sistema (<a href="mailto:sistemas@tanet.com.ar">sistemas@tanet.com.ar</a>) informando el presente error.</p>
            <div style="color:#999999;font-size:12px;display: block;">

                REQUEST:<ul>';

        $txtError = date('Y-m-d H:i:s'); 
        foreach ($_REQUEST as $k=>$v)
        {
            if ($k != 'PHPSESSID')
            {
                $error .='<li>'.$k.': <b>'.$v.'</b></li>';
                $txtError .= " [".$k.": ".$v.']';
            }
        }
        $txtError .= "\n".$message;

        file_put_contents(LOG_PATH.'mysql_error_'.date('Y.m').'.log', $txtError."\n", FILE_APPEND );

        $error .='
                </ul>
            </div>
        </div>';

        die($error);
    }

}

class DBStatement
{
    protected $result;

    function __Construct(&$result)
    {
        $this->result = $result;
    }

    function fetch()
    {
        return mysqli_fetch_assoc($this->result);
    }

    function fetchAll()
    {
        $arr = null;
        while ($rw = mysqli_fetch_assoc($this->result))
            $arr[] = $rw;
        return $arr;
    }
}

?>