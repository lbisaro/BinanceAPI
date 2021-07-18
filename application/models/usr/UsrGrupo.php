<?php
include_once LIB_PATH."Model.php";
include_once LIB_PATH."Tree.php";

/**
 * @package SGi_Models
 */
class UsrGrupo extends Model
{
    static protected $db     = DB_NAME;
    static protected $table  = 'usuario_grupo';
    static protected $idName = 'idgrupo';

    static protected $cachedTree = array();

    function formatData()
    {
        if (!$this->data['idFull'])
        {
            $grpTree = $this->getTree('idgrupo');

            $arr['idFull']           = $grpTree[$this->data['idgrupo']]['idFull'];
            $arr['nameFull']         = $grpTree[$this->data['idgrupo']]['nameFull'];
            $arr['ruta']             = str_replace($this->data['grupo'],'',$arr['nameFull']);
            $arr['nodeLevel']        = $grpTree[$this->data['idgrupo']]['nodeLevel'];
            $arr['hijosHtml']        = $grpTree[$this->data['idgrupo']]['hijosHtml'];
            $this->data['idFull']    = $arr['idFull'];
            $this->data['nameFull']  = $arr['nameFull'];
            $this->data['ruta']      = $arr['ruta'];
            $this->data['nodeLevel'] = $arr['nodeLevel'];
            $this->data['hijosHtml'] = $arr['hijosHtml'];
        }

        $arr['readBlock']        = ($this->getReadBlock()?'Bloqueado':'Permitido');
        $arr['writeBlock']       = ($this->getWriteBlock()?'Bloqueado':'Permitido');
        $arr['deleteBlock']      = ($this->getDeleteBlock()?'Bloqueado':'Permitido');

        $arr['blockFlag']        = 'Leer: '.$arr['readBlock'].
                                   '<br/>Eliminar: '.$arr['deleteBlock'].
                                   '<br/>Editar: '.$arr['writeBlock'];

        return $arr;

    }

    function formatFields()
    {
        $arr['idFull']    = 'Id Completo';
        $arr['nameFull']  = 'Nombre y ruta';
        $arr['ruta']      = 'Ruta';
        $arr['nodeLevel'] = 'Nivel en arbol';
        $arr['hijosHtml'] = 'Grupo y dependientes';

        $arr['blockFlag']        = 'Control del registro';

        $arr['readBlock']        = 'Leer registro';
        $arr['writeBlock']       = 'Editar registro';
        $arr['deleteBlock']      = 'Eliminar registro';

        return $arr;
    }

    function getInput($field,$tipo='',$att = array())
    {
        if ($field=='readBlock')
        {
            $chkR1['CHECKED']='CHECKED';
            $chkR2=array();
            if ($this->getReadBlock())
            {
                $chkR1=array();
                $chkR2['CHECKED']='CHECKED';
            }
            $ret['label'] = $this->getFields('readBlock');
            $ret['data']  = Html::getTagInput('readBlock','0','radio',$chkR1).' Permitido ';
            $ret['data'] .= ' - '.Html::getTagInput('readBlock','1','radio',$chkR2).' Bloqueado';

            return $ret;
        }

        if ($field=='writeBlock')
        {
            $chkW1['CHECKED']='CHECKED';
            $chkW2=array();
            if ($this->getWriteBlock())
            {
                $chkW1=array();
                $chkW2['CHECKED']='CHECKED';
            }
            $ret['label'] = $this->getFields('writeBlock');
            $ret['data']  = Html::getTagInput('writeBlock','0','radio',$chkW1).' Permitido ';
            $ret['data'] .= ' - '.Html::getTagInput('writeBlock','1','radio',$chkW2).' Bloqueado';
            return $ret;
        }

        if ($field=='deleteBlock')
        {
            $chkD1['CHECKED']='CHECKED';
            $chkD2=array();
            if ($this->getDeleteBlock())
            {
                $chkD1=array();
                $chkD2['CHECKED']='CHECKED';
            }
            $ret['label'] = $this->getFields('deleteBlock');
            $ret['data']  = Html::getTagInput('deleteBlock','0','radio',$chkD1).' Permitido ';
            $ret['data'] .= ' - '.Html::getTagInput('deleteBlock','1','radio',$chkD2).' Bloqueado';
            return $ret;
        }

        if ($field=='blockFlag')
        {
            $leer = $this->getinput('readBlock');
            $escribir = $this->getinput('writeBlock');
            $eliminar = $this->getinput('deleteBlock');

            $ret['label'] = $this->getFields('blockFlag');
            $ret['data']  = '<table class="FC">';
            $ret['data'] .= '<tr><th>'.$leer['label'].': </th><td>'.$leer['data'].'</td></tr>';
            $ret['data'] .= '<tr><th>'.$eliminar['label'].': </th><td>'.$eliminar['data'].'</td></tr>';
            $ret['data'] .= '<tr><th>'.$escribir['label'].': </th><td>'.$escribir['data'].'</td></tr>';
            $ret['data'] .= '</table>';

            return $ret;
        }

        return parent::getInput($field,$tipo,$att);
    }

