
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }

    #chartdiv {
      width: 95%;
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
                <td>
                    <div class="form-group">
                        <h5 class="data">{{tickerid}}</h5>
                        <input type="hidden" class="form-control" value="{{tickerid}}" id="tickerid">
                      </div>
                </td>
                <td style="text-align: right;">
                    <a class="btn btn-info btn-sm" href="app.cripto.editarTicker+id={{tickerid}}">Editar</a>
                </td>
            </tr>
        </table>
    </div>

    <div class="container">
      <ul class="nav nav-tabs">
        <li class="nav-item" id="tab_parametrosActuales">
          <a class="nav-link" href="#" onclick="activarTab('parametrosActuales')">Parametros</a>
        </li>
        <li class="nav-item" id="tab_chartdiv">
          <a class="nav-link" href="#" onclick="activarTab('chartdiv')">Grafica</a>
        </li>
      </ul>
    </div>

    <div id="parametrosActuales" class="container tabs" >
        <div class="row">
            <div class="col4">
              <h5>Configuracion</h5>
              <div class="form-group">
                <label for="inicio_usd">Capital</label>
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <div class="input-group-text">USD</div>
                    </div>
                    <input type="text" class="form-control" id="capital_usd" value="{{capital_usd}}" placeholder="0.000">
                </div>
              </div>

              <div class="form-group">
                <label for="inicio_usd">Compra inicial</label>
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <div class="input-group-text">USD</div>
                    </div>
                    <input type="text" class="form-control" id="inicio_usd" value="{{inicio_usd}}" placeholder="0.000">
                </div>
              </div>

              <div class="form-group" >
                <button onclick="obtenerParametros()" class="btn btn-success" >Obtener parametros</button>
              </div>

            </div>
        <div class="col">
          <h5>Referencia sobre la operacion</h5>
          <div class="container" id="oprTable"></div>
        </div>
    </div>

    
    <div id="chartdiv" class="container tabs" ></div>

    </div>




   



</div>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script language="javascript" >

    $(document).ready( function () {
        activarTab('parametrosActuales');
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
    });

    function activarTab(id)
    {
        $('.nav-tabs a').removeClass('active');
        $('.tabs').hide();
        $('#'+id).show();
        $('#tab_'+id+' a').addClass('active');

        if (id == 'chartdiv')
            readData();
    }

    function obtenerParametros()
    {
        CtrlAjax.sendCtrl("app","cripto","obtenerParametrosActuales");
    }
    
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
                console.log(info);
                var labels = info.labels;
                
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
                        valueAxis2.cursorTooltipEnabled = false;

                    chart.cursor = new am4charts.XYCursor();
                    chart.cursor.xAxis = dateAxis;

                    series = createSeriesCandlestick();
                    
                    // Add scrollbar
                    //var scrollbarX = new am4charts.XYChartScrollbar();
                    //scrollbarX.series.push(series);
                    //chart.scrollbarX = scrollbarX;

                    series = createSeriesPerc();
                    //series = createSeriesBBands('bb_h','BB','#00008888');
                    //series = createSeriesBBands('bb_m','BB','#88000088');
                    //series = createSeriesBBands('bb_l','BB','#00008888');
                    series = createSeriesHistorico('hst_min','Minimo','#BF3C0F');
                    series = createSeriesHistorico('hst_mid','Medio','#888888');
                    series = createSeriesHistorico('hst_max','Maximo','#58A029');
                    series = createSeriesHistorico('hst_ter_t','Tercio Up','#55AA55');
                    series = createSeriesHistorico('hst_ter_d','Tercio Down','#AA5555');
                    series = createSeriesPalancas('pal1','P#1','#ff0000');
                    series = createSeriesPalancas('pal2','P#2','#ff0000');
                    series = createSeriesPalancas('pal3','P#3','#ff0000');
                    series = createSeriesPalancas('pal4','P#4','#ff0000');
                    series = createSeriesPalancas('pal5','P#5','#ff0000');

                    
                    //Botones para zoom vertical
                    var buttonContainer = chart.plotContainer.createChild(am4core.Container);
                    buttonContainer.shouldClone = false;
                    buttonContainer.align = "left";
                    buttonContainer.valign = "top";
                    buttonContainer.zIndex = Number.MAX_SAFE_INTEGER;
                    buttonContainer.marginTop = 5;
                    buttonContainer.marginRight = 5;
                    buttonContainer.layout = "vertical";

                    var zoomInButton = buttonContainer.createChild(am4core.Button);
                    zoomInButton.label.text = "Zoom +";
                    zoomInButton.events.on("hit", function(ev) {
                      var diff = valueAxis.maxZoomed - valueAxis.minZoomed;
                      var delta = diff * 0.2;
                      valueAxis.zoomToValues(valueAxis.minZoomed, valueAxis.maxZoomed - delta);
                    console.log(valueAxis);
                    });

                    var zoomOutButton = buttonContainer.createChild(am4core.Button);
                    zoomOutButton.label.text = "Zoom -";
                    zoomOutButton.events.on("hit", function(ev) {
                      var diff = valueAxis.maxZoomed - valueAxis.minZoomed;
                      var delta = diff * 0.2;
                      valueAxis.zoomToValues(valueAxis.minZoomed, valueAxis.maxZoomed + delta);
                    });


                    series.smoothing = "monotoneY";

                    chart.legend = new am4charts.Legend();

                    chart.legend.position = "bottom";
                    chart.legend.scrollable = false;

                    function createSeriesCandlestick()
                    {
                        var srs = chart.series.push(new am4charts.CandlestickSeries());
                        srs.dataFields.dateX = "date";
                        srs.dataFields.valueY = "close";
                        srs.dataFields.openValueY = "open";
                        srs.dataFields.lowValueY = "low";
                        srs.dataFields.highValueY = "high";
                        srs.simplifiedProcessing = true;

                        srs.dropFromOpenState.properties.fill = am4core.color("#880000AA");
                        srs.dropFromOpenState.properties.stroke = am4core.color("#880000AA");

                        srs.riseFromOpenState.properties.fill = am4core.color("#008800AA");
                        srs.riseFromOpenState.properties.stroke = am4core.color("#008800AA");
                        
                        srs.name = info.tickerid+' ('+info.interval+')';

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
                            if (value == 'hst_min' || value == 'hst_max' )
                            {
                                srs.strokeWidth = 1; // px
                                srs.strokeDasharray = 3;
                            }
                            else
                            {
                                srs.strokeWidth = 1; // px
                                srs.strokeDasharray = 3;
                            }

                            
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

                    function createSeriesPalancas(value,label,color)
                    {
                        var srs = chart.series.push(new am4charts.LineSeries());
                            srs.dataFields.dateX = "date";
                            srs.dataFields.valueY = value;
                            
                            srs.stroke = am4core.color(color);
                            srs.strokeWidth = 0.5; // px
                            
                            srs.tooltipText = label;
                            srs.tooltip.getFillFromObject = false;
                            srs.tooltip.pointerOrientation = 'left';
                            srs.tooltip.label.fontSize = 8;
                            
                            srs.tooltip.background.fill = am4core.color('#b44');
                            srs.tooltip.label.fill = am4core.color('#ddd');
                            
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
                            srs.tooltip.background.fill = am4core.color('#44b');
                            srs.tooltip.label.fill = am4core.color('#ddd');
                            srs.tooltip.pointerOrientation = 'rigth';
                            srs.tooltip.label.fontSize = 9;

                            srs.tooltipText = "{valueY.value}%";

                            srs.hiddenInLegend = true;
                            
                            srs.name = '% en referencia al minimo historico';
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
