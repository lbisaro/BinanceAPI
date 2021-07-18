<?php
include_once "View.php";
include_once "ErrorLog.php";

/**
 * Controller
 *
 * @package myLibrary
 */
abstract class Controller extends ErrorLog
{
    /**
    * Instancia de la clase View, mediante la cual se mostraran
    * las diferentes vistas.
    */
    protected $view;

    /**
    * Array que contiene el nombre y las variables
    * Estas vistas se pueden obtener mediante el metodo
    * $this->getContent()
    */
    protected $views = array();

    /**
    * Array de cadenas que seran incluidas dentro del tag <HEAD></HEAD>
    */
    private $head = array();

    /**
    * Array de cadenas que seran incluidas dentro del tag <HEAD></HEAD>
    */
    private $links = array();


    /**
    * Cadena que contiene el script a agregar final del </HTML>
    */
    private $js;

    /**
    * Cadena que contiene el script a ejecutarse en el metodo body.onload()
    */
    private $onloadJs;

    /**
    * Titulo de la ventana, que será agregado en <TITLE></TITLE>
    */
    private $title;

    /**
    * Cadena que contiene el campo a l que se deberá hacer focus al cargar la pagina
    */
    private $focus = null;

    /**
    * Array en el que se almacenaran las funciones xajax a registrar
    *
    * Ver Controller::addXajaxFunction() y Controller::getXajaxFunctions()
    *
    */
    private $xajaxFunctions = array();

    /**
    * Crea una instancia de la clase View.
    */
    function Controller()
    {
        $this->view = new View();
    }

    /**
     * Agrega un error al ErrorLog::errLog,
     * complementando el nombre del error con el nombre
     * de la clase que se encuentra instanciada.
     *
     * @param string $error
     * @return void
     */
    function addErr($error)
    {
        if (is_array($error))
            foreach ($error as $errIt)
                ErrorLog::addErr(get_class($this).' :: '.$errIt);
        else
            ErrorLog::addErr(get_class($this).' :: '.$error);
    }

    /**
     * Muestra la vista de errores agregados a ErrorLog::errLog
     * durante el proceso.
     *
     * @return void
     */
    function showErrorLog()
    {
        if ($log = $this->getErrLog())
        {
            $this->view->setTpl('error');
            echo $this->view->get(array('errors'=>$log));
        }
    }

    /**
     * Muestra los metodos del contolador al momento
     * de ejecutar ejecutar un comando echo o print de
     * la clase instanciada.
     *
     * @return html
     */
    function __toString()
    {
        return arrayToTable(get_class_methods($this));
    }


    /**
     * Linkea archivos JavaScrits [.js] a la pagina.
     * Genera el codigo html dentro del tag <HEAD>
     */
    function addLinkJs($linkJs)
    {
        $linkJs = strtolower(trim($linkJs));
        if ($linkJs)
        {
            $headStr = '<script src="'.(SCR_PATH?SCR_PATH:'').$linkJs.'.js" language="javascript" type="text/javascript" ></script>';
            if (!in_array($headStr,$this->head))
                $this->head[] = $headStr;
        }

    }

    /**
     * Linkea archivos Hojas de estilo [.css] a la pagina.
     * Genera el codigo html dentro del tag <HEAD>
     *
     *
     * @param string $linkCss - Nombre del archivo a linkear, sin la extension .css
     * @param string $media - Puede ser screen o print en caso que sea necesario detallarlo.
     * @return void
     */
    function addLinkCss($linkCss,$media='')
    {
        $linkCss = strtolower(trim($linkCss));
        if ($linkCss)
        {
            $headStr = '<link href="'.(CSS_PATH?CSS_PATH:'').$linkCss.'.css" '.($media?' media="'.$media.'"':'').' rel="stylesheet" type="text/css" />';
            if (!in_array($headStr,$this->head))
                $this->head[] = $headStr;
        }
    }

    /**
    *
    * Agrega una linea dentro de los tags <head></head>
    */
    function addHead($head)
    {
        if ($head)
        {
            $head = str_replace ("'", "\'", $head);
            if (!in_array($head,$this->head))
                $this->head[] = $head;
        }
    }

