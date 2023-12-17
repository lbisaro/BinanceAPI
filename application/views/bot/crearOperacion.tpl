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
                <div class="input-group-text quoteAsset">USD</div>
            </div>
            <input type="text" class="form-control" id="capital_usd" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <label for="inicio_usd">Compra inicial</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text quoteAsset" >USD</div>
            </div>
            <input type="text" class="form-control" id="inicio_usd" onchange="refreshTable()" placeholder="0.000">
        </div>
      </div>

      <div class="form-group">
        <div class="input-group mb-2">
            <select id="destino_profit" class="form-control form-control-sm">
              <option value="0" SELECTED>Obtener ganancias en Quote</option>
              <!--
              <option value="1">Obtener ganancias en Base</option>
              -->
            </select>
        </div>
      </div>

      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Compras</label>
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
          <!-- <div id="check_MPAuto" class="form-group form-check menu-admin">
            <input type="checkbox" data-toggle="toggle" data-on="Automatico" data-off="Auto OFF" data-size="sm"class="form-check-input" id="multiplicador_porc_auto" onchange="refreshTable();getMPAuto();" >
          </div> -->
        </div>
      </div>

      <div class="form-group">
        <label for="porc_venta_up">Porcentaje de venta inicial/palanca</label>
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
        <label for="stop_loss">Stop-Loss</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="stop_loss"  onchange="refreshTable()" placeholder="Recomendado 2.00">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>
      <div class="form-group">
        <label for="max_op_perdida">Maximo de operaciones consecutivas a perdida</label>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="max_op_perdida" placeholder="Recomendado 3">
          <div class="input-group-append">
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>


      <div class="form-group">
        <label for="auto_restart">Iniciar compra al grabar</label>
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
        <h4 class="text-success">{{strTipoOp}}</h4>
        <div class="container" id="oprTable"></div>
        <div id="chartContainer" style="height: 300px; width: 100%;"></div>
      </div>

    </div>
  </div>

</div>
<input type="hidden" name="tipo" id="tipo" value="{{tipo}}">

<script src="https://cdn.canvasjs.com/jquery.canvasjs.min.js"></script>

