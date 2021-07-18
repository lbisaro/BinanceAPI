<?php
include_once "ErrorLog.php";
/** class: Sql
 *
 * Permite acceder a servidores de bases de datos MySQL, ejecutar
 *  sentencias y mantener un log de los acontecimientos.
 *
 * @package myLibrary
 */
class Sql extends ErrorLog
{
    /**
     * Almacena el identificador de enlace de la conexion luego de ser realizada.
     */
    static public $db_link;

    /**
     * Nombre de la base de datos seleccionada.
     */
    static public $dbname;

    /**
     * Lista de las bases de datos.
     */
    static public $dbList;

    /**
     * Lista de las tablas y sus bases de datos asociadas.
     */
    static public $tableList;

    /**
     * Cache en el que se guardan las estructuras de las tablas
     */
    static public $tablesCache;

    /**
     * Almacena el resultado de una consulta realizada mediante la funcion this->query().
     */
    protected $result;

    /**
     * Almacena el log de las acciones realizadas mediante esta clase.
     */
    protected $sql_log;

    /**
     * Abre una conexión a un servidor MySQL y almacena en this->db_link un
     * identificador de enlace positivo si tiene exito, o falso si error.
     */
    function Sql()
    {
        $this->sql_log = 'Sql:';
        Sql::edit_sql_log("Called From class: ".get_class($this));
    }


    /**
     * STATIC Sql::Connect()
     *
     * @param string $dbhost
     * @param string $dbuser
     * @param string $dbpassword
     * @param string $dbname
     * @return MySQL link identifier
     */
    static function Connect($dbhost,$dbuser,$dbpassword,$dbname = '')
    {
        self::$db_link = mysqli_connect( $dbhost, $dbuser, $dbpassword ) or die( 'Error: Could Not Connect To Database '.$dbuser.'@'.$dbhost.' from '.$_SERVER['SERVER_ADDR'].' '.mysqli_error(self::$db_link) );

        mysqli_query(self::$db_link,"SET character_set_client = '".DEFAULT_CHAR_DB_ENCODING."';");
        mysqli_query(self::$db_link,"SET character_set_results = '".DEFAULT_CHAR_DB_ENCODING."';");
        mysqli_query(self::$db_link,"SET character_set_connection = '".DEFAULT_CHAR_DB_ENCODING."';");
        mysqli_query(self::$db_link,"SET character_set_server = '".DEFAULT_CHAR_DB_ENCODING."';");
        if ($dbname)
            self::select_db( $dbname) or die( 'Error: Could Not Select Database ['.$dbname.']'.mysqli_error($this->db_link) );

        $db_list = mysqli_query(self::$db_link,"SHOW DATABASES");
        while ($rw = mysqli_fetch_array($db_list))
        {
            self::$dbList[$rw['Database']] = null;
            $table_list = mysqli_query(self::$db_link,'SHOW TABLES FROM '.$rw['Database']);
            if (!$table_list instanceof mysqli_result) {
                continue;
            }
            while ($rwT = mysqli_fetch_array($table_list))
            {
                self::$dbList[$rw['Database']]['tables'][$rwT[0]] = null;
                if (!isset(self::$tableList[$rwT[0]]))
                    self::$tableList[$rwT[0]] = $rw['Database'];
                else
                    self::$tableList[$rwT[0]] .= ','.$rw['Database'];
            }
        }

        return self::$db_link;
    }


    /**
     * Selecciona un base de datos MySQL y devuelve 1 si
     * todo se llevó a cabo correctamente, 0 en caso de fallo.
     */
    static function select_db($dbname)
    {
        self::$dbname = $dbname;
        return @mysqli_select_db(self::$db_link, self::$dbname) or die( 'Error: Could Not Select Database'.mysqli_error(self::$db_link) );
    }

    /**
     * Devuelve el log almacenado para el link
     */
    function get_sql_log()
    {
        $this->sql_log .= "get_sql_log()"."<br />\n";
        return $this->sql_log;
    }

    /**
     * Agregar una entrada al log almacenado para el link
     */
    function edit_sql_log( $content )
    {
        $this->sql_log .= ' ' . $content . "<br />\n";
    }

