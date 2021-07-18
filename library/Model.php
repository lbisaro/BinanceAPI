<?php
include_once LIB_PATH."Sql.php";
include_once LIB_PATH."functions.php";

/**
*
* Mediante esta clase se obtienen y almacenan datos referentes a las tablas
* que conforman los modelos de negocio del sistema.
* Entre los datos que se pueden obtener, se encuentran:
* Titulos de campos. Model::getFields(field='')
* Datos obtenidos de la base de datos. Model::get(field='')
* Arrays en los que se almacena titulo y dato. Model::getInfo(field)
*
* Además permite formatear o agregar los titulos de los campos y sus datos mediante:
* <ul>
* <li>Model::formatFields()
* <li>Model::formatData()
* </ul>
* <i>Estas funciones deben realizarse en la clase que extiende de model.</i>
*
* La clase permite cargar los datos a partir de un Id o bien de unn array
* que debe coincidir con un fetchArray tal como lo trae un query de la tabla,
* mediante las siguientes funciones:
* <ul>
* <li>Model::load(id)</li>
* <li>Model::set(array(fetch)) [array('field'=>'value')]
* </ul>
*
* Para agregar/actualizar registros en las tablas, se utilizan los metodos:
* <ul>
* <li>Model::valid()</li>
* <li>Model::save()</li>
* <li>Model::tableSave()</li>
* <li>Model::tableInsert()</li>
* <li>Model::tableUpdate()</li>
* </ul>
*
* En la clase que extiende de Model se debe realizar el metodo:
* <ul>
* <li>$this->validReglasNegocio()
* </ul>
* Este metodo es ejecutado por Model::valid() para complementar la validacion de los datos instanciados.
*
* NOTA:Todos los modelos de negocio deben extender desde esta clase
*
* @package myLibrary
*/
abstract class Model extends Sql
{
    /**
    * Datos que se cargaran desde la DB mediante this->load()
    */
    protected $data = array();

    /**
    * Campos que se cargaran desde la DB con propiedades
    */
    protected static $fields = array();

    /**
    * En este atributo se almacena la cantidad de registros
    * encontrados luego de realizar un Model::getDataSet(), con el parametro
    * calcFoundRows activado.
    *
    * Uso:
    * Luego de ejecutar el metodo Model::fetchQuery, se podrá obtener
    * el total de registros mediante Model::getFoundRows()
    *
    */
    private $foundRows = null;

    /**
    * Array en el que se almacena la inforacion referente a las tablas que
    * conforman cada clase
    */
    public $tableLinks = array();

    /**
    * El constructor carga los datos de los campos mediante Model::setup()
    * y en caso de recibir el parametro $id, carga los datos desde la DB mediante
    * Model::load([$id]).
    *
    * Las clases que extiendan de Model, deberan tener definidas varios parametros
    * static protected de acuerdo a lo siguiente:
    *
    * <ul>
    *
    * <li>static protected $db       = Nombre de la Base de datos en la que se encuentra la tabla. </li>
    * <li>static protected $table    = Nombre de la tabla, desde la cual se cargaran los campos y datos.</li>
    *
    * <li>static protected $idName   = Nombre del id al que se hace referencia, para obtebner los datos de la [db.]tabla.</li>
    *
    * <li>protected $loadQuery       = Query Sql medienta el cual se realizara la carga de datos de Model::load() y Model::getDataset()
    *
    * <li>protected $filterQuery     = Sentencia WHERE correspondiente al Query Sql que se anexara en todos los querys ejecutados,
    *                                  al realizar la carga de datos de Model::load() y Model::getDataset().
    *                                  La inclusion del filtro será de la siguiente manera: WHERE [$filterQuery] AND resto del where
    *                                  proporcionado por Model::load([$id]) y Model::getDataset([$where],....)
    *
    * <li>static protected $replacedFieldNames = (Opcional) Nombre de los campos a reemplazar dentro del objeto instanciado.
    * El contenido de esta constante debe ser un string separando por comas el nombre original del que se quiere tener como
    * atributo que formara parte de $this->data, y luego separando por ; los diferentes campos: <br/>
    * <b>Ej.: static protected $replacedFieldNames    = 'nombre,nomUsuario;id,idUsuario';</b><br/>
    * De esta manera el objeto tendra un campo denominado nomUsuario y otro idUsuario.<br/>
    * <p>La necesidad de definir este parametro esta basada en que cuando existen clases heredadas que extienden desde model, y
    * se heredan dos nombres de campos iguales, al no definir esta constante, se pisan entre si los datos de a instancia,
    * que estaran contenidos dentro de $this->data().</p>
    *
    * <p>Dado que estas constantes son heredadas al realizar la herencia entre clases,
    * se deberan redeclarar en caso que asi se requiera.</p>
    * </li>
    *
    * <li>static protected $excludedFields = (Opcional) Nombre de los campos que no deberan ser excluidos
    * en el objeto instanciado.
    * El contenido de esta constante debe ser un string separando por comas con los nombres
    * de los campos a excluir.
    * <b>Ej.: static protected $excludedFields = 'campo1,campo2'; </b><br/>
    * De esta manera el objeto no tendra los campos: campo1 y campo2.<br/>
    *
    * <p>Dado que estas constantes son heredadas al realizar la herencia entre clases,
    * se deberan redeclarar en caso que asi se requiera.</p>
    * </li>
    *
    * <li>protected $loadQuery = Query MySql mediante el cual se cargaran los datos mediante
    * Model::load(), o Model::getDataset().
    * </li>
    *
    * </ul>
    *
    * De lo contrario, el constructor no realizara la instancia de la
    * clase saliendo del sistema por tratarse de un error de desarrollo.
    *
    *
    *
    * @param int $id -> opcional
    */
    function __Construct($id = '')
    {
        $this->setup();

        if ($id)
            $this->load($id);

    }

