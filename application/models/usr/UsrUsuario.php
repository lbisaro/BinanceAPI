<?php
include_once LIB_PATH."Model.php";
include_once MDL_PATH."usr/UsrPermiso.php";
include_once MDL_PATH."usr/UsrGrupo.php";
include_once MDL_PATH."usr/UsrCsu.php";

/**
 * @package SGi_Models
 */
class UsrUsuario extends Model
{
    static protected $db     = DB_NAME;
    static protected $table  = 'usuario';
    static protected $idName = 'idusuario';

    // Tipos de usuario
    const USUARIO_CNS = 3;
    const USUARIO_OPR = 6;
    const USUARIO_ADM = 9;

    // Perfiles de usuario
    const PERFIL_CNS = 3;
    const PERFIL_OPR = 6;
    const PERFIL_ADM = 9;

    // Permisos de usuario
    const PERMISO_INB = 0;
    const PERMISO_CNS = 3;
    const PERMISO_OPR = 6;
    const PERMISO_ADM = 9;

    // Politicas de contraseñas - Cantidad minima de caracteres
    const PASS_MIN = 6;

    // Politicas de contraseñas - Cantidad minima de caracteres
    const PASS_MAX = 10;
    // Politicas de contraseñas - Al menos una letra minuscula
    const PASS_UPPER = true;
    // Politicas de contraseñas - Al menos una letra mayuscula
    const PASS_LOWER = true;
    // Politicas de contraseñas - Al menos un numero
    const PASS_NUM = true;
    // Politicas de contraseñas - No debe tener espacios ni caracteres especiales
    const PASS_ALFA = true;
    // Politicas de contraseñas - Vencimiento en dias
    const PASS_EXPIRE = 3600;
    // Politicas de contraseñas - Cantidad de passwords que no se pueden repetir
    const PASS_REPEAT = 6;
    // Politicas de contraseñas - Default password
    const PASS_DEFAULT = 1234;

    /**/
    function formatData()
    {
        $arr['strTipoUsuario'] = UsrUsuario::getTiposDeUsuario($this->data['idperfil']);

        $dias_vto = $this->getPasswordVtoDias();

            if ($dias_vto > 14)
                $arr['strVtoPassword'] = "Dentro de ".$dias_vto." dias.";

            if ($dias_vto <= 14 && $dias_vto > 7)
                $arr['strVtoPassword'] = "<b style='color:#0000FF;'>Dentro de ".$dias_vto." dias.</b>";

            if ($dias_vto <= 7 && $dias_vto > 1)
                $arr['strVtoPassword'] = "<b style='color:#993300;'>Dentro de ".$dias_vto." dias.</b>";

            if ($dias_vto <= 1)
                $arr['strVtoPassword'] = "<b style='color:#FE4E1B;'>".($dias_vto < 0 ?"VENCIDO desde el ".dateToStr($this->data['password_vto']):"Vence HOY")."</b>";


        $mail = strtolower($this->data['mail']);
        if (!empty($this->data['legajo']) || substr($mail,-12) == 'tanet.com.ar')
            $arr['usuario_interno'] = true;
        else
            $arr['usuario_interno'] = false;

        if (!$this->data['fecha_ultimo_acceso'])
            $arr['fecha_ultimo_acceso'] = 'No registrado';

        if ($this->data['cuil'])
            $arr['strCuil'] = cuitToStr($this->data['cuil']);

        return $arr;
    }

    /**/
    function formatFields()
    {
        $arr['strTipoUsuario']          = 'Tipo de usuario';
        $arr['password_vto']            = 'Vencimiento del password';
        $arr['strVtoPassword']          = 'Vencimiento del password';
        $arr['username']                = 'Nombre de usuario';
        $arr['mail']                    = 'E-Mail';
        $arr['fecha_alta']              = 'Fecha de alta';
        $arr['fecha_ultimo_acceso']     = 'Ultimo acceso';
        $arr['idperfil']                = 'Tipo de usuario';
        $arr['ayn']                     = 'Nombre y Apellido';
        $arr['cuil']                    = 'CUIL/CUIT';
        $arr['strCuil']                 = $arr['cuil'];
        $arr['legajo']                  = 'Numero de Legajo';


        return $arr;
    }

    /**/
    function getInput($field,$tipo='',$att = array())
    {
        $value = $this->get($field);

        if ($field == 'mail')
            $input = Html::getTagInputMail($field,$value,$att);

        if ($field == 'cuil')
            $input = Html::getTagInputCuit($field,cuitToStr($value),$att);

        if ($field == 'idperfil')
        {
            $tiposUsr = UsrUsuario::getTiposDeUsuario();
            foreach ($tiposUsr as $idperfil => $tipoDeUsuario)
            {
                $options[$idperfil] = $tipoDeUsuario;
            }

            $input = Html::getTagSelect($field,$options,$value,$att);
        }

        if ($input)
            return array('label' => $this->getFields($field),'data' => $input,'id' => $field);
        else
            return parent::getInput($field,$tipo,$att);

    }

