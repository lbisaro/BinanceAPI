<?php
/**
 * @title
 * @package MondoLib
 * @author Leonardo Daniel Bisaro <leonardo.bisaro@gmail.com>
 * 
 */
include_once LIB_PATH."DB.php";
include_once LIB_PATH."ErrorLog.php";
include_once LIB_PATH."functions.php";
include_once LIB_PATH."Html.php";

/**
 * Clase que permite la gestion de Modelos de bases de dato, basados en MySQL
 */
abstract class ModelDB
{
    protected $db;

    protected $fields = array();
    protected $data   = array();
    protected $foundRows;

    protected $errLog;

    public function __Construct()
    {
        if (!$this->pKey)
            criticalExit('ERROR CRITICO<br/>'.get_class($this).' - Se debe especificar el atributo '.get_class($this).'::$pKey');

        if (!$this->query)
            criticalExit('ERROR CRITICO<br/>'.get_class($this).' - Se debe especificar el atributo '.get_class($this).'::$query');

        $this->db = DB::getInstance();
        $this->errLog = new ErrorLog();
    }

    public function load($idValue)
    {
        if ($pTable = $this->getPTable())
            $pTable = $pTable.'.';

        $qry = $this->query." WHERE ".$pTable.$this->pKey." = '".$idValue."' LIMIT 1";

        $this->reset();
        if ($stmt = $this->db->query($qry))
            $this->set($stmt->fetch());


    }

    /**
    * Establece los datos de los campos enviados en el array.
    *
    * Agrega o actualiza el array $this->data
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
                if ($this->fields[$key]['type'] == 'datetime'||$this->fields[$key]['type']=='timestamp')
                {
                    if(explode('/',$val)>1)
                        $val= strToDate($val,true);
                }
                elseif($this->fields[$key]['type'] == 'date')
                {
                    if(explode('/',$val) > 1 )
                        $val= strToDate($val);
                }

                $this->data[$key] = $val;
            }
            return true;
        }
        return false;
    }



    /**
    * Devuelve el dato del campo indicado en el parametro field,
    * y en caso de no especificarse este, devuelve un array con
    * todos los campos y datos que conforman la instancia de la clase.
    *
    * @param string $field
    * @return string
    */
    public function get( $field )
    {
        $dta = $this->data;
        if ($field && $this->fields[$field]['type'] && ($ok = strpos('//-date-datetime-timestamp',strtolower($this->fields[$field]['type']))))
            $dta[$field] = dateToStr($dta[$field],($this->fields[$field]['type']!='date'?true:false));

        if ( $field && $dta[$field])
            return $dta[$field];

        return null;
    }

    function getAllData()
    {
        return $this->data;
    }

    /**
     * Devuelve el titulo del campo indicado en el parametro field
     *
     * @param string $field -> Nombre del campo
     * @return array string
     */
    public function getLabel( $field )
    {
        return $fieldTitle = $this->fields[$field]['label'];
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
        $this->foundRows = null;

        $qry = $this->getQuery($where, $order, $limit, $calcFoundRows );
        $stmt = $this->db->query($qry);

        if ($calcFoundRows)
        {
            $stmt2 = $this->db->query("SELECT FOUND_ROWS() foundRows");
            $rw = $stmt2->fetch();
            $this->foundRows = $rw['foundRows'];
        }

        return $stmt->fetchAll();
    }

    public function getQuery($where = '', $order = '', $limit = '', $calcFoundRows = false)
    {
        $qry = $this->query;

        if ($calcFoundRows)
            $qry = "SELECT SQL_CALC_FOUND_ROWS ".substr($qry,(-1)*(strlen($qry)-7))." ";
        if ( $where )
            $qry .= " WHERE ".$where;
        if ( $order )
            $qry .= " ORDER BY ".$order;
        if ( $limit )
            $qry .= " LIMIT ".$limit;

        return $qry;
    }

