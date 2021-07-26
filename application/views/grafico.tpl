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
    <div style="background:#fff">
        
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
            let data = [];
            let seriesLabels = [];
            let j=1; //Indice para cada moneda recibida
            if (info.prices)
            {
                $.each(info.prices, function(tickerid, prices) {
                    let dateField = 'date'+j;
                    let priceField = 'price'+j;
                    seriesLabels.push(tickerid);
                    for (let i=0; i<prices.length ;i++)
                    {
                        //var obj = 
                        data.push({[dateField]: new Date(prices[i].date),
                                   [priceField]: prices[i].price });
                    }
                    j++;
                });
            }

            function customizeGrip(grip) {
                 // This is empty for now
            }


            am4core.ready(function() {

                // Themes begin
                am4core.useTheme(am4themes_material);
                //am4core.useTheme(am4themes_animated);
                // Themes end

                // Create chart instance
                var chart = am4core.create("chartdiv", am4charts.XYChart);

                // Add data
                chart.data = data;

                var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                dateAxis.renderer.grid.template.location = 0;
                dateAxis.renderer.labels.template.fill = am4core.color("#e59165");

                var dateAxis2 = chart.xAxes.push(new am4charts.DateAxis());
                dateAxis2.renderer.grid.template.location = 0;
                dateAxis2.renderer.labels.template.fill = am4core.color("#dfcc64");

                var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis.tooltip.disabled = true;
                valueAxis.renderer.labels.template.fill = am4core.color("#e59165");

                valueAxis.renderer.minWidth = 60;

                var valueAxis2 = chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis2.tooltip.disabled = true;
                valueAxis2.renderer.labels.template.fill = am4core.color("#dfcc64");
                valueAxis2.renderer.minWidth = 60;
                valueAxis2.syncWithAxis = valueAxis;

                var series = chart.series.push(new am4charts.LineSeries());
                series.name = seriesLabels[0];
                series.dataFields.dateX = "date1";
                series.dataFields.valueY = "price1";
                series.tooltipText = "{valueY.value}";
                series.fill = am4core.color("#e59165");
                series.stroke = am4core.color("#e59165");
                //series.strokeWidth = 3;

                var series2 = chart.series.push(new am4charts.LineSeries());
                series2.name = seriesLabels[1];
                series2.dataFields.dateX = "date2";
                series2.dataFields.valueY = "price2";
                series2.yAxis = valueAxis2;
                series2.xAxis = dateAxis2;
                series2.tooltipText = "{valueY.value}";
                series2.fill = am4core.color("#dfcc64");
                series2.stroke = am4core.color("#dfcc64");
                //series2.strokeWidth = 3;

                chart.cursor = new am4charts.XYCursor();
                chart.cursor.xAxis = dateAxis2;

                var scrollbarX = new am4charts.XYChartScrollbar();
                scrollbarX.series.push(series);
                chart.scrollbarX = scrollbarX;

                chart.legend = new am4charts.Legend();
                chart.legend.parent = chart.plotContainer;
                chart.legend.zIndex = 100;

                valueAxis2.renderer.grid.template.strokeOpacity = 0.07;
                dateAxis2.renderer.grid.template.strokeOpacity = 0.07;
                dateAxis.renderer.grid.template.strokeOpacity = 0.07;
                valueAxis.renderer.grid.template.strokeOpacity = 0.07;

                //https://www.amcharts.com/docs/v4/tutorials/customizing-chart-scrollbar/
                chart.scrollbarX.background.fill = am4core.color("#000");
                chart.scrollbarX.background.fillOpacity = 0.05; 
                

            }); // end am4core.ready()
        }
    });
}


</script>
