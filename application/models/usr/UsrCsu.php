<?php
include_once LIB_PATH."Model.php";
include_once LIB_PATH."Tree.php";
include_once MDL_PATH."usr/UsrUsuario.php";

/**
 * @package SGi_Models
 */
class UsrCsu extends Model
{
    static protected $db     = DB_NAME;
    static protected $table  = 'csu';
    static protected $idName = 'idcsu';

    static protected $cachedTree = array();

    function formatData()
    {
        if (!$this->data['idFull'])
        {
            $csuTree = $this->getTree();

            $arr['idFull']            = $csuTree[$this->data['idcsu']]['idFull'];
            $arr['nameFull']          = $csuTree[$this->data['idcsu']]['nameFull'];
            $arr['ruta']              = str_replace($this->data['nombre'],'',$arr['nameFull']);
            $arr['nodeLevel']         = $csuTree[$this->data['idcsu']]['nodeLevel'];
            $arr['hijosHtml']         = $csuTree[$this->data['idcsu']]['hijosHtml'];
            $arr['CodigoCSU']         = $csuTree[$this->data['idcsu']]['CodigoCSU'];

            $this->data['idFull']     = $arr['idFull'];
            $this->data['nameFull']   = $arr['nameFull'];
            $this->data['ruta']       = $arr['ruta'];
            $this->data['nodeLevel']  = $arr['nodeLevel'];
            $this->data['hijosHtml']  = $arr['hijosHtml'];
            $this->data['CodigoCSU']  = $arr['CodigoCSU'];

        }

        $arr['strPermiso_minimo']     = UsrUsuario::getPermisosDeUsuario($this->data['permiso_minimo']);
        
        
        return $arr;

    }

    function formatFields()
    {
        $arr['idFull']    = 'Id Completo';
        $arr['nameFull']  = 'Nombre y ruta';
        $arr['ruta']      = 'Ruta';
        $arr['nodeLevel'] = 'Nivel en arbol';
        $arr['hijosHtml'] = 'CSU y dependientes';        
        
        $arr['CodigoCSU'] = 'Codigo de CSU';

        $arr['strPermiso_minimo']  = 'Permiso minimo de acceso';
        $arr['permiso_minimo']     = $arr['strPermiso_minimo'];

        return $arr;
    }

    /**
    * Devuelve un array con los idusuario relacionados con el Caso de uso,
    * o bien un array vacio en caso que no encuentre ningun usuario relacionado
    *
    * @return array[int idusuario]
    */
    public function getUsuarios($per = 0)
    {
        $arr = array();
        if ($this->data['idcsu'])
        {
            $this->query('SELECT idusuario, permiso FROM usuario_x_csu WHERE idcsu = '.$this->data['idcsu']);
            while ($rw = $this->fetch_array())
            {
                if($per!=0)
                {
                    $arr[$rw['idusuario']]['idusuario']= $rw['idusuario'];
                    $arr[$rw['idusuario']]['permiso']= $rw['permiso'];
                }else{
                    $arr[$rw['idusuario']] = $rw['idusuario'];
                }
            }
        }        
        return $arr;
    }

