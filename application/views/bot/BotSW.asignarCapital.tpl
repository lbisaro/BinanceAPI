{{divPop}}
{{actionBar}}
{{data}}
{{hidden}}
<style type="text/css">
  .data {
    font-weight: bolder;
  }
</style>

<!-- Modal -->
<div class="modal fade" id="modalAsignarCapital" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Asignar Capital</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text" id="assetInputGroup"></div>
            </div>
            <input type="text" class="form-control" name="capital" id="capital" placeholder="0.00" onkeyup="swapTokenToUsd()" onblur="swapTokenToUsd()" >
            <div class="input-group-append">
                <button type="button" class="btn btn-secondary" onclick="setMaxCapitalToken()">Maximo</span>
            </div>
        </div>
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text" >USD</div>
            </div>
            <input type="text" class="form-control" name="capitalUSD" id="capitalUSD" placeholder="0.00" onkeyup="swapUsdToToken()" onblur="swapUsdToToken()" >
            <div class="input-group-append">
                <button type="button" class="btn btn-secondary" onclick="setMaxCapitalUsd()">Maximo</span>
            </div>
        </div>
        <div class="container text-info">
            Precio: <span id="precio"></span>
        </div>
        <div class="container text-info">
            Capital actual: <span id="capitalActual"></span>
        </div>
        <div class="container text-info">
            Capital disponible: <span id="capitalDisponible"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="asignarCapital()">Asignar</button>
      </div>
    </div>
  </div>
</div>

<div class="container">
    <h4 class="text-info">{{titulo}}</h4>
    <div class="container" style="text-align: right;">
        {{addButtons}}
    </div>

    <table class="table table-borderless table_data">

        <tr>
            <td>
                Estado
            </td>
            <td colspan="3">
                <span class="data {{estado_class}}">{{strEstado}}</span>
                <div class="text-secondary">{{estado_msg}}</div>
            </td>
        </tr>

        <tr>
            <td style="width:20%;">
                StableCoin para operar
            </td>
            <td class="data" style="width:25%;" id="symbol_estable">
                {{symbol_estable}}
            </td>
            <td style="width:20%;">
                StableCoin para reserva
            </td>
            <td class="data" style="width:25%;" id="symbol_reserva">
                {{symbol_reserva}}
            </td>
        </tr>

    </table>
    <h5 class="text-info">Capital</h5>
    <div class="container" id="capital">{{htmlCapital}}</div>

</div>



<script language="javascript" >

    {{jsonData}}

    var activeAsset = '';

    $(document).ready( function () {

        $('#modalAsignarCapital').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var asset = button.data('asset');
            var modal = $(this);
            modal.find('.modal-title').text('Asignar capital en ' + asset);

            activeAsset = asset;
            var data = eval('data_'+asset);
            
            modal.find('.modal-body #capitalActual').html(data.Capital);
            modal.find('.modal-body #capitalDisponible').html(data.Free);
            modal.find('.modal-body #assetInputGroup').html(asset)

            if (data.Capital>0)
                modal.find('.modal-body #capital').val(data.Capital);
            else
                modal.find('.modal-body #capital').val(data.Capital);


            modal.find('.modal-body #asset').val(asset)
            modal.find('.modal-body #precio').html(data.Price);
            modal.find('.modal-body #capitalUSD').val(toDec(data.Capital*data.Price));
            
        })

    });

    function setMaxCapitalToken()
    {
        data = eval('data_'+activeAsset);
        $('.modal-body #capital').val(data.Free);   
        swapTokenToUsd();
    }

    function setMaxCapitalUsd()
    {
        data = eval('data_'+activeAsset);
        var capitalUSD = toDec(data.Free*data.Price);
        $('.modal-body #capitalUSD').val(capitalUSD);   
        swapUsdToToken();
    }

    function swapTokenToUsd()
    {
        data = eval('data_'+activeAsset);
        var price = $('.modal-body #precio').html();
        var capital = $('.modal-body #capital').val();
        var capitalUSD = toDec(capital*price);
        $('.modal-body #capitalUSD').val(capitalUSD);
    }

    function swapUsdToToken()
    {
        data = eval('data_'+activeAsset);
        var price = $('.modal-body #precio').html();
        var capitalUSD = $('.modal-body #capitalUSD').val();
        var capital = toDec(capitalUSD/price,data.qtyDecsUnits);
        $('.modal-body #capital').val(capital);
    }

    function asignarCapital()
    {
        data = eval('data_'+activeAsset);
        capital = $('#capital').val();
        if (capital < 0 || capital>data.Free)
            alert('Se debe asignar un capital entre 0.00 y '+data.Free);
        else
            CtrlAjax.sendCtrl("app","BotSW","asignarCapital","asset="+activeAsset+"&price="+data.Price);
    
    }

   
</script>