<script type="text/javascript">
    var quoteAsset = 'USD';
    var symbolDecs = 2;
    var qtyDecsPrice = 2;
    var symbolPrice = 10.0;

    var aCompras = [];
    var aVentas = [];
    var aStopLoss = [];
    var yMin = 0;

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
                    $('.quoteAsset').html(data.quoteAsset);
                    baseAsset = data.baseAsset;
                    quoteAsset = data.quoteAsset;
                    symbolDecs = data.qtyDecs;
                    qtyDecsPrice = data.qtyDecsPrice;
                    symbolPrice = parseFloat(data.price);
                    

                    $('#destino_profit option[value="0"]').html('Obtener ganancias en '+quoteAsset);
                    $('#destino_profit option[value="1"]').html('Obtener ganancias en '+baseAsset);

                    if (tipo != 1 && data.baseAsset == 'BNB')
                    {
                        alert('No es posible operar BNB dado que esa moneda se utiliza para la gestion de comisiones.');
                        return false;                        
                    }
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
        aCompras = [];
        aVentas = [];
        aStopLoss = [];
        yMin = 0;

        var capital_usd = $('#capital_usd').val();
        var inicio_usd = $('#inicio_usd').val();
        var m_compra = $('#multiplicador_compra').val();
        var m_porc = $('#multiplicador_porc').val();
        var m_porc_inc = ($('#multiplicador_porc_inc ').is(':checked')?1:0);
        var m_porc_auto = ($('#multiplicador_porc_auto ').is(':checked')?1:0);
        var sl_perc = $('#stop_loss').val();
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
                        <th>Precio de Compra</th>
                        <th>% Sobre ultima compra </th>
                        <th>% Sobre compra Inicial</th>
                        <th>Compra {{quoteAsset}}</th>
                        <th>Total Compra</th>
                        <th>Precio de Venta</th>
                        <th>Precio de Stop-Loss</th>
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
            ventaPorc = $('#porc_venta_up').val()/100;

            sl_usd_to_loss = capital_usd*(sl_perc/100); 

            var i=1;
            tot_units = 0;
            precioVenta = precio * (1+ventaPorc);
            qtyVenta = (totalCompra*precio)/precioVenta;
            tot_units = tot_units + (compraUsd/precio);
            while (totalCompra<=capital_usd)
            {
                sl_usd = totalCompra-sl_usd_to_loss;
                sl_price = sl_usd/tot_units;

                $sl_class = '';
                if (sl_price > precio)
                    $sl_class = 'text-danger ';

                table = table + '<tr>';
                table = table + '<td>#'+i+'</td>';
                table = table + '<td>'+format_number(precio,qtyDecsPrice)+'</td>';
                table = table + '<td class="text-danger">'+(psuc!=0?'-':'')+format_number(psuc,2)+'%</td>';
                table = table + '<td class="text-danger">'+format_number(psci,2)+'%</td>';
                table = table + '<td>'+format_number(compraUsd,qtyDecsPrice)+'</td>';
                table = table + '<td>'+format_number(totalCompra,qtyDecsPrice)+'</td>';
                table = table + '<td class="text-success">'+format_number(precioVenta,qtyDecsPrice)+'</td>';
                if (sl_perc>0 && sl_price>0)
                    table = table + '<td class="'+$sl_class+'">' +format_number(sl_price,qtyDecsPrice)+'</td>';
                else
                    table = table + '<td>&nbsp;</td>';
                table = table + '</tr>';

                aCompras.push({x: i, y: parseFloat(format_number(precio,qtyDecsPrice))});
                aVentas.push({x: i, y: parseFloat(format_number(precioVenta,qtyDecsPrice))});
                if (sl_perc>0 && sl_price > 0)
                    aStopLoss.push({x: i, y: parseFloat(format_number(sl_price,qtyDecsPrice))});
                

                //Calcular proxima compra
                if (m_porc_inc==1)
                    psuc = parseFloat(m_porc)*(i);
                else
                    psuc = parseFloat(m_porc);

                precio = (parseFloat(precio)*(parseFloat(1-(psuc/100))));
                
                psci = ((parseFloat(precio)/parseFloat(symbolPrice))-1)*100;

                compraUsd = parseFloat(compraUsd)*parseFloat(m_compra);

                tot_units = tot_units + (compraUsd/precio);

                totalCompra = parseFloat(totalCompra) + parseFloat(compraUsd);

                ventaPorc = $('#porc_venta_down').val()/100;
                usdVenta  = totalCompra * (1+ventaPorc);
                precioVenta = toDec(usdVenta/tot_units,qtyDecsPrice);

                yMin = precioVenta*0.95;

                i++;
            }
            
            table = table + `
                </tbody>
            </table>`;
        }
        
        $('#oprTable').html(table);  
        $('#oprTable tbody td').css('padding','0.25em 0.75em');
        $('#oprTable thead th').css('text-align','right');
        $('#oprTable tbody td').css('text-align','right');
        makeGraph();      
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
        $('#capital_usd').val(1000);
        $('#inicio_usd').val(100);
        $('#multiplicador_compra').val(1.75);
        $('#multiplicador_porc').val(2.75);
        $('#porc_venta_up').val(1.75);
        $('#porc_venta_down').val(2.00);
        $('#symbol').val('MATICUSDT');
        validSymbol();
    }



    function makeGraph(graph_data)
    {
        var chart = new CanvasJS.Chart("chartContainer", {
            axisY:{ 
              title: "Precio",
              minimum: yMin,
            },
            axisX:{
              title: "Compra #",
              interval: 1,
            },
            legend:{
                cursor:"pointer",
                verticalAlign: "top",
                horizontalAlign: "right",
                dockInsidePlotArea: true
            },
            data: [{
              name: "Compra",
              showInLegend: true,
              color: "#888888",
              type: "line",
              markerSize: 5,
              dataPoints: aCompras
            },
            {
              name: "Venta",
              showInLegend: true,
              color: "#80B080",
              type: "line",
              markerSize: 5,
              dataPoints: aVentas
            },
            {
              name: "Stop-Loss",
              showInLegend: true,
              color: "#F08080",
              type: "line",
              markerSize: 5,
              dataPoints: aStopLoss
            }]
        });
        chart.render();

    }

    
</script>