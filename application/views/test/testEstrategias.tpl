<style>
	#chartdiv {
	  width: 100%;
	  height: 500px;
	}
   .data {
		font-weight: bolder;
		color: #555;
	}

    .mapCompras {
        margin-right: 25px;
        display: inline-block;
        border: 1px solid #ccc;
        border-width: 0 0 1px 0;
    }
</style>
<div class="container" id="data">
    {{data}}
</div>
<div class="container">
  <div class="row">
		<div class="col">

          <div class="form-group">
            <div class="form-group">
                <label for="estrategia">Estrategia</label>
                <select id="estrategia" class="form-control" onchange="refreshForm();">
                    <option value="0">Seleccionar</option>
                    <option value="apalancamiento" SELECTED>Apalancamiento</option>
                    <option value="bot_auto">Bot Auto</option>
                    <!--<option value="at" >Analisis Tecnico</option>-->
                </select>
            </div>
          </div>

        </div>
        <div class="col">

		  <div class="form-group">
			<div class="form-group">
	        	<label for="symbol">Moneda</label>
	        	<select id="symbol" class="form-control" >
	        		<option value="0">Seleccionar moneda</option>
	        	</select>
	      	</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="usdInicial">Cantidad de USD Billetera</label>
			<div class="input-group">
				<div class="input-group-prepend">
					<div class="input-group-text">USD</div>
				</div>
				<input type="text" class="form-control" id="usdInicial" value="" placeholder="0.000">
			</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="usdInicial">Compra Inicial</label>
			<div class="input-group">
				<div class="input-group-prepend">
					<div class="input-group-text">USD</div>
				</div>
				<input type="text" class="form-control" id="compraInicial" value="" placeholder="0.000">
			</div>
		  </div>

		</div>
        <div class="col">

          <div class="form-group">
            <div class="form-group">
                <label for="from">Rango de fechas</label>
                <select id="from" class="form-control" >
                    {FOR#rangoFechas#
                    ENDFOR}
                </select>
            </div>
          </div>

        </div>

	</div>
	<div class="row">
		<div class="col">

		  <div class="form-group">
			<label for="multiplicadorCompra">Multiplicador Compras</label>
			<input type="text" class="form-control" id="multiplicadorCompra"  value="" placeholder="Recomendado 1.05 a 2.00">
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="multiplicadorPorc">Multiplicador Porcentajes</label>
			<div class="input-group">
			  <input type="text" class="form-control" id="multiplicadorPorc"  value="" placeholder="Recomendado 2.70 a 4.50">
			  <div class="input-group-prepend">
				<div class="input-group-text">%</div>
			  </div>
			</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="incremental">Incremental</label>
			<div class="input-group">
			  <select id="incremental" class="form-control" >
				  <option value="0" >No</option>
				  <option value="1" SELECTED>Si</option>
			  </select>
			</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="porcVentaUp">Porcentaje de venta inicial/palanca</label>
            <div class="input-group">
              <input type="text" class="form-control" id="porcVentaUp"  value="" placeholder="1.15/5.00">
              <div class="input-group-append">
                <div class="input-group-text">%</div>
              </div>
              &nbsp;
              <input type="text" class="form-control" id="porcVentaDown"  value="" placeholder="1.15/5.00">
              <div class="input-group-append">
                <div class="input-group-text">%</div>
              </div>
            </div>

		  </div>

		</div>

        <div class="col">
          <div class="form-group">
            <label for="mostrar">Mostrar</label>
            <div class="input-group mb-1">
              <select id="mostrar" class="form-control" >
                  <option value="0" SELECTED>Solo resultados</option>
                  <option value="grafico">Resultados y Grafico</option>
                  <option value="ordenes">Resultados y Ordenes</option>
              </select>
            </div>
          </div>
        </div>
        
    </div>
	<div class="row">
		<div class="col">

		  <div class="form-group" id="btnEditOperacion">
			<button onclick="analizar()" class="btn btn-warning btn-block" >Analizar</button>
		  </div>
		</div>
	</div>

</div>

<!-- FUENTE: 
    multiple
    https://www.amcharts.com/demos-v4/
    
    https://www.amcharts.com/demos-v4/range-area-chart-v4/ 
    https://www.amcharts.com/demos-v4/line-different-colors-ups-downs-v4/  
    https://www.amcharts.com/demos-v4/highlighting-line-chart-series-on-legend-hover-v4/
-->
<div class="container" id="resultado"></div>
<div class="container" id="chartdiv"></div>
<div class="container" id="months"></div>


<div class="container" id="hours"></div>
<div class="container" id="orderlist"></div>

{{data}}

{{hidden}}

