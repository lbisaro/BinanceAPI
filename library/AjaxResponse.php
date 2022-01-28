<?php

class AjaxResponse
{
    private $aCommands = array();
    private $aErrors = array();
    private $sEncoding = 'UTF-8';
    private $bOutputEntities = true;

    protected $echoOut = false;

	public function __Construct()
	{
	    if (defined('DEFAULT_CHAR_ENCODING'))
            $this->sEncoding = DEFAULT_CHAR_ENCODING;

    }

    public function setEchoOut($bool)
    {
        $this->echoOut = ($bool?true:false);
    }

    /**
	 * Agrega un comando que asigna el valor sData al
     * atributo sAttribute del elemento con id sTarget.
	 *
	 * <i>Uso:</i> <kbd>$objResponse->assign("contentDiv", "innerHTML", "Some Text");</kbd>
	 */
	public function assign($sTarget,$sAttribute,$sData)
	{
		$this->addCommand(array('cmd'=>'assign','id'=>$sTarget,'prop'=>$sAttribute),$sData);
	}


	/**
	 * Agrega un comando Javascript
	 *
	 * <i>Uso:</i> <kbd>$objResponse->script("var x = prompt('get some text');");</kbd>
	 */
	public function script($sJS)
	{
		$this->addCommand(array('cmd'=>'script'),$sJS);
	}


	/**
	 * Agrega un comando que remueve el elemento con id sTarget.
	 *
	 * <i>Uso:</i> <kbd>$objResponse->remove("Div2");</kbd>
 	 */
	public function remove($sTarget)
	{
		$this->addCommand(array('cmd'=>'remove','id'=>$sTarget),'');
	}


	/**
     * Agrega un comando que agrega el valor sData al final del 
     * atributo sAttribute del elemento con id sTarget.
     *
     * <i>Uso:</i> <kbd>$objResponse->append("contentDiv", "innerHTML", "Some New Text");</kbd>
     */
    public function append($sTarget,$sAttribute,$sData)
    {
        $this->addCommand(array('cmd'=>'append','id'=>$sTarget,'prop'=>$sAttribute),$sData);
    }


    /**
     * Agrega un comando que agrega el valor sData al inicio del 
     * atributo sAttribute del elemento con id sTarget.
     *
     * <i>Uso:</i> <kbd>$objResponse->append("contentDiv", "innerHTML", "Some New Text");</kbd>
     */
    public function prepend($sTarget,$sAttribute,$sData)
    {
        $this->addCommand(array('cmd'=>'prepend','id'=>$sTarget,'prop'=>$sAttribute),$sData);
    }


    /**
	 * Agrega un comando que redirecciona la URL del documento a otra URL.
     *
     * iDelay: Representa la cantidad de segundos previos a
     * redireccionar el documento a la nueva URL.
	 *
	 * <i>Uso:</i> <kbd>$objResponse->redirect("http://www.google.com.ar");</kbd>
	 */
	public function redirect($sURL, $iDelay=0)
	{
        if ($iDelay)
			$this->script('window.setTimeout("window.location = \''.$sURL.'\';",'.($iDelay*1000).');');
		else
			$this->script('window.location = "'.$sURL.'";');
	}


    /**
	 * Agrega un comando para que se ejecute un alert()
	 *
	 * <i>Uso:</i> <kbd>$objResponse->alert("This is important information");</kbd>
	 */
	public function alert($sMsg)
	{
		$this->addCommand(array('cmd'=>'alert'),$sMsg);
	}
	
	/**
	 * Agrega un comando que crea un elemento del tipo sTag
     * con id sId, contenido dentro del elemento con id sParent.
	 *
	 * <i>Uso:</i> <kbd>$objResponse->create("parentDiv", "h3", "myid");</kbd>
	 */
	public function create($sParent, $sTag, $sId)
	{
		$this->addCommand(array('cmd'=>'create','id'=>$sParent,'prop'=>$sId),$sTag);
	}


	/**
	 * Agrega un comando que agrega un link a un archivo .js
	 *
	 * <i>Uso:</i> <kbd>$objResponse->includeScript("functions.js");</kbd>
	 */
	public function includeScript($sFileName)
	{
		$this->addCommand(array('cmd'=>'includeScript'),$sFileName);
	}


    /**
    * Devuelve el XML generado por $this->getOutput()
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
    function __toString()
    {
        return $this->getOutput();
    }

	/**
	 * Devuelve el XML que contiene los comandos agregados al
     * objeto AjaxResponse instanciado.
     *
	 * <i>Uso:</i> <kbd>return $objResponse->getOutput();</kbd>
	 */
	public function getOutput()
	{
        if (!$this->echoOut)
        {
            if (count($this->aErrors) > 0)
            {
                return json_encode(array('errors'=>$this->aErrors));
            }
            else
            {
                return json_encode(array('commands'=>$this->aCommands));
            }
        }
        

	}

	private function addCommand($aAttributes, $sData)
	{
		$aAttributes['data'] = base64_encode(utf8_encode($sData));
		$this->aCommands[] = $aAttributes;
	}

    public function addError($error)
    {
        if (is_array($error))
            foreach ($error as $errIt)
                $this->aErrors[] = $errIt;
        else
            $this->aErrors[] = $error;
    }  

    public function debug($data)
    {
        if (is_array($data))
            foreach ($data as $data)
                $msgs[] = $msg;
        else
            $msgs[] = $data;
        foreach ($msgs as $msg)
            $this->script("console.log('".$msg."');");
    }
}
?>