    /**
     * Devuelve la lista de campos a mostrar en el query
     * seleccionando los campos a traer, con los nombres
     * reemplazados por self::$replacedFieldNames
     *
     * Ej. del string retornado: tabla1.*, tabla2.campo1,tabla2.capo2
     *
     * @return string
     */
    private function getMysqlFields()
    {
        $mysqlFields ="";
        $tLinks = $this->getTableLinks();
        if ( !empty( $tLinks ) )
        {
            // Armando los campos a traer, con los nombres reemplazados por self::$replacedFieldNames
            foreach ($tLinks as $className => $info)
            {
                $tableRFN = $info['RFN'];
                $tableFN  = $info['fields'];

                if (empty($tableRFN) && empty($tableEF))
                {
                    $mysqlFields .= ($mysqlFields?" , ":"").$info['table'].".* ";
                }
                else
                {
                    foreach ($tableFN as $field)
                    {
                        if (!$tableFN[$field])
                        {
                            $mysqlFields .= ($mysqlFields?" , ":"").$info['table'].".".$field;
                            if ($tableRFN[$field])
                                $mysqlFields .= " ".$tableRFN[$field];
                        }
                    }
                }
            }
        }
        return $mysqlFields;
    }

    /**
    * Carga los datos desde la DB, coincidentes con el Id enviado como parametro.
    *
    * @param int $id
    * @return bool
    */
    public function load($id)
    {
        $tLinks = $this->getTableLinks();
        if ( !empty( $tLinks ) )
        {
            $mysqlFields = $this->getMysqlFields();

            foreach ( $tLinks as $className => $info)
            {
                $tableName = $info['dbTable'];
                if ( !$iniTable )
                {
                    $iniTable = $tableName;
                    $iniId = $info['idName'];
                    $qry = "SELECT ".$mysqlFields." FROM " . $tableName;
                }
                else
                {
                    $qry .= " LEFT JOIN " . $tableName . " ON " . $info['table'] . "." . $info['idName'] . " = " .$prevTable . "." . $info['idName'];
                }
                $prevTable = $info['table'];
            }
        }
        if ($this->loadQuery)
        {
            $qry = $this->loadQuery;
        }

        $qry .= " WHERE " .(($this->filterQuery)?$this->filterQuery:'1').' AND '. $iniTable . "." . $iniId . " = '" . $id. "' ";

        $data = $this->exec_select( $qry, 1 );

        if ( !empty( $data ) )
        {
            foreach ( $data as $key => $val )
            {
                $this->data[$key] = $val;
            }
            return true;
        }
        return false;
    }

    /**
    * Establece los datos de los campos enviados en el array.
    *
    * Si $array es un array, agrega o actualiza el array $this->data
    *
    * NOTA: se pueden agregar metodos a los objetos denominados:
    *
    * set + [Nombre del campo con la primera letra en mayusculas]
    *
    * Y en caso de existir, estos se ejecutaran dentro del metodo set del objeto.
    *
    * @param array $array -> Datos a establecer en la instancia. array('field'=>'value').
    * @return bool
    */
    public function set($array)
    {
        if ( is_array( $array ) )
        {
            foreach( $array as $key => $val )
            {
                $setFunct = 'set'.ucfirst($key);
                if (is_callable( array(get_class($this),$setFunct)))
                {
                    $this->$setFunct($val);
                }
                else
                {
                    if ($this->fields[$key]['type'] == 'datetime'||$this->fields[$key]['type']=='timestamp')
                    {
                        if(explode('/',$val)>1)
                            $val= strToDate($val,true);
                    }
                    elseif($this->fields[$key]['type'] == 'date')
                    {
                        if(explode('/',$val)>1)
                            $val= strToDate($val);
                    }
                    $this->data[$key] = $val;
                }

            }
            return true;
        }
        return false;
    }


    /**
    * La clase que extienda desde Model, puede realizar el metodo formatData()
    * con el formato a mostrar de los campos de $this->data
    *
    * Ejemplo: Agregar un campo de fecha que pueda visualizarse en un
    * formato de dia/mes/año normal, mientras que en el campo original
    * se mantendra almacenada la fecha en el formato que entiende la DB.
    *
    * <code>
    * <?php
    *
    *
    * function formatData()
    * {
    *     $arr['fecha'] = formatearFecha($this->data['fecha']);
    *
    *     return $arr;
    * }
    *
    *
    * ?>
    * </code>
    *
    * @see Model::get() para obtener los datos con el formato establecido.
    * @return array
    */
    public function formatData()
    {
        return null;
    }

