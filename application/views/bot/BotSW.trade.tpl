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
        <h5 class="modal-title" id="modalLabel">Trade</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="input-group mb-2">
            <div class="input-group-prepend">
                <div class="input-group-text" >USD</div>
            </div>
            <input type="text" class="form-control" name="capitalUSD" id="capitalUSD" placeholder="0.00" >
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

    $(document).ready( function () {

        $('#modalAsignarCapital').on('show.bs.modal', function (event) {
            
            
            var button = $(event.relatedTarget);
            var action = $('#trade_action').val();
            var str_action = (action=='buy'?'Comprar':'Vender');
            var symbol = $('#trade_symbol').val();
            var modal = $(this);
            modal.find('.modal-title').text(str_action +' '+ symbol);
            modal.find('.modal-title').removeClass((action=='buy'?'text-danger':'text-success'));
            modal.find('.modal-title').addClass((action=='buy'?'text-success':'text-danger'));
            $('#capitalUSD').val('');
            setTimeout(function () {$('#capitalUSD').focus() } , 500);

                        
        })

    });

    function make_trade(action,symbol)
    {
        $('#trade_action').val(action);
        $('#trade_symbol').val(symbol);

        $('#modalAsignarCapital').modal('show');
    
    }

    function trade()
    {
        var capital = toDec($('#capitalUSD').val(),2);
        $('#capitalUSD').val(capital);
        capital = parseFloat(toDec($('#capitalUSD').val(),2));
        if (capital < 12 )
        {
            alert('Se debe asignar un capital superior a 12.0 USD');
        }
        else
        {
            str_confirm = 'Confirma la '+($('#trade_action').val()=='buy'?'COMPRA':'VENTA')+
                          ' de '+$('#trade_symbol').val()+' por el equivalente aproximado a USD '+$('#capitalUSD').val()+'?';
            if (confirm(str_confirm))
            {
                $('#modalAsignarCapital').modal('hide');
                $('#trade').hide();
                $('#trade_msg').html('Aguarde un momento, se esta ejecutando la operacion....');
                $('#trade_msg').attr('class','text-info');
                $('#trade_msg').show();
                CtrlAjax.sendCtrl("app","BotSW","trade");
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