    /**/
    function validReglasNegocio()
    {
        $err = 0;

        $idusuario = $this->data['idusuario'];
        $username = strtoupper($this->data['username']);
        $ayn  = strtoupper($this->data['ayn']);

        // Validando que el username no se encuentre previamente registrdo para otro usuario
        if ($this->getDataSet("upper(username) = '".$username."' ".($idusuario?" AND idusuario <> ".$idusuario:"")))
        {
            $err++;
            $this->addErr('El nombre de usuario ya se encuentra registrado para otro usuario');
        }

        // Validando que el nombre no se encuentre previamente registrdo para otro usuario
        if ($this->getDataSet("upper(ayn) = '".$ayn."' ".($idusuario?" AND idusuario <> ".$idusuario:"")))
        {
            $err++;
            $this->addErr('El nombre ya se encuentra registrado para otro usuario');
        }

        //Verificando que el nombre de la persona contenga al menos dos palabras.
        $arrAyn = explode(" ",strtoupper(trim($this->data['ayn'])));
        if (count($arrAyn) < 2)
        {
            $err++;
            $this->addErr('El campo '.$this->getFields('ayn').'[ '. $this->data['ayn'].'] debe contener al menos dos palabras.');
        }
        else
        {
            foreach ($arrAyn as $v)
            {
                if (strlen(preg_replace('/[^a-zA-Z]/','', $v))< 2)
                {
                    $err++;
                    $this->addErr('Los nombres o apellidos deben contener mas de una letra. Evitar el uso de iniciales.');
                }
            }
        }

        // Validando el Legajo en caso que se especifique
        if (!empty($this->data['legajo']) && !is_numeric($this->data['legajo']))
        {
            $err++;
            $this->addErr('El campo '.$this->getFields('legajo').' debe contener solo numeros.');
        }

        // Validando el CUIL en caso que se especifique
        if (!empty($this->data['cuil']) && !validCuit($this->data['cuil']))
        {
            $err++;
            $this->addErr('El campo '.$this->getFields('cuil').' debe ser un dato valido.');
        }


        if (!$err)
            return true;
        return false;
    }

    static public function setAuthInstance($username , $password)
    {
        $_SESSION['Auth']          = null;
        $_SESSION['SSN_idusuario'] = null;

        if ($idusuario = UsrUsuario::validAuth($username,$password))
        {
            $auth = new self($idusuario);
            $_SESSION['Auth'] = $auth->get();
            $_SESSION['SSN_idusuario'] = $auth->get('idusuario');

            return true;
        }
        return false;
    }

    static public function setAuthInternalInstance($idusuario)
    {
        $_SESSION['Auth']          = null;
        $_SESSION['SSN_idusuario'] = null;

        $auth = new self($idusuario);
        if ($idusuario && $auth->get('idusuario')==$idusuario)
        {
            $_SESSION['Auth'] = $auth->get();
            $_SESSION['SSN_idusuario'] = $auth->get('idusuario');

            return true;
        }
        return false;
    }


    static public function getAuthInstance()
    {
        if (!empty($_SESSION['Auth']['idusuario']) && $_SESSION['Auth']['idusuario'] == $_SESSION['SSN_idusuario'])
        {
            $auth = new UsrUsuario();
            $auth->set($_SESSION['Auth']);
            return $auth;
        }
        return null;
    }

    static public function killAuthInstance()
    {
        $_SESSION = array();
        return true;
    }

    public function loadByRSID($RSID)
    {
        if ($RSID)
        {
            $dataSet = $this->getDataset("RSID = '".$RSID."' ");
            if ($dataSet)
            {
                if (strToTime($dataSet[0]['RSID_vto']) >= strToTime('now') )
                {
                    if ($dataSet[0]['idusuario'])
                    {
                        $this->set($dataSet[0]);
                        return true;
                    }
                }
            }
        }

        return $RSID;
    }