    /**
    * Devuelve el dato del campo indicado en el parametro field,
    * y en caso de no especificarse este, devuelve un array con
    * todos los campos y datos que conforman la instancia de la clase.
    *
    * NOTA: Previo a devolver los datos, la funcion ejecuta $this->formatData()
    *
    * @param string $field
    * @return array /string
    * @return array
    */
    public function get( $field = '' )
    {
        $dta = $this->data;
        if ($field && $this->fields[$field]['type'] && ($a = strpos('//-date-datetime-timestamp',strtolower($this->fields[$field]['type']))))
        {
            $dta[$field] = dateToStr($dta[$field],($this->fields[$field]['type']!='date'?true:false));
        }
        if ( is_array( $arr = $this->formatData() ) )
            $dta = array_merge( $dta, $arr);

        if ( $field )
        {
            if(!is_array($dta[$field]))
                return stripcslashes ($dta[$field]);
            else
                return $dta[$field];
        }


        return $dta;
    }

    /**
    * Carga lod datos de la/las tablas que componen la/s clase/s que
    * extienden de Model, a partir de la sentencia DESCRIBE table de MySQL.
    *
    * @return void
    */
    private function setup()
    {

        $classList = array();
        $className = get_class($this);
        while ($className && $className != 'Model')
        {
            array_push($classList,$className);
            $className = get_parent_class($className);
        }

        foreach  ( $classList as $className )
        {
            eval('$db = '.$className.'::$db;');
            $classConfig['db'] = $db;

            eval('$table = '.$className.'::$table;');
            $classConfig['table'] = $table;

            $classConfig['dbTable'] = ($db && Sql::$dbname!=$db?$db.'.':'').$table;

            eval('$idName = '.$className.'::$idName;');
            $classConfig['idName'] = $idName;

            $this->tableLinks[$className] = $classConfig;

            if ($classConfig['table'] && $classConfig['idName'])
            {
                $tableName = ($classConfig['db']?$classConfig['db'].'.':'').$classConfig['table'];
                $idName = $classConfig['idName'];
            }
            else
            {
                die("Error en los datos devueltos $className -> getConfig()");
            }

            eval('$replacedFieldNames = (property_exists("$className","replacedFieldNames")?'.$className.'::$replacedFieldNames:"");');
            $this->tableLinks[$className]['RFN']=array();
            if ($replacedFieldNames)
            {
                $rfn = explode(";",$replacedFieldNames);
                foreach($rfn as $it)
                {
                    $it = explode(",",$it);
                    $this->tableLinks[$className]['RFN'][$it[0]] = $it[1];
                }
            }
            $tableRFN = $this->tableLinks[$className]['RFN'];

            eval('$excludedFields = (property_exists("$className","excludedFields")?'.$className.'::$excludedFields:"");');
            $this->tableLinks[$className]['EF']=array();
            if ($excludedFields)
            {
                $ef = explode(",",$excludedFields);
                foreach($ef as $excludeField)
                {
                    $this->tableLinks[$className]['EF'][$excludeField] = true;
                }
            }
            $tableEF = $this->tableLinks[$className]['EF'];

            $this->query( "DESCRIBE " . $tableName, 'fields' );
            $i=1;
            while ( $rw = $this->fetch_array( 'fields' ) )
            {
                if (!$tableEF[$rw['Field']])
                {
                    $field=array();
                    $field['field'] = ($tableRFN[$rw['Field']] ? $tableRFN[$rw['Field']]:$rw['Field']);
                    $field['table_field'] = $rw['Field'];


                    if ( substr( $field['field'], 0, 2 ) != 'id' )
                        $field['title'] = ucfirst( $field['field'] );
                    else
                        $field['title'] = 'id' . ucfirst( substr( $field['field'], 2 ) );

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

                    $field['type'] = ( $rval[1]?$rval[1]:$rw['Type'] );
                    $field['len'] = $rval[2];
                    if ( $rval[3] )
                        $field[trim( $rval[3] )] = 1;

                    if (strtoupper($rw['Null'])=='NO')
                        $field['null'] = 'NO';
                    else
                        $field['null'] = 'YES';

                    /** Cargando los datos de los campos */
                    $this->fields[$field['field']] = $field;

                    /** Cargando los campos de la tabla */
                    if ($field['field'] == $idName)
                        $key='ID';
                    else
                        $key=$i++;

                    $this->tableLinks[$className]['fields'][$key]=$rw['Field'];
                }
            }

            $className = get_parent_class( $className );
        }


        if ($this->loadQuery && $qryInfo = Sql::getQueryInfo($this->loadQuery))
        {
            foreach ($qryInfo as $db => $tables)
            {
                foreach ($tables as $table => $fields)
                {

                    $dsc = sql::describe($table,$classConfig['db']);

                    if ($fields['*'] == '*')
                    {
                        foreach ($dsc as $field => $info)
                        {
                            foreach ($dsc[$field] as $k => $v)
                            {
                                $this->fields[$field][$k] = $v;
                                $this->fields[$field]['table_field']=$field;
                            }
                        }
                    }
                    else
                    {
                        foreach ($fields as $field => $info)
                        {
                            foreach ($dsc[$field] as $k => $v)
                            {
                                $this->fields[$info][$k] = $v;
                                $this->fields[$info]['table_field']=$field;
                            }
                        }
                    }

                }
            }
        }
    }


