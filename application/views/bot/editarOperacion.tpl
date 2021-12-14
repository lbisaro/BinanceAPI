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
    <input type="text" class="form-control" id="multiplicador_compra"  value="{{multiplicador_compra}}" placeholder="Recomendado 1.05 a 2.00">
  </div>

  <div class="form-group">
    <label for="multiplicador_porc">Multiplicador Porcentajes</label>
    <div class="input-group mb-2">
      <input type="text" class="form-control" id="multiplicador_porc"  value="{{multiplicador_porc}}" placeholder="Recomendado 2.70 a 4.50">
      <div class="input-group-prepend">
        <div class="input-group-text">%</div>
      </div>
    </div>
  </div>

  <div class="form-group">
    <label for="multiplicador_porc">Multiplicador Porcentajes Incremental</label>
    <div class="input-group mb-2">
      <select id="multiplicador_porc_inc" class="form-control" >
          <option value="0" {{mpi_selected_0}}>No - Incrementa cada apalancamiento al mismo valor</option>
          <option value="1" {{mpi_selected_1}}>Si - Incrementa cada apalancamiento al doble del anterior</option>
      </select>
    </div>
  </div>

  <div class="form-group">
    <label for="porc_venta_up">Porcentaje de venta inicial/palanca</label>
    <div class="input-group mb-2">
      <select id="porc_venta_up" class="form-control" onchange="refreshTable()" >
          <option value="1.15">1.15%</option>
          <option value="1.5">1.50%</option>
          <option value="1.75">1.75%</option>
          <option value="2">2.00%</option>
          <option value="2.5">2.50%</option>
          <option value="3">3.00%</option>
      </select>
      <select id="porc_venta_down" class="form-control" onchange="refreshTable()" >
          <option value="1.5">1.50%</option>
          <option value="1.75">1.75%</option>
          <option value="2">2.00%</option>
          <option value="2.5">2.50%</option>
          <option value="3">3.00%</option>
          <option value="4">4.00%</option>
      </select>
    </div>
  </div>

  <input type="hidden" id="idoperacion" name="idoperacion" value="{{idoperacion}}">


  <div class="form-group" id="btnEditOperacion">
    <button onclick="editarOperacion()" class="btn btn-success" >Grabar</button>
  </div>

</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        $('#porc_venta_up option').each( function () {
            if ($(this).val() == {{PORCENTAJE_VENTA_UP}})
            {
                $(this).html(toDec({{PORCENTAJE_VENTA_UP}})+'% Default');
            }
            if (toDec($(this).val()) == {{porc_venta_up}})
            {
                $(this).attr('SELECTED',true);
            }
        });
        $('#porc_venta_down option').each( function () {
            if ($(this).val() == {{PORCENTAJE_VENTA_DOWN}})
            {
                $(this).html(toDec({{PORCENTAJE_VENTA_DOWN}})+'% Default');
            }
            if (toDec($(this).val()) == {{porc_venta_down}})
            {
                $(this).attr('SELECTED',true);
            }
        });
        
    });

    function editarOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","editarOperacion");
    }
    
</script>