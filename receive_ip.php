<?php
$rootFolder = dirname(__FILE__);
if ($_REQUEST['ip'])
{
    file_put_contents($rootFolder.'/bisaro_local_ip.txt',$_REQUEST['ip']);
    echo "Received OK!";
}
