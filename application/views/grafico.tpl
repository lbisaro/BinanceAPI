<!-- Styles -->
<style>
#chartdiv {
  width: 100%;
  height: 500px;
}
</style>
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
            <div id="last_update" class="text-success"></div>  
            <div class="progress">
              <div id="updatePB" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>    
        </div>
    </div>
    <div class="d-flex justify-content-between">
        <div>
            BUSQUEDA
        </div>
    </div>
    <div style="background:#888">
        <div id="chartdiv">Generando el grafico....</div>
    </div>

</div>

<!-- Resources -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/material.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/de_DE.js"></script>
<script src="https://cdn.amcharts.com/lib/4/geodata/germanyLow.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>

<!-- Chart code -->
<script>

var updateProgress=0;
var i1,i2;
$(document).ready(function() {
    $('#tbl_precios').tablesorter({ sortList: [[3,1]] });
    readPrecios();
    i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
    i2 = setInterval(function () {
        updateProgress += parseInt(100/60);
        $('#updatePB').css('width',updateProgress+'%');
        $('#updatePB').attr('aria-valuenow',updateProgress);
    },1000);
    
});

function readPrecios() 
{
    updateProgress=0;

    $.getJSON( 'app.CriptoAjax.historico+tickerid=BTCUSDT,SLPUSDT', function( info ) {
        if (info)
        {
            $('#last_update').html(`Actualizado <strong>${info.updatedStr}</strong>`);
            var s=0;
            if (info.prices)
            {
                am4core.ready(function() {

                    // Themes begin
                    am4core.useTheme(am4themes_material);
                    //am4core.useTheme(am4themes_animated);
                    // Themes end

                    // Create chart instance
                    var chart = am4core.create("chartdiv", am4charts.XYChart);

                    // Create axes
                    var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                        dateAxis.dataFields.category = "datetime";
                        dateAxis.renderer.labels.template.fill = am4core.color("#eee");
                    var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                        valueAxis.dataFields.category = "percent";
                        valueAxis.title.text = "% respecto al inicio";
                        valueAxis.renderer.labels.template.fill = am4core.color("#eee");

                    //
                    chart.cursor = new am4charts.XYCursor();
                    chart.cursor.xAxis = dateAxis;


                    //Adding data
                    $.each(info.prices, function(tickerid, prices) {
                        var name = tickerid;
                        var series = chart.series.push(new am4charts.LineSeries());
                        series.dataFields.valueY = "value" + s;
                        series.dataFields.dateX = "date";
                        series.name = name;
                        
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
