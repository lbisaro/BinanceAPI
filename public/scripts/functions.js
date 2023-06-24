/**
 * Funciones generales de javascripts
 *
 */

/**
 Detectando el typo de browser utilizado
 */
var BrowserType = "Desconocido";
if (navigator.appName.indexOf("Netscape") > -1)
{
    BrowserType = "DOM";
}
else if (navigator.appName.indexOf("Explorer") > -1)
{
    // IE6 in standards compliant mode (i.e. with a valid doctype as the first line in the document)
    if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0)
        BrowserType = "iExplorer";
    // older versions of IE
    else
        BrowserType = "iExplorerOld";
}

/**
 * CrossBrowser Add Handler
 *
 * Ejemplo:
 * addHandler(newRow, 'click', function()  { mvIt(this.id) });
 *
 */
function addHandler(target,eventName,handlerName)
{
    if ( target.addEventListener )
        target.addEventListener(eventName, handlerName, false);
    else if ( target.attachEvent )
        target.attachEvent("on" + eventName, handlerName);
    else
        target["on" + eventName] = handlerName;
}


function getIEVersion()
{

    var rv = -1; // Return value assumes failure.

    if (navigator.appName == 'Microsoft Internet Explorer')
    {
        var ua = navigator.userAgent;
        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    }
    return rv;
}

function checkVersion()
{

    var msg = "Windows Internet Explorer No detectado.";
    var ver = getInternetExplorerVersion();

    if (ver > -1)
    {
        if (ver >= 8.0)
            msg = "Windows Internet Explorer 8.0";
        else
            msg = "Windows Internet Explorer inferior a 8.0";
    }

    alert(msg);
}


function getBodyWidth()
{
    bodyW = 640;
    if (BrowserType == "DOM")
        bodyW = window.innerWidth;

    else if (BrowserType == "iExplorer")
        bodyW = document.documentElement.clientWidth;

    else if (BrowserType == "iExplorerOld")
        bodyW = document.getElementsByTagName('body')[0].clientWidth;

    return bodyW;
}


function getBodyHeight()
{

    bodyH = 480;
    if (BrowserType == "DOM")
        bodyH = window.innerHeight;

    else if (BrowserType == "iExplorer")
        bodyH = document.documentElement.clientHeight;

    else if (BrowserType == "iExplorerOld")
        bodyH = document.getElementsByTagName('body')[0].clientHeight;

    return bodyH;
}

function getWindowsSize(X)
{
    var winW =640;
    var winH =480;

    if (BrowserType == "DOM")
    {
        winW = window.innerWidth;
        winH = window.innerHeight;
    }
    else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
    {  //IE 6+
        winW = document.documentElement.clientWidth;
        winH = document.documentElement.clientHeight;
    }
    else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
    {  //IE 4+ compatible
        winW = document.body.clientWidth;
        winH = document.body.clientHeight;
    }

    if(X == 'W')
        return winW;
    else
        return winH;
}

function goNextElement(current)
{
    var form = document.forms[0];
    for (i=0;el = form.elements[i];i++)
    if (current.id == el.id)
    {
        i = getNextValidElement(i);
        form.elements[i].focus();
    }
    return null;
}

function getNextValidElement(j)
{
    var form = document.forms[0];
    // Si no hay un proximo elemento, va al elemento inicial
    if (!form.elements[j+1])
        return j;

    // Si el proximo elemento no es un TAG valido, avanza dos elementos.
    if (!form.elements[j+1].tagName.match('INPUT|SELECT|TEXTAREA'))
        return getNextValidElement(j+1);
    return (j+1);
}

/**
Valida un input con la hora
Completa un input con la hora formateada
*/
function mkHora(strTime)
{
    var strTime = trim(strTime);
    if (strTime.length < 1)
        return null;
    if (strTime.length == 1)
        strTime = '0'+strTime;

    // que contenga caracteres numericos  en al menos 1 grupo con 1 o 2 elementos,  opcional ':' y opcional el segundo grupo con 2 elementos obligatorios
    var horaPat     = '([0-9]{1,2}):?([0-9]{2})?';
    var now         = new Date() ;
    var hora        = now.getHours();
    var minu        = now.getMinutes();
    var minu        = ( minu < 10 && minu < 2 )?minu= '0' + minu : minu;

    if (strTime != '*')
    {
        if (( strTime.length <=5 && strTime.length >=2) && strTime.match(horaPat) && (RegExp.$1 <= 23) && (RegExp.$2 <= 59))
        {
            var h   =  RegExp.$1;
            var m   = (RegExp.$2 != '')?RegExp.$2   :'00';

             ( h < 10 && h.length < 2 )?h= '0' + h  :h;
             ( m < 10 && m.length < 2 )?m= '0' + m  :m;

            var mkHora = h+':'+m;
            return mkHora;

        }else{
            alert('Hora no valida');
        }

    }else{
        return hora+':'+minu;
    }
}

