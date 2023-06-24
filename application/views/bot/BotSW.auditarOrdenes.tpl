{{divPop}}
{{actionBar}}
<div class="container">
    <h3>Configuracion</h3>
    <div class="form-group">
        <span>Symbol</span>
        <input type="text" id="symbol" class="form-control" value="{{symbol}}">
        <!--
        <span>Fecha desde</span>
        <input type="text" id="fecha_desde" class="form-control" >
        -->
        <button type="button" id="btn_search" onclick="searchOrders()" class="form-control btn btn-success">Buscar Ordenes</button>
    </div>

    <div class="form-group" id="symbol_ok">
        <button type="button" id="btn_make" onclick="makeSql()" class="form-control btn btn-success">Generar SQL</button>
    </div>
</div>
{{data}}
<div class="container">
    <button type="button" id="btn_ejecutar" onclick="executeSql()" class="form-control btn btn-success">Ejecutar SQL</button>
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
        if (!$('#symbol').val())
            $('#symbol_ok').hide();
    }

    function searchOrders()
    {
        goTo('app.BotSW.agregarOrdenes+id='+$('#idbotsw').val()+'&symbol='+$('#symbol').val());
    }


    function makeSql()
    {
        CtrlAjax.sendCtrl("app","botSW","agregarOrdenesMakeSql");
        
    }

    function executeSql()
    {
        CtrlAjax.sendCtrl("app","botSW","agregarOrdenesMakeSql","execute=true");
        
    }

</script>
