<style>
	#chartdiv {
	  width: 100%;
	  height: 500px;
	}
   .data {
		font-weight: bolder;
		color: #555;
	}
</style>

<div class="container">
  <div class="row">
		<div class="col">

          <div class="form-group">
            <div class="form-group">
                <label for="estrategia">Estrategia</label>
                <select id="estrategia" class="form-control" >
                    <option value="0">Seleccionar</option>
                    <option value="apalancamiento" SELECTED>Apalancamiento</option>
                    <option value="grid">Grid</option>
                    <option value="at">Analisis Tecnico</option>
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
			<div class="input-group mb-2">
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
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<div class="input-group-text">USD</div>
				</div>
				<input type="text" class="form-control" id="compraInicial" value="" placeholder="0.000">
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
			<div class="input-group mb-2">
			  <input type="text" class="form-control" id="multiplicadorPorc"  value="" placeholder="Recomendado 2.70 a 4.50">
			  <div class="input-group-prepend">
				<div class="input-group-text">%</div>
			  </div>
			</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="incremental">Multiplicador Porcentajes Incremental</label>
			<div class="input-group mb-2">
			  <select id="incremental" class="form-control" >
				  <option value="0" >No - Incrementa cada apalancamiento al mismo valor</option>
				  <option value="1" SELECTED>Si - Incrementa cada apalancamiento al doble del anterior</option>
			  </select>
			</div>
		  </div>

		</div>
		<div class="col">

		  <div class="form-group">
			<label for="porcVentaUp">Porcentaje de venta inicial/palanca</label>
			<div class="input-group mb-2">
			  <select id="porcVentaUp" class="form-control" >
				  <option value="1.15">1.15%</option>
				  <option value="1.5">1.50%</option>
				  <option value="1.75">1.75%</option>
				  <option value="2" SELECTED>2.00%</option>
				  <option value="2.5">2.50%</option>
				  <option value="3">3.00%</option>
				  <option value="4">4.00%</option>
			  </select>
			  <select id="porcVentaDown" class="form-control" >
				  <option value="1.15">1.15%</option>
				  <option value="1.25">1.25%</option>
				  <option value="1.5">1.50%</option>
				  <option value="1.75" SELECTED>1.75%</option>
				  <option value="2">2.00%</option>
				  <option value="2.5">2.50%</option>
				  <option value="3">3.00%</option>
				  <option value="4">4.00%</option>
			  </select>
			</div>
		  </div>

		</div>
	</div>
    <div class="row">
        <div class="col">
          <div class="form-group">
            <label for="porcVentaUp">Grafico</label>
            <div class="input-group mb-2">
              <select id="grafico" class="form-control" >
                  <option value="SI">SI</option>
                  <option value="NO" SELECTED>NO</option>
              </select>
            </div>
          </div>
        </div>
        <div class="col">
          <div class="form-group">
            <label for="porcVentaUp">Ordenes</label>
            <div class="input-group mb-2">
              <select id="ordenes" class="form-control" >
                  <option value="SI">SI</option>
                  <option value="NO" SELECTED>NO</option>
              </select>
            </div>
          </div>
        </div>
        <!--
        <div class="col">
          <div class="form-group">
            <label for="porcVentaUp">Analisis Tecnico</label>
            <div class="input-group mb-2">
              <select id="at" class="form-control" >
                  <option value="SI">SI</option>
                  <option value="NO" SELECTED>NO</option>
              </select>
            </div>
          </div>
        </div>
        -->
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
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script language="javascript" >
	
	var symbols = [{{dataSymbols}}];
    
	$(document).ready( function () {
		if (symbols.length>0)
        {
            for (var i=0; i<symbols.length;i++)
                $('#symbol').append('<option value="'+symbols[i]+'" >'+symbols[i]+'</option>');
            
        }

        //setDefaultValues();
	});

    function setDefaultValues()
    {
        $('#usdInicial').val(1000);
        $('#compraInicial').val(100);
        $('#multiplicadorPorc').val(2.75);
        $('#multiplicadorCompra').val(1.75);
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
	

    var colors = [
      '#000000',//0 //Fecha
      '#4C0784',//1 //Billetera
      '#009B0A',//2 //USD
      '#C47400',//3 //Token
      '#888888',//4 //Token Price
      '#58A029',//5 //Compra 
      '#BF3C0F',//6 //Venta 
      '#58A029',//7 //AT_COMPRA
      '#BF3C0F',//8 //AT_VENTA
      '#4dc9f6',//9 
      '#f53794',//10
      '#f67019',//11
      '#537bc4',//12
      '#8549ba',//13
      ];

    var info;

    function daysGraph() 
    {
        $('#chartdiv').html('Cargando grafico...');
        
        if (info)
        {
            var labels = info[0];
            //console.log(labels);
            
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
                    valueAxis.dataFields.category = "usd";
                    valueAxis.title.text = "USD";                
                var valueAxis2 = chart.yAxes.push(new am4charts.ValueAxis());
                    valueAxis2.dataFields.category = "token";
                    valueAxis2.title.text = "Token Price";  
                    valueAxis2.renderer.opposite = true;   //Muestra la escala del lado opuesto           


                chart.cursor = new am4charts.XYCursor();
                chart.cursor.xAxis = dateAxis;



                //Billetera
                series = createSeriesUsd(1);
                series.data = createData(1);
                series.yAxis = valueAxis;
                //USD
                series = createSeriesUsd(2);
                series.data = createData(2);
                series.yAxis = valueAxis;

                //Token Comprado
                series = createSeriesUsd(3);
                series.data = createData(3);
                series.yAxis = valueAxis;

                //Token Price
                series = createSeriesUsd(4);
                series.data = createData(4);
                series.yAxis = valueAxis2;

                series = createSeriesUsd(7);
                series.data = createData(7);
                series.yAxis = valueAxis2;
                series.connect = false;

                series = createSeriesUsd(8);
                series.data = createData(8);
                series.yAxis = valueAxis2;
                series.connect = false;


                //Compra
                series = createSeriesBullet(5,'compra');
                series.data = createData(5);
                series.yAxis = valueAxis2;

                //Venta
                series = createSeriesBullet(6,'venta');
                series.data = createData(6);
                series.yAxis = valueAxis2;

                // Add scrollbar
                //var scrollbarX = new am4charts.XYChartScrollbar();
                //scrollbarX.series.push(series);
                //chart.scrollbarX = scrollbarX;

                series.smoothing = "monotoneY";

                chart.legend = new am4charts.Legend();

                chart.legend.position = "top";
                chart.legend.scrollable = false;

                function createSeriesUsd(s)
                {
                    var srs = chart.series.push(new am4charts.LineSeries());
                        srs.dataFields.valueY = "value" + s;
                        srs.dataFields.dateX = "date";
                        srs.name = labels[s];
                        srs.tooltipText = "USD {valueY.value}";
                        
                        srs.tooltip.getFillFromObject = false;
                        srs.tooltip.background.fill = am4core.color(colors[s]);
                        srs.tooltip.label.fill = am4core.color('#fff');

                        if (s==1 )
                        {
                            srs.strokeWidth = 1.2; // px
                        }
                        else if (s==2 || s==3)
                        {
                            srs.strokeWidth = 0.75;
                        }
                        else if (s==7 || s==8)
                        {
                            srs.strokeWidth = 1;
                        }
                        else if (s==4)
                        {
                            srs.strokeWidth = 0.75;
                            srs.strokeDasharray = 5;
                        }
                        else
                        {
                            srs.strokeDasharray = 4;
                            srs.strokeWidth = 1; // px
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
        

    }

</script>
