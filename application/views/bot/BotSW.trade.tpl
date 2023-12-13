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
<div class="modal fade" id="modalTrade" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLabel">Trade</h5>
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
                <div class="input-group-text"id="quoteInputGroup" ></div>
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
            Capital disponible: <span id="capitalDisponible"></span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="trade()">Ejecutar</button>
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
    <h5 class="text-info">Trade</h5>
    <div id="trade_msg"></div>
    <div class="container" id="trade">
    {{htmlTrade}}
    </div>
    <input type="hidden" name="idbotsw" id="idbotsw" value="{{idbotsw}}">
    <input type="hidden" name="trade_action" id="trade_action" >
    <input type="hidden" name="trade_symbol" id="trade_symbol" >
</div>
<div class="container">
    {{orders}}
</div>



<script language="javascript" >

    {{jsonData}}

    var activeAsset = '';
    var activeQuote = '';


    $(document).ready( function () {

        $('#modalTrade').on('show.bs.modal', function (event) {
            
            
            var button = $(event.relatedTarget);
            var action = $('#trade_action').val();
            var str_action = (action=='buy'?'Comprar':'Vender');
            var symbol = $('#trade_symbol').val();
            var modal = $(this);
            modal.find('.modal-title').text(str_action +' '+ symbol);
            modal.find('.modal-title').removeClass((action=='buy'?'text-danger':'text-success'));
            modal.find('.modal-title').addClass((action=='buy'?'text-success':'text-danger'));
                        
        })

    });

    function setMaxCapitalToken()
    {
        action = $('#trade_action').val();
        var data_base = eval('data_'+activeAsset);
        var data_quote = eval('data_'+activeQuote);

        if (action == 'sell')
        {
            var capital = toDec(data_base.Free,data_base.qtyDecsUnits);
            $('.modal-body #capital').val(capital);   
        }
        else
        {
            var capital = toDec(data_quote.Free/data_base.Price,data_base.qtyDecsUnits);
            $('.modal-body #capital').val(capital);              
        }
        swapTokenToUsd();
    }

    function setMaxCapitalUsd()
    {
        action = $('#trade_action').val();
        var data_base = eval('data_'+activeAsset);
        var data_quote = eval('data_'+activeQuote);

        if (action == 'sell')
        {
            var capitalUSD = toDec(data_base.Free*data_base.Price);
            $('.modal-body #capitalUSD').val(capitalUSD);   
        }
        else
        {
            var capitalUSD = toDec(data_quote.Free);
            $('.modal-body #capitalUSD').val(capitalUSD);              
        }
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

    function make_trade(action,base,quote)
    {
        $('#trade_action').val(action);
        $('#trade_symbol').val(base+quote);
        symbol = base+quote
        activeAsset = base;
        activeQuote = quote;
        
        var modal = $('#modalTrade');
        
        var data_base = eval('data_'+base);
        var data_quote = eval('data_'+quote);
        if (action == 'sell')
        {
            modal.find('.modal-body #capitalDisponible').html(base+' '+data_base.Free);
        }
        else
        {
            modal.find('.modal-body #capitalDisponible').html(quote+' '+toDec(data_quote.Free,data_quote.qtyDecsUnits));
        }
        modal.find('.modal-body #capital').val('');
        modal.find('.modal-body #capitalUSD').val('');

        modal.find('.modal-body #assetInputGroup').html(base)
        modal.find('.modal-body #quoteInputGroup').html(quote)

        modal.find('.modal-body #asset').val(base)
        modal.find('.modal-body #quote').val(quote)
        modal.find('.modal-body #precio').html(data_base.Price);


        $('#modalTrade').modal('show');
    
    }

    function trade()
    {
        var capitalUSD = $('#capitalUSD').val();
        var qty = $('#capital').val();
        
        capitalUSD = parseFloat(toDec($('#capitalUSD').val(),2));
        if (capital < 12 )
        {
            alert('Se debe asignar un capital superior a 12.0 USD');
        }
        else
        {
            str_confirm = 'Confirma la '+($('#trade_action').val()=='buy'?'COMPRA':'VENTA')+
                          ' de '+qty+' '+$('#trade_symbol').val()+' por el equivalente aproximado a USD '+$('#capitalUSD').val()+'?';
            if (confirm(str_confirm))
            {
                $('#modalTrade').modal('hide');
                $('#trade').hide();
                $('#trade_msg').html('Aguarde un momento, se esta ejecutando la operacion....');
                $('#trade_msg').attr('class','text-info');
                $('#trade_msg').show();
                CtrlAjax.sendCtrl("app","BotSW","trade",'symbol='+$('#trade_symbol').val());
            }

        }
        
    }
    function filter_par()
    {
        var par_to_filter = $('#par').val();

        if (par_to_filter)
        {
            $('#ordenes tbody tr').hide();
            $('#ordenes tbody tr.'+par_to_filter).show();
        }
        else
        {
            $('#ordenes tbody tr').show();

        }

    }

   
</script>
