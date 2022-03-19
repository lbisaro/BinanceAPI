
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }

    #chartdiv {
      width: 100%;
      height: 500px;
}
</style>

{{divPop}}
{{actionBar}}
{{data}}
{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>
<div class="container-fluid  ">
    <div class="container">
        <table class="table table-borderless">
            <tr>
                <td></td>
                <td style="text-align: right;">
                    <a class="btn btn-info btn-sm" href="app.cripto.editarTicker+id={{tickerid}}">Editar</a>
                </td>
            </tr>
        </table>
    </div>
    <div class="container">
      <div class="form-group">
        <label for="tickerid">Ticker</label>
        <h5 class="data">{{tickerid}}</h5>
        <input type="hidden" class="form-control" value="{{tickerid}}" id="tickerid">
      </div>
      <div class="form-group">
        <label for="hst_min">Rango de precio Historico</label>
        <span> Mminimo </span>
        <span class="data">USD {{hst_min}}</span>
        <span> Maximo </span>
        <span class="data">USD {{hst_max}}</span>
        
    </div>
     
    <div id="chartdiv"></div>

    </div>




   



</div>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script language="javascript" >

    $(document).ready( function () {
        readData();
    });

    //CtrlAjax.sendCtrl("app","cripto","grabarTicker");


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

    function readData() 
    {
        $('#chartdiv').html('Cargando grafico...');
        
        updateProgress=0;
        var url = 'app.CriptoAjax.readTicker+tickerid={{tickerid}}';
        $.getJSON( url, function( info ) {
            if (info)
            {
                var labels = info.labels;

                console.log(info.labels);
                console.log(info.data);
                
                am4core.ready(function() 
                {

                    // Themes begin
                    am4core.useTheme(am4themes_animated);
                    // Themes end

                    // Create chart instance
                    var chart = am4core.create("chartdiv", am4charts.XYChart);

                    chart.dateFormatter.inputDateFormat = "yyyy-MM-dd";

                    var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                        dateAxis.dataFields.category = "date";
                        dateAxis.title.fontWeight = "bold";
                    var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                        valueAxis.dataFields.category = "price";
                        valueAxis.title.text = "Precio USDT";
                        valueAxis.title.fontWeight = "bold";
                        valueAxis.renderer.grid.template.disabled = true;
                    var valueAxis2 = chart.yAxes.push(new am4charts.ValueAxis());
                        valueAxis2.dataFields.category = "ref_perc";
                        valueAxis2.renderer.grid.template.disabled = true;
                        valueAxis2.renderer.labels.template.disabled = true;
                        valueAxis2.renderer.opposite = true;   //Muestra la escala del lado opuesto           

                    chart.cursor = new am4charts.XYCursor();
                    chart.cursor.xAxis = dateAxis;

                    series = createSeriesCandlestick();
                    series = createSeriesPerc();
                    series = createSeriesBBands('bb_h','BB','#00008888');
                    series = createSeriesBBands('bb_m','BB','#88000088');
                    series = createSeriesBBands('bb_l','BB','#00008888');
                    series = createSeriesHistorico('hst_min','Minimo','#BF3C0F');
                    series = createSeriesHistorico('hst_mid','Medio','#888888');
                    series = createSeriesHistorico('hst_max','Maximo','#58A029');
                    series = createSeriesHistorico('hst_ter_t','Tercio Up','#888888');
                    series = createSeriesHistorico('hst_ter_d','Tercio Down','#888888');

                    
                    
                    // Add scrollbar
                    //var scrollbarX = new am4charts.XYChartScrollbar();
                    //scrollbarX.series.push(series);
                    //chart.scrollbarX = scrollbarX;

                    series.smoothing = "monotoneY";

                    chart.legend = new am4charts.Legend();

                    chart.legend.position = "top";
                    chart.legend.scrollable = false;

                    function createSeriesCandlestick()
                    {
                        var srs = chart.series.push(new am4charts.CandlestickSeries());
                        srs.dataFields.dateX = "date";
                        srs.dataFields.valueY = "close";
                        srs.dataFields.openValueY = "open";
                        srs.dataFields.lowValueY = "low";
                        srs.dataFields.highValueY = "high";
                        srs.simplifiedProcessing = false;

                        srs.dropFromOpenState.properties.fill = am4core.color("#880000AA");
                        srs.dropFromOpenState.properties.stroke = am4core.color("#880000AA");

                        srs.riseFromOpenState.properties.fill = am4core.color("#008800AA");
                        srs.riseFromOpenState.properties.stroke = am4core.color("#008800AA");
                        
                        srs.name = 'Precio USD';
                        //srs.tooltipText = "Open:${openValueY.value}\nLow:${lowValueY.value}\nHigh:${highValueY.value}\nClose:${valueY.value}";

                        srs.data = info.data;
                        return srs;                    
                    }

                    function createSeriesHistorico(value,label,color)
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.dateX = "date";
                            srs.dataFields.valueY = value;
                            
                            srs.stroke = am4core.color(color);
                            srs.strokeWidth = 1; // px
                            srs.strokeDasharray = 10;
                            
                            srs.name = label;

                            srs.data = info.data;

                            srs.hiddenInLegend = true;

                        return srs;
                        
                    }

                    function createSeriesBBands(value,label,color)
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.dateX = "date";
                            srs.dataFields.valueY = value;
                            
                            srs.stroke = am4core.color(color);
                            srs.strokeWidth = 0.75; // px
                            //srs.strokeDasharray = 10;
                            
                            srs.name = label;

                            srs.data = info.data;

                            srs.hiddenInLegend = true;

                        return srs;
                        
                    }

                    function createSeriesPerc()
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.dateX = "date";
                            srs.dataFields.valueY = "ref_perc";
                            
                            srs.stroke = am4core.color('#fff');
                            srs.strokeWidth = 0.25; // px
                            
                            srs.tooltip.getFillFromObject = false;
                            srs.tooltip.background.fill = am4core.color('#888');
                            srs.tooltip.label.fill = am4core.color('#fff');

                            srs.tooltipText = "{valueY.value}%";

                            srs.hiddenInLegend = true;
                            
                            srs.name = '% en referencia a la media';
                            srs.data = info.data;
                            srs.yAxis = valueAxis2;

                        return srs;
                        
                    }

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
