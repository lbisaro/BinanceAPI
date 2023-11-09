<?php
include_once MDL_PATH."binance/BinanceAPI.php";
include_once MDL_PATH."bot/Operacion.php";
include_once MDL_PATH."binance/Wallet.php";

if (!Operacion::lockProcess('Crontab::BOT()'))
{
    $lockFileText = Operacion::readLockFile();
    $msg = 'Error - Bot Bloqueado - '.$lockFileText;
    Operacion::logBot($msg);

    include_once (MDL_PATH."NotificacionApp.php");
    $usr = new UsrUsuario(NotificacionApp::SUPER_ADMIN_ID);
    $registration_ids[] = $usr->getFCM_token();

    $title = 'ALERTA!';
    $body = 'Bot Bloqueado'."\n".$lockFileText;
    $result = NotificacionApp::send($title,$body,$registration_ids);

    return null;
}

$procStart = date('Y-m-d H:i:s');
$procStartU = microtime(true);
file_put_contents(STATUS_FILE, $procStart);

//Operacion::logBot('START');

$usr = new UsrUsuario();
$opr = new Operacion();
//Lista usuarios con ordenes existentes
$usuarios = $opr->getUsuariosActivos();
    
foreach ($usuarios as $idusuario => $usuarioData)
{
    try {

        if (isset($opr))
            unset($opr);
        $opr = new Operacion();

        $usr->reset();
        $usr->load($idusuario);
        $ak = $usr->getConfig('bncak');
        $as = $usr->getConfig('bncas');
        
        echo "\n".date('d/m/Y H:i:s').' '.$usr->get('username');
    
        if (isset($api))
            unset($api);
        $api = new BinanceAPI($ak,$as);      

        //CONTROLAR SI EL USUARIO TIENE LAS CLAVES CORRECTAS
        try {
          //        Lista ordenes abiertas en Binance
          $openOrders = $api->openOrders();
        } catch (Throwable $e) {
            $msg = "Error: " . $e->getMessage();
            Operacion::logBot('u:'.$idusuario.' '.$msg);
            continue;
        }
        $binanceOpenOrders = array();
        foreach ($openOrders as $order)
        {
            $binanceOpenOrders[$order['orderId']] = $order['status'];
        }

        $main_prices = $api->prices();
        pr($main_prices);
        

        //Operaciones de APALANCAMIENTO ESTANDARD
        $tipo = Operacion::OP_TIPO_APL;
        //echo "\n".date('d/m/Y H:i:s').' '.$usr->get('username')." ".$opr->getTipoOperacion($tipo);
        include 'crontab_bot_tipo_0.php';

        //Operaciones tipo Martingala LONG
        $tipo = Operacion::OP_TIPO_APLCRZ;
        //echo "\n".date('d/m/Y H:i:s').' '.$usr->get('username')." ".$opr->getTipoOperacion($tipo);
        include 'crontab_bot_tipo_1.php';


        //Operaciones tipo Martingala LONG
        $tipo = Operacion::OP_TIPO_APLSHRT;
        //echo "\n".date('d/m/Y H:i:s').' '.$usr->get('username')." ".$opr->getTipoOperacion($tipo);
        include 'crontab_bot_tipo_2.php';
        

        //Actualizar Billetera
        include 'crontab_update_wallet.php';


    } catch (Throwable $e) {
        $msg = "BOT - Error: " . $e->getMessage();
        Operacion::logBot('u:'.$idusuario.' '.$msg);
        continue;
    }
    sleep(2); //Hace una espera entre cada usuario
}

$procEndU = microtime(true);

file_put_contents(STATUS_FILE, "\n".'Proceso: '.toDec($procEndU-$procStartU,4).' seg.',FILE_APPEND);

Operacion::unlockProcess();

Operacion::cleanLog();


