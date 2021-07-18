/**
 * Controller Ajax para JavaScripts
 *
 * Requiere Base64.encode()
 *
 */

/* Constructor */
function clsCtrlAjax(charSetEncoding)
{
    /* Propiedades */
    this.charSetEncoding            = (charSetEncoding?charSetEncoding:"iso-8859-1");
    this.READY_STATE_COMPLETE       = 4;
    this.rqKey                      = null;
    this.XHRequest                  = null;

    /* Metodos */
    this.initXHRequest              = initXHRequest;
    this._send                      = _send;
    this.processResponse            = processResponse;
    this.cmdExce                    = cmdExec;
    this.includeScript              = includeScript;
    this.remove                     = remove;
    this.create                     = create;
    this.rndSId                     = rndSId;
    this.getFormValuesPostEncode    = getFormValuesPostEncode;
    this.encodePostStr              = encodePostStr;
    this.getFormValuesPost          = getFormValuesPost;
    this.addPostParam               = addPostParam;
    this.GEById                     = GEById;
    this.loadMsg                    = loadMsg;

}

var CtrlAjax =
{
    /* Array de instancias de CtrlAjax
     *
     * Este array esta creado para poder manejar varios request Ajax simultaneos.
     *
     */
    aRequest : new Array(),

    send : function (sUrl)
    {
        var oAjx = new clsCtrlAjax();
        oAjx._send(sUrl);
    },

    sendCtrl : function (mod,ctrl,act,prms)
    {
        return CtrlAjax.send(CtrlAjax.getLink(mod,ctrl,act,prms));
    },

    receive : function ()
    {
        var oCtrlAjax;
        var maxKey = CtrlAjax.aRequest.length;
        for (i=0;i<maxKey;i++)
        {
            oCtrlAjax = CtrlAjax.aRequest[i];
            if (oCtrlAjax.XHRequest)
            {
                if (oCtrlAjax.XHRequest.readyState == oCtrlAjax.READY_STATE_COMPLETE)
                {
                    if (oCtrlAjax.XHRequest.status == 200)
                    {



                        /*
                         * Hace una demora de 20 ms entre cada proccesResponse para evitar que se solapen,
                         * y entre en recursividad, cuando se llama a metodos ajax desde ajax.
                         */
                        function delayPR() {oCtrlAjax.processResponse(oCtrlAjax.XHRequest.responseText);} setTimeout(delayPR,20);



                        CtrlAjax.aRequest[i] = 'Procesado OK';
                        if (CtrlAjax.aRequest.length)
                        {
                            CtrlAjax.receive();
                        }
                    }
                }
            }
        }

    },

    getLink : function (mod,ctrl,act,prms)
    {
        link = trim(mod)+'.'+trim(ctrl)+'Ajax.'+trim(act)+'+';
        if (prms)
            link += prms;
        return link;
    }

};

function initXHRequest()
{
    var xhr=null;

    if (window.XMLHttpRequest) // Firefox, Opera 8.0+, Safari
    {
            xhr = new XMLHttpRequest();
    }
    else if (window.ActiveXObject)// IE
    {
        try
        {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        }
        catch (e)
        {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        }
    }

    if (xhr)
    {
        nextKey = CtrlAjax.aRequest.length;
        this.rqKey = nextKey;
        this.XHRequest = xhr;
        return true;
    }
    alert ("Error de ejecucion\nclsCtrlAjax.initXHRequest()\nEl Explorador no soporta HTTP Request");
    return false;
}

