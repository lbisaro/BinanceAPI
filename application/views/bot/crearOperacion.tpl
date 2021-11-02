<style type="text/css">
  .data {
    font-weight: bolder;
  }
</style>
<div class="container">

  <div class="row">
    <div class="col3">
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
            <input type="text" class="form-control" id="inicio_usd" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Compras</label>
        <input type="text" class="form-control" id="multiplicador_compra"  onchange="refreshTable()" placeholder="(1 a 2.5) Recomendado 2.00">
      </div>
      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="multiplicador_porc"  onchange="refreshTable()" placeholder="(1 a 20) Recomendado 2.70">
          <div class="input-group-prepend">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes Incremental</label>
        <div class="input-group mb-2">
          <select id="multiplicador_porc_inc" class="form-control" onchange="refreshTable()" >
              <option value="1">Si</option>
              <option value="0">No</option>
          </select>
        </div>
      </div>

        <div class="form-group" id="btnAddOperacion">
            <button onclick="crearOperacion()" class="btn btn-success" >Crear Operacion</button>
        </div>
      </div>


      <div class="col">
        Referencia sobre la operacion
        <h6 cass=""text-info">
          Precio de referencia aproximado: <span class="data">USD</span> <span id="symbolPrice" class="data">0.00</span>
        </h6>
        <div class="container" id="oprTable"></div>
      </div>

    </div>
  </div>

</div>

<script type="text/javascript">
    
    $(document).ready( function () {
        $('#btnAddOperacion').hide();
    });

    var symbolPrice = 0;
    var symbolDecs = 0;

    function validSymbol()
    {
        if ($('#symbol').val())
        {
            $.getJSON('app.BotAjax.symbolData+symbol='+$('#symbol').val(), function( data ) {
                if (data.symbol)
                {
                    console.log(data);
                    $('#symbol').val(data.symbol);
                    $('#symbol').addClass('text-success');
                    symbolPrice = data.price*1;
                    symbolDecs = data.qtyDecsPrice*1;
                    $('#btnAddOperacion').show();
                    return true;
                }
            });
        }
        $('#btnAddOperacion').hide();
        $('#symbol').val('');
        $('#symbol').removeClass('text-success');
        refreshTable();
    }

    function crearOperacion()
    {
        $('#btnAddOperacion').hide();
        CtrlAjax.sendCtrl("app","bot","crearOperacion");
        setTimeout(function () {$('#btnAddOperacion').show();},2000);
    }

    function refreshTable()
    {
        $('#symbolPrice').html(symbolPrice);
        var inicio_usd = $('#inicio_usd').val();
        var m_compra = $('#multiplicador_compra').val();
        var m_porc = $('#multiplicador_porc').val();
        var m_porc_inc = $('#multiplicador_porc_inc option:selected ').val();
        var table = '';
        
        if (symbolPrice && inicio_usd>0 && m_compra>0 && m_porc>0)
        {

            table = `<table class="table">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Precio</th>
                        <th>% Sobre ultima compra </th>
                        <th>% Sobre compra Inicial</th>
                        <th>Compra USD</th>
                        <th>Total Compra</th>
                        <th>Venta</th>
                    </tr>
                </thead>
                <tbody>
                `;
            
            precio = format_number(symbolPrice,symbolDecs);
            psuc = 0;
            psci = 0;
            compraUsd = inicio_usd;
            totalCompra = compraUsd;
            venta = '+{{PORCENTAJE_VENTA_UP}}';


            for (var i=1; i<6; i++)
            {
                table = table + '<tr>';
                table = table + '<td>Compra #'+i+'</td>';
                table = table + '<td>'+format_number(precio,symbolDecs)+'</td>';
                table = table + '<td class="text-danger">-'+format_number(psuc,2)+'%</td>';
                table = table + '<td class="text-danger">-'+format_number(psci,2)+'%</td>';
                table = table + '<td>'+format_number(compraUsd,2)+'</td>';
                table = table + '<td>'+format_number(totalCompra,2)+'</td>';
                table = table + '<td class="text-success">'+venta+'</td>';
                table = table + '</tr>';

                if (m_porc_inc==1)
                    psuc = parseFloat(m_porc)*(i);
                else
                    psuc = parseFloat(m_porc);

                precio = (parseFloat(precio)*(parseFloat(1-(psuc/100))));
                
                psci = ((parseFloat(symbolPrice)/parseFloat(precio))-1)*100;

                compraUsd = parseFloat(compraUsd)*parseFloat(m_compra);

                totalCompra = parseFloat(totalCompra) + parseFloat(compraUsd);

                venta = '+{{PORCENTAJE_VENTA_DOWN}}%';

            }
            
            table = table + `
                </tbody>
            </table>`;
        }
        
        $('#oprTable').html(table);        
    }
    
</script>