    /**
    * Este metodo debe ser realizado en la clase que extiende de Model
    * con el fin de modificar o agregar los nombres a mostrar como titulo
    * de los datos de la instancia de la clase.
    * El metodo realizado debe devolver un array con los nombres de los campos.
    *
    * El metodo es util cuando el nombre del campo que se desea mostrar es diferente
    * al de la DB, o bien cuando el nombre del campo depende de algun dato de la instancia.
    *
    * <code>
    * <?php
    *
    * function formatFields()
    * {
    *     $arr['cod_int_art'] = 'Codigo interno del articulo';
    *
    *     if ($this->data['estado'] != 'Entregado')
    *           $arr['fecha_envio'] = 'Fecha prevista de entrega';
    *     else
    *           $arr['fecha_envio'] = 'Fecha de entrega realizada';
    *
    *     return $arr;
    * }
    *
    * ?>
    * </code>
    *
    * @see Model::getFields() para obtener los nombres de campos con el formato establecido.
    * @return array
    */
    public function formatFields()
    {
        return null;
    }

    /**
    * Devuelve el titulo del campo indicado en el parametro field,
    * y en caso de no especificarse este, devuelve un array con
    * todos los campos de la/s tabla/s que conforman la instancia de la clase.
    *
    * NOTA: Previo a devolver los datos, la funcion ejecuta $this->formatFields()
    *
    * @param string $field
    * @return array /string
    */
    public function getFields( $field = '' )
    {
        $className = get_class( $this );
        while ($className && $className != 'Model' )
        {
            eval('$arr = '.$className.'::formatFields() ;');

            if ( is_array( $arr ) )
            {
                foreach ( $arr as $key => $val )
                {
                    $this -> fields[$key]['title'] = $val;
                    if (!$this -> fields[$key]['field'])
                        $this -> fields[$key]['field'] = $key;
                }
            }
            $fld = $this -> fields;


            $className = get_parent_class($className);
        }

        if ( $field )
            return $fld[$field]['title'];

        return $fld;
    }
    /*Agregado poir compatibilidad*/
    function getLabel($field)
    {
        return $this->getFields($field);
    }

    public function getTableFields()
    {
        $tablesFields = array();
        $tLinks = $this->getTableLinks();
        if ( !empty( $tLinks ) )
            foreach ($tLinks as $className => $info)
                $tablesFields[$info['dbTable']]  = $info['fields'];

        return $tablesFields;
    }

    /**
    * Devuelve un array con los errores y una referencia al modelo e instancia.
    *
    * @return html
    */
    public function getErrLog($reset=true)
    {
        $errLog = parent::getErrLog();
        if ( !is_array( $errLog ) )
        {
            $this->addErr("Class: " . get_class( $this ));
        }
        return parent::getErrLog($reset);
    }

    /**
     * Devuelve el query formado por:
     *
     * SELECT * FROM [tablas de la instancia] WHERE $where ORDER BY $order LIMIT $limit
     *
     * @param string $where
     * @param string $order
     * @param string $limit
     * @return sqlQuery
     */
    public function getQuery($where = '', $order = '', $limit = '', $calcFoundRows = false)
    {
        $tLinks = $this->getTableLinks();

        if ($calcFoundRows)
            $calcFoundRows = " SQL_CALC_FOUND_ROWS ";
        else
            $calcFoundRows = "";

        if ($this->loadQuery)
        {
            $qry = $this->loadQuery;
            $qry = 'SELECT '.$calcFoundRows. substr($qry,7);
        }
        else
        {
            $mysqlFields = $this->getMysqlFields();
            foreach ( $tLinks as $className => $info )
            {
                $tableName = ($info['db']?$info['db'].'.':'').$info['table'];
                if ( !$iniTable )
                {
                    $iniTable = $tableName;
                    $iniId = $info['idName'];
                    $qry = "SELECT $calcFoundRows $mysqlFields FROM " . $tableName;
                }
                else
                {
                    $qry .= " LEFT JOIN " . $tableName . " ON " . $info['table'] . "." . $info['idName'] . " = " .$prevTable . "." . $info['idName'];
                }
                $prevTable = $info['table'];
            }
        }
        if ( $where )
            $qry .= " WHERE ".(($this->filterQuery)?$this->filterQuery:'1').' AND '." $where";
        if ( $order )
            $qry .= " ORDER BY $order";
        if ( $limit )
            $qry .= " LIMIT $limit";

        return $qry;
    }


    /**
    * Ejecuta un query armado en funcion de los parametros recibidos y this->table.
    *
    * Devuelve un array con todo el resultado del query ejecutado.
    *
    * Formato del query:
    * SELECT [SQL_CALC_FOUND_ROWS] * FROM [tablas de la instancia] WHERE $where ORDER BY $order LIMIT $limit
    *
    * NOTA sobre el parametro calcFoundRows: En caso que se pase como true, luego de
    * ejecutar el fetchQuery, se podra obtener la cantidad total de registros, completos sin prestar atencion
    * al LIMIT, mediante el metodo Model::getFoundRows().
    *
    * @param string $where
    * @param string $order
    * @param string $limit
    * @param string $query
    * @param bool calcFoundRows
    * @return fetchArray
    * @see Model::getFoundRows()
    */
    public function getDataSet( $where = '', $order = '', $limit = '', $calcFoundRows = false)
    {
        if ($qry = $this->getQuery($where, $order, $limit, $calcFoundRows))
        {
            Sql :: query( $qry );
            $fetch = Sql :: fetch_all();
            if ($calcFoundRows)
            {
                Sql::query( "SELECT FOUND_ROWS() foundRows" );
                $fr = Sql::fetch_array();
                $this->foundRows = $fr['foundRows'];
            }
            else
            {
                $this->foundRows = null;
            }
            return $fetch;
        }
        return null;
    }