    /**
    * Devuelve la cantidad de FOUND_ROWS luego de realizar el metodo
    * $this->getDataSet()
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
    public function __toString()
    {
        return arrayToTable( $this->data );
    }

    /**
     * Devuelve el campo editable correspondiente al field.
     *
     * En caso de no encontrar el titulo del campo devuelve 'NO ESPECIFICADO'.
     *
     * @param string $field
     * @param string $tipo (input [default], textarea)
     * @return HTML (Input/Textarea/etc...)
     */
    public function getInput( $field, $tipo = '' ,$att = array())
    {
        if (!$att['id'])
            $att['id'] = $field;

        if ( !$fld = $this->fields[$field] )
            return null;

        $idNombre = $att['id'];

        if ($fld['len'] > 100 && !$tipo)
            $tipo = 'textarea';

        if ($fld['type']=='datetime' || $fld['type']=='timestamp')
        {
            $fecha_hora = $this->get( $field );
            $fecha = substr($fecha_hora,0,10);
            $hora  = substr($fecha_hora,strlen($fecha_hora)-5,5);
            $input = Html::getTagInputFecha( $idNombre.'_f', $fecha);
            $input .= ' '.Html::getTagInputHora( $idNombre.'_h', $hora);
        }
        elseif ($fld['type']=='date')
        {
            $fecha = $this->get( $field );
            $input = Html :: getTagInputFecha( $idNombre, $fecha, $tipo, $att);
        }
        elseif ( $tipo == 'textarea' || ( !$tipo && $fld['type'] == 'text' ) )
        {
            $input = '<textarea ';
            $input .= ' name="' . $field . '" ';
            $input .= ' id="' . $field . '" ';
            $input .= ($att['DISABLED']?'DISABLED':'');
            $input .= ' rows="'.($att['rows'] ? $att['rows']:'6').'" cols="'.($att['cols']? $att['cols']:'80').'" >';


            $input .= $this->get( $field );

            $input .= '</textarea>';
        }
        elseif ( !$tipo || $tipo == 'input' )
        {
            $addAttr = array();
            if ( $fld['len'] )
            {
                $addAttr['maxlength'] = $fld['len'];
                $addAttr['size'] = ($fld['len'] > 99 ? 80:( $fld['len'] + 2 ));
            }
            elseif ( $fld['type'] == 'text' )
            {
                $addAttr['maxlength'] = '200';
                $addAttr['size'] = '50';
            }

            $addAttr = $addAttr+$att;

            $input = Html :: getTagInput( $idNombre, $this->get( $field ), null, $addAttr );
        }
        elseif ($this->data[$field])
        {
            $input = Html :: getTagInput( $idNombre, $this->get( $field ), null, $addAttr );
        }

        return $input;
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
    public function getLabelData( $field )
    {
        if (!$f = $this->getLabel($field))
            $f = 'ERROR: No existe field='.$field;

        if (!$d = $this->get($field))
            $d = 'Sin especificar';

        return array( 'label' => $f, 'data' => nl2br($d) );
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
    public function getLabelInput( $field )
    {
        if (!$f = $this->getLabel($field))
            $f = 'ERROR: No existe field='.$field;

        if (!$d = $this->getInput($field))
            $d = 'Sin especificar';

        return array( 'label' => $f, 'input' => $d);
    }



    /**
     * Valida que el parametro $fieldValue sea un dato valido
     * para almacenarse en el campo $fieldName
     *
     * Devuelve true si el dato es valido, y en caso contrario
     * devuelve false, y agrega al errorLog del Model una referencia
     * al motivo por el cual el dato no es valido.
     * Ver $this->errLog->get()
     *
     * @param string $fieldName
     * @param mixed $fieldValue
     * @return bool
     */
    protected function validField($fieldName,$fieldValue)
    {
        if ($this -> fields[$fieldName]['type'])
        {
            $fd = $this->fields[$fieldName];

            $fieldName = trim($fieldName);
            $fLabel = '<b>'.$this->getLabel($fieldName).'</b>';

            if ($fd['unsigned'] && $fieldValue < 0)
            {
                $errs[]='El campo '.$fLabel.' no puede tener signo. [Ref:'.$fieldValue.']';
            }

            if ($fd['null']=='NO' && empty($fieldValue))
            {
                if ($fieldValue && strpos('//-varchar-char-text',strtolower($fd['type'])) && !is_string($fieldValue))
                    $this->data[$fieldName] = '';
                else
                    $this->data[$fieldName] = intval(0);
            }

            if ($fd['len']>0 && strlen($fieldValue)>$fd['len'])
                $errs[]='El campo '.$fLabel.' no puede exceder de '.$fd['len'].' caracteres. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser numericos */
            if ($fieldValue && strpos('//-bigint-mediumint-smallint-dec-decimal-float-int-integer-long-double',strtolower($fd['type'])) && !is_numeric($fieldValue))
                $errs[]='El campo '.$fLabel.' debe ser un numero';

            /* Valida campos que debe texto */
            elseif ($fieldValue && strpos('//-varchar-char-text',strtolower($fd['type'])) && !is_string($fieldValue))
                $errs[]='El campo '.$fLabel.' debe ser texto.';

            /* Valida campos que debe ser fecha */
            elseif ($fieldValue && $fd['type']=='date' && !checkDbDateTime($fieldValue) && $fieldValue != '0000-00-00')
                $errs[]='El campo '.$fLabel.' contiene una fecha erronea. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser fecha y hora*/
            elseif ($fieldValue && $fd['type']=='time' && !checkDbTime($fieldValue))
                $errs[]='El campo '.$fLabel.' contiene una hora erronea. [Ref:'.$fieldValue.']';

            /* Valida campos que debe ser fecha y hora*/
            elseif ($fieldValue && $fd['type']!='time' && $fd['null']=='NO' && strpos('//-datetime-timestamp',strtolower($fd['type'])) && !checkDbDateTime($fieldValue))
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

        $this->errLog->add($errs);
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

        // Validando las reglas de negocio
        if (!$this->validReglasNegocio())
            $err++;

        //Validando todos los campos de las tablas

        $IDs=array();
        foreach ($this->tables as $tableInfo)
        {
            $pKey = $tableInfo['pKey'];
            foreach ($tableInfo['fields'] as $fld)
                if ($fld != $pKey && !$this->validField($fld,$this->data[$fld]))
                    $err++;
        }

        if ($err)
            return false;

        return true;
    }

    /**
     * Esta funcion debe ser realizada en el modelo que instancia de ModelDB,
     * ya que de no ser asi, la $this->valid() devolvera un error al ejecutar
     * $this->valid, o $this->save().
     *
     * La funcion a realizar deber� devolver true en caso de ser correctos
     * todos los datos, o bien devolver false, y agregar
     * los errores detectados mediante $this->errLog->add('error').
     *
     * @return bool
     */
    public function validReglasNegocio()
    {
        $this->errLog->add('No se ha definido la funcion '.get_class($this).'::validReglasNegocio()');
        return false;
    }

    /**
    * Esta funcion elimina todos los datos seteados en el objeto
    * instanciado, tal como si se hiciese un New.
    */
    public function reset()
    {
        $this->data = array();
        $this->errLog->reset();
    }

    /**
     * Agrega al atributo (array) $this->fields los campos correspondientes a la tabla,
     * y ademas actualiza el atributo (array) $this->tables con los datos de dicha tabla.
     *
     * @param string $db    -> Nombre de la DDBB a la que corresponde la tabla
     * @param string $table -> Nombre de la tabla
     * @param string $pKey  -> Nombre del indice primario (Primary Key)
     *
     * @return void
     */
    protected function addTable($db,$table,$pKey)
    {
        $this->tables[$db.'.'.$table] = array('db'=>$db,'table'=>$table,'pKey'=>$pKey);
        $this->__loadTableInfo($db,$table);
    }

    protected function getPTable()
    {
        $pTable = null;
        foreach ($this->tables as $tblIt)
        {
            if ($this->pKey == $tblIt['pKey'])
            {
                $pTable = $tblIt['table'];
            }
        }
        return $pTable;
    }

    /**
     * Devuelve el proximo Id a insertar en la tabla proncipal.
     *
     * Para definir cual es la tabla principal, busca en el array $this->tables
     * una tabla que tenga el pKey = a $this->pKey;
     *
     * Este metodo puede ser aprovechado para no reescribirlo en cada clase que extienda de ModelDB
     *
     */
    public function getNewId()
    {
        foreach ($this->tables as $tblIt)
        {
            if ($this->pKey == $tblIt['pKey'])
            {
                $db    = $tblIt['db'];
                $table = $tblIt['table'];
                $pKey  = $tblIt['pKey'];
            }
        }

        if (!$db || !$table || !$pKey)
            die('No se puede definir cual es la db, tabla y pKey principal para obtener el nuevo Id.');

        $stmt = $this->db->query("SELECT max(".$pKey.") maxId FROM ".$db.".".$table);
        $rw = $stmt->fetch();

        $newId = intval($rw['maxId'])+1;

        return $newId;

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
    public function tableInsert($db,$table)
    {
        $fields = $this->tables[$db.'.'.$table]['fields'];
        foreach ($fields as $field)
        {
            $data = $this->data[$field];
            if ($data)
            {
                $sqlFields .= ($sqlFields?" , ":"").$field;
                $sqlValues .= ($sqlValues?" , ":"")." '".$this->validDbData($data)."' ";
            }
        }

        if (!$sqlValues)
        {
            $this->errLog->add('No hay campos para insertar en la tabla: '.$db.'.'.$table);
            return false;
        }

        $insert = 'INSERT INTO '.$db.'.'.$table.' ( '.$sqlFields.' ) VALUES ( '.$sqlValues.' ) ';
        if ($this->db->exec($insert))
            return true;

        $this->errLog->add('Error ejecutando QUERY: '.$insert);
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
    public function tableUpdate($db,$table)
    {
        $fields    = $this->tables[$db.'.'.$table]['fields'];
        $pKey      = $this->tables[$db.'.'.$table]['pKey'];
        $pKeyValue = $this->data[$pKey];

        $stmt = $this->db->query("SELECT * FROM ".$db.".".$table." WHERE ".$pKey." = '".$pKeyValue."' ");
        $dbData = $stmt->fetch();

        //Si no encuentra datos en la tabla, se realiza el insert
        if (count($dbData) < 1)
            return $this->tableInsert($db,$table);

        foreach ($fields as $field)
        {
            $data = $this->data[$field];
            //No se modifica ni el id, ni los datos que no fueron modificados.
            
            if ( $field != $pKey && $dbData[$field] !== $data)
                $sqlSet .= ($sqlSet?", ":"").$field." = '".$this->validDbData($data)."' ";
        }
            

        //Si no hay datos para actualizar da por cumplido el UPDATE sin ejecutar el query.
        if (!$sqlSet)
            return true;

        $update = "UPDATE ".$db.".".$table." SET ".$sqlSet." WHERE ".$pKey." = '".$pKeyValue."' ";

        if ($this->db->exec($update))
            return true;

        $this->errLog->add('Error ejecutando QUERY: '.$update);
        return false;
    }

    /**ErrorLog
     * Devuelve un array con los errores registrados mediante $this->errlog->add()
     */
    public function getErrLog()
    {
        return $this->errLog->get();
    }


    /**
     * Analiza una tabla mediante SQL DESCRIBE, y actualiza los atributos (array)
     * $this->tables y $this->fields
     *
     * @param string $db    -> Nombre de la DDBB a la que corresponde la tabla
     * @param string $table -> Nombre de la tabla
     *
     * @return void
     */
    private function __loadTableInfo($db,$table)
    {
        $fields = $this->db->getTableInfo($db,$table);
        /*
        if (count($fields) < 1)
            die('ERROR CRITICO! <br/> No se encontraron campos para la tabla '.$db.'.'.$table);
        */
        foreach ($fields as $k=>$v)
        {
            if (!$this->fields[$k])
                $this->fields[$k] = $v;
            else
                $this->fields[$table.'_'.$k] = $v;

            $this->tables[$db.'.'.$table]['fields'][] = $k;
        }
    }

    protected function validDbData($data)
    {
        //$data = str_replace('/','',$data);
        //$data = str_replace('$','',$data);
        //$data = str_replace('"','`',$data);
        //$data = str_replace("'",'`',$data);
        $data = htmlspecialchars($data,null,strtoupper(DEFAULT_CHAR_ENCODING));
        return $data;
    }

}
?>
