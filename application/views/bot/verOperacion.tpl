
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }
</style>

<div class="container">
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="symbol">Moneda</label>
        <div class="data">{{symbol}}</div>
      </div>
    </div>    
    <div class="col">
      <div class="form-group">
        <label for="inicio_usd">Cantidad de USD compra inicial</label>
        <div class="data">{{inicio_usd}}</div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Compras</label>
        <div class="data">{{multiplicador_compra}}</div>
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes</label>
        <div class="data">{{multiplicador_porc}}</div>
      </div>
    </div>
  </div>  
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_compra">Estado</label>
        <div class="data" id="estado">{{estado}}</div>
      </div>
    </div>
  </div>  

  <!--
  <div class="form-group" id="btnAddOperacion">
    <button onclick="crearOperacion()" class="btn btn-success" >Crear Operacion</button>
  </div>
  -->
  {{hidden}}
</div>
<div class="container">
    <h2>Ordenes</h2>
    {{ordenes}}
</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        //
    });

    function checkMatch()
    {
        var preEstado = $('#estado').html();
        $('#estado').html('Verificando....');
        CtrlAjax.sendCtrl("app","bot","checkMatch");
        $.getJSON('app.BotAjax.checkMatch+idoperacion='+$('#idoperacion').val(), function( data ) {
            console.log(data);
            if (data.ordenesPendientes>0)
            {
                $('#estado').html('Esperando completar ordenes');
                $('#estado').addClass('text-warning');
                setTimeout(function() {checkMatch();},60000);
            }
            else if (data.ordenesPendientes == 0)
            {
                $('#estado').html(preEstado);
                $('#estado').addClass('text-primary');
            }
        });
    }
    
</script>