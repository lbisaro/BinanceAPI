<div class="container">

  <div class="form-group">
    <label for="symbol">Moneda</label>
    <input type="text" class="form-control" id="symbol" onchange="validSymbol()" placeholder="xxxUSDT">
  </div>

  <div class="form-group">
    <label for="inicio_usd">Cantidad de USD compra inicial</label>
    <div class="input-group mb-2">
        <div class="input-group-prepend">
            <div class="input-group-text">USD</div>
        </div>
        <input type="text" class="form-control" id="inicio_usd" onchange="validSymbol()"  placeholder="0.000">
    </div>
  </div>

  <div class="form-group">
    <label for="multiplicador_compra">Multiplicador Compras</label>
    <input type="text" class="form-control" id="multiplicador_compra"  placeholder="(1 a 2.5)">
  </div>

  <div class="form-group">
    <label for="multiplicador_porc">Multiplicador Porcentajes</label>
    <div class="input-group mb-2">
      <input type="text" class="form-control" id="multiplicador_porc"  placeholder="(1 a 20)">
      <div class="input-group-prepend">
        <div class="input-group-text">%</div>
      </div>
    </div>
  </div>


  <div class="form-group" id="btnAddOperacion">
    <button onclick="crearOperacion()" class="btn btn-success" >Crear Operacion</button>
  </div>

</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        $('#btnAddOperacion').hide();
    });

    function validSymbol()
    {
        if ($('#symbol').val())
        {
            $.getJSON('app.BotAjax.symbolData+symbol='+$('#symbol').val(), function( data ) {
                if (data.symbol)
                {
                    $('#symbol').val(data.symbol);
                    $('#symbol').addClass('text-success');
                    $('#btnAddOperacion').show();
                    return true;
                }
            });
        }
        $('#btnAddOperacion').hide();
        $('#symbol').val('');
        $('#symbol').removeClass('text-success');
    }

    function crearOperacion()
    {
        $('#btnAddOperacion').hide();
        CtrlAjax.sendCtrl("app","bot","crearOperacion");
        setTimeout(function () {$('#btnAddOperacion').show();},2000);
    }
    
</script>