    /**
     * Envía una consulta de MySQL y devuelve el resultado
     *
     * El funcionamiento de la funcion es similar a mysqli_query()
     *
     * Valores retornados<br />
     * Para las sentencias SELECT, SHOW, DESCRIBE o EXPLAIN,
     * mysqli_query() regresa un resource en caso exitoso, y FALSE en error.
     *
     * Para otro tipo de sentencia SQL, UPDATE, DELETE, DROP, etc, mysqli_query()
     * regresa TRUE en caso exitoso y FALSE en error.
     *
     * El resultado obtenido debe ser pasado a mysqli_fetch_array(), y otras funciones
     * para el manejo de las tablas del resultado, para accesar los datos regresados.
     *
     * Use mysqli_num_rows() para encontrar cuantas filas fueron regresadas para una
     * sentencia SELECT o mysqli_affected_rows() para encontrar cuantas filas fueron
     * afectadas por una sentencia DELETE, INSERT, REPLACE, o UPDATE.
     *
     * mysqli_query() también fallará y regresará FALSE si el usuario no tiene permiso
     * de accesar la o las tablas referenciadas por la consulta.
     */
    function query( $query, $ref = 0 )
    {
        $this->sql_log .= 'query:';
        $this->result[$ref] = @mysqli_query(self::$db_link, $query ) or die('Error: <b>Database Query Error</b><hr><i>-- '.$query.' --</i><hr>'.mysqli_error(self::$db_link));
        Sql::edit_sql_log("q: [$query] ref: [$ref]");
        return $this->result[$ref];
    }

    /**
     * Extrae la fila de resultado como una matriz y
     * devuelve una matriz que corresponde a la sentencia
     * extraida, o falso si no quedan más filas.
     */
    function fetch_array( $ref = 0 )
    {
        $this->sql_log .= 'fetch_array:';
        if( isset( $this->result[$ref] ) && !( empty( $this->result[$ref] ) ) )
        {
            Sql::edit_sql_log("ref: [$ref]");
            return @mysqli_fetch_assoc( $this->result[$ref]);
        }
        else
        {
            Sql::edit_sql_log("ref: [$ref] -> ERROR: Reference Not Found");
            return false;
        }
    }

    /**
     * Extrae todas las filas de resultado como una matriz y
     * devuelve una matriz con la totalidad de las filas que
     * corresponden a la sentencia extraida, o falso si no se
     * encontraron filas.
     */
    function fetch_all( $ref = 0 )
    {
        $this->sql_log .= 'fetch_all:';
        $result=array();
        if( isset( $this->result[$ref] ) && !( empty( $this->result[$ref] ) ) )
        {
            while( $rw = @mysqli_fetch_assoc( $this->result[$ref] ) )
            {
                $result[] = $rw;
            }
            Sql::edit_sql_log("ref: [$ref]");
            return $result;
        } else
        {
            Sql::edit_sql_log("ref: [$ref] -> ERROR: Reference Not Found");
            return false;
        }
    }

    /**
     * Devuelve el número de filas de un resultado
     */
    function num_rows( $ref = 0 )
    {
        $this->sql_log .= 'num_rows:';
        if( isset( $this->result[$ref] ) && !( empty( $this->result[$ref] ) ) )
        {
            Sql::edit_sql_log("ref: [$ref]");
            return @mysqli_num_rows($this->result[$ref] );
        } else
        {
            Sql::edit_sql_log("ref: [$ref] -> ERROR: Reference Not Found");
            return false;
        }
    }

    /**
     * Devuelve el número de filas afectadas de la última operación MySQL
     */
    function affectedRows( $ref = 0 )
    {
        $this->sql_log .= 'affectedRows:';
        if( isset( $this->result[$ref] ) && !( empty( $this->result[$ref] ) ) )
        {
            Sql::edit_sql_log("ref: [$ref]");
            return @mysqli_affected_rows( self::$db_link );
        } else
        {
            Sql::edit_sql_log("ref: [$ref] -> ERROR: Reference Not Found");
            return false;
        }
    }

    /**
     * Devuelve el identificador generado en la última llamada a INSERT
     */
    function insert_id()
    {
        return @mysqli_insert_id(self::$db_link);
    }

    /**
     * Libera la memoria del resultado
     */
    function free_result( $ref = 0 )
    {
        $this->sql_log .= 'free_result:';
        if( isset( $this->result[$ref] ) && !( empty( $this->result[$ref] ) ) )
        {
            if( @mysqli_free_result( $this->result[$ref] ) )
                $clear = true;
            unset( $this->result[$ref] );
            if( isset( $clear ) )
            {
                Sql::edit_sql_log("ref: [$ref]");
                return true;
            } else
            {
                Sql::edit_sql_log("ref: [$ref] -> ERROR: Unable to free result");
                return false;
            }
        } else
        {
            Sql::edit_sql_log("ref: [$ref] -> ERROR: Reference Not Found");
            return false;
        }
    }