/**
Completa un input con la fechA formateada
*/
function mkFecha(strDate)
{
    /**
    *
    *Arma y Valida la fecha
    *se puede armar una fecha enviando  un caracter '*' o lassigioentes cadenas
    * d,dd,dm,ddmm,dmyy,dmmyy,ddmmyy,ddmmyyyy,dd/mm/yyyy o la negacion  na,NA,n/a,N/A
    *Ej:
    *strDate = 12
    *       -> Resultado 12/mesactual/año actual
    *strDate = 128 ó 1208 ó 12/08 ó 12/8
    *       -> Resultado 12/08/año actual
    *strDate = 120804 ó 12/8/04 ó 12/08
    *       -> Resultado 12/08/2004
    */
    strDate  = trim(strDate);

    if (strDate.length < 1)
        return null;

    var val         = true;
    var date        = new Date();
    var curD        = date.getDate();
    var curM        = 1+date.getMonth();
    var curA        = date.getFullYear();

    if ( curD < 10 )
        curD = "0" + curD;

    if ( curM < 10 )
        curM = "0" + curM;

    if (strDate == '*')
        return  curD+'/'+curM+'/'+curA;

    var Pat         = '([0-9]{1,2})(\/)?([0-9]{1,2})?(\/)?([0-9]{2,4})?';
    var arrF        = strDate.match(Pat);
    var da          = (!arrF[1])?curD:arrF[1];
    var ms          = (!arrF[3])?curM:arrF[3];
    var ao          = (!arrF[5])?curA:arrF[5];

    // que no contenga caracteres alfabeticos
    if (strDate.match('[^a-zA-Z]'))
    {
        var fecha = checkFecha(da,ms,ao);
        if (!fecha)
        {
            alert('Fecha no valida');
            return '';
        }

        return fecha[0]+'/'+fecha[1]+'/'+fecha[2];

    }else{
        if(strDate.match('na|NA|n/a|N/A')){return 'N/A';}

    }
}

function checkFecha(da,ms,ao)
{
    var da = ( da < 10 )? "0" + Number(da): da;
    var ms = ( ms < 10 )? "0" + Number(ms): ms;
    var ao = ( ao.length < 3 )? '20' + ao: ao;

    var fecha = Array();

    var val = true;

    if (da < 1 || da > 31)
    {
        val = false;
    }
    else if (ms < 1 || ms > 12)
    {
        val = false;
    }
    else if ((ms==4 || ms==6 || ms==9 || ms==11) && da==31)
    {
        val = false;
    }
    else if (ms == 2)
    {
        var bisiesto = (ao % 4 == 0 && (ao % 100 != 0 || ao % 400 == 0));

        if (da > 29 || (da > 28 && !bisiesto))
        {
            val = false;
        }
    }

    fecha[0]=da;
    fecha[1]=ms;
    fecha[2]=ao;

    if (val)
    {
        return fecha;
    }else{
        return false;
    }
}

function valFechaANSI(obj)
{
    var val         = obj.value;
    var fecha       = val.match('([0-9]{4})(\.)([0-9]{2})(\.)([0-9]{2})');

    var fechaV      =(fecha)?checkFecha(fecha[5],fecha[3],fecha[1]):null;

    if (fechaV)
    {
        return true;
    }else{
        alert('Ingrese un formato valido de fecha');
        obj.value = '';
        obj.focus();
        return false;
    }

}

function mkCUIT(myfield, e, myvalue, dec )
{
    var key;
    var keychar;
    var mylen = myvalue.length;

    var strRegExp = /[0-9]{11}$/;
    console.log(myvalue);
    if (strRegExp.test(myvalue))
    {
        myfield.value = myvalue.substring(0, 2)+'-'+myvalue.substring(2, 10)+"-"+myvalue.substring(10, 11);
        return true;
    }


    if (window.event)
       key = window.event.keyCode;
    else if (e)
       key = e.which;
    else
       return true;
    keychar = String.fromCharCode(key);
    if ( (("0123456789-").indexOf(keychar) > -1))
    {
        if ( keychar != '-' && (mylen==2 || (mylen==11)) )
        {
            myfield.value = myfield.value+'-';
        }
        return true;
    }
    return true;
}