function _send(sUrl)
{
    var url = null;
    var sid = this.rndSId();
    var postPrms = '';
    var urlPrms = '';
    if (!sUrl)
        var sUrl = '';

    if (this.initXHRequest())
    {
        this.XHRequest.onreadystatechange = CtrlAjax.receive;

        //Separando la URL y los parametros recibidos por sUrl
        aUrl = sUrl.split('?');
        if (aUrl.length > 1)
        {
            url = aUrl[0];
            for (var i=1 ; i<aUrl.length ; i++)
                urlPrms += '&'+aUrl[i];
        }
        else
        {
            aUrl = sUrl.split('&');
            if (aUrl.length > 1)
            {
                url = aUrl[0];
                for (var i=1 ; i<aUrl.length ; i++)
                    urlPrms += '&'+aUrl[i];
            }
            else
            {
                url = sUrl;
                urlPrms = '';
            }
        }

        this.loadMsg('start');

        //Agregando al POST el Id de session Ajax
        postPrms = this.addPostParam(postPrms,'sid',sid);

        //Agregando al POST los parametros de URL del FORM
        postPrms += '&'+this.encodePostStr(urlPrms);

        //Agregando al POST los campos del FORM
        postPrms += '&'+this.getFormValuesPostEncode();

        this.XHRequest.open("POST",url,true);
        this.XHRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=ISO-8859-1");
        this.XHRequest.send(postPrms);
        CtrlAjax.aRequest[this.rqKey] = this;

    }
    else
    {
        alert("Error de ejecucion\nclsCtrlAjax.send("+url+" , "+prms+")");
    }
}

function processResponse(strJSON)
{
    try
    {
        ajaxRsp = eval("("+strJSON+")");
        aErrors = ajaxRsp.errors;
        aCommands = ajaxRsp.commands;
    }
    catch(err)
    {
        var aErrors = new Array();
        var aCommands = null;

        error="<h3>ERROR INESPERADO - CtrlAjax.processResponse</h3>";
        error+="<h4>"+err.message+"</h4>";
        if (!strJSON)
            error+="\n\nstrJSON = NULL\n\n";
        else
            error+="\n\n"+strJSON+".\n\n";
        
        aErrors[0] = error;

    }

    if (aErrors && aErrors.length > 0)
    {

        var oBody = document.getElementsByTagName('body')[0];
        var oErrMaster;
        var oErrContainer;
        var oErrList;

        if (!GEById('CtrlAjaxErrorMaster'))
        {
            oErrMaster=document.createElement('div');
            oErrMaster.setAttribute('id','CtrlAjaxErrorMaster');
            oBody.appendChild(oErrMaster);
        }
        else
        {
            oErrMaster = GEById('CtrlAjaxErrorMaster');
        }

        oErrContainer = document.createElement('div');
        oErrContainer.setAttribute('id','CtrlAjaxErrorContainer');
        oErrContainer.className = 'CtrlAjaxErrorMaster';

        oErrList = document.createElement('div');
        oErrList.setAttribute('id','CtrlajaxErrorListItem');
        oErrList.className = 'CtrlAjaxErrorList';

        for (var e = 0; e < aErrors.length ; e++)
        {
            oErrList.innerHTML += '<li >'+aErrors[e]+'</li>';
        }

        oErrContainer.innerHTML = '<table id="CtrlAjaxErrorTable" onclick="document.getElementsByTagName(\'body\')[0].removeChild(document.getElementById(\'CtrlAjaxErrorMaster\'));"><tr><th >ERRORES DETECTADOS</th><td id="CtrlAjaxErrorClose">&nbsp</td></tr></table>';
        oErrContainer.appendChild(oErrList);

        oErrMaster.innerHTML = '';
        oErrMaster.appendChild(oErrContainer);

    }

    if (aCommands && aCommands.length > 0)
    {
        for (var i=0 ; i < aCommands.length ; i++)
        {
            var cmd = '';
            var id = '';
            var prop = '';
            var data = '';

            if (aCommands[i].cmd)
                cmd = aCommands[i].cmd;
            if (aCommands[i].id)
                id = aCommands[i].id;
            if (aCommands[i].prop)
                prop = aCommands[i].prop;
            if (aCommands[i].data)
                data = Base64.decode(aCommands[i].data);
            cmdExec(cmd,id,prop,data);
        }
    }
    this.loadMsg('stop');


}

function cmdExec(cmd,id,prop,data)
{
    var objElement;

    if (id)
        objElement = this.GEById(id);

    try
    {
        if(cmd=="alert")
        {
            alert(data);
        }
        else if(cmd=="script")
        {
            eval(data);
        }
        else if(cmd=="includeScript")
        {
            this.includeScript(data);
        }
        else if(cmd=="assign")
        {
            eval("objElement."+prop+"=data;");
        }
        else if(cmd=="append")
        {
            eval("objElement."+prop+"+=data;");
        }
        else if(cmd=="prepend")
        {
            eval("objElement."+prop+" = data + objElement."+prop+";");
        }
        else if(cmd=="remove")
        {
            this.remove(id);
        }
        else if(cmd=="create")
        {
            this.create(id,data,prop);
        }

    }
    catch(e)
    {
        alert("Error de ejecucion\nthis.cmdExec("+cmd+","+id+","+prop+",data)\n\n"+e.name+": "+e.message+"\n\ndata:\n"+data);
    }
}

