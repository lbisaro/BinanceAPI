<?php
include_once LIB_PATH."Controller.php";
include_once LIB_PATH."Html.php";
include_once LIB_PATH."HtmlTableDg.php";
include_once LIB_PATH."HtmlTableFc.php";
include_once MDL_PATH."bot/BotArbitrajeAT.php";

/**
 * Controller: BotATController
 * @package SGi_Controllers
 */
class BotATController extends Controller
{
    function home($auth)
    {
        $this->addTitle('Bot Arbitraje Triangular');

        $ak = $auth->getConfig('bncak');
        $as = $auth->getConfig('bncas');

        if (empty($ak) || empty($as))
        {
            $arr['data'] = '<div class="alert alert-danger">No se encuentra registro de asociacion de la cuenta con Binance</div>';
        }
        else
        {
            //Importe en USD para cada operacion 
            $importe = 1000;
            //Token USD
            $tokenUSD = 'USDT';
            //Token BASE
            $tokenBase = 'BNB';

            $bot = new BotArbitrajeAT($ak,$as);

            $tokens = $bot->readTokens($tokenUSD,$tokenBase);

            if (!empty($tokens))
            {
                
                $dg = new HtmlTableDg();
                $dg->setCaption('Precios');
                $dg->addheader('Token');

                $dg->addheader('Token-'.$tokenUSD,'rigth');
                $dg->addheader('Token-'.$tokenBase,'rigth');
                $dg->addheader($tokenBase.'-'.$tokenUSD,'rigth');
                
                $dg->addheader('Porcentaje','rigth');
                $dg->addheader('Resultado','rigth');
                $dg->addheader('Comisiones','rigth');
                $dg->addheader('Ganancia','rigth');
                $changePercMax=0;
                foreach($tokens as $token => $rw)
                {
                    $unidades = $importe;
                    $unidades = $unidades/$rw[$token.$tokenUSD]['askPrice']; //$usdToken to $mainToken
                    $unidades = $unidades*$rw[$token.$tokenBase]['bidPrice']; //$mainToken to $baseToken
                    $unidades = $unidades*$rw[$tokenBase.$tokenUSD]['bidPrice']; //$baseToken to $usdToken
                    $resultado = $unidades-$importe;
                    $comisiones = ($importe * $bot->comision) * 3;
                    $ganancia = $resultado - $comisiones;
                    $row = array($token,
                                 str_replace('.',',',$rw[$token.$tokenUSD]['askPrice']),
                                 str_replace('.',',',toDec($rw[$token.$tokenBase]['bidPrice'],10)),
                                 str_replace('.',',',$rw[$tokenBase.$tokenUSD]['bidPrice']),
                                 '<span class="text-'.($rw['cambioPerc']>0?'danger':'success').'">'.toDec($rw['cambioPerc'],4).'</span>',
                                 toDec($resultado),
                                 toDec($comisiones),
                                 toDec($ganancia)
                                );
                    $dg->addRow($row);
                }

                $arr['data'] = $strChangePercMax.$dg->get();
            }

            $arr['hidden'] = '';
        }   
   
        $this->addView('ver',$arr);
    }
}
