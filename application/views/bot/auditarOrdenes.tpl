{{divPop}}
{{actionBar}}
<div class="container">
    <h3>Configuracion</h3>
    <div class="row">
        <div class="form-group col-4">
            <label for="check_last">Buscar ordenes desde</label>
            <input class="form-control" id="check_last" name="check_last" value="{{check_last}}" ></input>
        </div>
        <div class="form-group col-4">
            <label for="symbol">Par</label>
            <input class="form-control" id="symbol" {{symbol_read_only}} name="symbol" value="{{symbol}}" ></input>
        </div>
        <div class="form-group col-4">
            <span id="btn_find" onclick="buscar()" class="form-control btn btn-success">Buscar ordenes</span>
        </div>
    </div>
    <div class="row">
        <div class="form-group col-6">
            <select class="form-control" id="typeOrder">
              <option value="1">Pendiente</option>
              <option value="2">PNL Completo</option>
            </select>
        </div>
        <div class="form-group col-6">
            <span id="btn_make" onclick="makeSql()" class="form-control btn btn-success">Generar SQL</span>
        </div>
    </div>
</div>
{{data}}
<div class="container">
    <span id="btn_ejecutar" onclick="executeSql()" class="form-control btn btn-success">Ejecutar SQL</span>
</div>

{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>


<script language="javascript" >

    $(document).ready( function () {
        refresh();
    });

    function refresh()
    {
        $('#btn_ejecutar').hide();
    }

    function buscar()
    {
        var symbol = $('#symbol').val();
        var check_last = $('#check_last').val();
        var check_last_date = check_last.substring(6, 10)+'-'+check_last.substring(3, 5)+'-'+check_last.substring(0, 2);
        var url = 'app.bot.auditarOrdenes+{{url_prms}}&check_last='+check_last_date+'&symbol='+symbol+'&buscar=true';
        goTo(url);
    }


    function makeSql()
    {
        CtrlAjax.sendCtrl("app","bot","auditarOrdenesMakeSql");
        
    }

    function executeSql()
    {
        CtrlAjax.sendCtrl("app","bot","auditarOrdenesMakeSql","execute=true");
        
    }

</script>
