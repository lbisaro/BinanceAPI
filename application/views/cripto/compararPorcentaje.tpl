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


<div class="container-fluid" >
    <div class="d-flex justify-content-between">
        <form>
            <div class="container" >
                
                <div class="row">
                    <div class="col">
                        Moneda 1
                    </div>
                    <div class="col">
                        Moneda 2                
                    </div>
                    <div class="col">
                        Base
                    </div>
                    <div class="col">
                        Intervalo
                    </div>
                     <div class="col">
                        Velas
                    </div>
                    <div class="col">
                        
                    </div>
                </div>           
                <div class="row">
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="asset1" >
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="asset2" value="BTC" >
                        </div>                
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="assetQuote" value="USDT" >
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <select class="custom-select custom-select-sm" id="interval">
                                <option value="1m" >1m</option>
                                <option value="3m" >3m</option>
                                <option value="5m" >5m</option>
                                <option value="15m" >15m</option>
                                <option value="30m" >30m</option>
                                <option value="1h" >1h</option>
                                <option value="2h" >2h</option>
                                <option value="4h" >4h</option>
                                <option value="6h" >6h</option>
                                <option value="8h" >8h</option>
                                <option value="12h" >12h</option>
                                <option value="1d" SELECTED >1d</option>
                                <option value="3d" >3d</option>
                                <option value="1w" >1w</option>
                                <option value="1M" >1M</option>
                            </select>
                        </div>
                    </div>
                     <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm" id="limit" value="200" >
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <button type="button" class="btn btn-sm btn-success" onclick="readPrecios()">Comparar</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div id="chartdiv" class="text-light"></div>
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

        $('#asset1').focus();

        $('.force-uppercase').each(function () {
            $(this).change(function () {
                var str = $(this).val().toUpperCase();
                $(this).val(str);
            });
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
        
        updateProgress=0;
        var tckr1 = $("#asset1").val()+$("#assetQuote").val();
        var tckr2 = $("#asset2").val()+$("#assetQuote").val();
        var limit = $("#limit").val();
        var interval = $('#interval option:selected').val();
        var url = `app.CriptoAjax.historico+tickerid=${tckr2},${tckr1}&interval=${interval}&limit=${limit}`;
        $.getJSON( url, function( info ) {
            if (info)
            {
                if (info.error)
                {
                    $('#chartdiv').html('<div class="alert alert-danger">ERROR: '+info.error+'</div>');
                }
                else
                {

                    $('#last_update').html(`Actualizado <strong>${info.updatedStr}</strong>`);
                    var s=0;
                    if (info.prices)
                    {
                        am4core.ready(function() {

                            // Themes begin
                            am4core.useTheme(am4themes_animated);
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
            }
        });
    }
</script>
