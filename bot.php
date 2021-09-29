<?php
include_once("config.php");
date_default_timezone_set('UTC');

include_once(LIB_PATH."functions.php");
include_once(LIB_PATH."trade_functions.php");
include_once(LIB_PATH."Sql.php");
//include_once(LIB_PATH."Mailer.php");
include_once(MDL_PATH."usr/UsrUsuario.php");
Sql::Connect(DB_HOST,DB_USER,DB_PASSWORD,DB_NAME);

include_once(MDL_PATH."Binance.php");

$hostname = shell_exec('hostname');

$parametro = (isset($argv[1])?$argv[1]:"");

//--------------------------------------------------------------------------------------------------------------------

console("BOT_START ->\n");
if (substr($parametro,0,4)=='bot_') {
    include "bot/$parametro.php";
}

//--------------------------------------------------------------------------------------------------------------------
Sql::close();
console("BOT_END\n");
exit();


function console($msg)
{
    if (is_array($msg))
    {
        if (is_array_assoc($msg))
        {
            foreach ($msg as $k => $v)
            {
                echo "\n".$k.': ';
                if (is_array($v))
                    console($v);
                else
                    echo $v;
            }
        }
        else
        {
            echo "\n";
            foreach ($msg as $v)
            {
                if (is_array($v))
                    console($v);
                else
                    echo $v.",";
            }
        }
    }
    else
    {
        echo "\n".$msg;
    }
}
