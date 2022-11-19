<?php
include_once LIB_PATH."ErrorLog.php";

abstract class ModelJSON
{
    protected $file;  //Path y Nombre del archivo (Sin la extension)

    protected $errLog;

    public function __Construct()
    {
        if (!is_dir(JSON_DATA_PATH))
        {
            mkdir(JSON_DATA_PATH,0777);
        }
        if (!defined('JSON_DATA_PATH'))
            criticalExit('ModelJSON::ModelJSON() - ERROR CRITICO! - No se ha definido la constante JSON_DATA_PATH correspondiente a la carpeta de almacenamiento de datos.');
     
        if (empty($this->file))
            criticalExit('ModelJSON::ModelJSON() - ERROR CRITICO! - No se ha definido la propiedad ModelJSON::file correspondiente a la ruta y nombre de archivo de datos.');
        $this->errLog = new ErrorLog();
    }

    public function getFile()
    {
        return JSON_DATA_PATH.$this->file.'.json';
    }

    public function getAll()
    {
        return $this->getById('ALL');
    }

    public function getById($id='ALL')
    {
        if (is_file($this->getFile()))
        {
            $data = file_get_contents($this->getFile());
            if (mb_detect_encoding($data, 'UTF-8', true))
                $data = utf8_decode($data);

            $database = json_decode($data, true);
        }
        else
            $database = array();

        if (!empty($database))
        {
            foreach ($database as $k => $rw)
                $database[$k] = $this->parseData($rw);
        }

        if ($id && $id != 'ALL')
        {
            if (isset($database[$id]))
                return $database[$id];
            else
                return array();
        }
        return $database;

    }

    public function parseData($data)
    {
        return $data;
    }

    public function validReglasNegocio($data)
    {
        $err=null;

        $database = $this->getAll();
        // Control de errores

        if (false)
        {
            $err[] = 'El Model debe tener el metodo validReglasNegocio()';
        }

        // FIN - Control de errores

        if (!empty($err))
        {
            $this->errLog->add($err);
            return false;
        }
        return true;
    }
 
    public function add($id,$data)
    {
        $id = trim($id);
        $database = $this->getAll();
        if (isset($database[$id]))
        {
            $this->errLog->add('No se puede insertar el Id '.$id.' debido a que el mismo ya existe.');
            return false;
        }
        return $this->save($id,$data);
    }
 
    public function update($id,$data)
    {
        $id = trim($id);
        $database = $this->getAll();
        if (!isset($database[$id]))
        {
            $this->errLog->add('No se puede actualizar el Id '.$id.' debido a que el mismo no se encuentra registrado.');
            return false;
        }
        return $this->save($id,$data);
    }

    private function save($id,$data)
    {
        $id = trim($id);
        if ($this->validReglasNegocio($data))
        {
            foreach ($data as $k=>$v)
                if (!is_array($v))
                    $data[$k] = trim($v);
            
            $database = $this->getAll();
            $database[$id] = $data;
            file_put_contents($this->getFile(), json_encode($database));
            return true;       
        }
        return false;

    }

    public function delete($id)
    {
        $database = $this->getAll();
        unset($database[$id]);
        file_put_contents($this->getFile(), json_encode($database));
        return true;
    }

    public function reset()
    {
        $this->data = array();
    }

    public function getErrLog()
    {
        return $this->errLog->get();
    }

}

?>