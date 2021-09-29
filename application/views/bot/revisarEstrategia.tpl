<!-- Styles -->
<style>
body {
    background-color: #454d55;
}
#chartdiv {
  width: 100%;
  height: 500px;
}
</style>
<style>

.typeahead {
    width: 100%;
}
.tt-hint {
    color: #999999;
}
.tt-menu {
    background-color: #FFFFFF;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
    margin-top: 12px;
    padding: 8px 0;
    width: 120px;
}
.tt-suggestion {
    padding: 4px 20px;
    color: #888;
    border-radius: 5px;
    font-size: 1em;
}
.tt-suggestion:hover {
    cursor: pointer;
    background-color: #0097CF;
    color: #ddd;
}
.tt-suggestion:hover .tt-highlight {
    color: #fff;
}
.tt-highlight {
    font-weight: normal;
    color: #0097CF;
}

.tt-suggestion p {
    margin: 0;
}
</style>
<!-- Autocomplete-->


<!-- FUENTE: 
    multiple
    https://www.amcharts.com/demos/multiple-date-axes/?theme=material
    
    https://www.amcharts.com/demos/range-area-chart/?theme=material 
    https://www.amcharts.com/demos/line-different-colors-ups-downs/?theme=material  
    https://www.amcharts.com/demos/highlighting-line-chart-series-on-legend-hover/?theme=material
-->


<div class="container-fluid" id="variacion_del_precio">
    <div class="d-flex justify-content-between">
        <div>
            <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#cambiar-moneda">
              Cambiar Moneda
            </button>
        </div>
        <div>
            <div id="last_update" class="text-success"></div>  
            <div class="progress">
              <div id="updatePB" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>    
        </div>
    </div>
    <div id="chartdiv" class="text-light"></div>

</div>

<div id="cambiar-moneda" class="modal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Cambiar Moneda</h3>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div>
            <form>
                <div class="input-group">
                    <input class="form-control typeahead " id="tickerid" autocomplete="off" spellcheck="false">
                </div>
            </form>
        </div>


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" onclick="readPrecios()">Comparar</button>
      </div>
    </div>
  </div>
</div>

<!-- Resources -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/dark.js"></script>

<!-- Autocomplete-->
<script  type="text/javascript" src="public/scripts/typeahead.bundle.js"></script>

<script type="text/javascript">

    var updateProgress=0;
    var i1,i2;

    $('#cambiar-moneda').modal({
        show: true,
    });

    /** AUTOCOMPLETE  */

    //Tickers ID list
    {{availableTickers}}

    // Constructing the suggestion engine
    var availableTickers = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.whitespace,
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        local: availableTickers
    });

    // Initializing the typeahead
    $('.typeahead').typeahead({
        hint: true,
        highlight: true, /* Enable substring highlighting */
        minLength: 1 /* Specify minimum characters required for showing result */
    },
    {
        name: 'AvailableTickers',
        source: availableTickers
    });
    /** AUTOCOMPLETE -END */

    $(document).ready(function() {
        
        //i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
        //i2 = setInterval(function () {
        //    updateProgress += parseInt(100/60);
        //    $('#updatePB').css('width',updateProgress+'%');
        //    $('#updatePB').attr('aria-valuenow',updateProgress);
        //},1000);

        $('#tickerid').focus();

        $('.typeahead').change(function () {
            readPrecios();
        });
    });

    var colors = [
      '#aaaaaa',//0
      '#aaaaaa',//1
      '#acc236',//2
      '#166a8f',//3
      '#acc236',//4
      '#166a8f',//5
      '#acc236',//6
      '#166a8f',//7
      '#58595b',//8
      '#4dc9f6',//9
      '#f53794',//10
      '#f67019',//11
      '#537bc4',//12
      '#8549ba',//13
      ];

    function readPrecios() 
    {
        $('#chartdiv').html('Cargando grafico...');
        $('.tt-menu').dropdown('hide');
        $('#cambiar-moneda').modal('hide');

        updateProgress=0;
        var tckr = $("#tickerid").val();
        var url = `app.BotAjax.revisarEstrategia+tickerid=${tckr}`;
        $.getJSON( url, function( info ) {
            if (info)
            {
                var labels = info[0];
                console.log(labels);
                
                am4core.ready(function() 
                {

                    // Themes begin
                    am4core.useTheme(am4themes_dark);
                    // Themes end

                    // Create chart instance
                    var chart = am4core.create("chartdiv", am4charts.XYChart);

                    var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                        dateAxis.dataFields.category = "datetime";
                    var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                        valueAxis.dataFields.category = "price";
                        valueAxis.title.text = "Precio USDT";

                    chart.cursor = new am4charts.XYCursor();
                    chart.cursor.xAxis = dateAxis;


                    //Precio
                    series = createSeries(1);
                    series.data = createData(1);
                    
                    //Ema_1H
                    series = createSeries(2);
                    series.data = createData(2);
                    series = createSeries(3);
                    series.data = createData(3);
                    
                    //Ema_15m
                    //series = createSeries(4);
                    //series.data = createData(4);
                    //series = createSeries(5);
                    //series.data = createData(5);
                    
                    //Ema_5m
                    //series = createSeries(6);
                    //series.data = createData(6);
                    //series = createSeries(7);
                    //series.data = createData(7);

                    //Compra
                    //series = createSeries(8);
                    //series.data = createData(8);

                    //Venta
                    //series = createSeries(9);
                    //series.data = createData(9);

                    //CompraVenta
                    series = createSeries(10);
                    series.data = createData(10);

                    //StopLimit
                    series = createSeries(11);
                    series.data = createData(11);

                    // Add scrollbar
                    var scrollbarX = new am4charts.XYChartScrollbar();
                    scrollbarX.series.push(series);
                    chart.scrollbarX = scrollbarX;

                    series.smoothing = "monotoneY";

                    chart.legend = new am4charts.Legend();

                    chart.legend.position = "top";
                    chart.legend.scrollable = false;

                    function createSeries(s)
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.valueY = "value" + s;
                            srs.dataFields.dateX = "date";
                            srs.name = labels[s];
                            if (s==1)
                                srs.tooltipText = "USD {valueY.value}";
                            
                            srs.tooltip.getFillFromObject = false;
                            srs.tooltip.background.fill = am4core.color(colors[s]);
                            srs.tooltip.label.fill = am4core.color('#fff');


                            srs.strokeWidth = (s==10||s==11?2:0.5); // 3px
                            srs.stroke = am4core.color(colors[s]); 
                            srs.connect = false;


                        return srs;
                        
                    }

                    function createData(s)
                    {
                        var d = [];
                        for (var i = 1; i < info.length; i++) {
                            var dataItem = { date: new Date(info[i][0]) };
                            if (info[i][s] != 0)
                            {
                                dataItem["value" + s] = info[i][s];
                                d.push(dataItem);
                            }
                        }
                        return d;
                    }

                });
                
            }
        });

    }

</script>