function mkMAWB(myfield, e, myvalue, dec )
{
    var key;
    var keychar;
    var mylen = myvalue.length;

    if (window.event)
       key = window.event.keyCode;
    else if (e)
       key = e.which;
    else
       return true;
    keychar = String.fromCharCode(key);
    if ( (("0123456789-").indexOf(keychar) > -1))
    {
        if ( keychar != '-' && (mylen==3) )
        {
            myfield.value = myfield.value+'-';
        }
        return true;
    }
    return true;
}

function validarCUIT(strCuit)
{
    /*
     determina si el dígito verificador es correcto
     Retorna true si es correcto y false si es incorrecto
    */
    var iCalculo = 0;
    var iDigitoVerificador = 0;
    var strRegExp = /[0-9]{2}\-[0-9]{8}\-[0-9]{1}$/;

    if (strRegExp.test(strCuit))
    {
        iCalculo = (Number(strCuit.substr(0,1)) * 5 +
             Number(strCuit.substr(1,1)) * 4 +
             Number(strCuit.substr(3,1)) * 3 +
             Number(strCuit.substr(4,1)) * 2 +
             Number(strCuit.substr(5,1)) * 7 +
             Number(strCuit.substr(6,1)) * 6 +
             Number(strCuit.substr(7,1)) * 5 +
             Number(strCuit.substr(8,1)) * 4 +
             Number(strCuit.substr(9,1)) * 3 +
             Number(strCuit.substr(10,1)) * 2) % 11;
        iDigitoVerificador = 11 - iCalculo;
        switch (iDigitoVerificador)
        {
            case 11 : iDigitoVerificador = 0; break;
            case 10 : iDigitoVerificador = 9; break;
        }
        return Number(strCuit.substr(12,1)) == iDigitoVerificador;
    }
    return false;

}

function mkMAWB(myfield, e, myvalue, dec )
{
    var key;
    var keychar;
    var mylen = myvalue.length;

    if (window.event)
       key = window.event.keyCode;
    else if (e)
       key = e.which;
    else
       return true;
    keychar = String.fromCharCode(key);
    if ( keychar != '-' && (mylen==3) )
    {
        myfield.value = myfield.value+'-';
    }
    else
    {
        myfield.value = myfield.value.toUpperCase();
    }
    return true;
}

function validaMAWB(strMAWB)
{
    /*
     determina si el dígito verificador es correcto
     Retorna true si es correcto y false si es incorrecto
    */
    var iCalculo = 0;
    var iDigitoVerificador = 0;
    var strRegExp = /[(0-9)|(A-Z)]{3}\-[0-9]{8}$/;

    if (strRegExp.test(strMAWB))
    {
        return strMAWB;
    }
    return false;

}






/**
 *  Devuelve el objeto enviando el ID
 * @access public
 * @return void
 **/
function $(id)
{
    return gebId(id);
}

function gebId(id)
{
    return document.getElementById(id);
}

/**
Devuelve verdadero o falso si se trata de un numero entero
*/
function isNumber(theField)
{
    var strRegExp   = /^([0-9])+$/;
    return strRegExp.test(theField);
}


/**
Devuelve verdadero o falso si se trata de numeros
*/
function isNumber2(theField)
{
    var strRegExp   = /^([0-9.\-])+$/;
    return strRegExp.test(theField);
}

function isNumeric(n) {
  return !isNaN(parseFloat(n)) && isFinite(n);
}
/**
 * Devuelve true o false  si se trata de una direccion de correo
 * EJ: mail@mail.com  = TRUE
 *     nomail.com = FALSE
 * por parametro enviar un STRING
 **/
function check_email(email)
{
    var strRegExp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9])+$/;
    return strRegExp.test(email);
}

/**
 * Convierte la cadena en mayusculas
 * @access public
 * @return void
 **/
function mayuscula(obj)
{
    obj.value = obj.value.toUpperCase();
}


/**
 * Devuelve el valor quitando los espacios de los extremos
**/
function trim(cadena)
{
    for(i=0; i<cadena.length; )
    {
        if(cadena.charAt(i)==" ")
            cadena=cadena.substring(i+1, cadena.length);
        else
            break;
    }

    for(i=cadena.length-1; i>=0; i=cadena.length-1)
    {
        if(cadena.charAt(i)==" ")
            cadena=cadena.substring(0,i);
        else
            break;
    }

    return cadena;
}

/**
 *
 * @access public
 * @return void
 **/
function redir(url)
{
    //window.location = url;
    goTo(url);
}

function goTo(url)
{
    $(location).attr('href',url); 
}

/**
 * getCheckedValue(radioObj)  trae el valor seleccionado de un grupo de radio butons
 *
 * @access public
 * @return radio value
 **/
function getCheckedValue(radioObj)
{

    if(!radioObj)
        return "";

    var radioLength = radioObj.length;

    if(radioLength == undefined)
    {
        if(radioObj.checked)
            return radioObj.value;
        else
            return "";
    }

    for(var i = 0; i < radioLength; i++)
    {
        if(radioObj[i].checked)
        {
            return radioObj[i].value;
        }
    }
    return "";
}