    /**
    *
    */
    public function getFoundRows()
    {
        return $this->foundRows;
    }

    /**
    * Devuelve una tabla html con el contenido del array this->data,
    * cuando se hace un echo o print del objeto instanciado.
    *
    * Ver referencia en php.net sobre metodos magicos.
    *
    * @link ar2.php.net/manual/es/language.oop5.magic.php
    *
    * Para todas las clases que extiendan de Model, se podran
    * mostrar sus atributos (Contenidos en el array this->data)
    * mediante un print o echo, debido a que dentro de un comando
    * de este tipo, el objeto se comportara como una cadena (string)
    * que contiene la cadena que devuelve esta funcion.
    *
    * @return strng
    */
    function __toString()
    {
        return arrayToTable( $this->data );
    }

    /**
    * Devuelve un array que contiene los nombres de las clases desde las que se extiende
    * y para cada una, los nombres de las tablas e id, mediante las cuales se generan
    * las consultas, campos y datos de las tablas que confirman al objecto que extiende
    * de Model, incluyendo los que extiendan de otras clases Model
    *
    * @return array -> array('class'=>'','table'=>'','id'=>'')
    */
    protected function getTableLinks()
    {
        return $this->tableLinks;
    }

    /**
    * Devuelve un array con los keys label y data,
    * que contienen el titulo y el dato respectivamente, correspondientes
    * al campo solicitado mediante el parametro field.
    *
    * En caso de no encontrar el titulo del campo o el dato devuelve null
    *
    * @param mixed $field
    * @return array ('label','data')
    */
    public function getInfo( $field )
    {
        if (!$f = $this->getFields($field))
            $f = 'ERROR: No existe field='.$field;

        if (!$d = $this->get($field))
            $d = 'Sin especificar';

        return array( 'label' => $f, 'data' => nl2br($d) , 'id' => $field);
    }

    /**
    * Devuelve un array con los keys label e data, que contienen el
    * titulo y el tag input/textarea respectivamente, correspondientes
    * al campo solicitado mediante el parametro field, y el parametro tipo.
    *
    * En caso de no encontrar el titulo del campo devuelve null.
    *
    * @param string $field
    * @param string $tipo (input [default], textarea)
    * @return array ('label','data')
    */
    public function getInput( $field, $tipo = '' ,$att = array())
    {
        if (!$att['id'])
            $att['id'] = $field;

        if ( !$fld = $this -> fields[$field] )
            return array( 'label' => $this->getFields( $field ), 'input' => 'NO ESPECIFICADO' );

        $idNombre = $att['id'];

        if ($fld['len'] > 100 && !$tipo)
            $tipo = 'textarea';

        if ($fld['type']=='datetime' || $fld['type']=='timestamp')
        {
            $fecha_hora = $this->get( $fld['field'] );

            if (is_string($fecha_hora)) {
                $fecha = substr($fecha_hora,0,10);
                $hora  = substr($fecha_hora,strlen($fecha_hora)-5,5);
            } else {
                $fecha = null;
                $hora  = null;
            }
            $input = Html :: getTagInputFecha( $idNombre.'_f', $fecha);
            $input .= ' '.Html :: getTagInputHora( $idNombre.'_h', $hora);

        }
        elseif ($fld['type']=='date')
        {
            $fecha = $this->get( $fld['field'] );
            $input = Html :: getTagInputFecha( $idNombre, $fecha);
        }
        elseif ( $tipo == 'textarea' || ( !$tipo && $fld['type'] == 'text' ) )
        {
            $input = '<textarea ';
            $input .= ' name="' . $fld['field'] . '" ';
            $input .= ' id="' . $fld['field'] . '" ';
            $input .= ' rows="'.($att['rows']?$att['rows']:'6').'" cols="'.($att['cols']?$att['cols']:'80').'" >';

            $input .= $this->get( $fld['field'] );

            $input .= '</textarea>';
        }
        elseif ( !$tipo || $tipo == 'input' )
        {
            $addAttr = array();
            if ( $fld['len'] )
            {
                $addAttr['maxlength'] = $fld['len'];
                $addAttr['size'] = ($fld['len'] > 100 ? 80:( $fld['len'] + 2 ));
            }
            elseif ( $fld['type'] == 'text' )
            {
                $addAttr['maxlength'] = '200';
                $addAttr['size'] = '50';
            }

            $addAttr = $addAttr+$att;

            $input = Html :: getTagInput( $idNombre, $this->get( $fld['field'] ), null, $addAttr );
        }
        elseif ($this->data[$field])
        {
            $input = Html :: getTagInput( $idNombre, $this->get( $fld['field'] ), null, $addAttr );
        }

        return array( 'label' => $this->getFields( $field ), 'data' => $input ,'id'=>$field);
    }

