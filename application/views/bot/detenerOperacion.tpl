
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
      <td>Estado 
        <span class="data">{{estado}}</td>
      <td>Recompra Automatica 
        <span class="data" >{{auto-restart}}</td>
    </tr>
    <tr>
      <td colspan="2">
        {{addButtons}}
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <div class="alert alert-danger" style="text-align: center;">
          Al detener la operacion, se eliminan los registros de ordenes activas y anula la recompra automatica.<br/>
          Para finalizar la venta y/o apalancamiento, sera necesario generar manualmente las ordenes desde la aplicacion de Binance.<br/>
          Una vez resuelta la operacion en Binance, se puede activar la recompra automatica para reiniciar la operacion.
        </div>
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

    function detenerOperacion()
    {
        if (confirm('Confirma detener la operacion?'))
            CtrlAjax.sendCtrl("app","bot","detenerOperacion","id={{idoperacion}}");
    }

</script>