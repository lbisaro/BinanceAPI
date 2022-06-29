<?php
$rootFolder = dirname(__FILE__);
if ($_REQUEST['ip'])
{
    file_put_contents($rootFolder.'/prntsrv_oper_ip.txt',$_REQUEST['ip']);
    echo "Received OK!";
}
