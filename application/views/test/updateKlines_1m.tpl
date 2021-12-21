<div class="container-fluid">
    <div class="row">
        <div class="col-sm-4">
            <h3>Tokens</h3>
            <ul id="tokens" class="list-group">
            </ul>            
        </div>
        <div class="col">
            <h3>Status</h3>
            <p>Fecha de inicio de captura de datos: {{fechaInicio}}</p>
            <div id="status"></div>
            <div id="statusVar"></div>
            <h3>Log</h3>
            <div id="log"></div>
        </div>
    </div>
</div>

{{divPop}}
{{actionBar}}
{{data}}
{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>

<script language="javascript" >

    var symbols = [{{dataSymbols}}];
    var currentSymbol = 0;

    $(document).ready( function () {
        console.log(symbols);
        if (symbols.length>0)
        {
            for (var i=0; i<symbols.length;i++)
                $('#tokens').append('<li id="sym_'+symbols[i]+'" class="list-group-item">'+symbols[i]+'</li>');
            
        }
        update();
    });

    function update()
    {
        if (symbols[currentSymbol])
        {
            $('#status').html('Actualizando '+symbols[currentSymbol]);
            $('#sym_'+symbols[currentSymbol]).addClass('active');
            CtrlAjax.sendCtrl("test","test","updateKlines_1m","symbol="+symbols[currentSymbol]);
        }
        else
        {
            $('#log').prepend('<code>Proceso finalizado</code><br>');
        }
        
    }


</script>