    /**
    * Devuelve unnicamente el key data, provisto por el metodo $this->getInput(),
    * utilizando el mismo prototipo.
    *
    * @return -> Campo editable.
    */
    public function getInputField( $field, $tipo = '' ,$att = array())
    {
        $arr = $this->getInput($field , $tipo , $att);
        return $arr['data'];
    }

    /**
    * Valida que el parametro $fieldValue sea un dato valido
    * para almacenarse en el campo $fieldName
    *
    * Devuelve true si el dato es valido, y en caso contrario
    * devuelve false, y agrega al errorLog del Model una referencia
    * al motivo por el cual el dato no es valido.
    * Ver $this->getErrLog(), (Metodo eredado de ErrorLog)
    *
    * @param string $fieldName
    * @param mixed $fieldValue
    * @return bool
    */
    public function validField($fieldName="",$fieldValue="",$dummy="")
    {
        if ($this -> fields[$fieldName]['type'])
        {
            $fd = $this->fields[$fieldName];

            $fieldName = trim($fieldName);
            $fLabel = '<b>'.$this->getFields($fieldName).'</b>';

            if ($fd['unsigned'] && $fieldValue < 0)
            {
                $errs[]='El campo '.$fLabel.' no puede tener signo. [Ref:'.$fieldValue.']';
            }

            /*
            if ($fd['null']=='NO' && empty($fieldValue))
                $errs[]='El campo '.$fLabel.' no puede estar vacio. null['.$fd['null'].']';
            */

            if ($fd['len']>0 && strlen($fieldValue)>$fd['len'])
                $errs[]='El campo '.$fLabel.' no puede exceder de '.$fd['len'].' caracteres. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser numericos */
            if ($fieldValue && strpos('//-bigint-mediumint-smallint-dec-decimal-float-int-integer-long-double',strtolower($fd['type'])) && !is_numeric($fieldValue))
                $errs[]='El campo '.$fLabel.' debe ser un numero';

            /* Valida campos que debe texto */
            elseif ($fieldValue && strpos('//-varchar-text',strtolower($fd['type'])) && !is_string($fieldValue))
                $errs[]='El campo '.$fLabel.' debe ser texto.';

            /* Valida campos que debe ser fecha */
            elseif ($fieldValue && $fd['type']=='date' && !checkDbDateTime($fieldValue) && $fieldValue != '0000-00-00')
                $errs[]='El campo '.$fLabel.' contiene una fecha erronea. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser fecha y hora*/
            elseif ($fieldValue && $fd['type']=='time' && !checkDbTime($fieldValue))
                $errs[]='El campo '.$fLabel.' contiene una hora erronea. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser fecha y hora*/
            elseif ($fieldValue && $fd['type']!='time' && $fd['null']=='NO' && strpos('//-datetime-timestamp',strtolower($fd['type'])) && !checkDbDateTime($fieldValue) && $fieldValue != '0000-00-00')
                $errs[]='El campo '.$fLabel.' contiene una ['.$fd['type'].'] fecha/hora erronea. [Ref:'.$fieldValue.']';

            if(strpos('//-date-datetime-timestamp',strtolower($fd['type'])) && $fd['null']!='NO' && !$this->data[$fieldName])
                $this->data[$fieldName]= null;

        }
        else
        {
            $errs[]='El campo <b>'.($fLabel?$fLabel:$fieldName).'</b> no existe como campo valido.';
        }
        if (empty($errs))
            return true;

        foreach ($errs as $k => $v)
            $errs[$k] = $v.' <em>[invalid DB field]</em> ';

        $this->addErr($errs);

        return false;
    }

    /**
    * Valida que todos los datos que tiene asignados la instancia
    * al momento de ejecutar este metodo sean validos. Este metodo se realiza
    * por medio de Model::validField(field,value).
    *
    * Devuelve true si los dato de la instancia son validos, y en caso contrario
    * devuelve false, y agrega al errorLog del Model una referencia para cada dato
    * en base al motivo por el cual cada dato no es valido.
    * Ver $this->getErrLog(), (Metodo eredado de ErrorLog)
    *
    * @return bool
    */
    public function valid()
    {
        $err=0;

        //Se prepara un array con todos los campos idName para que no se validen si estan vacios.
        $IDs=array();
        $tLinks = $this->getTableLinks();
        if ( !empty( $tLinks ) )
            foreach ($tLinks as $className => $info)
                $IDs[$info['idName']] = true;

        $tLinks = $this->getTableLinks();
        if ( !empty( $tLinks ) )
        {
            foreach ($tLinks as $className => $info)
            {
                foreach ($info['fields'] as $key => $field)
                {
                    // Valida el contenido de los campos que no son ID.
                    if ($info['RFN'][$field])
                        $field = $info['RFN'][$field];
                    if (!$IDs[$field] && !$this->validField($field,$this->data[$field]))
                        $err++;
                }
            }
        }

        /**
        * Validando las reglas de negocio
        */
        if (!$this->validReglasNegocio())
            $err++;

        if ($err)
            return false;
        return true;
    }

