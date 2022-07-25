
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
      <td>{{symbolSelector}}</td>
      <td style="text-align: right;">
        <a class="btn btn-info btn-sm" href="app.bot.verOperacion+id={{idoperacion}}">Cancelar</a>
        </td>
    </tr>
    <tr>
        <td>{{strTipo}}</td>
        <td>{{strDestinoProfit}}</td>
    </tr>
    <tr>
      <td>Capital 
        <span class="data">{{capital_usd}}</span></td>
      <td>Venta inicial 
        <span class="data">{{inicio_usd}}</span></td>
      
    </tr>
    <tr>
      <td>Multiplicador Ventas 
        <span class="data">{{multiplicador_compra}}</td>
      <td>Multiplicador Porcentajes 
        <span class="data" >{{multiplicador_porc}}</td>
    </tr>
    <tr>
      <td>Compra inicial 
        <span class="data">{{porc_venta_up}}%</span></td>
      <td>Compra palanca 
        <span class="data">{{porc_venta_down}}%</span></td>
    </tr>
    <tr>
      <td>Estado 
        <span class="data">{{estado}}</td>
      <td>Recompra/Reventa Automatica 
        <span class="data" colspan="2">{{auto-restart}}</td>
    </tr>

  </table>

  {{hidden}}

</div>

<div class="container">
  <h5 class="text-info">
    Seleccione las opciones adicionales al apagar el Bot
  </h5>
  <div class="container">
    <div class="input-group mb-2">
        <div class="form-group form-check">
          <input type="checkbox" data-toggle="toggle" data-on="Si" data-off="No" data-size="mini" class="form-check-input" CHECKED id="delOrdenesActivas" >
          Eliminar registros de ordenes activas en el Bot
        </div>
    </div>
    <div class="input-group mb-2">
        <div class="form-group form-check">
          <input type="checkbox" data-toggle="toggle" data-on="Si" data-off="No" data-size="mini" class="form-check-input" CHECKED id="autoRestartOff" >
          Anular el recompra/reventa automatica
        </div>
    </div>    
    <div class="input-group mb-2">
        <div class="form-group form-check">
          <input type="checkbox" data-toggle="toggle" data-on="Si" data-off="No" data-size="mini" class="form-check-input" CHECKED id="delOrdenesBinance" >
          Eliminar ordenes abiertas en Binance
        </div>
    </div>
  </div>
  <div class="input-group mb-2">
    <button class="btn btn-danger btn-large btn-block" onclick="apagarBot();">Detener Bot</button>
  </div>
</div>


<div class="container">
  <h5 class="text-info">Ordenes Activas</h5>
    {{ordenesActivas}}
</div>






<script type="text/javascript">
    
    function apagarBot()
    {
        if (confirm('Confirma Apagar el Bot?'))
            CtrlAjax.sendCtrl("app","bot","apagarBot","id={{idoperacion}}");
    }

</script>