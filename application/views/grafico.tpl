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
        <div >
            <h3 class="text-warning">Grafico</h3>
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
            let data = [];
            let j=0; //Indice para cada moneda recibida
            console.log(info);
            if (info.prices)
            {
                $.each(info.prices, function(tickerid, prices) {
                    j++;
                    let dateField = 'date'+j;
                    let priceField = 'price'+j;
                    for (let i=0; i<prices.length ;i++)
                    {
                        //var obj = 
                        data.push({[dateField]: new Date(prices[i].date),
                                   [priceField]: prices[i].price });
                        console.log(tickerid,' ',j,'.',i);
                    }
                    
            
                });
            }
            console.log(data);
            return;

            am4core.ready(function() {

                // Themes begin
                am4core.useTheme(am4themes_material);
                //am4core.useTheme(am4themes_animated);
                // Themes end

                // Create chart instance
                var chart = am4core.create("chartdiv", am4charts.XYChart);

                // Add data
                chart.data = data;

                // Create axes
                var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                dateAxis.renderer.minGridDistance = 50;

                var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

                // Create series
                var series = chart.series.push(new am4charts.LineSeries());
                series.dataFields.valueY = "prices";
                series.dataFields.dateX = "date";
                series.strokeWidth = 2;
                series.minBulletDistance = 10;
                series.tooltipText = "{valueY}";
                series.tooltip.pointerOrientation = "vertical";
                series.tooltip.background.cornerRadius = 20;
                series.tooltip.background.fillOpacity = 0.5;
                series.tooltip.label.padding(12,12,12,12)

                // Add scrollbar
                chart.scrollbarX = new am4charts.XYChartScrollbar();
                chart.scrollbarX.series.push(series);

                // Add cursor
                chart.cursor = new am4charts.XYCursor();
                chart.cursor.xAxis = dateAxis;
                //console.log(chart.cursor.xAxis);
                chart.cursor.snapToSeries = series;

                /*
                function generateChartData() {
                    
                    var chartData = [];
                    var firstDate = new Date();
                    firstDate.setDate(firstDate.getDate() - 1000);
                    var prices = 1200;
                    for (var i = 0; i < 500; i++) {
                        // we create date objects here. In your data, you can have date strings
                        // and then set format of your dates using chart.dataDateFormat property,
                        // however when possible, use date objects, as this will speed up chart rendering.
                        var newDate = new Date(firstDate);
                        newDate.setDate(newDate.getDate() + i);
                        
                        prices += Math.round((Math.random()<0.5?1:-1)*Math.random()*10);

                        chartData.push({
                            date: newDate,
                            prices: prices
                        });
                    }
                

                    console.log(chartData);
                }
                */
                

            }); // end am4core.ready()
        }
    });
}


</script>
