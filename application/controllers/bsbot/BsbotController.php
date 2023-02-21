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

        //Rango de pagos proximos 15 dias
        $rango = array();
        for ($i = 0; $i<=64 ; $i++)
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
                $arr['bots'] .= '<tr>';
                $arr['bots'] .= '<td>'.$rw['fecha'].'</td>';
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

        $dias[1] = 'Lun';
        $dias[2] = 'Mar';
        $dias[3] = 'Mie';
        $dias[4] = 'Jue';
        $dias[5] = 'Vie';
        $dias[6] = 'Sab';
        $dias[7] = 'Dom';

        $dg = new HtmlTableDg(null,null,'table-hover table-sm');
        $dg->addHeader('Fecha');
        $dg->addHeader('Importe BSBOT');
        foreach ($pagos as $fecha=>$importe)
        {
            $row = array();
            $diaSemana = date('N',strtotime(strToDate($fecha)));
            $dia = $dias[$diaSemana].' '.date('d/m',strtotime(strToDate($fecha)));
           
            $className = ($fecha == date('d/m/Y') ? 'resaltado' : '' );

            if ($importe != 0)
                $dg->addRow(array($dia,toDec($importe)),$className);
        }
        
        $arr['pagos'] = $dg->get();

        $arr['fecha'] = date('d/m/Y');
        $arr['hidden'] = '';
   
        $this->addView('bsbot/home',$arr);
    }
}
