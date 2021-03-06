<style type="text/css">
  .data {
    font-weight: bolder;
  }
</style>
<div class="container">

  <div class="row">
    <div class="col-4">
      <h5>Configuracion de la operacion</h5>
      <div class="form-group">
        <label for="symbol">Moneda</label>
        <input type="text" class="form-control" id="symbol" onchange="validSymbol()" placeholder="xxxUSDT">
      </div>

      <div class="form-group">
        <label for="inicio_usd">Capital</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text baseAsset"></div>
            </div>
            <input type="text" class="form-control" id="capital_usd" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="inicio_usd">Venta inicial</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text baseAsset" ></div>
            </div>
            <input type="text" class="form-control" id="inicio_usd" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <div class="input-group mb-2">
            <select id="destino_profit" class="form-control form-control-sm">
              <option value="0">Obtener ganancias en Quote</option>
              <option value="1" SELECTED>Obtener ganancias en Base</option>
            </select>
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Ventas</label>
        <input type="text" class="form-control" id="multiplicador_compra"  onchange="refreshTable()" placeholder="Recomendado 1.05 a 2.00">
      </div>
      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="multiplicador_porc"  onchange="refreshTable()" placeholder="Recomendado 2.70 a 4.50">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
        <div class="input-group mb-2">
          <div class="form-group form-check">
            <input type="checkbox" data-toggle="toggle" data-on="Incremental" data-off="Lineal" data-size="sm"class="form-check-input" CHECKED id="multiplicador_porc_inc" onchange="refreshTable()" >
          </div>
          <!--
          <div id="check_MPAuto" class="form-group form-check menu-admin">
            <input type="checkbox" data-toggle="toggle" data-on="Automatico" data-off="Auto OFF" data-size="sm"class="form-check-input" id="multiplicador_porc_auto" onchange="refreshTable();getMPAuto();" >
          </div>
            -->
        </div>
      </div>

      <div class="form-group">
        <label for="porc_venta_up">Porcentaje de compra inicial/palanca</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="porc_venta_up"  onchange="refreshTable()" placeholder="Recomendado 1.15 a 5.00">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="porc_venta_down"  onchange="refreshTable()" placeholder="Recomendado 1.15 a 5.00">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="auto_restart">Iniciar venta al grabar</label>
        <div class="input-group mb-2">
          <select id="auto_restart" class="form-control" >
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
        <h5>Referencia sobre la operacion</h5>
        <h4 class="text-danger">{{strTipoOp}}</h4>
        <div class="container" id="oprTable"></div>
      </div>

    </div>
  </div>

</div>
<input type="hidden" name="tipo" id="tipo" value="{{tipo}}">

<script type="text/javascript">
    var baseAsset = 'USD';
    var symbolDecs = 2;
    var qtyDecsPrice = 2;
    var symbolPrice = 10.0;
    
    $(document).ready( function () {
        $('#btnAddOperacion').hide();

        if (SERVER_ENTORNO == 'Test')
            setDefaultValues();

        $('#check_MPAuto').hide();
    });

    function validSymbol()
    {
        var tipo = $('#tipo').val()
        if ($('#symbol').val())
        {
            $.getJSON('app.BotAjax.symbolData+symbol='+$('#symbol').val(), function( data ) {
                if (data.symbol)
                {
                    $('.baseAsset').html(data.baseAsset);
                    baseAsset = data.baseAsset;
                    quoteAsset = data.quoteAsset;
                    symbolDecs = data.qtyDecs;
                    qtyDecsPrice = data.qtyDecsPrice;
                    symbolPrice = parseFloat(data.price);
                    
                    $('#destino_profit option[value="0"]').html('Obtener ganancias en '+quoteAsset);
                    $('#destino_profit option[value="1"]').html('Obtener ganancias en '+baseAsset);

                    $('#symbol').val(data.symbol);
                    $('#symbol').addClass('text-success');
                    $('#btnAddOperacion').show();
                    if (data.show_check_MPAuto)
                    {
                        $('#check_MPAuto').show();
                    }
                    else
                    {
                        $('#check_MPAuto').hide();
                        $('#multiplicador_porc_auto').attr('checked',false);
                    }

                    refreshTable();
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
        var auto_restart = $('#auto_restart option:selected').val();
        console.log(auto_restart);
        if (auto_restart=='0' || (auto_restart=='1' && confirm('Desea crear la operacion con una compra inicial a precio MARKET?')))
        {
            $('#btnAddOperacion').hide();
            CtrlAjax.sendCtrl("app","bot","crearOperacion");
            setTimeout(function () {$('#btnAddOperacion').show();},2000);
        }
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
                        <th>Venta <span class="baseAsset">`+baseAsset+`</span></th>
                        <th>Total Venta</th>
                        <th>Precio de compra</th>
                    </tr>
                </thead>
                <tbody>
                `;
            
            
            
            precio = format_number(symbolPrice,qtyDecsPrice);
            psuc = 0;
            psci = 0;
            compraUsd = inicio_usd*1;
            totalCompra = compraUsd;
            ventaPorc = $('#porc_venta_up').val()/100;

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


    function setDefaultValues()
    {
        $('#capital_usd').val(800);
        $('#inicio_usd').val(100);
        $('#multiplicador_compra').val(2.1);
        $('#multiplicador_porc').val(2.5);
        $('#porc_venta_up').val(1.75);
        $('#porc_venta_down').val(2.00);
        $('#symbol').val('OCEANBTC');
        validSymbol();
    }

    
</script>