/**
 *  Recorre un array o una variable y si su valor es undefined lo cambia por null
 *
 * @access public
 * @return obj
 **/
function undXnull(obj)
{
    if (isArray(obj))
    {
        for (i in obj)
        {
            if (obj[i] == undefined){obj[i] = null;}
        }
    }else{
        if (obj == undefined){obj = null;}
    }

    return obj;
}

/**
 *
 *  Reconoce si una variable es array o no
 *
 * @access public
 * @return bulean
 **/
function isArray(obj)
{

   if (!obj || obj.constructor.toString().indexOf("Array") == -1)
      return false;
   else
      return true;
}

function HTMLinputHelp(obj)
{
    if (obj)
    {
        obj.value = '';
        obj.className = '';
    }
    /*
    var input = obj;
    var helper = gebId(input.id+'_help');
    if (helper)
    {
        helper.innerHTML = '';
        removeElementById(helper.id);
    }
    input.focus();
    */
}

/*********** PARCEADOR ERRORES **************/
function dump(o)
{
    var str = "";
    for(p in o)
    {
        str += "\t" + p + " => " + o[p] + "\r\n";
    }
    str = "(" + typeof(o) + ") " + o + " {\r\n" + str + "}";
    return str;
}

// USAR dumpW <-----------------------------------------<<<
function dumpW(o)
{
    var hw       = popup('', 'Debug', 600, 600, 'yes', 'yes');
    var htmlDump = o.replace(/<(\/)?script/gi,'< $1script');
    var htmlDoc = '<html><body><pre style="font: 13px \'Courier New\'">%1</pre></body></html>'
    htmlDoc = sprintf(htmlDoc, htmlDump);
    hw.document.open();
    hw.document.write(htmlDoc);
    hw.document.close();
}

function sprintf()
{
    if(arguments.length==0) return '';
    var str = arguments[0];
    for(var i=arguments.length-1; i>0; --i)
    {
        var re = new RegExp("%" + i,"i");
        str = str.replace(re, arguments[i]);
    }
    return str;
}

/*********** FIN PARCEADOR ERRORES **************/


function popup(url, name, width, height, isResizable, hasScrollbars, hasToolbar, hasMenubar, hasStatus)
{
    isResizable   = typeof(isResizable)   =='undefined' ? 'no'  :isResizable;
    hasScrollbars = typeof(hasScrollbars) =='undefined' ? 'auto':hasScrollbars;
    hasToolbar    = typeof(hasToolbar)    =='undefined' ? 'no'  :hasToolbar;
    hasMenubar    = typeof(hasMenubar)    =='undefined' ? 'no'  :hasMenubar;
    hasStatus     = typeof(hasStatus)     =='undefined' ? 'no'  :hasStatus;

    var top = (screen.height - height) / 2;
    var left = (screen.width - width) / 2;
    var settings = 'width=%1, height=%2, top=%3, left=%4, resizable=%5, scrollbars=%6, toolbar=%7, menubar=%8, status=%9';
    settings = sprintf(settings, width, height, top, left, isResizable, hasScrollbars, hasToolbar, hasMenubar, hasStatus);
    return window.open(url, name, settings);
}
//**********************************************************


//solo funciona en IE
/*
    permite el marcado de solo numeros
    si no se carga un numero anula la tecla marcada
*/
function soloNumeros()
{
   var bt=navigator.userAgent;
   //Caracteres permitidos en ER solo numeros
   var pt="/^\d$/";

   //cambio keycode x la letra q corresponde
   var re=String.fromCharCode(event.keyCode);

   /*Verifico que la tecla apretada este dentro de
     los caracteres permitidos
     Si no esta dentro de los caracteres permitidos
     Anulo la tecla apretada*/
   if (!pt.test(re))
   {
        event.returnValue = 0
   }
}

function sinEspacios()
{
   //Caracteres permitidos
   var s="0123456789abcdefghijklmnñopqrstuvwxyz";
   //Verifico que la tecla apretada este dentro de
   //los caracteres permitidos
   var re=String.fromCharCode(event.keyCode);
   //Si no esta dentro de los caracteres permitidos
   if ( s.indexOf(re) == -1 )
   {
    //Anulo la tecla apretada
    event.returnValue = 0
   }
}