    /**
    * Devuelve un array con los idusuario relacionados con el grupo,
    * o bien un array vacio en caso que no encuentre ningun usuario relacionado
    *
    * @return array[int idusuario]
    */
    public function getUsuarios()
    {
        $arr = array();
        if ($this->data['idgrupo'])
        {
            $this->query('SELECT idusuario FROM usuario_x_grupo WHERE idgrupo = '.$this->data['idgrupo']);
            while ($rw = $this->fetch_array())
                $arr[$rw['idusuario']] = $rw['idusuario'];
        }
        return $arr;
    }

    public function addUsuario($idusuario)
    {
        if ($this->data['idgrupo'])
        {
            if ($idusuario)
            {
                if (!$this->exec_select('SELECT idusuario FROM usuario_x_grupo WHERE idgrupo = '.$this->data['idgrupo'].' AND idusuario = '.$idusuario))
                {
                    if ($this->query('INSERT INTO usuario_x_grupo (idgrupo,idusuario) VALUES ('.$this->data['idgrupo'].','.$idusuario.')'))
                        return true;
                }
                $this->addErr('El usuario ya esta relacionado al grupo.');
            }
            else
            {
                $this->addErr('Se debe especificar idusuario.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idgrupo.');
        }
        return false;
    }

    public function delUsuario($idusuario)
    {
        if ($this->data['idgrupo'])
        {
            if ($idusuario)
            {
                if ($this->query('DELETE FROM usuario_x_grupo WHERE idgrupo = '.$this->data['idgrupo'].' AND idusuario = '.$idusuario))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idusuario.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idgrupo.');
        }
        return false;
    }

    /**
    * Devuelve un array con los idusuario relacionados con el grupo,
    * o bien un array vacio en caso que no encuentre ningun usuario relacionado
    *
    * @return array[int idusuario]
    */
    public function getCsus()
    {
        $arr = array();
        if ($this->data['idgrupo'])
        {
            $this->query('SELECT idcsu,permiso FROM usuario_grupo_x_csu WHERE idgrupo = '.$this->data['idgrupo']);
            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idcsu']]['idcsu'] = $rw['idcsu'];
                $arr[$rw['idcsu']]['permiso'] = $rw['permiso'];
            }
        }
        return $arr;
    }

    public function addCsu($idcsu,$permiso)
    {
        if ($this->data['idgrupo'])
        {
            if ($idcsu && $permiso)
            {
                if (!$this->exec_select('SELECT idcsu,permiso FROM usuario_grupo_x_csu WHERE idgrupo = '.$this->data['idgrupo'].' AND idcsu = '.$idcsu))
                {
                    if ($this->query('INSERT INTO usuario_grupo_x_csu (idgrupo,idcsu,permiso) VALUES ('.$this->data['idgrupo'].','.$idcsu.','.$permiso.')'))
                        return true;
                }
                else
                {
                    if ($this->query('UPDATE usuario_grupo_x_csu SET permiso = '.$permiso.' WHERE idgrupo = '.$this->data['idgrupo'].' AND idcsu = '.$idcsu))
                        return true;
                }
            }
            else
            {
                $this->addErr('Se debe especificar idcsu y permiso.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idgrupo.');
        }
        return false;
    }

    public function delCsu($idcsu)
    {
        if ($this->data['idgrupo'])
        {
            if ($idcsu)
            {
                if ($this->query('DELETE FROM usuario_grupo_x_csu WHERE idgrupo = '.$this->data['idgrupo'].' AND idcsu = '.$idcsu))
                    return true;
            }
            else
            {
                $this->addErr('Se debe especificar idcsu.');
            }
        }
        else
        {
            $this->addErr('Se debe especificar idgrupo.');
        }
        return false;
    }

    /**
    * Devuelve un array con los grupos hijos del grupo instanciado,
    * o bien un array vacio en caso que no encuentre ningun hijo
    *
    * @return array[int idusuario]
    */
    public function getHijos()
    {
        $arr = array();
        if ($this->data['idgrupo'])
        {
            $this->query('SELECT * FROM usuario_grupo WHERE idgrupo_padre = '.$this->data['idgrupo']);
            while ($rw = $this->fetch_array())
            {
                $arr[$rw['idgrupo']] = $rw;
            }
        }
        return $arr;
    }

    public function getTree()
    {
        if (empty(self::$cachedTree))
        {
            if($fetchAll = $this->getDataSet(null,'grupo'))
            {
                $tree = new Tree('uGrp','Grupos de usuarios');

                foreach ($fetchAll as $rw)
                {
    				$tree->add($rw['idgrupo'], $rw['idgrupo_padre'], $rw['grupo'], IMG_PATH.'usrGrupo.gif', IMG_PATH.'usrGrupo.gif');
                }

                $grupos = array();

                foreach ($fetchAll as $rw)
                {
                    $arr['idgrupo']         = $rw['idgrupo'];
                    $arr['idgrupo_padre']   = $rw['idgrupo_padre'];
                    $arr['grupo']           = $rw['grupo'];

                    $arr['idFull']          = $tree->getIdFull($rw['idgrupo']);
                    $arr['nameFull']        = $tree->getNameFull($rw['idgrupo']);
                    $arr['nodeLevel']       = $tree->getNodeLevel($rw['idgrupo']);
                    $arr['hijosHtml']       = $tree->getHtml($rw['idgrupo']);

                    $arr['hijos']           = $tree->getSubNodes($rw['idgrupo']);

                    $keyToSort = (!empty($arr['hijos'])?'2':'1').'_'.$arr['grupo'].'_'.$arr['idgrupo'];

                    $gruposTmp[$keyToSort] = $arr;
                }
                uksort($gruposTmp,'cmp');
                foreach ($gruposTmp as $grp)
                    $grupos[$grp['idgrupo']] = $grp;

                self::$cachedTree = $grupos;
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
            
        $qtyCsus = count($this->getCsus());
        if ($qtyCsus > 0)
            return true;

        $qtyHijos = count($this->getHijos());
        if ($qtyHijos > 0)
            return true;

        return false;

    }

    public function validReglasNegocio()
    {
        return true;
    }

    public function eliminar()
    {
        $err = 0;
        if (!$this->data['idgrupo'] )
        {
            $this->addErr('Se debe especificar un idgrupo a eliminar.');
            return false;
        }

        if ($this->getRelaciones())
        {
            $this->addErr('No es posible eliminar el grupo.');
            return false;
        }

        $id = $this->data['idgrupo'];

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

}

?>