    /**
     * Registra el RSID (Remote Session ID) en la tabla usuario tal como se pasa el parametro
     */
    public function setRSID($RSID)
    {
        $idusuario = $this->data['idusuario'];
        if ($idusuario)
        {
            $RSID = trim($RSID);
            if ($RSID)
                $RSID_vto = date('Y-m-d H:i:s',strToTime('+60 days'));
            else
                $RSID_vto = date('Y-m-d H:i:s',strToTime('1901-01-01 01:00:00 AM'));

            if ($this->query("UPDATE usuario SET RSID = '".$RSID."', RSID_vto = '".$RSID_vto."' WHERE idusuario = '".$idusuario."' " ))
            {
                $this->data['RSID']     = $RSID;
                $this->data['RSID_vto'] = $RSID_vto;
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica que el username y password pasados por parametro
     * existan en la DB y registra el idusuario y lo devuelve, o
     * devuelve false en caso de no encontrar dicha combinacion de datos.
     *
     * @param string $username
     * @param string $password
     * @return int  idusuario / false
     */
    static public function validAuth($username,$password)
    {
        $sql = new Sql();
        $sql->Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);
        $qry = "SELECT usuario.idusuario
                  FROM usuario
                  WHERE (usuario.username='$username' OR  usuario.mail='$username') AND usuario.password='".UsrUsuario::encryptPassword($password)."' ";
        $sql->query($qry);
        if ($rw = $sql->fetch_array())
            return $rw["idusuario"];

        return null;
    }

    /**
    * Carga los permisos del usuario desde la DB.
    *
    * Para poder obtener estos datos, se debera ejecutar
    * $this->get('permisos'), y esta funcion devuelve un array con los datos.
    *
    * @return bool true si cargo permisos, o false en caso contrario
    */
    public function loadPermisos()
    {
        $this->data['permisos'] = array();
        if ($idusuario = $this->data['idusuario'])
        {
            $maxPrms = self::USUARIO_ADM;
            if ($this->data['idperfil'] == self::USUARIO_CNS)
            {
                $maxPrms = self::USUARIO_CNS;
            }
            elseif ($this->data['idperfil'] == self::USUARIO_ADM)
            {
                $allPrms = self::USUARIO_ADM;
                $maxPrms = self::USUARIO_ADM;
            }
            elseif ($this->data['idperfil'] < 1)
            {
                $allPrms = '0';
                $maxPrms = 0;
            }

            if ($this->data['idperfil'] > 0 && $this->data['idperfil'] != self::USUARIO_ADM)
            {
                $qry = 'SELECT * FROM permiso_usuario WHERE idusuario = '.$idusuario;
                $this->query($qry);
                while ($rw = $this->fetch_array())
                {
                    $prmsUsr[$rw['idpermiso']] = ($rw['idperfil'] <= $maxPrms ? $rw['idperfil'] : $maxPrms );
                }
            }

            $prmsList = new UsrPermiso();
            $fetchAll = $prmsList->getDataSet(null,'idpermiso');
            foreach ($fetchAll as $rw)
            {
                $arr['idperfil'] =($allPrms ? $allPrms : $prmsUsr[$rw['idpermiso']]);
                $arr['permiso'] = $rw['descripcion'];
                $this->data['permisos'][$rw['idpermiso']] = $arr;
            }

            $this->data['permisosCSU'] = $this->getCsuEfectivo();

            if(!empty($this->data['permisosCSU'])||!empty($this->data['permisos']))
                return true;

            return null;
        }
        return null;
    }

    /**
    * Quita los permisos del usuario (Solo en la instancia
    * sin modificar la DB)
    */
    public function resetPermisos()
    {
        $this->data['permisos']=null;
    }

    /**
     * Establece un permiso del usuario
     *
     * Agrega un idpermiso,idperfil en caso que el mismo no exista.
     * Reemplaza con un nuevo idperfil un idpermiso existente
     * Elimina un idpermiso si el idperfil es igual 0
     *
     * NOTA: La funcion no actualiza la DB.
     *
     * Ver Usuario::resetPermisos() y Usuario::save().
     *
     * @param mixed $idpermiso
     * @param integer $idperfil
     * @return bool
     */
    public function setPermiso($idpermiso,$idperfil=0)
    {
        if ($idpermiso > 0)
        {
            $this->data['permisos'][$idpermiso]['idperfil'] = $idperfil;
            return true;
        }
        $this->addErr('No se pudo establecer el permiso: idpermiso='.$idpermiso.' - El valor de idpermiso debe ser mayor a 0');
        return false;
    }

    /**
    * Actualiza la DB registrando los permisos del usuario
    * en funcion de los establecidos en la instancia
    */
    public function savePermisos()
    {
        if ($this->data['idusuario'] > 0)
        {
            /* Elimina los permisos existentes en la DB */
            $this->query('DELETE FROM permiso_usuario WHERE idusuario = '.$this->data['idusuario']);

            if ($this->data['idperfil'] > 0 && $this->data['idperfil'] != self::USUARIO_ADM)
            {
                if (is_array($this->data['permisos']))
                {
                    $values='';
                    foreach ($this->data['permisos'] as $idpermiso => $val)
                    {
                        $idperfil = $val['idperfil'];
                        if ($idperfil > 0)
                            $values .= ($values?' , ':'').' ( '.$this->data['idusuario'].' , '.$idpermiso.' , '.$idperfil.' )';
                    }
                }
                if ($values)
                {
                    $qry = 'INSERT INTO permiso_usuario ( idusuario , idpermiso , idperfil )
                                   VALUES '.$values;
                    $this->adderr($qry);
                    if ($this->query($qry))
                    {
                        return true;
                    }
                    else
                    {
                        $this->addErr('No se pudieron actualizar los permisos en la DB. Ref.Query: '.$qry);
                        return false;
                    }
                }
            }
        }
        $this->addErr('No se pueden grabar los permisos. El idusuario debe ser mayor a 0');
        return false;
    }

    /**
     * Devuelve el idperfil del usuario para un permiso en particular
     *
     * @param int $idpermiso
     * @return int idperfil
     */
    function checkPermiso($permiso)
    {
        if (!is_array($this->data['permisos']))
            $this->loadPermisos();

        if ($this->data['idperfil'] == self::PERFIL_ADM)
            return self::PERFIL_ADM;

        if ($this->data['idperfil'] < self::PERFIL_CNS)
            return null;

        if(is_int($permiso))
            return $this->data['permisos'][$permiso]['idperfil'];
    }

    function checkCsu($codigoCSU)
    {

        if (!is_array($this->data['permisosCSU']))
            $this->loadPermisos();

        if (!$codigoCSU)
        {
            CriticalExit('UsrUsuario::checkCsu() - Se debe especificar el nombre del caso de uso a chequear.');
            return false;
        }

        /**
          * El array $this->data['CSUInDB'] contiene todos los casos de uso
          * almacenados en la tabla usuarios.csu, y se crea cuando se llama al metodo:
          *
          * $this->getCsuEfectivo() desde el metodo $this->loadPermisos()
          *
          */
        if (!isset($this->data['CSUInDB'][$codigoCSU]))
        {
            echo  '
            <div class="csuError msgAlert" style="position: absolute;right: 5px;top: 79px;padding: 10px;">No se encuentra registrado el Caso de uso: <b>'.$codigoCSU.'</b></div>"';
            return false;
        }

        if ($this->data['idperfil'] == self::PERFIL_ADM)
            return true;

        if ($this->data['idperfil'] < self::PERFIL_CNS)
            return null;

        if($this->data['permisosCSU'][$codigoCSU])
            return true;
    }

    public function getGrupos($incluirHeredados = false)
    {
        $arr = array();
        if ($this->data['idusuario'])
        {
            $this->query('SELECT idgrupo FROM usuario_x_grupo WHERE idusuario = '.$this->data['idusuario']);

            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idgrupo']] = $rw['idgrupo'];
            }
        }
        if ($incluirHeredados && count($arr) > 0)
        {
            $grps = new UsrGrupo();
            $grpTree = $grps->getTree();
            if (is_array($grpTree))
            {
                foreach ($grpTree as $rw)
                {
                    if ($arr[$rw['idgrupo']])
                    {
                        $aIdFull = explode('.',$rw['idFull']);
                        foreach ($aIdFull as $parentId)
                        {
                            if (!$arr[$parentId])
                            {
                                $arr[$parentId] = $parentId;
                            }
                        }
                    }
                }
            }
        }
        return $arr;
    }

    /**
    * Carga los grupos a los que el usuario esta vinculado desde la DB.
    *
    * Para poder obtener estos datos, se debera ejecutar
    * $this->get('grupos'), y esta funcion devuelve un array con los datos.
    *
    * @return bool true si cargo grupos, o false en caso contrario
    */
    public function loadGrupos()
    {
        $usrGrp = new UsrGrupo();
        $tree = new Tree('uGrp','Grupos de usuarios');

        if($fetchAll = $usrGrp->getDataSet(null,'grupo'))
        {
            foreach ($fetchAll as $rw)
            {
                $tree->add($rw['idgrupo'], $rw['idgrupo_padre'], $rw['grupo'], IMG_PATH.'usrGrupo.gif', IMG_PATH.'usrGrupo.gif');
            }
        }

        $this->data['grupos'] = array();
        if ($idusuario = $this->data['idusuario'])
        {
            $qry = 'SELECT * FROM usuario_x_grupo WHERE idusuario = '.$idusuario;
            $this->query($qry);
            while ($rw = $this->fetch_array())
                $miembroDe[$rw['idgrupo']] = 'YES';

            $grpsList = new UsrGrupo();
            if($fetchAll = $grpsList->getDataSet(null,'grupo'))
            {
                foreach ($fetchAll as $rw)
                {
                    $arr['vinculado'] = $miembroDe[$rw['idgrupo']];
                    $arr['idgrupo'] = $rw['idgrupo'];
                    $arr['grupo'] = $rw['grupo'];

                    $arr['idFull'] = $tree->getIdFull($rw['idgrupo']);
                    $arr['nameFull'] = $tree->getNameFull($rw['idgrupo']);

                    $grupos[$arr['idFull']] = $arr;
                    uksort($grupos,'cmp');
                    $this->data['grupos'] = $grupos;
                }
            }
            return true;
        }
        return null;
    }

    /**
    * Vincula al usuario como miembro del grupo indicado mediante el parametro
    *
    * @param int $idgrupo
    * @return bool -> True si pudo vincular el grupo, o false en caso contrario
    */
    public function setGrupo($idgrupo)
    {
        if ($idgrupo > 0)
        {
            $this->data['grupos'][$idgrupo]['vinculado'] = 'YES';
            return true;
        }
        $this->addErr('El valor de idgrupo debe ser mayor a 0. ');
        return false;
    }


    /**
    * Quita los grupos a los que el usuario esta vincularo
    * (Solo en la instancia sin modificar la DB)
    */
    public function resetGrupos()
    {
        $this->data['grupos']=null;
    }


    /**
    * Actualiza la DB registrando los grupos a los cuales el usuario
    * se encuentra vinculado en funcion de los establecidos en la instancia.
    */
    public function saveGrupos()
    {
        if ($this->data['idusuario'] > 0)
        {
            //Arma una lista con los grupos existentes
            $grpsList = new UsrGrupo();
            $fetchAll = $grpsList->getDataSet();
            foreach ($fetchAll as $rw)
            {
                //Establece el valor estado de vinculacion del usuario al grupo
                $arr['vinculado'] = $this->data['grupos'][$rw['idgrupo']]['vinculado'];
                $arr['idgrupo'] = $rw['idgrupo'];
                $arr['grupo'] = $rw['grupo'];
                $this->data['grupos'][$rw['idgrupo']] = $arr;
            }

            //Verificando que no existan grupos no validos establecidos
            if (is_array($this->data['grupos']))
            {
                foreach ($this->data['grupos'] as $grp)
                {
                    if (!$grp['grupo'])
                    {
                        $err++;
                        $this->addErr('idgrupo='.$grp['grupo'].' no es un dato valido.');
                    }
                }
            }

            if ($err)
            {
                $this->addErr('No se grabaron los grupos relacionados con el usuario');
                return false;
            }
            else
            {
                foreach ($this->data['grupos'] as $grp)
                {
                    if (!$grp['vinculado']) // Si no esta vinculado elimina el registro
                    {
                        $this->query('DELETE FROM usuario_x_grupo WHERE idgrupo = '.$grp['idgrupo'].' AND idusuario = '.$this->data['idusuario']);
                    }
                    else // Si esta vinculado, inserta en caso que no este registrado o no hace nada.
                    {
                        if (!$this->exec_select('SELECT idgrupo FROM usuario_x_grupo WHERE idgrupo = '.$grp['idgrupo'].' AND idusuario = '.$this->data['idusuario'],2,'idgrupo'))
                            $this->query('INSERT INTO usuario_x_grupo (idgrupo,idusuario) VALUES ('.$grp['idgrupo'].','.$this->data['idusuario'].')');
                    }
                }
                return true;
            }


        }
        $this->addErr('No se pueden grabar los grupos. El idusuario debe ser mayor a 0');
        return false;
    }

    /**
     * Devuelve un array con todos los perfiles de usuario disponibles.
     *
     * El array esta compuesto por:<br/>
     * key => idperfil
     * value => Descripcion del perfil de usuario
     *
     * @param integer $idperfil='ALL'
     * @return array
     */
    static function getPerfilesDeUsuario($idperfil='ALL')
    {
        $perfiles[0]="Inhabilitado";
        $perfiles[self::PERFIL_CNS]="Consulta";
        $perfiles[self::PERFIL_OPR]="Operador";
        $perfiles[self::PERFIL_ADM]="Administrador";
        if ($idperfil == 'ALL')
            return $perfiles;
        else
            return ($perfiles[$idperfil]?$perfiles[$idperfil]:$perfiles[0]);
    }

     /**
     * Devuelve un array con todos los permisos de usuario disponibles.
     *
     * El array esta compuesto por:<br/>
     * key => idpermiso
     * value => Descripcion del permiso de usuario
     *
     * @param integer $idpermiso='ALL'
     * @return array
     */
    static function getPermisosDeUsuario($idpermiso='ALL')
    {
        $permisos[0]="Inhabilitado";
        $permisos[self::PERFIL_CNS]="Consulta";
        $permisos[self::PERFIL_OPR]="Operador";
        $permisos[self::PERFIL_ADM]="Administrador";
        if ($idpermiso == 'ALL')
            return $permisos;
        else
            return ($permisos[$idpermiso]?$permisos[$idpermiso]:$permisos[0]);
    }

    /**/
    public function getPermisos($id = null)
    {
        $idusuario = (!empty($id)? $id:$this->data['idusuario']);
    }

    /**
     * Devuelve un array con todos los tipos de usuario disponibles.
     *
     * El array esta compuesto por:<br/>
     * key => idtipousuario
     * value => Descripcion del tipo de usuario
     *
     * @param integer $idtipousuario='ALL'
     * @return array
     */
    static function getTiposDeUsuario($idtipousuario='ALL')
    {
        $tipos[0]="Inhabilitado";
        $tipos[self::USUARIO_CNS]="Consulta";
        $tipos[self::USUARIO_OPR]="Operador";
        $tipos[self::USUARIO_ADM]="Administrador";
        if ($idtipousuario == 'ALL')
            return $tipos;
        else
            return ($tipos[$idtipousuario]?$tipos[$idtipousuario]:$tipos[0]);
    }

    /**
     * Recibe una cadena de caracteres y la devuelve encriptada.
     *
     * @param string $str -> Password a encriptar
     * @return string -> Password encriptado.
     */
    static function encryptPassword($str)
    {
        return md5(trim($str));
    }

    /**
     * Valida la politica de password.
     *
     * Devuelve true o false en caso de validar o no respectivamente.
     *
     * @param string $pass -> Password a validar.
     * @param string $erroresDetectados -> Puntero a la variable en la que se almacenaran los errores detectados.
     * @return bool
     */
        static function validPoliticaPassword($pass,&$erroresDetectados)
        {
            $pass = trim($pass);
            $err='';
            if ((self::PASS_MIN > 0 && self::PASS_MAX >0) && (strlen($pass)<self::PASS_MIN || strlen($pass)>self::PASS_MAX) )
                $err .= ($err?", ":"")."Debe tener entre ".self::PASS_MIN." y ".self::PASS_MAX." caracteres";

            $n=$num=$upper=$lower=0;
            while (ord(substr($pass,$n,1)) && $n < strlen($pass))
            {
                $chr = ord(substr($pass,$n,1));

                if ($chr>=48 && $chr<=57)
                    $num++;
                elseif ($chr>=65 && $chr<=90)
                    $upper++;
                elseif ($chr>=97 && $chr<=122)
                    $lower++;
                else
                      $no_alfa++;
                $n++;
            }
            if (self::PASS_NUM && $num<1)
                $err .= ($err?", ":"")."Debe tener al menos un numero";
            if (self::PASS_LOWER && $lower<1)
                $err .= ($err?", ":"")."Debe tener al menos una letra minuscula";
            if (self::PASS_UPPER && $upper<1)
                $err .= ($err?", ":"")."Debe tener al menos una letra mayuscula";
            if (self::PASS_ALFA && $no_alfa>0)
                  $err .= ($err?", ":"")."No debe tener espacios ni caracteres especiales";
            if ($err)
                  $err = $err.".";

              $erroresDetectados = $err;
              if ($err)
                  return false;

              return true;
        }

    /**
     * Devuelve un mensaje con la politica de password establecida
     *
     * @return html
     */
    static function getPasswordPolicy()
    {
        $pass_pol_msg = "";

        if (self::PASS_MIN>0 && self::PASS_MAX>0)
            $pass_pol_msg .= "<li>Debe tener entre ".self::PASS_MIN." y ".self::PASS_MAX." caracteres.</li>";
        if (self::PASS_UPPER)
            $pass_pol_msg .= "<li>Debe tener al menos una letra mayuscula</li>";
        if (self::PASS_LOWER)
            $pass_pol_msg .= "<li>Debe tener al menos una letra minuscula</li>";
        if (self::PASS_NUM)
            $pass_pol_msg .= "<li>Debe tener al menos un numero</li>";
        if (self::PASS_ALFA)
            $pass_pol_msg .= "<li>No debe tener espacios ni caracteres especiales.</li>";
        if (self::PASS_EXPIRE>0)
            $pass_pol_msg .= "<li>Debera renovarse cada ".self::PASS_EXPIRE." dias.</li>";
        if (self::PASS_REPEAT>0)
            $pass_pol_msg .= "<li>No se deben repetir las ultimas ".self::PASS_REPEAT." contraseñas utilizadas anteriormente.</li>";

        if ($pass_pol_msg)
            $pass_pol_msg = "<b>Caracteristicas de las contraseñas:</b> <ul>".$pass_pol_msg."</ul>";
        else
            $pass_pol_msg = "No se especificaron caracteristicas de las contraseñas";

        return ($pass_pol_msg);
    }

    /**
     * Establece el password del usuario, de acuerdo a la constante PASS_DEFAULT,
     * previamente definida en el archivo config.php.
     * En caso de detectarse errores, la funcion devuelve un array con los errores detectados.
     *
     * La funcion actualiza el nuevo password en la DB, estableciendo el
     * vencimiento del mismo, y vaciando el historial de passwords.
     */
    function resetPassword()
    {
        $this->data['password']     = UsrUsuario::encryptPassword(self::PASS_DEFAULT);
        $this->data['password_vto'] = strToDate(date("Y/m/d", mktime(0, 0, 0, date("m"), date("d") + 7, date("Y"))));
        $this->data['password_hst'] = null;

        $qry = "UPDATE ".(self::$db?self::$db.'.':'').self::$table." SET
                     password='" . $this->data['password'] . "',
                     password_vto='" . $this->data['password_vto'] . "',
                     password_hst=''
                     WHERE ".self::$idName."='" . $this->data['idusuario'] . "'";
        $this->query($qry);
    }

    /**
     * Establece el nuevo password pasado como parametro mediante $new_pass,
     * y lo actualiza en la DB, actualizando ademas el vencimiento del password y el historial.
     * En caso de ser necesario verificar el password anterior al cambio,
     * se debera pasar mediante el parametro $old_pass
     *
     * En caso de no ser valido el password a establecer o bien que el password
     * anterior no coincida, la funcion devuelve false, y registra
     * los errores detectados en el errLog del usuario
     *
     * @see $usr->getErrLog()
     * @param string $new_pass
     * @param string $old_pass (Opcional)
     * @return bool
     */
    function setNewPassword($new_pass, $old_pass = -1)
    {
        $err=0;
        if ($this->data['idusuario'] < 1)
        {
            $err++;
            $this->addErr("El usuario no se encuentra registrado.");
        }
        if ($old_pass != -1 && $this->data['password'] && ($this->data['password'] != UsrUsuario::encryptPassword($old_pass)))
        {
            $err++;
            $this->addErr("No coincide el password anterior con el actual.#1 ");
        }

        if ($old_pass != -1 && !$this->data['password'] && $old_pass)
        {
            $err++;
            $this->addErr("No coincide el password anterior con el actual.#2 ");
        }
        if ($this->data['password'] == UsrUsuario::encryptPassword($new_pass))
        {
            $err++;
            $this->addErr("El password a establecer es identico al actual.");
        }
        if (!$this->validarPassword($new_pass))
        {
            $err++;
        }
        
        
        if (!$err)
        {
            $pass = UsrUsuario::encryptPassword($new_pass);
            $vto = date('Ymd', mktime(0, 0, 0, date("m"), date("d") + self::PASS_EXPIRE, date("Y")));
            $hst = '';

            $this->data['password_hst'] = $pass . "#" . $this->data['password_hst'];

            $hst = explode("#",$this->data['password_hst']);

            $arrData['password_vto'] = null;
            for ($i=0;( $hst[$i] && $i <= (self::PASS_REPEAT-1) );$i++)
                $arrData['password_hst'] .= $hst[$i]."#";
            $arrData['password'] = $pass;
            $arrData['password_vto'] = $vto;
            $this->set($arrData);

            if (!$this->save())
            {
                $err++;
                $this->addErr("No se pudo grabar el password");
            }
        }
        if ($err)
        {
            return false;
        }
        return true;
    }

    function validarUsername($username)
    {
        if (preg_match("/^[\.a-zA-Z0-9_-]{2,20}?$/", $username) || preg_match("/^[\.a-zA-Z0-9_-]{2,}@[a-zA-Z0-9_-]{2,}\.[a-zA-Z]{2,4}(\.[a-zA-Z]{2,4})?$/", $username))
            return true;
        return false;
    }

    /**
     * Valida el password incluyendo el control del historial.
     *
     * La funcion devuelve false, y registra
     * los errores detectados en el errLog del usuario
     *
     * @param string $pass
     * @return bool
     */
    function validarPassword($pass)
    {
        $pass = trim($pass);
        $err = 0;

        if (strpos("###".$this->data['password_hst'],UsrUsuario::encryptPassword($pass)))
        {
            $err++;
            $this->addErr("El password fue registrado anteriormente. No se pueden repetir los ultimos " . self::PASS_REPEAT . " passwords utilizados.");

        }
        if ($policy_error = UsrUsuario::validarPoliticaPassword($pass))
        {
            $err++;
            $this->addErr("El password no cumple con las caracteristicas requeridas: " . $policy_error);
        }

        if ($err)
            return false;

        return true;
    }

    /**
     * Valida la politica de password
     */
        static function validarPoliticaPassword($pass)
        {
            $pass = trim($pass);
            $err='';
            if ((self::PASS_MIN > 0 && self::PASS_MAX >0) && (strlen($pass)<self::PASS_MIN || strlen($pass)>self::PASS_MAX) )
                $err .= ($err?", ":"")."Debe tener entre ".self::PASS_MIN." y ".self::PASS_MAX." caracteres";

            $n=$num=$upper=$lower=0;
            while (ord(substr($pass,$n,1)) && $n < strlen($pass))
            {
                $chr = ord(substr($pass,$n,1));

                if ($chr>=48 && $chr<=57)
                    $num++;
                elseif ($chr>=65 && $chr<=90)
                    $upper++;
                elseif ($chr>=97 && $chr<=122)
                    $lower++;
                else
                    $no_alfa++;
                $n++;
            }
            if (self::PASS_NUM && $num<1)
                $err .= ($err?", ":"")."Debe tener al menos un numero";
            if (self::PASS_LOWER && $lower<1)
                $err .= ($err?", ":"")."Debe tener al menos una letra minuscula";
            if (self::PASS_UPPER && $upper<1)
                $err .= ($err?", ":"")."Debe tener al menos una letra mayuscula";
            if (self::PASS_ALFA && $no_alfa>0)
                $err .= ($err?", ":"")."No debe tener espacios ni caracteres especiales";
            if ($err)
                $err = $err.".";
            return $err;
        }

    /**
    * Devuelve los dias que faltan para el vencimiento del password
    * */
    function getPasswordVtoDias()
    {
        $tms1 = date('U', strtotime($this->data['password_vto']));
        $tms2 = time();

        if (!empty($tms1))
        {
            $ret = ceil(($tms1 - $tms2) / 86400);
            return ($ret !=0 ? $ret : 0 );
        }
        else
        {
            return null;
        }
    }

    /**
     * Actualizar en la DB la ultima fecha/hora que el usuario igreso al sistema
     */
    function registrarAcceso()
    {
        if ($this->data['idusuario'])
        {
            $fechaHora = strToDate(date('Y-m-d H:i'), true);
            $query = "UPDATE ".self::$db.'.'.self::$table." SET fecha_ultimo_acceso='$fechaHora' WHERE ".self::$idName." = " . $this->data['idusuario'];
            $this->query($query);
        }
    }

    function saveFCM_token($token)
    {
        $upd = "UPDATE usuario SET FCM_token = '".$token."' WHERE idusuario = '".$this->data['idusuario']."'";
        
        return $this->query($upd);
    }

    function getFCM_token()
    {
        $qry = "SELECT FCM_token FROM usuario WHERE idusuario = '".$this->data['idusuario']."'";
        $this->query($qry);
        $rw = $this->fetch_array();

        return $rw['FCM_token'];
    }

    /**
    */
    function setConfig($set, $str = '')
    {
        $conf = $this->getConfig();
        if (!$this->data['idusuario'])
            return null;

        if($conf[$set] != $str)
        {
            $conf[$set] = $str;

            //Borrando configuraciones vacias
            foreach ($conf as $k=>$v)
                if (!$v)
                    unset($conf[$k]);
            //FIN - Borrando configuraciones vacias

            $this->data['conf'] = serialize($conf);
            $qry = 'UPDATE '.self::$db.'.'.self::$table.' set conf = \''.$this->data['conf'].'\' WHERE '.self::$idName.' = '.$this->data[self::$idName];
            $_SESSION['Auth']['conf'] = serialize($conf);
            if ($this->query($qry))
                return true;
        }

        return false;
    }

    /**
    */
    function getConfig($get = '')
    {
        if($get)
        {
            $conf = unserialize($this->data['conf']);
            return $conf[$get];
        }
        else
        {
            return unserialize($this->data['conf']);
        }
    }

    /**/
    public function addGrupo($idgrupo)
    {
        if ($this->data['idusuario'])
        {
            if ($idgrupo)
            {
                if (!$this->exec_select('SELECT idgrupo FROM usuario_x_grupo WHERE idusuario = '.$this->data['idusuario'].' AND idgrupo = '.$idgrupo))
                {
                    if ($this->query('INSERT INTO usuario_x_grupo (idgrupo,idusuario) VALUES ('.$idgrupo.','.$this->data['idusuario'].')'))
                        return true;
                }
                $this->addErr('El usuario ya esta relacionado al grupo.');
            }
            else
            {
                $this->addErr('Se debe especificar idgrupo.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idusuario.');
        }
        return false;
    }

    /**/
    public function delGrupo($idgrupo)
    {
        if ($this->data['idusuario'])
        {
            if ($idgrupo)
            {
                if ($this->query('DELETE FROM usuario_x_grupo WHERE idusuario = '.$this->data['idusuario'].' AND idgrupo = '.$idgrupo))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idgrupo.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idusuario.');
        }
        return false;
    }

    /**/
    public function getCsu()
    {
        $arr = array();
        if ($this->data['idusuario'])
        {
            $this->query('SELECT idcsu, permiso FROM usuario_x_csu WHERE idusuario = '.$this->data['idusuario']);
            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idcsu']]['idcsu']= $rw['idcsu'];
                $arr[$rw['idcsu']]['permiso']= $rw['permiso'];
            }
        }
        return $arr;
    }

    /**/
    public function addCsu($idCsu,$permiso)
    {
        if ($this->data['idusuario'])
        {
            if ($idCsu)
            {
                if (!$this->exec_select('SELECT idusuario,permiso FROM usuario_x_csu WHERE idusuario = '.$this->data['idusuario'].' AND idcsu = '.$idCsu))
                {
                    if ($this->query('INSERT INTO usuario_x_csu (idusuario,idcsu,permiso) VALUES ('.$this->data['idusuario'].','.$idCsu.','.$permiso.')'))
                        return true;
                }
                else
                {
                    if ($this->query('UPDATE usuario_x_csu SET permiso = '.$permiso.' WHERE idcsu = '.$idCsu.' AND idusuario = '.$this->data['idusuario']))
                        return true;
                }
            }
            else
            {
                $this->addErr('Se debe especificar idusuario y permiso.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idusuario.');
        }
        return false;
    }

    /**/
    public function delCsu($idCsu)
    {
        if ($this->data['idusuario'])
        {
            if ($idCsu)
            {
                if ($this->query('DELETE FROM usuario_x_csu WHERE idusuario = '.$this->data['idusuario'].' AND idcsu = '.$idCsu))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idcsu.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idusuario.');
        }
        return false;
    }

    /**/
    public function getCsuPorGrupo($detalle = false)
    {
        $arrGruposFull = $this->getGrupos($incluirHeredados = true);
        if (count($arrGruposFull) > 0)
        {
            $whereIn = '';
            foreach ($arrGruposFull as $idgrupo)
                $whereIn .= ($whereIn?' , ':'').$idgrupo;
            $where = ' UGxC.idgrupo IN ('.$whereIn.')';

            $qry = 'SELECT UGxC.idgrupo, csu.idcsu, UGxC.permiso
                    FROM usuario_grupo_x_csu UGxC
                    LEFT JOIN csu ON UGxC.idcsu = csu.idcsu
                    WHERE '.$where;
            $this->query($qry);
            $arr = array();

            if($detalle)
            {
                while ($rw = $this->fetch_array())
                {
                    $arr[$rw['idcsu']][$rw['idgrupo']] = $rw['permiso'];
                }
            }
            else
            {
                while ($rw = $this->fetch_array())
                {
                    $arr[$rw['idcsu']] = ($arr[$rw['idcsu']] > $rw['permiso'] ? $arr[$rw['idcsu']] : $rw['permiso']);
                }
            }
        }
        return $arr;
    }

    /**/
    public function getCsuPorUsuario()
    {
        $arr = array();
        if ($this->data['idusuario'])
        {
            if ($this->data['idperfil'] == self::PERMISO_ADM)
            {
                $this->query('SELECT idcsu FROM csu');
                while ($rw = $this->fetch_array())
                {
                    $arr[$rw['idcsu']] = self::PERMISO_ADM;
                }
            }
            else
            {
                $this->query('SELECT idcsu, permiso FROM usuario_x_csu WHERE idusuario = '.$this->data['idusuario']);
                while ($rw = $this->fetch_array())
                {
                    $arr[$rw['idcsu']] = ($rw['permiso'] > 0 ? $rw['permiso'] : -1 );
                }
            }
        }
        return $arr;
    }

    /**/

    public function getCsuEfectivo()
    {
        $csu = new UsrCsu();

        $this->_treeCsu = $csu->getTree(false);
        $this->_arrCsu = array();

        //Agrega los permisos asignados para los Grupos.
        $this->_arrPrm = $this->getCsuPorGrupo();
        if ($arrRaiz = $csu->getDataSet('idcsu_padre < 1'))
        {
            foreach ($arrRaiz as $rw)
            {
                $this->__ExtiendeCsuPermiso($rw['idcsu'],$this->_arrPrm[$rw['idcsu']],'grp');
            }
        }


        //Agrega los permisos asignados para los Usuarios.
        $this->_arrPrm = $this->getCsuPorUsuario();
        if ($arrRaiz = $csu->getDataSet('idcsu_padre < 1'))
        {
            foreach ($arrRaiz as $rw)
            {
                $this->__ExtiendeCsuPermiso($rw['idcsu'],$this->_arrPrm[$rw['idcsu']],'usr');
            }
        }

        $arrCsu = array();
        $this->data['CSUInDB'] = array();
        foreach ($this->_arrCsu as $idcsu => $rw)
        {
            $permiso = $rw['permiso'];
            if ($this->data['idperfil'] == self::PERFIL_CNS && $rw['permiso'] > self::PERMISO_CNS)
                $permiso = self::PERMISO_CNS;

            $this->data['CSUInDB'][$rw['CodigoCSU']] = $idcsu;

            if ($permiso >= $rw['permiso_minimo'])
                $arrCsu[$rw['CodigoCSU']] = true;
        }

        return $arrCsu;
    }

    /**/
    private function __ExtiendeCsuPermiso($idcsu,$prm,$tipoPrm)
    {
        $this->_arrPrm;

        $this->_arrCsu[$idcsu]['CodigoCSU']      = $this->_treeCsu[$idcsu]['CodigoCSU'];
        $this->_arrCsu[$idcsu]['permiso_minimo'] = $this->_treeCsu[$idcsu]['permiso_minimo'];

        if ($tipoPrm == 'grp' )
            if ($prm > $this->_arrPrm[$idcsu] )
                $this->_arrCsu[$idcsu]['permiso'] = $prm;
            else
                $this->_arrCsu[$idcsu]['permiso'] = $this->_arrPrm[$idcsu];

        if ($tipoPrm == 'usr')
            if ($this->_arrPrm[$idcsu])
                $this->_arrCsu[$idcsu]['permiso'] = $this->_arrPrm[$idcsu];
            elseif ($prm)
                $this->_arrCsu[$idcsu]['permiso'] = $prm;


        if ($hijos = $this->_treeCsu[$idcsu]['hijos'])
        {
            foreach ($hijos as $idcsu_hijo)
            {
                $this->__ExtiendeCsuPermiso($idcsu_hijo,$this->_arrCsu[$idcsu]['permiso'],$tipoPrm);
            }
        }
    }

    function getClientesDisponibles()
    {
        if ($this->data['idusuario'])
        {
            $accData = unserialize($this->data['acceso_datos']);
            $accDataCli = $accData['cli'];

            if ($accDataCli == 'all')
                return $accDataCli;

            if (substr($accDataCli,0,3)=='add')
            {
                $aux = explode(':',$accDataCli);
                return $aux[1];
            }
        }
        return null;
    }

    function getCouriersDisponibles()
    {
        if ($this->data['idusuario'])
        {
            $accData = unserialize($this->data['acceso_datos']);
            $accDataCou = $accData['cou'];

            if ($accDataCou == 'all')
                return $accDataCou;

            if (substr($accDataCou,0,3)=='add')
            {
                $aux = explode(':',$accDataCou);
                return $aux[1];
            }
        }
        return null;
    }

    function getPaisesDisponibles()
    {
        if ($this->data['idusuario'])
        {
            $accData = unserialize($this->data['acceso_datos']);
            $accDataPais = $accData['pais'];

            if (!$accDataPais)
                return 'all';

            return $accDataPais;

        }
        return null;
    }

    function saveLog()
    {
        if (defined('LOG_PATH'))
        {
            if ($_REQUEST['mod'] == '_lib' && $_REQUEST['ctrl'] == 'SessionTimeoutAjax' && $_REQUEST['act'] == 'getStatus')
                return;

            $url = $_SERVER['REQUEST_URI'];
            $strToLog = date('Ymd His').' '.$url;

            if (!empty($_POST))
                foreach($_POST as $k => $v)
                    if (is_string($v))
                        $strToLog .= '&'.$k.'='.(strlen($v)<20 ? strlen($v) : substr($v,0,20).'...');

            $logFolder = LOG_PATH.date('Ym');
            if (!is_dir($logFolder))
                mkdir($logFolder, 0777, true);

            $fileName = $logFolder.'/'.date('d').'_'.$this->get('username').'.criptoLog';

            file_put_contents($fileName, $strToLog."\n", FILE_APPEND);
        }
        
    }

    function isAdmin()
    {
        if ($this->data['idperfil'] == self::PERFIL_ADM)
            return true;
        return false;        
    }

}
?>