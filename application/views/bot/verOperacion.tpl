
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }
</style>

<div class="container">
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="symbol">Moneda</label>
        <div class="data">{{symbol}}</div>
      </div>
    </div>    
    <div class="col">
      <div class="form-group">
        <label for="inicio_usd">Cantidad de USD compra inicial</label>
        <div class="data">{{inicio_usd}}</div>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_compra">Multiplicador Compras</label>
        <div class="data">{{multiplicador_compra}}</div>
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_porc">Multiplicador Porcentajes</label>
        <div class="data">{{multiplicador_porc}}</div>
      </div>
    </div>
  </div>  
  <div class="row">
    <div class="col">
      <div class="form-group">
        <label for="multiplicador_compra">Estado</label>
        <div class="data" id="estado">{{estado}}</div>
      </div>
    </div>
    <div class="col">
      <div class="form-group">
        <label for="auto-restart">Recompra Automatica</label>
        <div class="data" id="auto-restart">{{auto-restart}}</div>
      </div>
    </div>
  </div>  

  <!--
  <div class="form-group" id="btnAddOperacion">
    <button onclick="crearOperacion()" class="btn btn-success" >Crear Operacion</button>
  </div>
  -->
  {{hidden}}
</div>
<div class="container">
    <h2>Ordenes</h2>
    {{ordenes}}
</div>


<script type="text/javascript">
    
    $(document).ready( function () {
        $('#arBtn').click(function () {
            CtrlAjax.sendCtrl("app","bot","toogleAutoRestart");
        });
    });

    function start()
    {
        if (confirm('Desea reiniciar la operacion?'))
            CtrlAjax.sendCtrl("app","bot","start");
    }

    function setAutoRestartTo(set)
    {
        if (set)
        {
            $('#arBtn').attr('class','btn btn-sm btn-success');
            $('#arBtn span').attr('class','glyphicon glyphicon-ok');
        }
        else
        {
            $('#arBtn').attr('class','btn btn-sm btn-danger');
            $('#arBtn span').attr('class','glyphicon glyphicon-ban-circle');
        }
    }

</script>