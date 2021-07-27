<!-- Styles -->
<style>
#chartdiv {
  width: 100%;
  height: 500px;
}
</style>
<!-- Autocomplete-->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />


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
            <form>
                <div class="input-group">
                  <select class="form-control ticker-slct" id="tickerid1" style="width:150px;">
                  </select>
                  <select class="form-control ticker-slct" id="tickerid2" style="width:150px;">
                  </select>
                </div>
            </form>
        </div>
        <div>
            <div id="last_update" class="text-success"></div>  
            <div class="progress">
              <div id="updatePB" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>    
        </div>
    </div>
    <div id="chartdiv">Generando el grafico....</div>

</div>

<!-- Resources -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/dark.js"></script>
<!-- Autocomplete-->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>

var updateProgress=0;
var i1,i2;

//Tickers ID list
{{availableTickers}}

$(document).ready(function() {
    $('#tbl_precios').tablesorter({ sortList: [[3,1]] });
    
    i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
    i2 = setInterval(function () {
        updateProgress += parseInt(100/60);
        $('#updatePB').css('width',updateProgress+'%');
        $('#updatePB').attr('aria-valuenow',updateProgress);
    },1000);

    $('.ticker-slct').each( function (e) {
        for (var i=0; i<availableTickers.length; i++)
        {
            var selected = false;
            if ($(this).attr('id')=='tickerid1' && availableTickers[i]=='BTCUSDT')
                selected = true;
            $(this).append(`<option ${(selected?'SELECTED':'')} value="${availableTickers[i]}">${availableTickers[i]}</option>`);

        }
        $(this).select2({
              theme: "classic"
            });
        $(this).change( function () { readPrecios(); } );
    });

    readPrecios();
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
    updateProgress=0;
    var tckr1 = $("#tickerid1 option:selected").val();
    var tckr2 = $("#tickerid2 option:selected").val();
    var url = `app.CriptoAjax.historico+tickerid=${tckr1},${tckr2}`;
    console.log(url);
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
                        valueAxis.dataFields.category = "percent";
                        valueAxis.title.text = "% respecto al inicio";
 
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
                            dataItem["value" + s] = prices[i].perc;
                            data.push(dataItem);
                        }
                        
                        series.data = data;
                        s++;       
                    });

                    chart.legend = new am4charts.Legend();

                    chart.legend.position = "top";
                    chart.legend.scrollable = false;

                    

                }); // end am4core.ready()
            }

            

           


                


                
                

            
        }
    });
}


</script>
