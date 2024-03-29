<style type="text/css">
  .data {
        font-weight: bolder;
        color: #555;
    }
</style>

<div class="container">

  <div class="row">
    <div class="col-4">
      <h5>Configuracion de la operacion</h5>
      <div class="form-group">
        <label for="symbol">Moneda</label>
        <input type="text" class="form-control" value="{{symbol}}" readonly id="symbol" >
      </div>

      <div class="form-group">
        <label for="inicio_usd">Capital</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">{{baseAsset}}</div>
            </div>
            <input type="text" class="form-control" id="capital_usd" value="{{capital_usd}}" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="inicio_usd">Venta inicial</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">{{baseAsset}}</div>
            </div>
            <input type="text" class="form-control" id="inicio_usd" value="{{inicio_usd}}" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <div class="input-group mb-2">
            <select id="destino_profit" class="form-control form-control-sm">
              <option value="0" {{dp_selected_0}}>Obtener ganancias en {{quoteAsset}}</option>
              <option value="1" {{dp_selected_1}}>Obtener ganancias en {{baseAsset}}</option>
            </select>
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Ventas</label>
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
        <div class="input-group mb-2">
          <div class="form-group form-check">
            <input type="checkbox" data-toggle="toggle" data-on="Incremental" data-off="Lineal" data-size="sm" class="form-check-input" {{mpi_checked}} id="multiplicador_porc_inc" onchange="refreshTable()" >
          </div>
          <!--<div id="check_MPAuto" class="form-group form-check menu-admin">
            <input type="checkbox" data-toggle="toggle" data-on="Automatico" data-off="Auto OFF" data-size="sm" class="form-check-input" {{mpa_checked}} id="multiplicador_porc_auto" onchange="refreshTable();getMPAuto();" >
          </div>-->
        </div>
      </div>

      <div class="form-group">
        <label for="porc_venta_up">Porcentaje de compra inicial/palanca</label>
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
        <h4 class="text-danger">{{strTipoOp}}</h4>
      <div class="container" id="oprTable"></div>
    </div>

  </div>

  <input type="hidden" id="idoperacion" name="idoperacion" value="{{idoperacion}}">
  <input type="hidden" name="tipo" id="tipo" value="{{tipo}}">


</div>


<script type="text/javascript">
    
    var show_check_MPAuto = {{show_check_MPAuto}};

    var baseAsset = '{{baseAsset}}';
    var symbolDecs = {{qtyDecs}};
    var qtyDecsPrice = {{qtyDecsPrice}};
    var symbolPrice = {{symbolPrice}};
            

    $(document).ready( function () {
        refreshTable();

        if (show_check_MPAuto)
        {
            $('#check_MPAuto').show();
        }
        else
        {
            $('#check_MPAuto').hide();
            $('#multiplicador_porc_auto').attr('checked',false);
        }

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
        var m_porc_inc = ($('#multiplicador_porc_inc ').is(':checked')?1:0);
        var m_porc_auto = ($('#multiplicador_porc_auto ').is(':checked')?1:0);
        var table = '';

        if (m_porc_auto)
            $('#multiplicador_porc').attr('readonly',true);
        else
            $('#multiplicador_porc').attr('readonly',false);
        
        if (capital_usd>0 && inicio_usd>0 && m_compra>0 && m_porc>0)
        {

            table = `<table class="table">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Precio de Venta</th>
                        <th>% Sobre ultima venta </th>
                        <th>% Sobre venta Inicial</th>
                        <th>Venta {{baseAsset}}</th>
                        <th>Total Venta</th>
                        <th>Precio de Compra</th>
                    </tr>
                </thead>
                <tbody>
                `;
            
            symbolDecs = 2;
            precio = format_number(symbolPrice,qtyDecsPrice);
            psuc = 0;
            psci = 0;
            compraUsd = inicio_usd*1;
            totalCompra = compraUsd;
            ventaPorc = ventaPorc = $('#porc_venta_up').val()/100;

            var i=1;
            while (totalCompra<=capital_usd)
            {
                precioVenta = precio * (1-ventaPorc);
                strVenta = totalCompra+'*'+precio+' = '+(totalCompra*precio)+' -> '+totalCompra+'*'+precioVenta+' = '+(totalCompra*precioVenta);
                qtyVenta = (totalCompra*precio)/precioVenta;

                table = table + '<tr>';
                table = table + '<td>#'+i+'</td>';
                table = table + '<td>'+format_number(precio,qtyDecsPrice)+'</td>';
                table = table + '<td class="text-success">'+format_number(psuc,2)+'%</td>';
                table = table + '<td class="text-success">'+format_number(psci,2)+'%</td>';
                table = table + '<td>'+format_number(compraUsd,qtyDecsPrice)+'</td>';
                table = table + '<td>'+format_number(totalCompra,qtyDecsPrice)+'</td>';
                table = table + '<td class="text-danger">'+format_number(precioVenta,qtyDecsPrice)+'</td>';
                table = table + '</tr>';

                if (m_porc_inc==1)
                    psuc = parseFloat(m_porc)*(i);
                else
                    psuc = parseFloat(m_porc);

                precio = (parseFloat(precio)*(parseFloat(1+(psuc/100))));
                
                psci = ((parseFloat(precio)/parseFloat(symbolPrice))-1)*100;

                compraUsd = parseFloat(compraUsd)*parseFloat(m_compra);

                totalCompra = parseFloat(totalCompra) + parseFloat(compraUsd);

                ventaPorc = $('#porc_venta_down').val()/100;
                i++;
            }
            
            table = table + `
                </tbody>
            </table>`;
        }
        
        $('#oprTable').html(table);        
    }

    function getMPAuto()
    {
        if ($('#multiplicador_porc_auto ').is(':checked'))
        {
            $('#multiplicador_porc').val('Obteniendo valor...');

            var url = 'app.BotAjax.getMultiplicadorPorcAuto+symbol='+$('#symbol').val();
            $.get( url, function( data ) {
                $('#multiplicador_porc').val(data);
                refreshTable();
                });

        }
        else
        {
            $('#multiplicador_porc').val('{{multiplicador_porc}}');
        }


    }
    
</script>