    /**
     * Cierra el enlace con MySQL
     */
    static function close()
    {
        @mysqli_close(self::$db_link);
    }

    /**
     * Verifica que $dato sea valido para almacenarse en el $campo de $tabla<br>
     * Devuelve un mensaje de error cuando $dato no puede ser almacenado en $campo.<br>
     * $tabla puede contener ademas el nombre de la DB que contiene a la tabla.
     */
    function validField($tabla="", $campo="", $dato="")
    {
        $this->query("SELECT $campo FROM $tabla",'valid');
        $fields = mysqli_num_fields($this->result['valid']);
        $type   = $this->field_type ($this->result['valid'],0);
        $name   = $this->field_name ($this->result['valid'],0);
        $len    = $this->field_len ($this->result['valid'],0);
        $flags  = $this->field_flags ($this->result['valid'],0);        

        // 0 => texto
        // 1 => entero
        // 2 => decimal
        // 3 => fecha

        if ($type=="string") {
            $tipo=0;
        } elseif ($type=="blob") {
            $tipo=0;
        } elseif ($type=="int") {
            $tipo=1;
        } elseif ($type=="decimal") {
            $tipo=1;
        } elseif ($type=="integer") {
            $tipo=1;
        } elseif ($type=="long") {
            $tipo=1;
        } elseif ($type=="double") {
            $tipo=2;
        } elseif ($type=="timestamp" || $type=="date" || $type=="datetime") {
            $tipo=3;
        } elseif ($type=="real") {
            $tipo=2;
        }

        //Codigos para campos de textos
        //10 => No es texto
        //11 => es nulo y no debería serlo
        //12 => Supera el largo permitido
        $errores=0;
        if ($tipo==0){
           if (is_string($dato)){
              if (stristr($flags,"not_null")) {
                 if (empty($dato)) {
                     $leyenda="El campo '$campo' está vacio. Es necesario que lo complete.<br>";
                     $errores=11;
                 }
                 if ($errores==0){
                     if ($len<strlen(trim($dato))) {
                         $leyenda="El contenido de '$campo' supera el largo permitido.<br>";
                         $errores=12;
                     }
                 }
              }


           } else {
               $leyenda="El contenido de '$campo' debería ser texto.<br>";
               $errores=10;
           }
        }

        //Codigos para campos de enteros
        //20 => No es entero
        //21 => es nulo y no debería serlo
        //22 => Supera el largo permitido
        if ($tipo==1){
           if (settype($dato,"integer")){
              if (stristr($flags,"not_null")) {
                 if (empty($dato)) {
                     $leyenda="El campo '$campo' está vacio. Es necesario que lo complete.<br>";
                     $errores=21;
                 }
                 if ($errores==0){
                     if ($len<strlen(trim($dato))) {
                         $leyenda="El contenido de '$campo' supera el largo permitido.<br>";
                         $errores=22;
                     }
                 }
              }

           } else {
               $leyenda="El contenido de '$campo' debería ser un número entero.<br>";
               $errores=20;
           }
        }

        //Codigos para campos decimales
        //30 => No es numero
        //31 => es nulo y no debería serlo
        //32 => Supera el largo permitido
        if ($tipo==2){
           if (settype($dato,"double")){
              if (stristr($flags,"not_null")) {
                 if (empty($dato)) {
                     $leyenda="El campo '$campo' está vacio. Es necesario que lo complete.<br>";
                     $errores=31;
                 }
                 if ($errores==0){
                     if ($len<strlen(trim($dato))) {
                         $leyenda="El contenido de '$campo' supera el largo permitido.<br>";
                         $errores=32;
                     }
                 }
              }

           } else {
               $leyenda="El contenido de '$campo' debería ser un número con decimales.<br>";
               $errores=30;
           }
        }

        //Codigos para campos de fecha
        //40 => No es el formato correcto
        //41 => es nulo y no debería serlo
        if ($tipo==3)
        {
            if (stristr($flags,"not_null"))
            {
                if (empty($dato))
                {
                    $leyenda="El campo '$campo' está vacio. Es necesario que lo complete.<br>";
                    $errores=41;
                }
                elseif (date('U',strtotime($dato)) < 1)
                {
                    $leyenda="La fecha en el campo '$campo' está mal escrita.<br>";
                    $errores=40;
                }
            }
            else
            {
                if ($dato && ($u = date('U',strtotime($dato)) < 1))
                {
                    $leyenda="La fecha en el campo '$campo' está mal escrita.<br>";
                    $errores=40;
                }
            }
        }
        return $leyenda;
    }

