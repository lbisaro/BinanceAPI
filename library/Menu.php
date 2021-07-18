<?php

/**
 * Generador javascript para menu de opciones DHTML
 *
 * Basado en modulo javascript dhtml.menu
 *
 * La funcion de la clase es instanciar un menu para crear la raiz del
 * mismo, y luego ir agregando Items, a los cuales se le podran agregar
 * subitems, pero en todos los casos son tratados como Items.<br>
 * Cada Item agregado tiene la particularidad de poder almacenar sus
 * propios atributos, ademas de un array de punteros a items (Hijos)
 *
 * Ejemplo de modo de uso
 *
 * <code>
 * <?php
 *
 * //Crea el menu $mnu.
 * $mnu = new Menu(); // Instancia el menu
 *
 * // Agrega un item al menu $mnu, y lo almacena en el puntero $mnuSsn.
 * $mnuSsn = $mnu->addItem('Sesion');
 *
 * // Agrega 4 items al menu $mnuSsn, y los almacena en los punteros $mnuSsn1, $mnuSsn2, $mnuSsn3 y $mnuSsn4 respectivamente.
 * // Agrega un separador entre medio de los items 2 y 3
 *
 * $mnuSsn1 = $mnuSsn->addItem('Nueva ventana','index.php?act=ssnIniScreen','_blank');<br>
 * $mnuSsn2 = $mnuSsn->addItem('Ver pagina inicial del sistema','index.php?act=ssnIniScreen');<br>
 * $mnuSsn->addSeparator();
 * $mnuSsn3 = $mnuSsn->addItem('Ver datos personales','index.php?act=usrVer&idusuario='.$auth->idusuario);<br>
 * $mnuSsn4 = $mnuSsn->addItem('Cerrar sesion','index.php?act=ssnCerrar');<br>
 *
 * // Agrega 2 items al menu $mnuSsn3, y los almacena en los punteros $mnuSsn3_1 y $mnuSsn3_2 respectivamente.<br>
 * $mnuSsn3_1 = $mnuSsn3->addItem('Sub item nivel 3 (1)','http://www.google.com.ar','_blank');<br>
 * $mnuSsn3_2 = $mnuSsn3->addItem('Sub item nivel 3 (1)','http://www.yahoo.com.ar','_blank');<br>
 *
 * // Vuelve a agregar un item en la raiz del menu ($mnu)<br>
 * $mnuAdm = $mnu->addItem('Administracion');<br>
 *
 * // Agrega a mas items a menu Administracion<br>
 * $mnuAdm1 = $mnuAdm->addItem('Nueva ventana','index.php?act=ssnIniScreen','_blank');<br>
 * $mnuAdm2 = $mnuAdm->addItem('Ver pagina inicial del sistema','index.php?act=ssnIniScreen');<br>
 *
 * //El item agregado a continuacion no será mostrado en el menu por no tener ni hijos ni URL asociada.<br>
 * $mnuNew = $mnu->addItem('Nuevo');<br>
 *
 * echo $mnu->getJavascript();<br>
 * ?>
 * </code>
 *
 * @package myLibrary
 */
class Menu
{
	protected $titulo;
	protected $url;
	protected $target;
	protected $hijos = array();
	protected $id;
	protected $item = 0;
	protected $parameters = array();
	protected $oldMM = array();

	const SEPARATOR = '[-]';
    const ROOT_ID = 0;


	/**
	* El constructor se utiliza para instanciar tanto el el ROOT del menu,
    * como para los items, ya que cada item, en si es un nuevo menu, por
    * contener o no, sus propios items.
	*
	* Al recibir $titulo = null, se configura como raiz del menu, y en caso contrario
	* se instanciara como un item,
	*
	*/
	function Menu($titulo=null, $url=null, $target=null, $id =null)
	{
		$this->titulo = $titulo;
		$this->url = $url;
		$this->target = $target;
		if (!$id)
            $id = 'mnu_'.rand(100,99999);
        $this->id = $id;
		$this->hijos = array();
	}

	/**
	* Agregar Items al menu.
	*
	* sbIt: Variable en la que se almacenara el item, y
    * que luego servira para agrega items dentro de la misma.<br>
	* titulo: Titulo del item<br>
	* url: Link a ejecutar al clickear sobre el item<br>
	* target: Destino en el que se abrira el link.
    * (Ej.: _target para que abra en una nueva ventana.)
	*/
	function addItem($titulo, $url=null, $target=null, $id =null)
	{
        $this->item++;
        if(!$id)
            $id = $this->id.'_'.rand(100,99999);

        $new = new Menu($titulo, $url, $target, $id);
        $this->hijos[] = &$new;
		return $new;
	}

	/**
	* Agregar un Separador al menu.
	*
	*/
	function addSeparator()
	{
		if(!empty($this->hijos))
			$this->hijos[] = new Menu(Menu::SEPARATOR);
	}

    function tieneUrl(&$menu)
    {
   		$q = count($menu->hijos) ;
   		if ($menu->url)
   			return true;

   		if ($q > 0)
   		{
            for($i=0; $i < $q; $i++)
               	if ($this->tieneUrl($menu->hijos[$i]))
               		return true;
            return false;
        }
        return false;
   	}

    function getId()
    {
        return $this->id;
    }

    function getHijos()
    {
        return $this->hijos;
    }

    function get()
    {
        $data['id'] = $this->id;
        if ($this->titulo)
            $data['titulo'] = $this->titulo;
        if (!empty($this->hijos))
            foreach ($this->hijos as $hijo)
                $data['hijos'][] = $hijo->get();
        return $data;
    }
}

?>