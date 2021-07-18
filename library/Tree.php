<?php
/**
 * Tree
 *
 * Clase utilizada para crear una estructura dinamica tipo arbol,
 * a partir de un array de datos identificado con id e idParent.
 *
 *
 * Para crear el arbol se debe contar con un array de elementos, y cada uno de estos
 * elementos debe ser un array que contenga lo siguiente:
 *
 * Key 0: Id
 * Key 1: IdPadre
 * Key 2: Nombre de la hoja
  *
 * El uso de la clase se realiza de la siguiente manera:
 *
 * <code>
 *
 *
 * $arr[] = array('1','0','Item 0.1');
 * $arr[] = array('2','0','Item 0.2');
 * $arr[] = array('7','1','Item 1.7');
 * $arr[] = array('4','2','Item 2.4');
 * $arr[] = array('5','2','Item 2.5');
 * $arr[] = array('6','2','Item 2.6');
 * $arr[] = array('8','3','Item 3.8');
 * $arr[] = array('9','3','Item 3.9');
 * $arr[] = array('3','6','Item 6.3');
 *
 * $tree = new Tree($arr);
 *
 * echo $tree;
 *
 * </code>
 *
 * Con este codigo se muestran los datos del array enviado al constructor,
 * en forma de lista HTML tipo <ul> <li></li> <li></li> </ul>.
 *
 * @package myLibrary
 */

class Tree
{
    protected $root;
    protected $nodes = array();
    protected $links = array();
    protected $treeId;
    protected $treeName;

    const ROOT_ID = 0;
    const ROOT_NAME = 'Inicio';
    const ID_SEPARATOR = '.';
    const NAME_SEPARATOR = ' / ';

    /**
    * El constructor debe recibir un array de elementos que contengan los siguientes Campos:
    * id, name, idParent.
    *
    */
    function __Construct($treeId, $rootName, $icon=null, $iconOpen=null, $isOpen=true)
    {
        $this->setId( ( $treeId ? $treeId : 'rndId_'.rand(100,999) ) );
        $this->setName( ( $rootName ? $rootName : self::ROOT_NAME ) );

        // Creando la raiz del arbol
        $this->add(0, -1, $this->treeName, $icon, $iconOpen, $isOpen );

    }

    public function setId($treeId)
    {
        $treeId = trim(str_replace(' ','',$treeId));
        if (!empty($treeId))
            $this->treeId = $treeId;
    }

    public function setName($treeName)
    {
	    $this->treeName = $treeName;
    }

    function add($id, $idParent, $name, $icon=null, $iconOpen=null, $isOpen=false)
    {
        $newNode['id']          = $id;
        $newNode['idParent']    = $idParent;
        $newNode['name']        = $name;
        $newNode['icon']        = $icon;
        $newNode['iconOpen']    = $iconOpen;
        $newNode['isOpen']      = $isOpen;

        $this->nodes[$id] = $newNode;
        $this->links[$idParent][$id] = $id;
    }

    function addNodesFromArray($arr)
    {
        if (is_array($arr))
        {
            // Construyendo todos los nodos y almacenandolos en un array identificado con su Id
            foreach ($arr as $it)
            {
                if (!$_keyId)
                {
                	$pass=0;
                    foreach ($it as $k => $v)
                    {
                        if ($pass==0)
                            $_keyId = $k;
                        if ($pass==1)
                            $_keyIdParent = $k;
                        if ($pass==2)
                            $_keyName = $k;
                        if ($pass==3)
                            $_keyIcon = $k;
                        if ($pass==4)
                            $_keyIconOpen = $k;
                        if ($pass==5)
                            $_keyIsOpen = $k;

                        $pass++;
                    }
                }
                $this->add($it[$_keyId],$it[$_keyIdParent],$it[$_keyName],$it[$_keyIcon],$it[$_keyIconOpen],$it[$_keyIsOpen]);
            }
        }
    }

    /**
    * Devuelve una cadena de caracteres HTML con el menu.
    *
    * Dependiendo del valor del parametro $mode, la salida puede ser:
    *
    * $mode = 'JScript' => Menu Html dinamico
    * ['default'] lista Html formateada con tags <ul> y <li>
    */
    function getHtml($id=null,$mode=null)
    {
        if (!isset($this->nodes[$id]))
            $id = self::ROOT_ID;

        if ($mode == 'static')
        {
            return $this->getStaticHtml($id);
        }
        else
        {
            return $this->getDynamicHtml($id);
        }
    }

