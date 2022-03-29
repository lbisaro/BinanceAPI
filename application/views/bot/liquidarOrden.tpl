
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
      <td colspan="2">
        <div id="status_msg" class="alert alert-warning" style="text-align: center;">
          {{warningMsg}}
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
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
    });

    function liquidarOrden()
    {
        //$('#btnLiquidarOrden').attr('disabled',true);
        $('#status_msg').html('Liquidando la orden, aguarde un momento.');
        $('#status_msg').attr('class','alert alert-success');
        CtrlAjax.sendCtrl("app","bot","liquidarOrden");
    }

    function statusMessage(msg,msgClass)
    {
        $('#status_msg').html(msg);
        $('#status_msg').attr('class','alert alert-'+msgClass);
    }

</script>