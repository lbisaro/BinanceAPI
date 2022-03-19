{{divPop}}
{{actionBar}}
{{data}}
{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>
<div class="container-fluid  ">
    <div class="container">
      <div class="form-group">
        <label for="tickerid">Ticker</label>
        <input type="text" class="form-control" value="{{tickerid}}" {{readonly}} id="tickerid">
      </div>
      <div class="form-group">
        <label for="hst_min">Minimo Historico</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">USD</div>
            </div>
            <input type="text" class="form-control" value="{{hst_min}}" id="hst_min">
        </div>
      </div>
      <div class="form-group">
        <label for="hst_max">Maximo Historico</label>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text">USD</div>
            </div>
            <input type="text" class="form-control" value="{{hst_max}}" id="hst_max">
        </div>
      </div>
    

      <div class="form-group">
          <button onclick="grabarTicker()" class="btn btn-success" >Grabar</button>
      </div>
    

    </div>




   



</div>
<script language="javascript" >

    $(document).ready( function () {

    });

    function grabarTicker()
    {
        CtrlAjax.sendCtrl("app","cripto","grabarTicker");
    }

</script>
