<div class="container">
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="symbol">Par</label>
        <input type="text" class="form-control" id="symbol" onchange="validSymbol()" placeholder="BTCUSDT">
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="inicio_precio">Precio de compra inicial</label>
        <input type="text" class="form-control" id="inicio_precio" placeholder="0.000">
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="inicio_usd">Cantidad de USD compra inicial</label>
        <input type="text" class="form-control" id="inicio_usd"  placeholder="0.000">
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_compra">Configuracion Multiplicador Compras</label>
        <input type="text" class="form-control" id="multiplicador_compra"  placeholder="2">
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_porc">Configuracion Multiplicador Porcentajes</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="multiplicador_porc"  placeholder="10">
          <div class="input-group-prepend">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row" id="btnCalcular">
    <div class="col">
      <div class="form-group">
        <button onclick="calcularOperacion()" class="btn btn-primary float-right" >Calcular</button>
      </div>
    </div>
  </div>
</div>

<div class="container" id="calculoOperacion"></div>


<script type="text/javascript">
    
    $(document).ready( function () {
    });

    function validSymbol()
    {
        var symbol = $('#symbol').val()

        //CtrlAjax.sendCtrl("mod","ctrl","act");
    }

    function calcularOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","calcularOperacion");

    }
    
</script>