    function addOnloadJs($js)
    {
       $this->onloadJs .= $js;
    }

    function addJs($js)
    {
        $this->js .= '
        '.$js.'

        /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

        ';
    }

    /**
     * Establece el atributo Controller::title, que sera
     * mostrado en el titulo de la ventana.
     *
     * @param string $title
     * @return void;
     */
    function setTitle($title)
    {
       if ($title)
           $this->title = $title;
    }

    /**
     * Agrega contenido al atributo Controller::title,
     * que sera mostrado en el titulo de la ventana.
     *
     * @param string $title
     * @return void;
     */
    function addTitle($title)
    {
       if ($title)
           $this->title .= ($this->title?' - '.$title:$title);
    }

    /**
    * Establece el campo al que se debera hacer focus al carga la pagina
    *
    * En caso de recibir una cadena vacia, se quita el focus establecido previamente.
    *
    * @param string focus
    */
    function setFocus($focus='')
    {
        $this->focus = $focus;
    }

    /**
    * Devuelve el texto establecido como titulo
    *
    * @return string titulo
    */
    public function getTitle()
    {
        return $this->title;
    }

    /**
    * Muestra la vista Header, en la que se incluye
    * el contennido del atributo Controller::title y
    * Controller::head
    */
    function getHeader($tpl = 'header')
    {
        $this->view->setTpl($tpl);

        $this->view->addVar('softwareName',SOFTWARE_NAME);
        $this->view->addVar('sofrwareVer',SOFTWARE_VER);
        $this->view->addVar('serverAddr',$_SERVER['SERVER_ADDR']);
        $this->view->addVar('remoteAddr',$_SERVER['REMOTE_ADDR']);

        $this->view->addVar('head',$this->head);
        $this->view->addVar('title',$this->title);
        $this->view->addVar('charset',DEFAULT_CHAR_ENCODING);

        $onloadJs = $this->onloadJs;
        if ($this->focus)
            $onloadJs .= '; window.document.forms[0].'.$this->focus.'.focus();';
        $this->view->addVar('onloadJs',$onloadJs);

        echo $this->view->get();
        $this->showErrorLog();
    }

    /**
     * Agrega vistas al array $this->views, que serán mostradas en
     * el orden que se agregaron mediante el metodo $this->getContent()
     *
     * @param string $name
     * @param array $vars
     * @return void
     */
    public function addView($name,$vars = array())
    {
        $this->views[]=array('name'=>$name,'vars'=>$vars);
    }

    /**
    * Muestra las vistas que contiene el array $this->views
    * en el orden que fueron agregadas mediante $this->addView()
    */
    public function getContent()
    {
        foreach ($this->views as $view)
        {
            $this->view->setTpl($view['name']);
            return $this->view->get($view['vars']);

        }
    }

    public function printContent()
    {
        echo $this->getContent();
    }

    /**
    * Muestra la vista Header, en la que se incluye
    * el contennido del atributo Controller::title y
    * Controller::head
    */
    function getFooter($tpl = 'footer')
    {
        $sessionData = 'No se ha iniciado sesion de usuario';
        $auth = UsrUsuario::getAuthInstance();
        if ($auth)
            $sessionData = 'Sesion: ['.$auth->get('username').'] '.$auth->get('nombre');

        $this->view->setTpl($tpl);
        $this->view->addVar('jsScripts',$this->js);

        $this->view->addVar('softwareName',SOFTWARE_NAME);
        $this->view->addVar('sofrwareVer',SOFTWARE_VER);
        if (defined('SERVER_ENTORNO') && SERVER_ENTORNO)
            $this->view->addVar('ambito',SERVER_ENTORNO);
        $this->view->addVar('serverAddr',$_SERVER['SERVER_ADDR']);
        $this->view->addVar('remoteAddr',$_SERVER['REMOTE_ADDR']);
        $this->view->addVar('sessionData',$sessionData);

        echo $this->view->get();
    }

    static function getLink($mod,$ctrl,$act,$prms='')
    {
        return $link = strtolower(trim($mod)).'.'.trim($ctrl).'.'.trim($act).'+'.$prms;
    }
}
?>