    /**
    * Devuelve un array con los csus hijos del csu instanciado,
    * o bien un array vacio en caso que no encuentre ningun hijo
    *
    * @return array[int idusuario]
    */
    public function getHijos()
    {
        $arr = array();
        if ($this->data['idcsu'])
        {
            $this->query('SELECT * FROM '.self::$db.'.'.self::$table.' WHERE idcsu_padre = '.$this->data['idcsu']);
            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idcsu']] = $rw;
            }
        }       
        return $arr;
    }
    
    public function getGrupos()
    {
        $arr = array();
        if ($this->data['idcsu'])
        {
            $this->query('SELECT idgrupo,permiso FROM usuario_grupo_x_csu WHERE idcsu = '.$this->data['idcsu']);
            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idgrupo']]['idgrupo'] = $rw['idgrupo'];
                $arr[$rw['idgrupo']]['permiso'] = $rw['permiso'];
            }
        }
      
        return $arr;
    }
    
    public function getTree($html =  true)
    {   
        if (empty(self::$cachedTree))
        {
            if ($fetchAll = $this->getDataSet(null,'nombre'))
            {
                $tree = new Tree('csu','Casos de uso');
                foreach ($fetchAll as $rw)
                {
                    $imgClose = IMG_PATH.'usrCsu.png';
                    $imgOpen  = IMG_PATH.'usrCsu.png';
                    if (!empty($rw['hijos']) && count($rw['hijos']) > 0)
                    {
                        $imgClose = IMG_PATH.'usrCsuGroup.png';
                        $imgOpen  = IMG_PATH.'usrCsuGroupOpen.png';
                    }
                    $tree->add($rw['idcsu'], $rw['idcsu_padre'], $rw['nombre'], $imgClose, $imgOpen);
                    $codigos[$rw['idcsu']] = $rw['codigo'];

                }

                $csus = array();

                foreach ($fetchAll as $rw)
                {
                    $arr['idcsu']       = $rw['idcsu'];
                    $arr['idcsu_padre'] = $rw['idcsu_padre'];
                    $arr['nombre']      = $rw['nombre'];
                    $arr['codigo']      = $rw['codigo'];
                    $arr['permiso_minimo'] = $rw['permiso_minimo'];


                    $arr['idFull']      = $tree->getIdFull($rw['idcsu']);
                    $arr['nameFull']    = $tree->getNameFull($rw['idcsu']);
                    $arr['nodeLevel']   = $tree->getNodeLevel($rw['idcsu']);

                    if($html)
                        $arr['hijosHtml']   = $tree->getHtml($rw['idcsu']);

                    $arr['hijos']       = $tree->getSubNodes($rw['idcsu']);

                    $arr['nameFull']    = $tree->getNameFull($rw['idcsu']);

                    $arrCodigo = explode('.',$arr['idFull']);
                    $csuCod ='';
                    foreach($arrCodigo AS $csuId)
                    {
                        $csuCod .= ($csuCod!='' ? '.'.$codigos[$csuId]:$codigos[$csuId]);
                    }
                    $arr['CodigoCSU']   = $csuCod;

                    $keyToSort = (!empty($arr['hijos'])?'2':'1').'_'.$arr['nombre'].'_'.$arr['idcsu'];
                    $csusTmp[$keyToSort] = $arr;
                }

                uksort($csusTmp,'cmp');

                foreach ($csusTmp as $csu)
                    $csus[$csu['idcsu']] = $csu;

                self::$cachedTree = $csus;
            }
        }
        return self::$cachedTree;
    }

    public function getRelaciones()
    {
        if (parent::getDeleteBlock())
            return true;

        $qtyUsuarios = count($this->getUsuarios());
        if ($qtyUsuarios > 0)
            return true;

        $qtyHijos = count($this->getHijos());
        if ($qtyHijos > 0)
            return true;

        return false;
    }

    public function getParentPerMin()
    {
        $tree = $this->getTree();
        $parent = $tree[$this->data['idcsu_padre']];
        if ($parent['permiso_minimo'] >= $this->data['permiso_minimo'])
            return $parent['permiso_minimo'];
        else
            return $this->data['permiso_minimo'];
            
    }

    function getInput($field,$tipo='',$att = array())
    {
        $value = $this->data[$field];

        if ($field == 'permiso_minimo')
        {

            $permisos = UsrUsuario::getPermisosDeUsuario();
            $idp = $this->data['idcsu_padre'];
            $permiso_base = $this->exec_select('SELECT permiso_minimo FROM csu WHERE idcsu = '.$idp,2);
            foreach ($permisos as $idpermiso => $permisoDeUsuario)
            {
                if($idpermiso && $idpermiso >= $permiso_base)
                    $options[$idpermiso] = $permisoDeUsuario;
            }

            $input = Html::getTagSelect($field,$options,$value,$att);
        }

        if ($field == 'codigo')
        {    
            $idp = $this->data['idcsu_padre'];
            $csup = new UsrCsu($idp);
            $cc = parent::getInput($field,$tipo,$att);
            $cc['data'] = ($idp!= 0)? $csup->get('CodigoCSU').'. '.$cc['data']: $cc['data'];
            return $cc;
        }

        if ($input)
            return array('label'=>$this->getFields($field),'data'=>$input,'id' => $field);
        else
            return parent::getInput($field,$tipo,$att);
    }

    public function addGrupo($idgrupo,$permiso)
    {
        if ($this->data['idcsu'])
        {
            if ($idgrupo && $permiso)
            {
                if (!$this->exec_select('SELECT idgrupo,permiso FROM usuario_grupo_x_csu WHERE idcsu = '.$this->data['idcsu'].' AND idgrupo = '.$idgrupo))
                {
                    if ($this->query('INSERT INTO usuario_grupo_x_csu (idgrupo,idcsu,permiso) VALUES ('.$idgrupo.','.$this->data['idcsu'].','.$permiso.')'))
                        return true;
                }
                else
                {
                    if ($this->query('UPDATE usuario_grupo_x_csu SET permiso = '.$permiso.' WHERE idcsu = '.$this->data['idcsu'].' AND idgrupo = '.$idgrupo))
                        return true;
                }
            }
            else
            {
                $this->addErr('Se debe especificar idgrupo y permiso.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idcsu.');
        }
        return false;
    }

    public function delGrupo($idgrupo)
    {
        if ($this->data['idcsu'])
        {
            if ($idgrupo)
            {
                if ($this->query('DELETE FROM usuario_grupo_x_csu WHERE idcsu = '.$this->data['idcsu'].' AND idgrupo = '.$idgrupo))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idgrupo.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idcsu.');
        }
        return false;
    }

    public function validReglasNegocio()
    {               
        $err     = 0;
        $tree    = $this->getTree();
        $idP     = $tree[$this->data['idcsu']]['idcsu_padre'];
        $Pcodigo = $tree[$idP]['codigo'];
        $Phijos  = $tree[$idP]['hijos'];
        
        if(!empty($Phijos))
        {
            foreach($Phijos AS $ph)
            {
                if($ph != $this->data['idcsu'])
                {
                    if($tree[$ph]['codigo'] != $this->data['codigo'])
                        $return = true;
                    else
                    {                    
                        $this->addErr('Ya se encuentra un Caso de Uso registrado con el codigo: '.$this->data['codigo'].'  en el modulo: '.$Pcodigo);
                        $err++;
                    }                       
                }            
            }
        }           
    
             
        if($err==0)  
            return true;
        else       
            return false;        
    }

    public function eliminar()
    {
        $err = 0;
        if (!$this->data[self::$idName] )
        {
            $this->addErr('Se debe especificar un idcsu a eliminar.');
            return false;
        }

        if ($this->getRelaciones())
        {
            $this->addErr('No es posible eliminar el CSU por tener relaciones activas.');
            return false;
        }

        $id = $this->data[self::$idName];

        if ($this->query("DELETE FROM ".self::$db.".".self::$table." WHERE ".self::$idName." = ".$this->data[self::$idName]))
        {
            return true;
        }
        else
        {
            $this->addErr('No es posible ejecutar el query para eliminar el registro');
        }
        return false;
    }
    
    public function addUsuario($idusuario,$permiso)
    {
        if ($this->data['idcsu'])
        {
            if ($idusuario)
            {
                if (!$this->exec_select('SELECT idusuario,permiso FROM usuario_x_csu WHERE idcsu = '.$this->data['idcsu'].' AND idusuario = '.$idusuario))
                {
                    if ($this->query('INSERT INTO usuario_x_csu (idusuario,idcsu,permiso) VALUES ('.$idusuario.','.$this->data['idcsu'].','.$permiso.')'))
                        return true;
                }
                else
                {
                    if ($this->query('UPDATE usuario_x_csu SET permiso = '.$permiso.' WHERE idcsu = '.$this->data['idcsu'].' AND idusuario = '.$idusuario))
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
            $this->addErr('Se debe especificar idcsu.');
        }
        return false;
    }
    
    public function delUsuario($idusuario)
    {
        if ($this->data['idcsu'])
        {
            if ($idusuario)
            {
                if ($this->query('DELETE FROM usuario_x_csu WHERE idcsu = '.$this->data['idcsu'].' AND idusuario = '.$idusuario))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idusuario.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idcsu.');
        }
        return false;
    }

    function testToProduccion($update=false)
    {
        GLOBAL $serverEntornoIp;

        $dbTest = new Sql();
        $dbProd = new Sql();

        $log = array();
        $err = array();

        if (count($serverEntornoIp) > 2)
            criticalExit('No es posible especificar la IP de los servidores de TEST y PRODUCCION dado que existen mas de 2 servidores. (Ver config.php $serverEntornoIp)');

        foreach ($serverEntornoIp as $ip => $entorno)
        {
            if (strtoupper($entorno) == 'TEST')
                $ipTest = $ip;
            else
                $ipProd = $ip;
        }

        if (!isset($ipTest))
            criticalExit('No fue posible especificar la IP del servidor de TEST (Ver config.php $serverEntornoIp)');

        if (!isset($ipProd))
            criticalExit('No fue posible especificar la IP del servidor de PRODUCCION (Ver config.php $serverEntornoIp)');


        if ($dbTest->Connect($ipTest,DB_USER,DB_PASSWORD,'usuario'))
            $log[] = 'Conexion con el server de TEST: OK';
        else
            $err[] = 'No fue posible conectar con el servidor MySQL de TEST ['.$ipTest.']';

        if (empty($err))
        {
            $dbTest->query('SELECT * FROM csu ORDER BY idcsu');
            $arrTest = $dbTest->fetch_all();
            if (!empty($arrTest))
            {

                $ins = "INSERT INTO csu (idcsu, nombre, codigo, detalle, idcsu_padre, permiso_minimo) VALUES ";
                $insValues = null;
                $syncRows = 0;
                foreach ($arrTest as $rw)
                {
                    $syncRows++;
                    $insValues .= ($insValues ? ' , ' : '').' ( '.
                                  "'".$rw['idcsu']."' , ".
                                  "'".$rw['nombre']."' , ".
                                  "'".$rw['codigo']."' , ".
                                  "'".$rw['detalle']."' , ".
                                  "'".$rw['idcsu_padre']."' , ".
                                  "'".$rw['permiso_minimo']."' ) ";

                }
                $ins .= $insValues;

                $log[] = 'Se detectaron '.$syncRows.' registros para sincronizar en el server de TEST.';

                if (!$update)
                {
                    $log[] = '<a href="'.Controller::getLink('usr','usrCsu','testToProduccion','update=OK').'">Hacer click para actualizar '.$syncRows.' registros para sincronizar los casos de uso desde la IP '.$ipTest.' hacia la IP '.$ipProd.'.</a>';                    
                    $log[] = 'Query:<br/>TRUNCATE TABLE csu;<br/>'.$ins.';';
                }
        
                if ($dbProd->Connect($ipProd,DB_USER,DB_PASSWORD,'usuario'))
                    $log[] = 'Conexion con el server de PRODUCCION: OK';
                else
                    $err[] = 'No fue posible conectar con el servidor MySQL de PRODUCCION ['.$ipProd.']';

                if ($update)
                {
                    $dbProd->query('TRUNCATE TABLE csu');
                    $log[] = 'Se eliminaron todos los registros en el server de PRODUCCION.';
    
                    $dbProd->query($ins);
                    $dbProd->query("SELECT count(idcsu) qty FROM csu");
                    $rw = $dbProd->fetch_array();
                    $log[] = 'Se insertaron '.$rw['qty'].' registros en el server de PRODUCCION.';
    
                }


                if ($update && $rw['qty'] != $syncRows)
                {
                    $this->addErr('No fue posible sincrinizar todos los registros.');
                    $this->addErr($log);
                    return null;
                }

                $log[] = 'El proceso finalizo satisfactoriamente';
                return $log;
                
            }
        }
        else
        {
            $this->addErrLog($err);
        }
        return null;
    }
    
    function actualizarCsu($response_ws){
        $dbLocal = new Sql();
        $csuActualizados = $response_ws;

        if ($dbLocal->Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME)){
            $log[] = 'Conexion con la DB local: OK';
        }
        else{
            $err[] = 'No fue posible conectar con el servidor MySQL';
        }

        if (empty($err)){
            $dbLocal->query('TRUNCATE TABLE csu');
            $log[] = 'Se eliminaron todos los registros de tabla csu.';
            
            $dbLocal->query($csuActualizados);
            $dbLocal->query("SELECT count(idcsu) qty FROM csu");
            $rw = $dbLocal->fetch_array();
            $log[] = 'Se insertaron '.$rw['qty'].' registros en la tabla csu.';
            $log[] = 'El proceso finalizo satisfactoriamente';
            return $log;
        }
        else{
            $this->addErrLog($err);
        }
        return null;
    }
}
?>