	private function getStaticHtml($id)
	{
        $level = $this->getNodeLevel($id);
        $node = $this->nodes[$id];
        if ($node['name'])
		{
			$echo = "<li ".($node['id']?"id=\"tree_li_".$node['id']."\"":"")." class=\"tree_li tree_level_".$level."\">";
            $echo .= $node['name'].' ['.$node['id'].']'."</li>";
		}

        $subNodes = $this->getSubNodes($id);

        if (!empty($subNodes))
        {
            $echo .= "<ul ".($node['id']?"id=\"tree_ul_".$node['id']."\"":"")." class=\"tree_ul tree_level_".$level."\">";

            foreach($subNodes as $subNodeId)
                $echo .= $this->getStaticHtml($subNodeId);

            $echo .= "</ul>";
        }

		return $echo;

	}

	private function getDynamicHtml($id=self::ROOT_ID)
	{
        if (!isset($this->nodes[$id]))
            $id = self::ROOT_ID;

        $node = $this->nodes[$id];

        $html = "<script type=\"text/javascript\" language=\"javascript\"> ".
                 "\n"."\n".$this->treeId." = new Tree ('".$this->treeId."');";

        $html .= "\n".$this->getDynamicHtml_(self::ROOT_ID);

        $html .= "\n"."\n"."document.write(".$this->treeId.");";

        $html .= "\n"."\n".$this->treeId.".openTo(".$id.",true);";

        $html .= "\n"."\n"."</script>";

        return $html;
    }


    private function getDynamicHtml_($id)
    {
        if (!isset($this->nodes[$id]))
            $id = self::ROOT_ID;

        $node = $this->nodes[$id];

        if ($node['name'])
        {
            $echo = "\n".$this->treeId.".add(".$node['id'] .",".$node['idParent'].",'".$node['name']."','".$node['icon']."','".$node['iconOpen']."',".($node['isOpen']?'true':'false').");";
        }

        $subNodes = $this->getSubNodes($id);

        if (!empty($subNodes))
            foreach($subNodes as $subNodeId)
                $echo .= $this->getDynamicHtml_($subNodeId);

		return $echo;

	}


    public function getSubNodes($id)
    {
        $subNodes = array();
        if ($this->links[$id])
	        $subNodes = $this->links[$id];
        return $subNodes;
    }



    /**
    * Devuelve el nivel jerarquico que ocupa un nodo
    */
    public function getNodeLevel($id)
    {
        if (isset($this->nodes[$id]))
        {
            $aux = $this->nodes[$id];
            if ($aux['idParent']>0)
                return (1+$this->getNodeLevel($aux['idParent']));
            else
                return 1;
        }
        return 0;
    }

    public function getIdFull($id = null,$sep = null)
    {
        if (!isset($this->nodes[$id]))
            $node = $this->nodes[self::ROOT_ID];
        else
            $node = $this->nodes[$id];

        if (!$sep)
            $sep = self::ID_SEPARATOR;

        if ($node)
        {
            $idFull = $node['id'];
            while ($node && $idParent = $node['idParent'])
            {
                if ($idParent)
                {
                    $node = $this->nodes[$idParent];
                    $idFull = $node['id']. $sep .$idFull;
                }
                else
                {
                    $node = null;
                }
            }
            return $idFull;
        }
        return null;
    }

    public function getNameFull($id = null,$sep = null)
    {
        if (!isset($this->nodes[$id]))
            $node = $this->nodes[self::ROOT_ID];
        else
            $node = $this->nodes[$id];

        if (!$sep)
            $sep = self::NAME_SEPARATOR;

        if ($node)
        {
            $nameFull = $node['name'];
            while ($node && $idParent = $node['idParent'])
            {
                if ($idParent)
                {
                    $node = $this->nodes[$idParent];
                    $nameFull = $node['name']. $sep .$nameFull;
                }
                else
                {
                    $node = null;
                }
            }
            return $nameFull;
        }
        return null;
    }

    public function getNameParent($id = null,$sep = null)
    {
        if (!isset($this->nodes[$id]))
            $node = $this->nodes[self::ROOT_ID];
        else
            $node = $this->nodes[$id];

        if (!$sep)
            $sep = self::NAME_SEPARATOR;

        if ($node)
        {
            $nameFull = '';
            while ($node && $idParent = $node['idParent'])
            {
                if ($idParent)
                {
                    $node = $this->nodes[$idParent];
                    $nameFull = $node['name']. $sep .$nameFull;
                }
                else
                {
                    $node = null;
                }
            }
            return $nameFull;
        }
        return null;
    }

    /**
    * Permite hacer un echo del objeto instanciado, mostrando una cadena de caracteres
    */
    function __toString()
    {
        return $this->getHtml();
    }
}


?>