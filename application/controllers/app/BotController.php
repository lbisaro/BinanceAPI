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

        $api = new BinanceAPI(null,null);
        $ei =   
        debug($ei);
        $arr['data'] = '';
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

        $arr['symbol'] = $opr->get('symbol');
        $arr['inicio_usd'] = 'USD '.$opr->get('inicio_usd');
        $arr['multiplicador_compra'] = $opr->get('multiplicador_compra');
        $arr['multiplicador_porc'] = $opr->get('multiplicador_porc').'%';
        $arr['estado'] = $opr->get('strEstado');
        $arr['hidden'] = Html::getTagInput('idoperacion',$opr->get('idoperacion'),'hidden');

        $data = $opr->matchOrdenesEnBinance();
        if ($data['ordenesPendientes'] >= 0)
            $this->addOnloadJs('checkMatch();');


        $ordenes = $opr->getOrdenes();

        $dg = new HtmlTableDg(null,null,'table table-hover table-striped');
        $dg->addHeader('ID');
        $dg->addHeader('Tipo');
        $dg->addHeader('Unidades');
        $dg->addHeader('Precio');
        $dg->addHeader('USD');
        $dg->addHeader('Estado');
        foreach ($ordenes as $rw)
        {
            $usd = toDec($rw['origQty']*$rw['price']);
            $row = array($rw['orderId'],
                         $rw['sideStr'],
                         ($rw['origQty']*1),
                         ($rw['price']*1),
                         $usd,
                         $rw['statusStr']
                        );
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
