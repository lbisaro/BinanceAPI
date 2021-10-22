<style type="text/css">
  .data {
        font-weight: bolder;
        color: #555;
    }
</style>

<div class="container">

  <div class="form-group">
    <label for="symbol">Moneda</label>
    <div class="data">{{symbol}}</div>
  </div>

  <div class="form-group">
    <label for="inicio_usd">Cantidad de USD compra inicial</label>
    <div class="input-group mb-2">
        <div class="input-group-prepend">
            <div class="input-group-text">USD</div>
        </div>
        <input type="text" class="form-control" id="inicio_usd" value="{{inicio_usd}}" placeholder="0.000">
    </div>
  </div>

  <div class="form-group">
    <label for="multiplicador_compra">Multiplicador Compras</label>
    <input type="text" class="form-control" id="multiplicador_compra"  value="{{multiplicador_compra}}" placeholder="(1 a 2.5)">
  </div>

  <div class="form-group">
    <label for="multiplicador_porc">Multiplicador Porcentajes</label>
    <div class="input-group mb-2">
      <input type="text" class="form-control" id="multiplicador_porc"  value="{{multiplicador_porc}}" placeholder="(1 a 20)">
      <div class="input-group-prepend">
        <div class="input-group-text">%</div>
      </div>
    </div>
  </div>

  <input type="hidden" id="idoperacion" name="idoperacion" value="{{idoperacion}}">


  <div class="form-group" id="btnEditOperacion">
    <button onclick="editarOperacion()" class="btn btn-success" >Grabar</button>
  </div>

</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        
    });

    function editarOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","editarOperacion");
    }
    
</script>