    /**
    * Esta funcion debe ser realizada en el modelo que instancia de Model,
    * ya que de no ser asi, la $this->valid() devolvera un error al ejecutar
    * $this->valid, o $this->save().
    *
    * La funcion a realizar deberá devolver true en caso de ser correctos
    * todos los datos, o bien devolver false, y agregar
    * los errores detectados mediante $this->addErr('error').
    *
    * @return bool
    */
    public function validReglasNegocio()
    {
        $this->addErr('No se ha definido la funcion '.get_class($this).'::validReglasNegocio()');
        return false;
    }

    /**
     * Realiza un SQL UPDATE para la tabla $table, actualizando los valores
     * detallados en el array $fields, WHERE $idName=$idValue
     *
     * En caso de no poder realizar en query, devuelve false y registra
     * los errores detectados mediante $this->addErr()
     *
     * @param string $table
     * @param array $fields -> array('field'=>'value')
     * @param string $idName
     * @param string $idValue
     * @return bool
     */
    public function tableUpdate($table,$fields,$idName,$idValue)
    {
        if ($table && $idName && $idValue && is_array($fields))
        {
            foreach ($fields as $k => $v)
            {
                $set .= ($set?", ":"").$k." =".($v == null? 'null ':"'".addslashes($v)."' ");
            }

            $update = 'UPDATE '.$table.' SET '.$set.' WHERE '.$idName.' = '.$idValue;

            if ($this->query($update))
                return true;

            $this->addErr('Error ejecutando QUERY: '.$update);
        }
        $this->addErr('El parametro $fields deberia ser una array');
        return false;
    }

    /**
     * Realiza un SQL INSERT INTO $table, con los valores
     * detallados en el array $fields
     *
     * En caso de no poder realizar en query, devuelve false y registra
     * los errores detectados mediante $this->addErr()
     *
     * @param string $table
     * @param array $fields -> array('field'=>'value')
     * @return bool
     */
    public function tableInsert($table,$fields)
    {
        if ($table && is_array($fields))
        {
            foreach ($fields as $k => $v)
            {
                $fld .= ($fld?" , ":"").$k;
                $val .= ($val?" , ":"").($v===null?'null ':" '".addslashes($v)."' ");
            }
            $insert = 'INSERT INTO '.$table.' ( '.$fld.' ) VALUES ( '.$val.' ) ';
            if ($this->query($insert))
                return true;
            $this->addErr('Error ejecutando QUERY: '.$insert);
        }
        $this->addErr('El parametro $fields debería ser una array');
        return false;
    }

    /**
     * Registra los datos detallados en $fields, en la tabla $table,
     * realizando un INSERT cuando la instancia no tenga asignado un valor de ID,
     * o bien realizando un UPDATE.
     * El metodo es realizado mediante $this->tableInsert() o $this->tableUpdate()
     * respectivamente.
     *
     * En caso de no poder realizar la operación, devuelve false y registra
     * los errores detectados mediante $this->addErr()
     *
     * @param string $table
     * @param array $fields -> array('field'=>'value')
     * @return bool
     */
    public function tableSave($table,$fields,$tableRFN)
    {
        $newRw = $this->data;

        $id = $newRw[$fields['ID']];
        if ($id) //UPDATE
        {
            Sql :: query('SELECT * FROM '.$table.' WHERE '.$fields['ID'].' = '.$id);
            $fetch = Sql :: fetch_all();
            $oldRw = $fetch[0];

            $toUpdate=array();
            foreach ($fields as $k=>$v)
            {
                $fld = $v;
                if ($fldReplace = $tableRFN[$v])
                    $fld = $fldReplace;

                /* Compara los campos que tuvieron cambios, excepto el ID */
                if ($v != $fields['ID'] && $oldRw[$v] != $newRw[$fld])
                    $toUpdate[$v] = $newRw[$fld];
            }

            /* Ejecuta el UPDATE solo si hay algun cambio */
            if (!empty($toUpdate))
                if (!$this->tableUpdate($table,$toUpdate,$fields['ID'],$id))
                {
                    $this->addErr('No se pudo realizar la actualizacion.');
                    return false;
                }

        }
        else //INSERT
        {

            /* Obtiene el próximo ID a insertar*/

            Sql :: query('SELECT max('.$fields['ID'].') maxId FROM '.$table);
            $fetch = Sql :: fetch_array();
            $this->data[$fields['ID']] = ($fetch['maxId']+1);
            $newRw[$fields['ID']] = $this->data[$fields['ID']];

            $toInsert=array();
            foreach ($fields as $k=>$v)
            {
                $fld = $v;
                if ($fldReplace = $tableRFN[$v])
                    $fld = $fldReplace;

                if ($newRw[$fld])
                    $toInsert[$v] = $newRw[$fld];

            }

            /* Ejecuta el INSERT solo si hay datos a insertar */

            if (!empty($toInsert))
                if (!$this->tableInsert($table,$toInsert))
                {
                    $this->addErr('No se pudo insertar el registro.');
                    return false;
                }

        }

        return true;
    }

