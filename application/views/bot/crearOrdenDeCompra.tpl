
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
        <span class="data" >{{auto-restart}}</td>
    </tr>
    <tr>
      <td>Precio de compra  
        <span class="data">
          <input class="form-control form-control-sm" id="symbolPrice" value="{{symbolPrice}}">
          <div class="text-info">Precio actual: {{precioActual}}</div>
        </td>
      <td>Importe USD 
        <span class="data" >
          <input class="form-control form-control-sm" id="qtyUSD" value="{{qtyUSD}}">
        </td>
    </tr>

    <tr>
      <td colspan="2">
        <div class="alert alert-warning" style="text-align: center;">
          El sistema creara una nueva orden de compra LIMITE con los parámetros informados. <br/>
          El sistema generará la misma en Binance siempre que exista saldo disponible.<br/>
          <b>Verificar el precio actual</b> de la moneda en la web de Binance

        </div>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        {{addButtons}}
      </td>
    </tr>
  </table>

  {{hidden}}

</div>






<script type="text/javascript">
    
    $(document).ready( function () {
        activarTab('ordenesActivas');
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
    });

    function activarTab(id)
    {
        return false;
    }

    function crearOrdenDeCompra()
    {
        if (confirm('Desea crear la orden de compra LIMIT?'))
            CtrlAjax.sendCtrl("app","bot","crearOrdenDeCompra","id={{idoperacion}}");
    }

</script>