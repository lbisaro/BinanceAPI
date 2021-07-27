<!-- Styles -->
<style>
#chartdiv {
  width: 100%;
  height: 500px;
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
            <form>
                <div class="input-group">
                  <input class="form-control readonly" READONLY id="tickerid1" value="BTCUSDT" style="width: 100px;">
                  <input class="form-control" id="tickerid2" placeholder="Comparar con" style="width: 130px;">
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
    <div id="chartdiv">Seleccionar las monedas a comparar....</div>

</div>

<!-- Resources -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/dark.js"></script>
<!-- Autocomplete-->

<script src="https://cdn.jsdelivr.net/npm/bootstrap-4-autocomplete/dist/bootstrap-4-autocomplete.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script>

var updateProgress=0;
var i1,i2;

/** AUTOCOMPLETE  */

//Tickers ID list
{{availableTickers}}

$('#tickerid2').autocomplete({
  source: availableTickers,
  onSelectItem: readPrecios,
  highlightClass: 'text-danger',
  treshold: 2,
});

/** AUTOCOMPLETE -END */

$(document).ready(function() {
    $('#tbl_precios').tablesorter({ sortList: [[3,1]] });
    
    i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
    i2 = setInterval(function () {
        updateProgress += parseInt(100/60);
        $('#updatePB').css('width',updateProgress+'%');
        $('#updatePB').attr('aria-valuenow',updateProgress);
    },1000);

    $('#tickerid2').focus();
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
    $('#tickerid1').dropdown('hide');
    $('#tickerid2').dropdown('hide');
    updateProgress=0;
    var tckr1 = $("#tickerid1").val();
    var tckr2 = $("#tickerid2").val();
    var url = `app.CriptoAjax.historico+tickerid=${tckr2},${tckr1}`;
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
