 <?php
include_once MDL_PATH."Binance.php";
include_once MDL_PATH."Ticker.php";



$reporte = array();

//Iniciando el reporte
$reporte[] = "\nINICIO $hostname\n";

$fichero = ROOT_DIR.'/log.txt';

$bnc = new Binance();
$tck = new Ticker();
file_put_contents($fichero, "\n"."Crontab.1 ".date('H:i:s'));
$prices = $bnc->price();
file_put_contents($fichero, "\n"."Crontab.2 ".date('H:i:s'),FILE_APPEND);
$tck->addPrices($prices);
file_put_contents($fichero, "\n"."Crontab.3 ".date('H:i:s')."\n",FILE_APPEND);

$reporte[] = "\n\Ticker Prices OK\n";
//foreach ($prices as $k => $v)
//    $reporte[] = "\t$k: $v\n";
$reporte[] = "\n\n";
        

        

$reporte[] = "\n\n";
$reporte[] = "\nEND $hostname\n";
//--------------------------------------------------------------------------------------------------------------------

foreach ($reporte as $it)
    echo $it;
?>