    /**
      * Ejecuta un query (SELECT) y devuelve los datos segun el tipo de consulta
      * @access private
      * @param string
      * @param int
      *   0 = Devuelve todas las filas.
      *   1 = Devuelve una fila
      *   2 = Devuelve el campo que se encuentra en la columna $field;
      * @param int
      * @param int
      * @return array
      *
    */
    function exec_select($query , $type =2 , $field = 0 , $ref = 0)
    {
        $this->sql_log .= 'query:';
        $this->result[$ref] = @mysqli_query(self::$db_link, $query ) or die('Error: <b>Database Query Error</b><hr><i>-- '.$query.' --</i><hr>'.mysqli_error(self::$db_link));
        Sql::edit_sql_log("q: [$query] ref: [$ref]");

        switch ($type)
        {
            case 0 :
                $return = $this->fetch_all($ref);
                break;
            case 1 :
                $return = $this->fetch_array($ref);
                break;
            case 2 :
                $data = $this->fetch_array($ref);
                if(!empty($data)&& is_numeric($field))
                {
                    reset($data);
                    for($i=0; $i<=$field; $i++)
                    {
                       $return = current($data);
                       next($data);
                    }
                }
                else
                {
                    $return = $data[$field];
                }
                break;
        }
        return $return;
    }

    public static function describe($table,$dbRefer='')
    {
        $fields=null;
        if ($pos = strpos($table,'.'))
        {
            $db = substr($table,0,$pos);
            $table = substr($table,$pos+1,strlen($table));
        }
        else
        {
            $db = Sql::$tableList[$table];
            $aDb = explode (',',$db);
            if (count($aDb) > 1)
            {
                if (Sql::$dbList[Sql::$dbname][$table])
                    $db = Sql::$dbname;
                elseif ($dbRefer && in_array($dbRefer,$aDb))
                    $db = $dbRefer;
                else
                    die('ERROR - <b>Sql::describe('.$table.($dbRefer?','.$dbRefer:'').')</b> no puede definir la DDBB que contiene a la tabla <b>'.$table.'</b>. Las opciones de DDBB pueden ser: <b>'.$db.'</b>');
            }
         }

        //Si la tabla esta en cache devuelve la estructura
        if (isset(Sql::$tablesCache[$db.'.'.$table]) && is_array(Sql::$tablesCache[$db.'.'.$table]))
            return Sql::$tablesCache[$db.'.'.$table];

        $query = "DESCRIBE ".($db?$db.'.':'').$table;

        $rs = @mysqli_query(self::$db_link,$query) or die('Error: <b>Database Query.Explain Error</b><hr><i>-- '.$query.' --</i><hr>'.mysqli_error(self::$db_link));
        while ( $rw = mysqli_fetch_assoc($rs ) )
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
            preg_match( "/([^\(]+)\(([\d]+)\)?(.+)?/", $rw['Type'], $rval );

            $field['type'] = ( isset($rval[1])?$rval[1]:$rw['Type'] );
            $field['len'] = (isset($rval[2]) ? $rval[2] : '');
            if ( isset($rval[3]) )
                $field[trim( $rval[3] )] = 1;

            if (strtoupper($rw['Null'])=='NO')
                $field['null'] = 'NO';
            else
                $field['null'] = 'YES';

            $field['key'] = $rw['Key'];

            $fields[$rw['Field']] = $field;

        }

