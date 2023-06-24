{{divPop}}
{{actionBar}}
{{data}}
{{hidden}}
<style type="text/css">
  .data {
    font-weight: bolder;
  }
</style>
<div class="container">
    <h4 class="text-info">{{titulo}}</h4>
    <div class="container" style="text-align: right;">
        <div class="btn-group">    
        {{addButtons}}
        </div>
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
            <td class="data" style="width:25%;">
                {{symbol_estable}}
            </td>
            <td style="width:20%;">
                StableCoin para reserva
            </td>
            <td class="data" style="width:25%;">
                {{symbol_reserva}}
            </td>
        </tr>

    </table>
</div>

<div class="container" id="open_orders">
    <h5 class="text-info">Ordenes abiertas</h5>
    {{htmlOpenOrders}}
</div>

<div class="container" id="posiciones">
    <h5 class="text-info">Posiciones</h5>
    {{htmlPosiciones}}
</div>

<div class="container" id="capital">
    <h5 class="text-info">Capital Inicial</h5>
    {{htmlCapital}}
</div>

<script language="javascript" >

    var showOrders = {{showOrders}};
    $(document).ready( function () {
        if (showOrders)
            $('#open_orders').show();
        else
            $('#open_orders').hide();
    });

    function start()
    {
        CtrlAjax.sendCtrl("app","BotSW","setStatus","newStatus=START");
    }
    
    function stop()
    {
        CtrlAjax.sendCtrl("app","BotSW","setStatus","newStatus=STOP");
    }    

    function standby()
    {
        CtrlAjax.sendCtrl("app","BotSW","setStatus","newStatus=STANDBY");
    }

</script>
