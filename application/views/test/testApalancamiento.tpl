<style type="text/css">
  .data {
		font-weight: bolder;
		color: #555;
	}
</style>

<div class="container">
  <div class="row">
		<div class="col3">

		  <div class="form-group">
				<div class="form-group">
        	<label for="symbol">Moneda</label>
        	<input type="text" class="form-control" id="symbol" onchange="this.value = this.value.toUpperCase();" placeholder="xxxUSDT">
      	</div>
		  </div>

		  <div class="form-group">
			<label for="usdInicial">Cantidad de USD Billetera</label>
			<div class="input-group mb-2">
				<div class="input-group-prepend">
					<div class="input-group-text">USD</div>
				</div>
				<input type="text" class="form-control" id="usdInicial" value="" placeholder="0.000">
			</div>
		  </div>

		  <div class="form-group">
			<label for="multiplicadorCompra">Multiplicador Compras</label>
			<input type="text" class="form-control" id="multiplicadorCompra"  value="" placeholder="Recomendado 1.05 a 2.00">
		  </div>

		  <div class="form-group">
			<label for="multiplicadorPorc">Multiplicador Porcentajes</label>
			<div class="input-group mb-2">
			  <input type="text" class="form-control" id="multiplicadorPorc"  value="" placeholder="Recomendado 2.70 a 4.50">
			  <div class="input-group-prepend">
				<div class="input-group-text">%</div>
			  </div>
			</div>
		  </div>

		  <div class="form-group">
			<label for="incremental">Multiplicador Porcentajes Incremental</label>
			<div class="input-group mb-2">
			  <select id="incremental" class="form-control" >
				  <option value="0" >No - Incrementa cada apalancamiento al mismo valor</option>
				  <option value="1" SELECTED>Si - Incrementa cada apalancamiento al doble del anterior</option>
			  </select>
			</div>
		  </div>

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

		  <div class="form-group" id="btnEditOperacion">
			<button onclick="analizar()" class="btn btn-success" >Analizar</button>
		  </div>
		</div>
	  <div class="col">
		
		<div class="container" >
			<h3>Resultado sobre la operacion</h3>
			<div id="resultado"></div>
	  	</div>

	</div>


</div>

<div class="container" id="months"></div>
<div class="container" id="ordenes"></div>

{{data}}

{{hidden}}

<script language="javascript" >

	$(document).ready( function () {

	});

	function analizar()
	{
		CtrlAjax.sendCtrl("test","test","testAPL");   
	}
	//

</script>
