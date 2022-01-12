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

		  <div class="form-group" id="btnEditOperacion">
			<button onclick="analizar()" class="btn btn-warning btn-block" >Analizar</button>
		  </div>
		</div>
	</div>

</div>

<!-- FUENTE: 
    multiple
    https://www.amcharts.com/demos/multiple-date-axes/?theme=material
    
    https://www.amcharts.com/demos/range-area-chart/?theme=material 
    https://www.amcharts.com/demos/line-different-colors-ups-downs/?theme=material  
    https://www.amcharts.com/demos/highlighting-line-chart-series-on-legend-hover/?theme=material
-->
<div class="container" id="resultado"></div>
<div class="container" id="chartdiv"></div>
<div class="container" id="months"></div>


<div class="container" id="days"></div>
<div class="container" id="ordenes"></div>

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
	});

	function analizar()
	{
		CtrlAjax.sendCtrl("test","test","testAPL");   
	}
	

    var colors = [
      '#000000',//0 //Fecha
      '#4C0784',//1 //Billetera
      '#009B0A',//2 //USD
      '#C47400',//3 //Token
      '#aaaaff',//4 
      '#58A029',//5 
      '#BF3C0F',//6 
      '#88FF88',//7 
      '#FF8888',//8 
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
                    valueAxis.dataFields.category = "price";
                    valueAxis.title.text = "Precio USDT";

                chart.cursor = new am4charts.XYCursor();
                chart.cursor.xAxis = dateAxis;



                //Billetera
                series = createSeriesPrecio(1);
                series.data = createData(1);
                //USD
                series = createSeriesPrecio(2);
                series.data = createData(2);
                //Token Comprado
                series = createSeriesPrecio(3);
                series.data = createData(3);
                
                // Add scrollbar
                //var scrollbarX = new am4charts.XYChartScrollbar();
                //sscrollbarX.series.push(series);
                //chart.scrollbarX = scrollbarX;

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
                        srs.tooltipText = "USD {valueY.value}";
                        
                        srs.tooltip.getFillFromObject = false;
                        srs.tooltip.background.fill = am4core.color(colors[s]);
                        srs.tooltip.label.fill = am4core.color('#fff');

                        if (s==1 )
                        {
                            srs.strokeWidth = 3; // px
                        }
                        else if (s==2 || s==3)
                        {
                            srs.strokeWidth = 1;
                        }
                        else
                        {
                            srs.strokeDasharray = 4;
                            srs.strokeWidth = 1.5; // px
                            srs.strokeWidth = 1.5; // px
                        }

                        srs.stroke = am4core.color(colors[s]); 
                        srs.connect = true; 


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