    /**
     * Registra en la DB los datos de la instancia, mediante los siguientes metodos:
     * <ul>
     * <li>$this->validReglasNegocio()
     * <br>-> Validando que los datos de la instancia sean correctos para las reglas del negocio.</li>
     * <li>$this->valid()
     * <br>-> Validando que los datos de la instancia sean correctos para para DB.</li>
     * <li>$this->tableSave()
     * <br>-> SQL INSERT o SQL UPDATE [$this->tableInsert() o $this->tableUpdate()] de los</li>
     * datos de la instancia, para cada una de las tablas que conforman la clase. (Ver Model::getTableLinks())</li>
     * </ul>
     * Devuelve false en caso que alguna de las operaciones no se haya podido
     * llevar a cabo, y registra los errores mediante $this->addErr().
     *
     * @return bool
     */
    public function save($validOk=false)
    {
        $err=0;
        if ($validOk || $this->valid())
        {
            $tLinks = $this->getTableLinks();
            $tLinks = array_reverse($tLinks);

            if ( !empty( $tLinks ) )
            {
                foreach ($tLinks as $className => $info)
                    if (!$this->tableSave($info['dbTable'],$info['fields'],$info['RFN']))
                        $err++;

                if ($err>0)
                    return false;
            }


            return true;
        }
        return false;
    }

    /**
    * Esta funcion elimina todos los datos seteados en el objeto
    * instanciado, tal como si se hiciese un New.
    */
    public function reset()
    {
        $this->data = array();
        $this->errLog = array();
    }


    /*  Funciones de manejo del campo blockFlag

        Asignacion del campo blockFlag [ int (1) ]

        Se debe contar con este campo en la tabla del model para que el mismo
        pueda ser registrado, y en caso que no exista el campo, al estar todos
        los flags en 0, las funciones devolveran que

        Numero      Binario     Leer(R)         Eliminar(D)     Escribir(W)
        ----------------------------------------------------------------------
        0           000         Permitido       Permitido       Permitido
        1           001         Permitido       Permitido       Bloqueado
        2           010         Permitido       Bloqueado       Permitido
        3           011         Permitido       Bloqueado       Bloqueado
        4           100         Bloqueado       Permitido       Permitido
        5           101         Bloqueado       Permitido       Bloqueado
        6           110         Bloqueado       Bloqueado       Permitido
        7           111         Bloqueado       Bloqueado       Bloqueado
        ----------------------------------------------------------------------
    */


    /*
     * Devuelve true si el registro esta bloqueado para lectura.
     */
    public function getReadBlock()
    {
        //Verifica que en la tabla del Model exista el campo blockFlag
        if (!$this->fields['blockFlag'])
            return false;

        //Transforma el entero guardado en el objeto en un binario de tres bits
        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);

        //Verifica que el bit cero
        if (substr($blockFlag,0,1))
            return true;
        return false;
    }

    /*
     * Devuelve true si el registro esta bloqueado para escritura.
     */
    public function getWriteBlock()
    {
        if (!$this->fields['blockFlag'])
            return false;

        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);
        if (substr($blockFlag,2,1))
            return true;
        return false;
    }

    /*
     * Devuelve true si el registro esta bloqueado para su eliminacion.
     */
    public function getDeleteBlock()
    {
        if (!$this->fields['blockFlag'])
            return false;

        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);
        if (substr($blockFlag,1,1))
            return true;
        return false;
    }

    /*
     * Establece que el registro esta bloqueado para lectura.
     */
    public function setReadBlock($set)
    {
        //Verifica que en la tabla del Model exista el campo blockFlag
        if (!$this->fields['blockFlag'])
            return false;

        //Transforma el entero guardado en el objeto en un binario de tres bits
        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);
        //Establece que el bit cero en 1
        $blockFlag = ($set?'1':'0').substr($blockFlag,1,2);
        //Transforma el binario en entero y lo guarda en el objeto.
        $this->data['blockFlag'] = bindec($blockFlag);
        return true;
    }

    /*
     * Establece que el registro esta bloqueado para escritura.
     */
    public function setWriteBlock($set)
    {
        //Verifica que en la tabla del Model exista el campo blockFlag
        if (!$this->fields['blockFlag'])
            return false;

        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);
        $blockFlag = substr($blockFlag,0,2).($set?'1':'0');
        $this->data['blockFlag'] = bindec($blockFlag);
        return true;
    }

    /*
     * Establece que el registro esta bloqueado para su eliminacion.
     */
    public function setDeleteBlock($set)
    {
        //Verifica que en la tabla del Model exista el campo blockFlag
        if (!$this->fields['blockFlag'])
            return false;

        $blockFlag = str_pad(decbin($this->data['blockFlag']), 3, "0", STR_PAD_LEFT);
        $blockFlag = substr($blockFlag,0,1).($set?'1':'0').substr($blockFlag,2,1);
        $this->data['blockFlag'] = bindec($blockFlag);
        return true;
    }

    /* FUNCIONES IMPLEMENTADAS POR COMPATIBILIDAD */
    function getAllData()
    {
        return $this->get();
    }

    function getLabelData($field)
    {
        $arr['label'] = $this->getFields($field);
        $arr['data'] = $this->get($field);
        return $arr;
    }

    function getLabelInput($field)
    {
        $arr = $this->getInput($field);
        unset($arr['id']);
        return $arr;
    }

}

?>