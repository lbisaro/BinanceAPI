<?php
class ErrorLog
{
    protected $log = array();

    function add($error)
    {

        if(!empty($error))
        {
            if (is_array($error))
                foreach ($error as $it)
                    $this->log[] = $it;
            else
                $this->log[] = $error;
        }
    }

    function get($reset = true)
    {
        if (empty($this->log))
            return null;

        $log = $this->log;
        if ($reset)
            $this->reset();

        return $log;
    }

    function reset()
    {
        $this->log = null;
    }

    /**
     * Funcion agregada para mantener compatibilidad con $this->addErr()
     */
    function addError($error)
    {
        $this->add($error);
    }

    /** addErr()
    *
    * Agrega cadenas de errores o un array de cadenas de errores al array.
    *
    */
    function addErr($error)
    {
        $this->add($error);
    }


    /**
    * Devuelve un array con los errores, o null si no se encuentran errores.
    */
    function getErrLog($reset = true)
    {
        if (empty($this->log))
            return null;

        $log = $this->log;
        if ($reset)
            $this->reset();

        return $log;
    }

    /**
     * Elimina el historial de registro de errores.
     *
     */
    function resetErrLog()
    {
        $this->reset();

    }
}
?>