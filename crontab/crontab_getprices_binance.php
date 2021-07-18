 <?php
include_once MDL_PATH."Binance.php";
include_once MDL_PATH."Ticker.php";



$reporte = array();

//Iniciando el reporte
$reporte[] = "\nINICIO $hostname\n";

$bnc = new Binance();
$tckr = new Ticker();

$prices = $bnc->price();
$tckr->addPrices($prices);

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