
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }
</style>


<div class="container">
  <table class="table table-borderless">
    <tr>
      <td>{{symbolSelector}}</td>
      <td style="text-align: right;">
        <a class="btn btn-info btn-sm menu-admin" href="app.bot.auditarOrdenes+id={{idoperacion}}">Auditar Ordenes</a>
        <a class="btn btn-info btn-sm" href="app.bot.editarOperacion+id={{idoperacion}}">Modificar</a>
        <a class="btn btn-info btn-sm" href="app.bot.revisarEstrategia+id={{idoperacion}}">Grafica</a>
        {{addButtons}}
        <button class="btn btn-{{toogleStopClass}} btn-sm" onclick="revertirStop()">{{toogleStopText}}</button>
        </td>
    </tr>
    <tr>
        <td>{{strTipo}}</td>
        <td>{{strDestinoProfit}}</td>
    </tr>
    <tr>
      <td>Capital 
        <span class="data">{{capital_usd}}</span></td>
      <td>Compra inicial 
        <span class="data">{{inicio_usd}}</span></td>
      
    </tr>
    <tr>
      <td>Multiplicador Compras 
        <span class="data">{{multiplicador_compra}}</td>
      <td>Multiplicador Porcentajes 
        <span class="data" >{{multiplicador_porc}}</td>
    </tr>
    <tr>
      <td>Venta inicial 
        <span class="data">{{porc_venta_up}}%</span></td>
      <td>Venta palanca 
        <span class="data">{{porc_venta_down}}%</span></td>
    </tr>
    <tr>
      <td>Estado 
        <span class="data">{{estado}}</td>
      <td>Reinicio Automatica 
        <span class="data" colspan="2">{{auto-restart}}</td>
    </tr>
    <tr>
      <td>PNL</td>
      <td colspan="2">
        <div style="width:30%;display:inline-block;vertical-align:top;"><div class="text-info">Ordenes Abiertas</div>{{pnlAbiertas}}</div>
        <div style="width:30%;display:inline-block;vertical-align:top;"><div class="text-info">Ordenes Completas</div>{{pnlCompletas}}</div>
        <div style="width:30%;display:inline-block;vertical-align:top;"><div class="text-info">General</div>{{pnlGeneral}}</div>
        </td>
    </tr>
  </table>

  {{hidden}}

</div>
<div class="container">
  <ul class="nav nav-tabs">
    <li class="nav-item" id="tab_ordenesActivas">
      <a class="nav-link" href="#" onclick="activarTab('ordenesActivas')">Ordenes Activas</a>
    </li>
    <li class="nav-item" id="tab_ordenesCompletas">
      <a class="nav-link" href="#" onclick="activarTab('ordenesCompletas')">Ordenes Completadas</a>
    </li>
    <!--
    <li class="nav-item">
      <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
    </li>
    -->
  </ul>
</div>
<div class="container tabs" id="ordenesActivas">
    {{ordenesActivas}}
    {{crearOrden_btn}}
    {{start_btn}}
</div>
<div class="container tabs" id="ordenesCompletas">
    {{ordenesCompletas}}
</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        activarTab('ordenesActivas');
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
        $('#arBtn').click(function () {
            CtrlAjax.sendCtrl("app","bot","toogleAutoRestart");
        });
    });

    function setAutoRestartTo(set)
    {
        if (set)
        {
            $('#arBtn').attr('class','btn btn-sm btn-success');
            $('#arBtn span').attr('class','glyphicon glyphicon-ok');
        }
        else
        {
            $('#arBtn').attr('class','btn btn-sm btn-danger');
            $('#arBtn span').attr('class','glyphicon glyphicon-ban-circle');
        }
    }

    function activarTab(id)
    {
        $('.nav-tabs a').removeClass('active');
        $('.tabs').hide();
        $('#'+id).show();
        $('#tab_'+id+' a').addClass('active');
        if (id=='ordenesCompletas')
        {
            CtrlAjax.sendCtrl("app","bot","cargarOrdenesCompletas");
        }
    }

    function revertirStop()
    {
        CtrlAjax.sendCtrl("app","bot","toogleStop");  
    }

    function startOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","start");
    }

</script>