function strSafe(str)
{
    //Este metodo tambien se encuentra en Functions.php

    //Caracteres a buscar comillas simples  o dobles
    var pat=/[\"\']+ /g;

    //hacemos replace
    str = str.replace(rep, "`");

    //Caracteres a buscar cualqueira distinto de a-z en min y en mayus, numeros de 0 al 9 y espacio
    var rep = /[^a-zA-Z0-9 `\#\$\%\(\)\*\+\,\-\.\/\:;=<>@_]+ /g;

    //hacemos replace
    str = str.replace(rep, "");

    //debolvemos str
    return str;
}



function showErrors(divName)
{
    if (!divName)
        divName = 'errores';
    gebId(divName).style.display = 'block';
    gebId(divName+'_cont').style.display = 'block';
}

function zeroFill(num, digits)
{
    numtmp='"'+num+'"';
    largo=numtmp.length-2;
    numtmp=numtmp.split('"').join('');
    if(largo==digits)
        return numtmp;
    ceros='';
    pendientes=digits-largo;
    for(i=0;i<pendientes;i++)
        ceros+='0';
    return ceros+numtmp;

}

function serialize( mixed_value )
{
    // http://kevin.vanzonneveld.net
    // +   original by: Arpad Ray (mailto:arpad@php.net)
    // +   improved by: Dino
    // +   bugfixed by: Andrej Pavlovic
    // +   bugfixed by: Garagoth
    // %          note: We feel the main purpose of this function should be to ease the transport of data between php & js
    // %          note: Aiming for PHP-compatibility, we have to translate objects to arrays
    // *     example 1: serialize(['Kevin', 'van', 'Zonneveld']);
    // *     returns 1: 'a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}'
    // *     example 2: serialize({firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'});
    // *     returns 2: 'a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}'

    var _getType = function( inp )
    {
        var type = typeof inp, match;
        var key;
        if (type == 'object' && !inp)
        {
            return 'null';
        }
        if (type == "object")
        {
            if (!inp.constructor)
            {
                return 'object';
            }
            var cons = inp.constructor.toString();
            if (match = cons.match(/(\w+)\(/))
            {
                cons = match[1].toLowerCase();
            }
            var types = ["boolean", "number", "string", "array"];
            for (key in types)
            {
                if (cons == types[key])
                {
                    type = types[key];
                    break;
                }
            }
        }
        return type;
    };
    var type = _getType(mixed_value);
    var val, ktype = '';

    switch (type)
    {
        case "function":
            val = "";
            break;
        case "undefined":
            val = "N";
            break;
        case "boolean":
            val = "b:" + (mixed_value ? "1" : "0");
            break;
        case "number":
            val = (Math.round(mixed_value) == mixed_value ? "i" : "d") + ":" + mixed_value;
            break;
        case "string":
            val = "s:" + mixed_value.length + ":\"" + mixed_value + "\"";
            break;
        case "array":
        case "object":
            val = "a";
            /*
            if (type == "object") {
                var objname = mixed_value.constructor.toString().match(/(\w+)\(\)/);
                if (objname == undefined) {
                    return;
                }
                objname[1] = serialize(objname[1]);
                val = "O" + objname[1].substring(1, objname[1].length - 1);
            }
            */
            var count = 0;
            var vals = "";
            var okey;
            var key;
            for (key in mixed_value)
            {
                ktype = _getType(mixed_value[key]);
                if (ktype == "function")
                {
                    continue;
                }

                okey = (key.match(/^[0-9]+$/) ? parseInt(key) : key);
                vals += serialize(okey) +
                        serialize(mixed_value[key]);
                count++;
            }
            val += ":" + count + ":{" + vals + "}";
            break;
    }
    if (type != "object" && type != "array") val += ";";
    return val;
}

function unserialize(data)
{
    // http://kevin.vanzonneveld.net
    // +     original by: Arpad Ray (mailto:arpad@php.net)
    // +     improved by: Pedro Tainha (http://www.pedrotainha.com)
    // +     bugfixed by: dptr1988
    // +      revised by: d3x
    // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +        input by: Brett Zamir (http://brettz9.blogspot.com)
    // +     improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // %            note: We feel the main purpose of this function should be to ease the transport of data between php & js
    // %            note: Aiming for PHP-compatibility, we have to translate objects to arrays
    // *       example 1: unserialize('a:3:{i:0;s:5:"Kevin";i:1;s:3:"van";i:2;s:9:"Zonneveld";}');
    // *       returns 1: ['Kevin', 'van', 'Zonneveld']
    // *       example 2: unserialize('a:3:{s:9:"firstName";s:5:"Kevin";s:7:"midName";s:3:"van";s:7:"surName";s:9:"Zonneveld";}');
    // *       returns 2: {firstName: 'Kevin', midName: 'van', surName: 'Zonneveld'}

    var error = function (type, msg, filename, line){throw new window[type](msg, filename, line);};
    var read_until = function (data, offset, stopchr)
    {
        var buf = [];
        var chr = data.slice(offset, offset + 1);
        var i = 2;
        while (chr != stopchr)
        {
            if ((i+offset) > data.length)
            {
                error('Error', 'Invalid');
            }
            buf.push(chr);
            chr = data.slice(offset + (i - 1),offset + i);
            i += 1;
        }
        return [buf.length, buf.join('')];
    };
    var read_chrs = function (data, offset, length)
    {
        var buf;

        buf = [];
        for(var i = 0;i < length;i++)
        {
            var chr = data.slice(offset + (i - 1),offset + i);
            buf.push(chr);
        }
        return [buf.length, buf.join('')];
    };
    var _unserialize = function (data, offset)
    {
        var readdata;
        var readData;
        var chrs = 0;
        var ccount;
        var stringlength;
        var keyandchrs;
        var keys;

        if(!offset) offset = 0;
        var dtype = (data.slice(offset, offset + 1)).toLowerCase();

        var dataoffset = offset + 2;
        var typeconvert = new Function('x', 'return x');

        switch(dtype)
        {
            case "i":
                typeconvert = new Function('x', 'return parseInt(x)');
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
            break;
            case "b":
                typeconvert = new Function('x', 'return (parseInt(x) == 1)');
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
            break;
            case "d":
                typeconvert = new Function('x', 'return parseFloat(x)');
                readData = read_until(data, dataoffset, ';');
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 1;
            break;
            case "n":
                readdata = null;
            break;
            case "s":
                ccount = read_until(data, dataoffset, ':');
                chrs = ccount[0];
                stringlength = ccount[1];
                dataoffset += chrs + 2;

                readData = read_chrs(data, dataoffset+1, parseInt(stringlength));
                chrs = readData[0];
                readdata = readData[1];
                dataoffset += chrs + 2;
                if(chrs != parseInt(stringlength) && chrs != readdata.length){
                    error('SyntaxError', 'String length mismatch');
                }
            break;
            case "a":
                readdata = {};

                keyandchrs = read_until(data, dataoffset, ':');
                chrs = keyandchrs[0];
                keys = keyandchrs[1];
                dataoffset += chrs + 2;

                for(var i = 0;i < parseInt(keys);i++)
                {
                    var kprops = _unserialize(data, dataoffset);
                    var kchrs = kprops[1];
                    var key = kprops[2];
                    dataoffset += kchrs;

                    var vprops = _unserialize(data, dataoffset);
                    var vchrs = vprops[1];
                    var value = vprops[2];
                    dataoffset += vchrs;

                    readdata[key] = value;
                }

                dataoffset += 1;
            break;
            default:
                error('SyntaxError', 'Unknown / Unhandled data type(s): ' + dtype);
            break;
        }
        return [dtype, dataoffset - offset, typeconvert(readdata)];
    };
    return _unserialize(data, 0)[2];
}

function removeElementById(id)
{
    if (document.getElementById(id)) {
      var child = document.getElementById(id);
      var parent = child.parentNode;
      parent.removeChild(child);
    }
}

function str_replace(cadena, cambia_esto, por_esto)
{
    return cadena.split(cambia_esto).join(por_esto);
}

function strstr(haystack, needle, bool)
{
    // Finds first occurrence of a string within another
    //
    // version: 1103.1210
    // discuss at: http://phpjs.org/functions/strstr
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // *     example 1: strstr('Kevin van Zonneveld', 'van');
    // *     returns 1: 'van Zonneveld'
    // *     example 2: strstr('Kevin van Zonneveld', 'van', true);
    // *     returns 2: 'Kevin '
    // *     example 3: strstr('name@example.com', '@');
    // *     returns 3: '@example.com'
    // *     example 4: strstr('name@example.com', '@', true);
    // *     returns 4: 'name'
    var pos = 0;

    haystack += '';
    pos = haystack.indexOf(needle);    if (pos == -1)
    {
        return false;
    } else {
        if (bool) 
        {
            return haystack.substr(0, pos);        
        } 
        else 
        {
            return haystack.slice(pos);
        }
    }
}

function stristr (haystack, needle, bool) {
    // Finds first occurrence of a string within another, case insensitive  
    // 
    // version: 1109.2015
    // discuss at: http://phpjs.org/functions/stristr    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfxied by: Onno Marsman
    // *     example 1: stristr('Kevin van Zonneveld', 'Van');
    // *     returns 1: 'van Zonneveld'
    // *     example 2: stristr('Kevin van Zonneveld', 'VAN', true);    // *     returns 2: 'Kevin '
    var pos = 0;
 
    haystack += '';
    pos = haystack.toLowerCase().indexOf((needle + '').toLowerCase());    if (pos == -1) {
        return false;
    } else {
        if (bool) {
            return haystack.substr(0, pos);        } else {
            return haystack.slice(pos);
        }
    }
}


function getFormValues()
{
    var objForm = document.forms[0];;
    var strUrl='';

    if(objForm && objForm.tagName.toUpperCase() == 'FORM')
    {
        var formElements=objForm.elements;
        for(var i=0;i < formElements.length;i++)
        {
            if(formElements[i].name)
            {
                if(formElements[i].type && ( formElements[i].type.toUpperCase() == 'RADIO' || formElements[i].type.toUpperCase() == 'CHECKBOX') && formElements[i].checked == false)
                    continue;

                var name=formElements[i].name;
                if(name)
                {
                    if(strUrl)
                        strUrl+='&';
                    if(formElements[i].type.toUpperCase()=='SELECT-MULTIPLE')
                    {
                        for(var j=0;j < formElements[i].length;j++)
                        {
                            if(formElements[i].options[j].selected==true)
                                strUrl+=name+"[]="+Base64.encode(formElements[i].options[j].value)+'&';
                        }
                    }
                    else
                    {
                        if(formElements[i].type &&  formElements[i].type.toUpperCase() == 'CHECKBOX' && formElements[i].checked == true)
                            strUrl+=name+'='+Base64.encode('ok');
                        else
                            strUrl+=name+'='+Base64.encode(formElements[i].value);
                    }
                }
            }
        }
    }
    return strUrl;
}


/*
Clase que codifica y decodifica strings en Base64

Uso:
    string = 'Una cadena';
    codificada = Base64.encode(string);
    decodificada = Base64.decode(codificada);

    De esta manera string y decodificada quedaran identicas.

*/

var Base64 =
{

    // private property
    _keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

    // public method for encoding
    encode : function (input) {
        var output = "";
        var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
        var i = 0;

        input = Base64._utf8_encode(input);

        while (i < input.length) {

            chr1 = input.charCodeAt(i++);
            chr2 = input.charCodeAt(i++);
            chr3 = input.charCodeAt(i++);

            enc1 = chr1 >> 2;
            enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
            enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
            enc4 = chr3 & 63;

            if (isNaN(chr2)) {
                enc3 = enc4 = 64;
            } else if (isNaN(chr3)) {
                enc4 = 64;
            }

            output = output +
            this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
            this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

        }

        return output;
    },

    // public method for decoding
    decode : function (input) {
        var output = "";
        var chr1, chr2, chr3;
        var enc1, enc2, enc3, enc4;
        var i = 0;

        input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

        while (i < input.length) {

            enc1 = this._keyStr.indexOf(input.charAt(i++));
            enc2 = this._keyStr.indexOf(input.charAt(i++));
            enc3 = this._keyStr.indexOf(input.charAt(i++));
            enc4 = this._keyStr.indexOf(input.charAt(i++));

            chr1 = (enc1 << 2) | (enc2 >> 4);
            chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
            chr3 = ((enc3 & 3) << 6) | enc4;

            output = output + String.fromCharCode(chr1);

            if (enc3 != 64) {
                output = output + String.fromCharCode(chr2);
            }
            if (enc4 != 64) {
                output = output + String.fromCharCode(chr3);
            }

        }

        output = Base64._utf8_decode(output);

        return output;

    },

    // private method for UTF-8 encoding
    _utf8_encode : function (string) {
        if (!string)
            var string = '';
        string = string.replace(/\r\n/g,"\n");
        var utftext = "";

        for (var n = 0; n < string.length; n++) {

            var c = string.charCodeAt(n);

            if (c < 128) {
                utftext += String.fromCharCode(c);
            }
            else if((c > 127) && (c < 2048)) {
                utftext += String.fromCharCode((c >> 6) | 192);
                utftext += String.fromCharCode((c & 63) | 128);
            }
            else {
                utftext += String.fromCharCode((c >> 12) | 224);
                utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                utftext += String.fromCharCode((c & 63) | 128);
            }

        }

        return utftext;
    },

    // private method for UTF-8 decoding
    _utf8_decode : function (utftext) {
        var string = "";
        var i = 0;
        var c = c1 = c2 = 0;

        while ( i < utftext.length ) {

            c = utftext.charCodeAt(i);

            if (c < 128) {
                string += String.fromCharCode(c);
                i++;
            }
            else if((c > 191) && (c < 224)) {
                c2 = utftext.charCodeAt(i+1);
                string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
                i += 2;
            }
            else {
                c2 = utftext.charCodeAt(i+1);
                c3 = utftext.charCodeAt(i+2);
                string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            }

        }

        return string;
    }

}

//******************** Fin Clse Base64 ***********************

//**************************** mostrar/ocultar ITEM **********
function showHide(item)
{
   var it = item;

   if(it.length > 0)
   {
       if(gebId(it).style.display == 'none')
        gebId(it).style.display= 'block';
       else
        gebId(it).style.display= 'none';
   }
}
//**********************************************************

//*************************** Obtener una variable Get ****
function getVar(name)
{

    get_string = document.location.search;
    return_value = '';

    do
    { //con este loop obtenemos todos los Get
        name_index = get_string.indexOf(name + '=');

        if(name_index != -1)
        {
          get_string = get_string.substr(name_index + name.length + 1, get_string.length - name_index);

          end_of_value = get_string.indexOf('&');
          if(end_of_value != -1)
            value = get_string.substr(0, end_of_value);
          else
            value = get_string;

          if(return_value == '' || value == '')
             return_value += value;
          else
             return_value += ', ' + value;
        }
    } while(name_index != -1)

    //reinsertamos los espacios
    space = return_value.indexOf('+');
    while(space != -1)
    {
        return_value = return_value.substr(0, space) + ' ' +
        return_value.substr(space + 1, return_value.length);

        space = return_value.indexOf('+');
    }

    return(return_value);
}

function toDec(x,n)
{
    if (!n)
        n=2;
    return format_number(x,n);
}

function format_number(x,n)
{
    x = parseFloat(x);
    return x.toFixed(n);
}


function redondear(x,n)
{
    if(!parseInt(n))
        var n=0;
    if(!parseFloat(x))
        return false;
    return Math.round(x*Math.pow(10,n))/Math.pow(10,n);

}

var mouseTopPos;
var mouseLeftPos;
function mousePos(oEvnt)
{
    mouseTopPos = oEvnt.clientY;
    mouseLeftPos = oEvnt.clientX;
};


function submitToUrl(url)
{
    document.forms[0].action = url;
    document.forms[0].method = 'POST';
    document.forms[0].submit();
}

function sleep(delay)
{
    var start = new Date().getTime();
    while (new Date().getTime() < start + delay);
}

function SetOpacity(object,opacityPct)
{
    // IE.
    object.style.filter = 'alpha(opacity=' + opacityPct + ')';
    // Old mozilla and firefox
    object.style.MozOpacity = opacityPct/100;
    // Everything else.
    object.style.opacity = opacityPct/100;
}

function ChangeOpacity(id,msDuration,msStart,fromO,toO)
{
    var elem=document.getElementById(id);
    var opacity = elem.style.opacity * 100;
    var msNow = (new Date()).getTime();
    opacity = fromO + (toO - fromO) * (msNow - msStart) / msDuration;
    if (opacity<0)
    SetOpacity(elem,0)
    else if (opacity>100)
    SetOpacity(elem,100)
    else
    {
    SetOpacity(elem,opacity);
    elem.timer = window.setTimeout("ChangeOpacity('" + id + "'," + msDuration + "," + msStart + "," + fromO + "," + toO + ")",1);
    }
}

function FadeIn(id)
{
    var elem=document.getElementById(id);
    if (elem.timer) window.clearTimeout(elem.timer);
    var startMS = (new Date()).getTime();
    elem.timer = window.setTimeout("ChangeOpacity('" + id + "',1000," + startMS + ",0,100)",1);
}

function FadeOut(id)
{
    var elem=document.getElementById(id);
    if (elem.timer) window.clearTimeout(elem.timer);
    var startMS = (new Date()).getTime();
    elem.timer = window.setTimeout("ChangeOpacity('" + id + "',1000," + startMS + ",100,0)",1);
}

function FadeInImage(foregroundID,newImage,backgroundID)
{
    var foreground=document.getElementById(foregroundID);
    if (backgroundID)
    {
        var bknd=document.getElementById(backgroundID);
        if (bknd)
        {
            bknd.style.backgroundImage = 'url(' + foreground.src + ')';
            bknd.style.backgroundRepeat = 'no-repeat';
        }
    }
    SetOpacity(foreground,0);
    foreground.src = newImage;
    if (foreground.timer) window.clearTimeout(foreground.timer);
    var startMS = (new Date()).getTime();
    foreground.timer = window.setTimeout("ChangeOpacity('" + foregroundID + "',1000," + startMS + ",0,100)",10);
}

function nl2br (str, is_xhtml) 
{   
    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';    
    return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
}

function blockButton(o)
{
    $(o).prop('disabled', true);
    $(o).css('cursor', 'not-allowed');
    setTimeout(function () {
        $(o).prop('disabled', false);
        $(o).css('cursor', 'pointer');
    }, 2000);
    
}
