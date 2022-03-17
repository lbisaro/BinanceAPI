<style type="text/css">
  .data {
        font-weight: bolder;
        color: #555;
    }
</style>

<div class="container">

  <div class="row">
    <div class="col4">
      <h5>Configuracion de la operacion</h5>
      <div class="form-group">
        <label for="symbol">Moneda</label>
        <input type="text" class="form-control" value="{{symbol}}" readonly id="symbol" >
      </div>

      <div class="form-group">
        <label for="inicio_usd">Capital</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">USD</div>
            </div>
            <input type="text" class="form-control" id="capital_usd" value="{{capital_usd}}" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="inicio_usd">Compra inicial</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">USD</div>
            </div>
            <input type="text" class="form-control" id="inicio_usd" value="{{inicio_usd}}" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Compras</label>
        <input type="text" class="form-control" id="multiplicador_compra" value="{{multiplicador_compra}}"  onchange="refreshTable()" placeholder="Recomendado 1.05 a 2.00">
      </div>
      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="multiplicador_porc" value="{{multiplicador_porc}}"  onchange="refreshTable()" placeholder="Recomendado 2.70 a 4.50">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes Incremental</label>
        <div class="input-group mb-2">
          <select id="multiplicador_porc_inc" class="form-control" onchange="refreshTable()" >
          <option value="1" {{mpi_selected_1}}>Si</option>
          <option value="0" {{mpi_selected_0}}>No</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="porc_venta_up">Porcentaje de venta inicial/palanca</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="porc_venta_up" value="{{porc_venta_up}}"  onchange="refreshTable()" placeholder="Recomendado 1.15 a 5.00">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="porc_venta_down" value="{{porc_venta_down}}"  onchange="refreshTable()" placeholder="Recomendado 1.15 a 5.00">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>

      <div class="form-group" id="btnEditOperacion">
        <button onclick="editarOperacion()" class="btn btn-success" >Grabar</button>
      </div>

    </div>

    <div class="col">
      <h5>Referencia sobre la operacion</h5>
      <div class="container" id="oprTable"></div>
    </div>

  </div>

  <input type="hidden" id="idoperacion" name="idoperacion" value="{{idoperacion}}">



</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        refreshTable();
    });

    function editarOperacion()
    {
        CtrlAjax.sendCtrl("app","bot","editarOperacion");
    }


    function refreshTable()
    {
        var capital_usd = $('#capital_usd').val();
        var inicio_usd = $('#inicio_usd').val();
        var m_compra = $('#multiplicador_compra').val();
        var m_porc = $('#multiplicador_porc').val();
        var m_porc_inc = $('#multiplicador_porc_inc option:selected ').val();
        var table = '';
        
        if (capital_usd>0 && inicio_usd>0 && m_compra>0 && m_porc>0)
        {

            table = `<table class="table">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Precio Generico</th>
                        <th>% Sobre ultima compra </th>
                        <th>% Sobre compra Inicial</th>
                        <th>Compra USD</th>
                        <th>Total Compra</th>
                        <th>Venta</th>
                    </tr>
                </thead>
                <tbody>
                `;
            
            symbolPrice = 100;
            symbolDecs = 2;
            precio = format_number(symbolPrice,symbolDecs);
            psuc = 0;
            psci = 0;
            compraUsd = inicio_usd*1;
            totalCompra = compraUsd;
            venta = '+'+toDec($('#porc_venta_up').val())+'%';

            var i=1;
            while (totalCompra<=capital_usd)
            {
                table = table + '<tr>';
                table = table + '<td>#'+i+'</td>';
                table = table + '<td>'+toDec(precio)+'</td>';
                table = table + '<td class="text-danger">'+(psuc!=0?'-':'')+format_number(psuc,2)+'%</td>';
                table = table + '<td class="text-danger">'+format_number(psci,2)+'%</td>';
                table = table + '<td>'+format_number(compraUsd,2)+'</td>';
                table = table + '<td>'+format_number(totalCompra,2)+'</td>';
                table = table + '<td class="text-success">'+venta+'</td>';
                table = table + '</tr>';

                if (m_porc_inc==1)
                    psuc = parseFloat(m_porc)*(i);
                else
                    psuc = parseFloat(m_porc);

                precio = (parseFloat(precio)*(parseFloat(1-(psuc/100))));
                
                psci = ((parseFloat(precio)/parseFloat(symbolPrice))-1)*100;

                compraUsd = parseFloat(compraUsd)*parseFloat(m_compra);

                totalCompra = parseFloat(totalCompra) + parseFloat(compraUsd);

                venta = '+'+toDec($('#porc_venta_down').val())+'%';
                i++;
            }
            
            table = table + `
                </tbody>
            </table>`;
        }
        
        $('#oprTable').html(table);        
    }
    
</script>