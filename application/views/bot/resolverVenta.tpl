
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
      <td>Precio {{symbol}} 
        <span class="data">
          <input class="form-control form-control-sm" id="symbolPrice" value="{{symbolPrice}}">
        </td>
      <td>Cantidad de unidades 
        <span class="data" >
          <input class="form-control form-control-sm" READONLY id="qtyUnit" value="{{qtyUnit}}">
        </td>
    </tr>

    <tr>
      <td colspan="2">
        <div class="alert alert-warning" style="text-align: center;">
          Para resolver la venta, el sistema creara una nueva orden de venta LIMITE con los parámetros informados. <br/>
          El sistema generará la misma en Binance siempre que exista saldo disponible.<br/>
          Si bien el sistema propone los parámetros, <b>verificar el precio actual</b> de la moneda en la web de Binance

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
<div class="container">
  <ul class="nav nav-tabs">
    <li class="nav-item" id="tab_ordenesActivas">
      <a class="nav-link" href="#" onclick="activarTab('ordenesActivas')">Ordenes Activas</a>
    </li>
  </ul>
</div>
<div class="container tabs" id="ordenesActivas">
    {{ordenesActivas}}
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

    function resolverVenta()
    {
        if (confirm('Desea crear la orden de venta LIMIT?'))
            CtrlAjax.sendCtrl("app","bot","resolverVenta","id={{idoperacion}}");
    }

</script>