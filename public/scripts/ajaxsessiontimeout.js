/**

 Funciones ajax y javascript para el control del TimeOut de sesion de usuarios

 Utilizar en forma conjunta con:

    application\controllers\_lib\SessionTimeoutAjax.php - SessionTimeoutAjax::getStatus()

*/

/* Configuracion */

    // Frecuencia con la que el sistema enviara un request para no perder sesion de PHP (En segundos)
        var stoInterval = 5 * 60; //5 Minutos

/* FIN - Condiguracion */



var stoInterval_; //Timer del intervalo
var d = new Date();
var stoStart = Math.round( d.getTime() / 1000);

var stoXHR;
var stoRsp
var sid;


stoInterval_ = setInterval(stoRestart,stoInterval*1000);


function stoGetXHR()
{
    var stoXHR=null;

    if (window.XMLHttpRequest) // Firefox, Opera 8.0+, Safari
    {
            stoXHR = new XMLHttpRequest();
    }
    else if (window.ActiveXObject)// IE
    {
        try
        {
            stoXHR = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (e)
        {
            stoXHR = new ActiveXObject("Msxml2.XMLHTTP");
        }
    }
    return stoXHR;
}

/*
 * Envia peticion Ajax para verificar en PHP - $_SESSION
 * cuando fue iniciada la ultima peticion Http
 */
function stoRestart()
{
    sid = 'AJX_'+Math.round(Math.random()*100000);

    stoXHR=stoGetXHR();

    if (stoXHR==null)
    {
        alert ("ERROR!! - El Explorador no soporta HTTP Request");
        return false;
    }

    var d = new Date();
    var U = Math.round(d.getTime() / 1000);

    var url = '_lib.SessionTimeoutAjax.getStatus+sid=' + sid;
        url += '&restart=Ok&U='+U;

    stoXHR.onreadystatechange = stoRestart_receive;
    stoXHR.open("GET",url,true);
    stoXHR.send(null);
}


/*
 * Devuelve peticion Ajax para verificar en PHP - $_SESSION
 * cuando fue iniciada la ultima peticion Http,
 * y de esta manera actualiza el Session Timeout como
 * si hubiese iniciado cuando cuando se hizo la ultima
 * peticion Http de la sesion.
 *
 * Con esto se evita que la sesion se cierre al trabajar con
 * varias ventanas a la vez.
 */
function stoRestart_receive()
{
    var d = new Date();
    if (stoXHR.readyState == 4 /*READY_STATE_COMPLETE*/ )
    {
        if (stoXHR.status == 200)
        {
            // Se actualiza el valor de stoStart
            var stoRsp = parseInt(stoXHR.responseText);
            console.log(stoRsp);
        }
    }
}

