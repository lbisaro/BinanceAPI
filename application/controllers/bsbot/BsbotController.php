<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once LIB_PATH."Html.php";
include_once MDL_PATH.'bsbot/Bsbot.php';

/**
 * Controller: BsbotController
 * @package SGi_Controllers
 */
class BsbotController extends Controller
{
    function home($auth)
    {
        $this->addTitle('BSBOT');

        $bot = new Bsbot();

        $ds = $bot->getAll();

        $dias[1] = 'Lunes';
        $dias[2] = 'Martes';
        $dias[3] = 'Miercoles';
        $dias[4] = 'Jueves';
        $dias[5] = 'Viernes';
        $dias[6] = 'Sabado';
        $dias[7] = 'Domingo';

        //Rango de pagos proximos 64 dias
        $diaHoy = date('N');
        $iStart = -$diaHoy;
        $rango = array();
        for ($i = $iStart; $i<=64 ; $i++)
        {
            if ($i<0)
                $fechaRef = date('d/m/Y',strtotime($i.' days'));
            elseif ($i>0)
                $fechaRef = date('d/m/Y',strtotime('+'.$i.' days'));
            else
                $fechaRef = date('d/m/Y');
            $rango[] = $fechaRef;
        }
        foreach ($rango as $fecha)
            $pagos[$fecha] = 0;

        $arr['totalQty'] = 0;
        if (!empty($ds))
        {
            $arr['bots'] = '';
            foreach ($ds as $id => $rw)
            {
                $diaSemana = date('N',strtotime(strToDate($rw['fecha'])));

                $arr['bots'] .= '<tr>';
                $arr['bots'] .= '<td>'.$dias[$diaSemana].' '.$rw['fecha'].'</td>';
                $arr['bots'] .= '<td>'.$rw['bot']['nombre'].'</td>';
                $arr['bots'] .= '<td>'.$rw['qty'].'</td>';
                $arr['bots'] .= '<td>'.$rw['estado'].'</td>';
                $arr['bots'] .= '<td><button class="btn btn-sm btn-danger" onclick="delBsbot(\''.$id.'\')">Eliminar</button></td>';
                $arr['bots'] .= '</tr>';
                
                $arr['totalQty'] += $rw['qty'];

                //Pagos dentro del rango
                if (!empty($rw['pagos']))
                    foreach ($rw['pagos'] as $fecha=>$pago)
                        if (isset($pagos[$fecha]))
                            $pagos[$fecha] += $pago;

            }

            
            
        }


        $dg = new HtmlTableDg(null,'100%','table-bordered table-hover table-sm');
        $dg->setCaption('Proximos pagos');
        foreach ($dias as $dia)
        {
            $dg->addHeader($dia,null,null,'center');
        }
        
        $diaRef = 1;
        $row = array();
        foreach ($pagos as $fecha=>$importe)
        {
            $diaSemana = date('N',strtotime(strToDate($fecha)));
            $dia = $dias[$diaSemana].' '.date('d/m',strtotime(strToDate($fecha)));
            $className = (date('d/m/Y')==$fecha?'text-primary font-weight-bold':'text-secondary font-weight-normal');
            $row[$diaRef] = '<div class="'.$className.'">'.substr($fecha,0,5).'</div>'.($importe>0?'<b>'.toDec($importe).'</b>':'&nbsp;');

            if ($diaRef == 7)
            {
                $dg->addRow($row);
                $row = array();
                $diaRef = 0;
            }

            $diaRef++;
        }
        
        $arr['pagos'] = $dg->get();

        $arr['fecha'] = date('d/m/Y');
        $arr['hidden'] = '';
   
        $this->addView('bsbot/home',$arr);
    }
}
