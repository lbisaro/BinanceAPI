<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once MDL_PATH."Ticker.php";
include_once MDL_PATH."bot/Operacion.php";
include_once MDL_PATH."binance/BinanceAPI.php";

/**
 * Controller: BotController
 * @package SGi_Controllers
 */
class BotController extends Controller
{
    
    function operaciones($auth)
    {
        $this->addTitle('Operaciones');

        $opr = new Operacion();
        $ds = $opr->getDataset('idusuario = '.$auth->get('idusuario'),'symbol');
        
        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->addHeader('Moneda');
        $dg->addHeader('Cantidad de USD compra inicial',null,null,'center');
        $dg->addHeader('Multiplicador Compras',null,null,'center');
        $dg->addHeader('Multiplicador Porcentajes',null,null,'center');
        $dg->addHeader('Estado',null,null,'center');
        $dg->addHeader('Recompra Automatica',null,null,'center');

        foreach ($ds as $rw)
        {
            $opr->reset();
            $opr->set($rw);
            $link = '<a class="" href="'.Controller::getLink('app','bot','verOperacion','id='.$opr->get('idoperacion')).'">'.$opr->get('symbol').'</a>';
            $autoRestart = '<span class="glyphicon glyphicon-'.($opr->autoRestart()?'ok text-success':'ban-circle text-danger').'"></span>';

            $data = array($link,
                          $opr->get('inicio_usd'),
                          $opr->get('multiplicador_compra'),
                          $opr->get('multiplicador_porc'),
                          $opr->get('strEstado'),
                          $autoRestart
                          );
            $dg->addRow($data);
        }

        $arr['lista'] = $dg->get();
        $arr['hidden'] = '';
    
        $this->addView('bot/operaciones',$arr);
    }

    function crearOperacion($auth)
    {
        $this->addTitle('Crear Operacion');

        $arr['data'] = '';
        $arr['hidden'] = '';

        $this->addView('bot/crearOperacion',$arr);
    }    

    function verOperacion($auth)
    {
        $idoperacion = $_REQUEST['id'];
        $this->addTitle('Operacion #'.$idoperacion);

        $opr = new Operacion($idoperacion);

        if ($opr->get('idusuario') != $auth->get('idusuario'))
        {
            $this->addError('No esta autorizado a visualizar esta pagina.');
            return false;
        }
        $link = '<a href="https://www.binance.com/es/trade/'.$opr->get('symbol').'" target="_blank">'.$opr->get('symbol').'</a>';
        $arr['symbol'] = $link;
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%';
        $arr['estado'] = $opr->get('strEstado');

        if ($opr->canStart())
            $arr['estado'] .= ' <button id="startBtn" onclick="start();" class="btn btn-sm btn-success">Iniciar</button>';

        if ($opr->autoRestart())
            $autoRestart = '<button id="arBtn" class="btn btn-sm btn-success">
                <span class="glyphicon glyphicon-ok"></span>
                </button>';
        else
            $autoRestart = '<button id="arBtn" class="btn btn-sm btn-danger">
                <span class="glyphicon glyphicon-ban-circle"></span>
                </button>';

        $arr['auto-restart'] = $autoRestart;
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $ordenes = $opr->getOrdenes($enCurso=false);

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->addHeader('ID');
        $dg->addHeader('Tipo');
        $dg->addHeader('Unidades',null,null,'right');
        $dg->addHeader('Precio',null,null,'right');
        $dg->addHeader('USD',null,null,'right');
        $dg->addHeader('Estado');
        $dg->addHeader('Fecha Hora');
        $completed = '-';
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);
            $row = array($rw['orderId'],
                         $rw['sideStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         ($rw['side']==Operacion::SIDE_BUY?'-':'').$usd,
                         $rw['statusStr'],
                         $rw['updatedStr']
                        );
            if ($rw['completed']!=$completed)
            {
                $dg->addSeparator('<strong>'.(!$rw['completed']?'En curso':'Completas').'</strong>');
                $completed = $rw['completed'];
            }
            $dg->addRow($row,$rw['sideClass'],null,null,$id='ord_'.$rw['orderId']);
        }
        $arr['ordenes'] = $dg->get();

        $this->addView('bot/verOperacion',$arr);
    }    
    

    function revisarEstrategia($auth)    
    {
        $this->addTitle('Operaciones');
        $tkr = new Ticker();
        $ds = $tkr->getDataSet('','tickerid');
        
        $arr['availableTickers'] = 'var availableTickers = [';
        foreach ($ds as $rw)
            $arr['availableTickers'] .= "\n   '".$rw['tickerid']."',"; 
        $arr['availableTickers'] .= '
        ];'; 
        $this->addView('bot/revisarEstrategia',$arr);

    }


    function BinanceAPI($auth)
    {
        $this->addTitle('pyScript');
    

        $arr['data'] = '';
        $arr['hidden'] = '';
    
        $this->addView('ver',$arr);
    }
    
    
}