        //Guarda la info de la tabla en el cache
        Sql::$tablesCache[$db.'.'.$table] = $fields;
        return $fields;
    }

    static public function getQueryInfo($qry)
    {
        $qryInfo = null;
        if (preg_match('/FROM (.*?)[[:space:]]/is', $qry,$arr))
            $qryTables[$arr[1]] = $arr[1];

        $wrds = array('LEFT JOIN','RIGHT JOIN','INNER JOIN');
        foreach ($wrds as $wrd)
            if (preg_match_all('/'.$wrd.'(.*?) ON/is', $qry,$arr))
                if (is_array($arr))
                    foreach ($arr[1] as $table)
                        $qryTables[$table] = $table;

        foreach ($qryTables as $k => $v)
        {
            if ($pos = strpos($k,'.'))
            {
                $db = trim(substr($k,0,$pos));
                $table  = trim(substr($k,$pos+1,strlen($k)));
            }
            else
            {
                $db = self::$dbname;
                $table  = $k;
            }
            $table = trim($table);
            $db    = trim($db);

            $tables[$table]['db'] = $db;
            $qryInfo[$db][$table]=array();
        }

        if (preg_match('/SELECT (.*?) FROM/is', $qry,$arr))
        {
            // Eliminando la sentencia DISTINCT del query en caso que exista.
            $qryFields = explode(',',str_ireplace('distinct','',$arr[1]));
            foreach ($qryFields as $field)
            {
                $field = trim($field);
                $name = null;
                $alias = null;
                if ($pos = strpos($field,'.'))
                {
                    $table = trim(substr($field,0,$pos));
                    $name  = trim(substr($field,$pos+1,strlen($field)));
                }
                else
                {
                    $table = trim('none');
                    $name  = trim(substr($field,$pos,strlen($field)));
                }
                if ($pos = strpos($name,' '))
                {
                    $alias = trim(substr($name,$pos,strlen($name)));
                    $name = trim(substr($name,0,$pos));
                }
                else
                {
                    $alias = $name;
                }
                if ($alias != $name)
                {
                    $fieldAlias[$name] = $alias;
                }
                $db = $tables[$table]['db'];

                $qryInfo[$db][$table][$name]=$alias;

            }

        }
        return $qryInfo;
    }

    static public function getQueryTables($qry)
    {
        $tables = null;
        $rs = mysqli_query(self::$db_link,'EXPLAIN '.$qry) or die('Error: <b>Database Query.Explain Error</b><hr><i>-- '.$query.' --</i><hr>'.mysqli_error(self::$db_link));
        while ( $rw = mysqli_fetch_assoc($rs ) )
        {
            $tables[$rw['table']] = array();
        }
        return $tables;
    }

    function field_name($result,$key)
    {
        $table_info = mysqli_fetch_field_direct($result, $key);
        return $table_info->name;
    }

    function field_len($result,$key)
    {
        $table_info = mysqli_fetch_field_direct($result, $key);
        return $table_info->length;
    }

    function field_type($result,$key)
    {
        $table_info = mysqli_fetch_field_direct($result, $key);

        $type_id = mysqli_fetch_field_direct( $result, $field_offset)->type;
        $arrType = array();
        $constants = get_defined_constants(true);
        foreach ($constants['mysqli'] as $c => $n)
         if (preg_match('/^MYSQLI_TYPE_(.*)/', $c, $m))
          $arrType[$n] = $m[1];
        $resultType = array_key_exists( $type_id, $arrType ) ? $arrType[$type_id] : NULL;

        //Fonzando el Type para que se ajuste al sistema
        $arrType[246] = 'decimal';
        $arrType[252] = 'text';
        $arrType[3]   = 'int';

        //el Key = 1 puede ser tinyint o char
        if ($table_info->type == 1)
        {
            $flags = mysqli_field_flags($result,$key);
            if (stristr($flags,"char"))
                $arrType[1]   = 'char';
            else
                $arrType[1]   = 'int';
        }

        if (isset($arrType[$table_info->type]))
            $type = strtolower($arrType[$table_info->type]);

        return $type;
    }

    function field_flags($result,$key)
    {
         $table_info = mysqli_fetch_field_direct($result, $key);
   
        $flags = array();
        $constants = get_defined_constants( true );
        $returnFlags = '';
        foreach ($constants['mysqli'] as $c => $n)
        {
            if (preg_match('/MYSQLI_(.*)_FLAG$/', $c, $m))
                if (!array_key_exists($n, $flags))
                    $flags[$n] = $m[1];
            $flags_num = $table_info->flags;
            $match = array();
            foreach ($flags as $n => $t)
                if ($flags_num & $n)
                    $match[] = $t;
              $returnFlags = implode(' ', $match);
              $returnFlags = str_replace( 'PRI_KEY', 'PRIMARY_KEY', $returnFlags);
              $returnFlags = strtolower($returnFlags);
        }

        return $returnFlags;
    }


}
?>