<!-- Styles -->
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
            <h3>{{symbol}}</h3>
        </div>
    </div>
    <div id="chartdiv"></div>

</div>


<!-- Resources -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script type="text/javascript">

    var updateProgress=0;
    var i1,i2;


    $(document).ready(function() {
        
        //i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
        //i2 = setInterval(function () {
        //    updateProgress += parseInt(100/60);
        //    $('#updatePB').css('width',updateProgress+'%');
        //    $('#updatePB').attr('aria-valuenow',updateProgress);
        //},1000);
        readPrecios();

    });

    var colors = [
      '#000000',//0 //Fecha
      '#888888',//1 //Precio
      '#58A029',//2 //Compra
      '#BF3C0F',//3 //Venta
      '#aaaaff',//4 //Compra Venta
      '#58A029',//5 //Compra Abierta
      '#BF3C0F',//6 //Venta Abierta
      '#88FF88',//7 //high
      '#FF8888',//8 //Low
      '#4dc9f6',//9
      '#f53794',//10
      '#f67019',//11
      '#537bc4',//12
      '#8549ba',//13
      ];

    function readPrecios() 
    {
        $('#chartdiv').html('Cargando grafico...');
        
        updateProgress=0;
        var url = 'app.BotAjax.revisarEstrategia+idoperacion={{idoperacion}}';
        $.getJSON( url, function( info ) {
            if (info)
            {
                var labels = info[0];
                
                am4core.ready(function() 
                {

                    // Themes begin
                    am4core.useTheme(am4themes_animated);
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
                    //series = createSeriesPrecio(1);
                    //series.data = createData(1);
                    //High
                    series = createSeriesPrecio(7);
                    series.data = createData(7);
                    //Low
                    series = createSeriesPrecio(8);
                    series.data = createData(8);
                    //Compra
                    series = createSeriesBullet(2,'compra');
                    series.data = createData(2);
                    //Venta
                    series = createSeriesBullet(3,'venta');
                    series.data = createData(3);
                    //Compra Venta
                    //series = createSeries(4);
                    //series.data = createData(4);
                    //Compra Abierta
                    series = createSeriesPrecio(5);
                    series.data = createData(5);                    
                    //Compra Abierta
                    series = createSeriesPrecio(6);
                    series.data = createData(6);
                    
                    // Add scrollbar
                    var scrollbarX = new am4charts.XYChartScrollbar();
                    scrollbarX.series.push(series);
                    chart.scrollbarX = scrollbarX;

                    series.smoothing = "monotoneY";

                    chart.legend = new am4charts.Legend();

                    chart.legend.position = "top";
                    chart.legend.scrollable = false;

                    function createSeriesPrecio(s)
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

                            if (s==1 || s==7 || s==8)
                            {
                                srs.strokeWidth = 1; // px
                            }
                            else if (s==5 || s==6)
                            {
                                srs.strokeWidth = 3;
                                srs.strokeDasharray = 4;
                                srs.strokeWidth = 1.5; // px
                            }
                            else
                            {
                                srs.strokeWidth = 1.5; // px
                            }

                            srs.stroke = am4core.color(colors[s]); 
                            srs.connect = true; 


                        return srs;
                        
                    }

                    function createSeriesBullet(s,tipo)
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.valueY = "value" + s;
                            srs.dataFields.dateX = "date";
                            srs.name = labels[s];
                            
                            srs.tooltip.getFillFromObject = false;
                            srs.tooltip.background.fill = am4core.color(colors[s]);
                            srs.tooltip.label.fill = am4core.color('#fff');

                            srs.strokeWidth = 10;

                            // Add simple bullet
                            var bullet = srs.bullets.push(new am4charts.Bullet());

                            if (tipo=='compra')
                            {
                                var circle = bullet.createChild(am4core.Circle);
                                circle.width = 4;
                                circle.height = 4;
                                circle.fillOpacity = 1;
                            }
                            else //Venta
                            {
                                var circle = bullet.createChild(am4core.Circle);
                                circle.width = 8;
                                circle.height = 8;
                                circle.fillOpacity = 0.0;
                            }
                            circle.horizontalCenter = "middle";
                            circle.verticalCenter = "middle";

                            //// Add outline to the circle bullet
                            //circle.stroke = am4core.color(colors[s]);
                            //circle.strokeWidth = 1;

                            // Make circle drop shadow by adding a DropShadow filter
                            //var shadow = new am4core.DropShadowFilter();
                            //shadow.dx = 2;
                            //shadow.dy = 2;
                            //circle.filters.push(shadow);

                            // Make chart not mask the bullets
                            chart.maskBullets = true;                            


                            srs.strokeWidth = 0; // px
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