<!-- Resources -->
<!-- Chart code -->
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/lang/es_ES.js"></script>
<script src="https://cdn.amcharts.com/lib/4/fonts/notosans-sc.js"></script>
<!--<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>-->

<script language="javascript" >
	
	var symbols = [{{dataSymbols}}];
    var symbolsBotAuto = [{{symbolsBotAuto}}];
    var maxCompraNum = 0;
    
	$(document).ready( function () {

        setDefaultValues();
        refreshForm();
	});

    function refreshForm()
    {
        var estrategia = $('#estrategia option:selected').val();
        $('#symbol').html('');
        if (estrategia == 'bot_auto')
        {
            if (symbolsBotAuto.length>0)
                for (var i=0; i<symbolsBotAuto.length;i++)
                    $('#symbol').append('<option value="'+symbolsBotAuto[i]+'" >'+symbolsBotAuto[i]+'</option>');
            $('#multiplicadorCompra').parent().parent().hide();
            $('#multiplicadorPorc').parent().parent().hide();
            $('#incremental').parent().parent().hide();
        }
        else
        {
            if (symbols.length>0)
                for (var i=0; i<symbols.length;i++)
                    $('#symbol').append('<option value="'+symbols[i]+'" >'+symbols[i]+'</option>');
                
            $('#multiplicadorCompra').parent().parent().show();
            $('#multiplicadorPorc').parent().parent().show();
            $('#incremental').parent().parent().show();
        }

    }

    function setDefaultValues()
    {
        $('#usdInicial').val('1010.00');
        $('#compraInicial').val('50.00');
        $('#multiplicadorCompra').val('1.5');
        $('#multiplicadorPorc').val('3.0');
        $('#porcVentaUp').val('2.5');
        $('#porcVentaDown').val('1.15');
        if (SERVER_ENTORNO == 'Test')
            $('#symbol option[value="MATICUSDT"]').attr('selected',true);

    }

	function analizar()
	{
        $('#resultado').html('Aguarde.....');
        $('#chartdiv').html('');
        $('#orderlist').html('');
        $('#months').html('');
		CtrlAjax.sendCtrl("test","test","testEstrategias");   
	}
	
    var info;

    function daysGraph() 
    {

        $('#chartdiv').html('Cargando grafico...');


        if (info)
        {
            //console.log(info);
            var labels = info.labels;
            
            am4core.ready(function() 
            {

                // Create chart instance
                var chart = am4core.create("chartdiv", am4charts.XYChart);

                chart.dateFormatter.inputDateFormat = "yyyy-MM-dd HH:mm";

                var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                    dateAxis.dataFields.category = "date";
                    dateAxis.title.fontWeight = "bold";
                    dateAxis.tooltipDateFormat = "yy-MM-dd HH:mm";

                var valueAxisPrice = chart.yAxes.push(new am4charts.ValueAxis());
                    valueAxisPrice.dataFields.category = "price";
                    valueAxisPrice.title.text = "Precio USDT";
                    valueAxisPrice.title.fontWeight = "bold";
                    valueAxisPrice.renderer.grid.template.disabled = true;

                var valueAxisUSD = chart.yAxes.push(new am4charts.ValueAxis());
                    valueAxisUSD.dataFields.category = "usd";
                    valueAxisUSD.renderer.labels.template.disabled = true;
                    valueAxisUSD.renderer.opposite = true;   //Muestra la escala del lado opuesto  
                    valueAxisUSD.cursorTooltipEnabled = false;

                chart.cursor = new am4charts.XYCursor();
                chart.cursor.xAxis = dateAxis;


                //Series de rango de precio
                var seriesRH = createSeriesRango('kh','High','#AA000088');
                var seriesRL = createSeriesRango('kl','Low','#00AA0088');
                seriesRH.hiddenInLegend = false;
                seriesRL.hiddenInLegend = true;

                seriesRH.events.on("hidden", function() {
                    seriesRH.hide();
                    seriesRL.hide();
                });

                seriesRH.events.on("shown", function() {
                    seriesRH.show();
                    seriesRL.show();
                });

                //Series de bullets
                createSeriesBullet('buy','Compra','#166a16');
                createSeriesBullet('sell','Venta','#6a1616');
                createSeriesBullet('apins','Ap.Ins.','#000000');

                //Ordenes abiertas
                var sov = createSeriesOrden('ov','OV','#6a1616');
                var soc1 = createSeriesOrden('oc1','OC #1','#166a16');
                var soc2 = createSeriesOrden('oc2','OC #2','#166a16');
                var soc3 = createSeriesOrden('oc3','OC #3','#166a16');
                var soc4 = createSeriesOrden('oc4','OC #4','#166a16');
                var soc5 = createSeriesOrden('oc5','OC #5','#166a16');
                var soc6 = createSeriesOrden('oc6','OC #6','#166a16');
                sov.hiddenInLegend = false;
                soc1.hiddenInLegend = true;
                soc2.hiddenInLegend = true;
                soc3.hiddenInLegend = true;
                soc4.hiddenInLegend = true;
                soc5.hiddenInLegend = true;
                soc6.hiddenInLegend = true;

                sov.events.on("hidden", function() {
                    sov.hide();
                    soc1.hide();
                    soc2.hide();
                    soc3.hide();
                    soc4.hide();
                    soc5.hide();
                    soc6.hide();
                });

                sov.events.on("shown", function() {
                    sov.show();
                    soc1.show();
                    soc2.show();
                    soc3.show();
                    soc4.show();
                    soc5.show();
                    soc6.show();
                });

                // Add scrollbar
                var scrollbarX = new am4charts.XYChartScrollbar();
                scrollbarX.series.push(seriesRH);
                chart.scrollbarX = scrollbarX;


                
                //Botones para zoom vertical
                /*
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
                
                });

                var zoomOutButton = buttonContainer.createChild(am4core.Button);
                zoomOutButton.label.text = "Zoom -";
                zoomOutButton.events.on("hit", function(ev) {
                  var diff = valueAxis.maxZoomed - valueAxis.minZoomed;
                  var delta = diff * 0.2;
                  valueAxis.zoomToValues(valueAxis.minZoomed, valueAxis.maxZoomed + delta);
                });
                */


                

                chart.legend = new am4charts.Legend();

                chart.legend.position = "bottom";
                chart.legend.scrollable = false;

                

                function createSeriesPrice(value,label,color)
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

                    srs.yAxis = valueAxisPrice;
                    return srs;
                    
                }

                function createSeriesOrden(value,label,color)
                {
                    var srs = chart.series.push(new am4charts.LineSeries());
                    srs.dataFields.dateX = "date";
                    srs.dataFields.valueY = value;
                    
                    srs.stroke = am4core.color(color);
                    srs.strokeWidth = 0.5; // px
                    
                    srs.strokeDasharray = 3;                    
                    
                    srs.name = 'Ordenes Activas';
                    srs.data = info.data;
                    srs.yAxis = valueAxisPrice;
                    srs.connect = false; 
                    
                    return srs;
                    
                }
               

                function createSeriesRango(value,label,color)
                {
                    var srs = chart.series.push(new am4charts.LineSeries());
                    srs.dataFields.dateX = "date";
                    
                    srs.dataFields.valueY = value;
                    
                    srs.stroke = am4core.color(color);
                    srs.strokeWidth = 0.5; // px
                    
                    srs.data = info.data;
                    srs.name = info.tickerid+ ' (' + info.interval +') ';
                    //srs.smoothing = "monotoneY";

                    srs.yAxis = valueAxisPrice;
                    return srs;
                    
                }


                function createSeriesBullet(value,label,color)
                {
                    var srs = chart.series.push(new am4charts.LineSeries());
                    srs.dataFields.dateX = "date";
                    
                    srs.dataFields.valueY = value;
                    
                    srs.tooltip.getFillFromObject = false;      
                    srs.tooltip.background.fill = am4core.color(color);
                    srs.tooltip.label.fill = am4core.color('#fff');

                    srs.strokeWidth = 10;

                    srs.stroke = am4core.color(color);
                    srs.strokeWidth = 0.5; // px
                    
                    srs.data = info.data;
                    srs.name = label;

                    // Add simple bullet
                    var bullet = srs.bullets.push(new am4charts.Bullet());
                    bullet.horizontalCenter = "middle";
                    
                    if (value=='buy')
                    {
                        var bullet = bullet.createChild(am4core.Circle);
                        bullet.width = 3;
                        bullet.height = 3;
                        bullet.fillOpacity = 1;
                    }
                    else if (value=='sell')
                    {
                        var bullet = bullet.createChild(am4core.Circle);
                        bullet.width = 5;
                        bullet.height = 5;
                        bullet.fillOpacity = 0.0;
                    }
                    else if (value=='apins')
                    {
                        var bullet = bullet.createChild(am4core.Triangle);
                        bullet.width = 6;
                        bullet.height = 6;
                        bullet.fillOpacity = 1;
                        bullet.verticalCenter = "middle";
                    }

                    // Make chart not mask the bullets
                    chart.maskBullets = true;                            

                    srs.strokeWidth = 0; // px
                    srs.stroke = am4core.color(color); 
                    srs.connect = false; 
                    
                    return srs;
                }

                

            });
            
        }
    }
</script>
