<?php
$ip = file_get_contents('https://wgetip.com');
echo "\n".file_get_contents('http://bisaro.ar/receive_ip.php?ip='.$ip);
echo "\nIP registrada: ".$ip;