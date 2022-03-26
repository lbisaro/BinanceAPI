
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
        {{addButtons}}
        <a class="btn btn-info btn-sm" href="app.bot.auditarOrdenes+id={{idoperacion}}">Auditar Ordenes</a>
        <a class="btn btn-info btn-sm" href="app.bot.editarOperacion+id={{idoperacion}}">Modificar</a>
        <a class="btn btn-info btn-sm" href="app.bot.revisarEstrategia+id={{idoperacion}}">Grafica</a>
        </td>
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
      <td>Recompra Automatica 
        <span class="data" colspan="2">{{auto-restart}}</td>
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
    <li class="nav-item" id="tab_estadistica">
      <a class="nav-link" href="#" onclick="activarTab('estadistica')">Estadistica</a>
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
    {{crearOrdenDeCompra_btn}}
    {{start_btn}}
</div>
<div class="container tabs" id="ordenesCompletas">
    {{ordenesCompletas}}
</div>
<div class="container tabs" id="estadistica">
  <div class="container">
    
    <div class="row">
      <div class="col">
        <div class="form-group">
          <label for="symbol">Total de ventas</label>
          <div class="data">{{est_totVentas}}</div>
        </div>
      </div>    
    </div>
    <div class="row">
      <div class="col">
        <div class="form-group">
          <label for="symbol">Total de ganancias</label>
          <div class="data">USD {{est_gananciaUsd}}</div>
        </div>
      </div>    
    </div>
  </div>
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
    }

    function startOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","start");
    }

</script>