
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
      <td>Reinicio automatico 
        <span class="data" colspan="2">{{auto-restart}}</td>
    </tr>

  </table>

  {{hidden}}

</div>

<div class="container">
  <h5 class="text-info">
    Seleccione las opciones adicionales al liquidar la operacion
  </h5>
  <div class="container">
    <div class="input-group mb-2">
        <div class="form-group form-check">
          <input type="checkbox" data-toggle="toggle" data-on="Si" data-off="No" data-size="mini" class="form-check-input" CHECKED id="autoRestartOff" >
          Anular el reinicio automatico
        </div>
    </div>    
  </div>
  <div class="input-group mb-2">
    <button id="btnLiquidar" class="btn btn-warning btn-large btn-block" onclick="apagarBot();">Liquidar la operacion</button>
  </div>
</div>


<div class="container">
  <h5 class="text-info">Ordenes Activas</h5>
    {{ordenesActivas}}
</div>






<script type="text/javascript">
    
    function apagarBot()
    {
        if (confirm('Confirma Liquidar la operacion?'))
        {
            $('#btnLiquidar').attr('disabled','disabled');
            CtrlAjax.sendCtrl("app","bot","liquidarOp","id={{idoperacion}}");
        }
    }

</script>