
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }
</style>



<div style="float: right;">
  
</div>


<div class="container">
  <table class="table table-borderless">
    <tr>
      <td>Moneda 
        <span class="data">{{symbol}}</span></td>
      <td>Cantidad de USD compra inicial 
        <span class="data">{{inicio_usd}}</span></td>
      <td style="text-align: right;"><a class="btn btn-info btn-sm" href="app.bot.editarOperacion+id={{idoperacion}}">Modificar</button></td>
    </tr>
    <tr>
      <td>Multiplicador Compras 
        <span class="data">{{multiplicador_compra}}</td>
      <td>Multiplicador Porcentajes 
        <span class="data" colspan="2">{{multiplicador_porc}}</td>
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

    function start()
    {
        if (confirm('Desea reiniciar la operacion?'))
            CtrlAjax.sendCtrl("app","bot","start");
    }

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

</script>