<?php
include_once LIB_PATH."Model.php";
include_once MDL_PATH."usr/UsrUsuario.php";

/**
 * @package SGi_Models
 */
class UsrPermiso extends Model
{
    static protected $db     = DB_NAME;
    static protected $table  = 'permiso';
    static protected $idName = 'idpermiso';

    function getMatriz()
    {
        $matriz = array();

        $prmDataSet = $this->getDataSet('','descripcion');
        foreach($prmDataSet as $rwP)
        {
            $matriz['permiso'][$rwP['idpermiso']] = $rwP;
            $permisos[]=$rwP['idpermiso'];
        }

        $usr = new UsrUsuario();
        $usrDataSet = $usr->getDataSet('idperfil > 0 ','username');
        foreach($usrDataSet as $rwU)
        {
            $matriz['usuario'][$rwU['idusuario']] = $rwU;

            $usr->set($rwU);
            $usr->loadPermisos();
            foreach($permisos as $idpermiso)
            {
                $matriz['permiso_usuario_perfil'][$idpermiso][$rwU['idusuario']] = $usr->checkPermiso($idpermiso);
            }
        }


        return $matriz;

    }
}

?>