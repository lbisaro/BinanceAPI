

<!-- Resources -->
<!-- Demo https://www.amcharts.com/demos/candlestick-chart/ -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<style type="text/css">
    table tfoot {
        font-weight: bolder;
    }

    #chartdiv {
      width: 100%;
      height: 300px;
    }

    .small_usdt {
        color: #5555aa;
    }
    .small_usdt_join {
        font-weight: bolder;
        color: #5555aa;
    }
</style>

<div class="container">
{{alertas}}
{{data}}
<div class="container">
    <h4 class="info">
        Billetera: <b>USD {{totalUSD}}</b>
    </h4>
</div>
</div>
<div class="container">
  <ul class="nav nav-tabs">
    <li class="nav-item" id="tab_compras">
      <a class="nav-link" href="#" onclick="activarTab('compras',true)">Estado de Compras</a>
    </li>
    <li class="nav-item" id="tab_capitalDisponible">
      <a class="nav-link" href="#" onclick="activarTab('capitalDisponible',true)">Gestion del capital</a>
    </li>
    <li class="nav-item" id="tab_billetera">
      <a class="nav-link" href="#" onclick="activarTab('billetera',true)">Disposicion de la Billetera</a>
    </li>
    <!--
    <li class="nav-item">
      <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
    </li>
    -->
  </ul>
</div>
<div class="container tabs" id="compras">
    {{tab_compras}}
</div>
<div class="container tabs" id="capitalDisponible">
    {{tab_capitalDisponible}}
    <div class="container">
        {{tab_capitalDisponible_analisis}}
    </div>
</div>
<div class="container tabs" id="billetera">
    <div class="container-fluid" id="variacion_del_precio">
        <div class="input-group mb-3">
          <div class="input-group-prepend">
            <label class="input-group-text" for="periodo">Periodo</label>
          </div>
          <select class="custom-select" id="periodo" onchange="cambiarPeriodo()">
            {{periodo_opt}}
          </select>
        </div>
        <div id="chartdiv"></div>

    </div>
    {{tab_billetera}}
</div>

<script type="text/javascript">
    
    $(document).ready( function () {
        activarTab('{{activeTab}}',false);
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });

        //Grafica 
        readWallet();
    });

    function cambiarPeriodo()
    {
        goTo('app.Cripto.estadoDeCuenta+periodo='+$('#periodo').val());
    }

    function activarTab(id,update)
    {
        $('.nav-tabs a').removeClass('active');
        $('.tabs').hide();
        $('#'+id).show();
        $('#tab_'+id+' a').addClass('active');
        if (update)
            CtrlAjax.sendCtrl("usr","usr","setConfig","set=cripto.estadoDeCuenta.tab&str="+id);
    }


// Grafica diaria


    function readWallet() 
    {
        $('#chartdiv').html('');
        
        updateProgress=0;
        var url = 'app.CriptoAjax.readWallet+periodo='+$('#periodo').val();
        $.getJSON( url, function( data ) {
            if (data)
            {
                for (var i = 0; i < data.length; i++)
                {
                    var datetime = new Date(data[i].date);
                    datetime.setDate(datetime.getDate() +1);
                    data[i].date = datetime.getTime()+1000;
                    data[i].value = data[i].value*1;
                    data[i].open = data[i].open*1;
                    data[i].high = data[i].high*1;
                    data[i].low = data[i].low*1;
                }
                
                am5.ready(function() 
                {

                    // Create root element
                    // https://www.amcharts.com/docs/v5/getting-started/#Root_element
                    var root = am5.Root.new("chartdiv");

                    // Set themes
                    // https://www.amcharts.com/docs/v5/concepts/themes/
                    root.setThemes([am5themes_Animated.new(root)]);

                    // Create chart instance
                    var chart = root.container.children.push(
                      am5xy.XYChart.new(root, {
                        focusable: true,
                        panX: true,
                        panY: true,
                        wheelX: "panX",
                        wheelY: "zoomX"
                      })
                    );

                    // Create axes
                    // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/
                    var xAxis = chart.xAxes.push(
                      am5xy.DateAxis.new(root, {
                        groupData: true,
                        maxDeviation:0.5,
                        baseInterval: { timeUnit: "day", count: 1 },
                        renderer: am5xy.AxisRendererX.new(root, {pan:"zoom"}),
                        tooltip: am5.Tooltip.new(root, {})
                      })
                    );

                    var yAxis = chart.yAxes.push(
                      am5xy.ValueAxis.new(root, {
                        maxDeviation:1,
                        renderer: am5xy.AxisRendererY.new(root, {pan:"zoom"}),
                        tooltip: am5.Tooltip.new(root, {})
                      })

                    );

                    var color = root.interfaceColors.get("background");

                    

                    // Add series
                    // https://www.amcharts.com/docs/v5/charts/xy-chart/series/
                    var series = chart.series.push(
                      am5xy.CandlestickSeries.new(root, {
                        fill: color,
                        calculateAggregates: true,
                        stroke: color,
                        name: "USD",
                        xAxis: xAxis,
                        yAxis: yAxis,
                        valueYField: "value",
                        openValueYField: "open",
                        lowValueYField: "low",
                        highValueYField: "high",
                        valueXField: "date",
                        lowValueYGrouped: "low",
                        highValueYGrouped: "high",
                        openValueYGrouped: "open",
                        valueYGrouped: "close",
                        legendValueText:
                          "Apertura: {openValueY} Min: {lowValueY} Max: {highValueY} Cierre: {valueY}",
                        legendRangeValueText: "{valueYClose}",
                      })
                    );
                    
                    // Add cursor
                    // https://www.amcharts.com/docs/v5/charts/xy-chart/cursor/
                    var cursor = chart.set(
                      "cursor",
                      am5xy.XYCursor.new(root, {
                        xAxis: xAxis
                      })
                    );
                    cursor.lineY.set("visible", false);

                    // Stack axes vertically
                    // https://www.amcharts.com/docs/v5/charts/xy-chart/axes/#Stacked_axes
                    chart.leftAxesContainer.set("layout", root.verticalLayout);


                    // Add legend
                    // https://www.amcharts.com/docs/v5/charts/xy-chart/legend-xy-series/
                    var legend = yAxis.axisHeader.children.push(am5.Legend.new(root, {}));

                    legend.data.push(series);

                    legend.markers.template.setAll({
                      width: 10
                    });

                    legend.markerRectangles.template.setAll({
                      cornerRadiusTR: 0,
                      cornerRadiusBR: 0,
                      cornerRadiusTL: 0,
                      cornerRadiusBL: 0
                    });

                    // set data
                    series.data.setAll(data);
                    
                    // Make stuff animate on load
                    // https://www.amcharts.com/docs/v5/concepts/animations/
                    series.appear(1000);
                    chart.appear(1000, 100);                    

                });
                
            }
        });

    }

</script>