function includeScript(sFileName)
{
    var objHead=document.getElementsByTagName('head');
    var objScript=document.createElement('script');
    objScript.type='text/javascript';
    objScript.src=sFileName;objHead[0].appendChild(objScript);
}

function remove(id)
{
    objElement=this.GEById(id);
    if (objElement && objElement.parentNode && objElement.parentNode.removeChild)
    {
        objElement.parentNode.removeChild(objElement);
    }
}

function create(parentId,tag,id)
{
    var objParent = this.GEById(parentId);
    objElement=document.createElement(tag);
    objElement.setAttribute('id',id);
    if(objParent)
        objParent.appendChild(objElement);
}


function rndSId()
{
    return 'AjaxSId_'+Math.round(Math.random()*100000);
}

function getFormValuesPostEncode()
{
    var formValues = this.getFormValuesPost();
    return this.encodePostStr(formValues);
}

function encodePostStr(sPrms)
{
    var strEncoded = '';

    prmVal = sPrms.split('&');
    for (var p=0 ; p < prmVal.length ; p++)
    {
        prm = prmVal[p].split('=');
        strEncoded = this.addPostParam(strEncoded,prm[0],prm[1]);
    }
    return strEncoded;

}

function getFormValuesPost()
{
    var objForms = document.body.getElementsByTagName("form");
    
    var strUrl = '';

    for (var k=0 ; k<objForms.length ; k++)
    {
        objForm = objForms[k];

        if (objForm && objForm.tagName.toUpperCase() == 'FORM')
        {
            var formElements=objForm.elements;
            for (var i=0;i < formElements.length;i++)
            {
                if (strUrl)
                    strUrl += '&';

                if (formElements[i].name || formElements[i].id)
                {
                    if (formElements[i].type && ( formElements[i].type.toUpperCase() == 'CHECKBOX' || formElements[i].type.toUpperCase() == 'RADIO' ) && formElements[i].checked == false)
                        continue;

                    var param=formElements[i].name;
                    if (!param && formElements[i].id)
                        param = formElements[i].id;
                    
                    if (param)
                    {
                        if (formElements[i].type.toUpperCase()=='SELECT-MULTIPLE')
                        {
                            for (var j=0;j < formElements[i].length;j++)
                            {
                                if (formElements[i].options[j].selected==true)
                                    strUrl += param+"[]="+formElements[i].options[j].value+'&';
                            }
                        }
                        else
                        {
                            if (formElements[i].type && formElements[i].type.toUpperCase() == 'CHECKBOX' && formElements[i].checked == true )
                                strUrl += param+"="+'checked';
                            else
                                strUrl += param+"="+formElements[i].value;
                        }
                    }
                }
            }
        }
    }
    return strUrl;
}

function addPostParam(sParams, sParamName, sParamValue)
{
    if (sParamName.length < 1)
        return sParams;
    if (sParams.length > 0)
        sParams += '&';
    return sParams + encodeURIComponent(sParamName) + '=' + encodeURIComponent(sParamValue);
}

function GEById(id)
{
    return document.getElementById(id);
}

/**
 * action == start => Muestra el mensaje CARGANDO:
 * action != start => Oculta el mensaje CARGANDO:
 */
function loadMsg(action)
{
    var loadMsgId = 'loadMsgCont';
    var oBody = document.getElementsByTagName('body')[0];

    if (action == 'start' && !this.GEById(loadMsgId))
    {
        oLoadMsg=document.createElement('div');
        oLoadMsg.setAttribute('id',loadMsgId);
        oBody.appendChild(oLoadMsg);
        oLoadMsg.className = 'CtrlAjaxLoadMsg';
        oLoadMsg.innerHTML = 'Cargando ...';
    }
    else if (GEById(loadMsgId))
    {
        removeElementById(loadMsgId);
    }
}
