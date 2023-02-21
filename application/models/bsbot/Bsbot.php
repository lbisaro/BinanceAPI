<?php
include_once LIB_PATH."ModelJSON.php";

class Bsbot extends ModelJSON
{
    protected $file  = 'Bsbot_';

    protected $tipoBots;

    const IDCLIENTE_UPS = 12394;
    const IDDOMICILIO_ORI_UPS = 12394;

    public function __Construct()
    {
        $auth = UsrUsuario::getAuthInstance();
        $this->file .= $auth->get('username'); 

        parent::__Construct();

        //Tipos de Bot
        $this->tipoBots['bsbot64']['nombre'] = 'BSBOT 70';
        $this->tipoBots['bsbot64']['vence_dias'] = 64;
        $this->tipoBots['bsbot64']['pago_parcial_frec'] = 7;
        $this->tipoBots['bsbot64']['pago_parcial_imp'] = 2.25;
        $this->tipoBots['bsbot64']['pago_cierre_frec'] = $this->tipoBots['bsbot64']['vence_dias'];
        $this->tipoBots['bsbot64']['pago_cierre_imp'] = 60;

    }

    public function parseData($data)
    {
        $data['bot'] = $this->getTipo($data['tipo']);
        $data['vence'] = date('d/m/Y',strtotime(strToDate($data['fecha']).' +'.$data['bot']['vence_dias'].' days'));
        
        $data['estado'] = 'Desconocido';
        if (strToDate($data['fecha'])<=date('Y-m-d'))
            $data['estado'] = 'Activo';
        if (strToDate($data['vence'])==date('Y-m-d'))
            $data['estado'] = 'Vence hoy';
        if (strToDate($data['vence'])<date('Y-m-d'))
            $data['estado'] = 'Vencido';

        for ($i=1;$i<=$data['bot']['vence_dias'];$i++)
        {
            $fechaRef = date('d/m/Y',strtotime(strToDate($data['fecha']).' +'.$i.' days'));
            if (fmod($i,$data['bot']['pago_parcial_frec'])==0)
                $data['pagos'][$fechaRef] = $data['qty']*$data['bot']['pago_parcial_imp'];
            if ($i == $data['bot']['vence_dias'])
                $data['pagos'][$fechaRef] = $data['qty']*$data['bot']['pago_cierre_imp'];
        }

        return $data;
    }

    public function validReglasNegocio($data)
    {
        $err=null;

        // Control de errores
        if (!isset($this->tipoBots[$data['tipo']]))
            $err[] = 'Se debe especificar un Tipo de Bot valido';

        if (strToDate($data['fecha']) > strToDate(date('d/m/Y') ))
            $err[] = 'No es posible asignar una fecha en el futuro';

        if ($data['qty'] < 1 || $data['qty'] > 100 )
            $err[] = 'La cantidad debe ser un numero entre 1 y 100';

        // FIN - Control de errores

        if (!empty($err))
        {
            $this->errLog->add($err);
            return false;
        }
        return true;
    }

    public function add($data)
    {
        $id = date('U');
        return parent::add($id,$data);
    }

    public function getTipo($id='ALL')
    {
        if ($id == 'ALL')
            return $this->tipoBots;
        elseif (isset($this->tipoBots[$id]))
            return $this->tipoBots[$id];
        return array(); 
    }
}
