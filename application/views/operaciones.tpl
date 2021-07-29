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
        
        i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
        i2 = setInterval(function () {
            updateProgress += parseInt(100/60);
            $('#updatePB').css('width',updateProgress+'%');
            $('#updatePB').attr('aria-valuenow',updateProgress);
        },1000);

        $('#tickerid').focus();

        $('.typeahead').change(function () {
            readPrecios();
        });
    });

    var colors = [
      '#f67019',
      '#acc236',
      '#166a8f',
      '#00a950',
      '#58595b',
      '#4dc9f6',
      '#8549ba',
      '#f53794',
      '#537bc4',
      ];

    function readPrecios() 
    {
        $('#chartdiv').html('Cargando grafico...');
        $('.tt-menu').dropdown('hide');
        $('#cambiar-moneda').modal('hide');

        updateProgress=0;
        var tckr = $("#tickerid").val();
        var url = `app.CriptoAjax.historico+tickerid=${tckr}&ema=7,14`;
        $.getJSON( url, function( info ) {
            if (info)
            {
                
                $('#last_update').html(`Actualizado <strong>${info.updatedStr}</strong>`);
                var s=0;
                if (info.prices)
                {
                    am4core.ready(function() {

                        // Themes begin
                        am4core.useTheme(am4themes_dark);
                        //am4core.useTheme(am4themes_animated);
                        // Themes end

                        // Create chart instance
                        var chart = am4core.create("chartdiv", am4charts.XYChart);

                        // Create axes
                        var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                            dateAxis.dataFields.category = "datetime";
                        var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                            valueAxis.dataFields.category = "price";
                            valueAxis.title.text = "Precio USDT";
     
                        //
                        chart.cursor = new am4charts.XYCursor();
                        chart.cursor.xAxis = dateAxis;


                        //Adding data
                        $.each(info.prices, function(tickerid, prices) {
                            var series = chart.series.push(new am4charts.LineSeries());
                            series.dataFields.valueY = "value" + s;
                            series.dataFields.dateX = "date";
                            series.name = tickerid;
                            series.tooltipText = "{valueY.value}%";

                            series.tooltip.getFillFromObject = false;
                            series.tooltip.background.fill = am4core.color(colors[s]);
                            series.tooltip.label.fill = am4core.color('#fff');

                            series.strokeWidth = 2; // 3px
                            series.stroke = am4core.color(colors[s]); 
                            
                            var segment = series.segments.template;
                            segment.interactionsEnabled = true;
                            
                            var hoverState = segment.states.create("hover");
                            hoverState.properties.strokeWidth = 1;
                            
                            var dimmed = segment.states.create("dimmed");
                            dimmed.properties.stroke = am4core.color("#dadada");
                            
                            var data = [];
                            for (var i = 1; i < prices.length; i++) {
                                var dataItem = { date: new Date(prices[i].date) };
                                dataItem["value" + s] = prices[i].price;
                                data.push(dataItem);
                            }

                            series.data = data;
                            s++;       

                            var series = chart.series.push(new am4charts.LineSeries());
                            series.dataFields.valueY = "value" + s;
                            series.dataFields.dateX = "date";
                            series.name = 'EMA7-1h';
                            series.tooltipText = "{valueY.value}%";

                            series.tooltip.getFillFromObject = false;
                            series.tooltip.background.fill = am4core.color(colors[s]);
                            series.tooltip.label.fill = am4core.color('#fff');

                            series.strokeWidth = 2; // 3px
                            series.stroke = am4core.color(colors[s]); 
                            
                            var segment = series.segments.template;
                            segment.interactionsEnabled = true;
                            
                            var hoverState = segment.states.create("hover");
                            hoverState.properties.strokeWidth = 1;
                            
                            var dimmed = segment.states.create("dimmed");
                            dimmed.properties.stroke = am4core.color("#dadada");
                            
                            var data = [];
                            for (var i = 1; i < prices.length; i++) {
                                var dataItem = { date: new Date(prices[i].date) };
                                dataItem["value" + s] = prices[i].ema7;
                                data.push(dataItem);
                            }
                             
                            series.data = data;
                            s++;

                            var series = chart.series.push(new am4charts.LineSeries());
                            series.dataFields.valueY = "value" + s;
                            series.dataFields.dateX = "date";
                            series.name = 'EMA14-1h';
                            series.tooltipText = "USD {valueY.value}";

                            series.tooltip.getFillFromObject = false;
                            series.tooltip.background.fill = am4core.color(colors[s]);
                            series.tooltip.label.fill = am4core.color('#fff');

                            series.strokeWidth = 2; // 3px
                            series.stroke = am4core.color(colors[s]); 
                            
                            var segment = series.segments.template;
                            segment.interactionsEnabled = true;
                            
                            var hoverState = segment.states.create("hover");
                            hoverState.properties.strokeWidth = 1;
                            
                            var dimmed = segment.states.create("dimmed");
                            dimmed.properties.stroke = am4core.color("#dadada");
                            
                            var data = [];
                            for (var i = 1; i < prices.length; i++) {
                                var dataItem = { date: new Date(prices[i].date) };
                                dataItem["value" + s] = prices[i].ema14;
                                data.push(dataItem);
                            }
                             
                            series.data = data;
                            s++;
                        });

                        chart.legend = new am4charts.Legend();

                        chart.legend.position = "top";
                        chart.legend.scrollable = false;

                        console.log('Chart Data');
                        console.log(chart.data);

                    }); // end am4core.ready()
                }
            }
        });
    }
</script>
