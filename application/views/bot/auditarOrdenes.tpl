{{divPop}}
{{actionBar}}
<div class="container">
    <h3>Configuracion</h3>
    <div class="form-group">
        <select class="form-control" id="typeOrder">
          <option value="1">Pendiente</option>
          <option value="2">PNL Completo</option>
        </select>
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
