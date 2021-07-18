<?php
include_once "View.php";
include_once "ErrorLog.php";
include_once "AjaxResponse.php";

/**
 * ControllerAjax
 *
 * @package myLibrary
 */
abstract class ControllerAjax extends ErrorLog
{
    /**
    * Crea una instancia de la clase View.
    */
    function __Construct()
    {
        $this->ajxRsp = new AjaxResponse();
    }

    function __Destruct()
    {
        echo $this->ajxRsp->getOutput();
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
     * Muestra los metodos del contolador al momento
     * de ejecutar ejecutar un comando echo o print de
     * la clase instanciada.
     *
     * @return html
     */
    function __toString()
    {
        header('Content-Type: text/html; charset='.DEFAULT_CHAR_ENCODING);
        $encoding =  '<?xml version="1.0"'.DEFAULT_CHAR_ENCODING.' ?'.'>';

        return $encoding.$this->ajxRsp;
    }

    static function getLink($mod,$ctrl,$act,$prms='')
    {
        return $link = trim($mod).'.'.trim($ctrl).'Ajax.'.trim($act).'+&'.$prms;
    }
}
?>