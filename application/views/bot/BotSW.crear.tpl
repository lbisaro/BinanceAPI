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

    <div class="form-group">
        <label for="titulo">Titulo</label>
        <input type="text" class="form-control" id="titulo" placeholder="Titulo que identifica al Bot">
    </div>

    <div class="form-group">
        <label for="symbol_estable">StableCoin para operar</label>
        <div class="input-group mb-2">
            <select class="form-control" id="symbol_estable" onchange="checkSymbols()">
                <option value="USDT">USDT</option>
                <option value="BUSD">BUSD</option>
                <option value="USDC">USDC</option>
            </select>
        </div>
    </div>

    <div class="form-group">
        <label for="symbol_reserva">StableCoin para reserva</label>
        <div class="input-group mb-2">
            <select class="form-control" id="symbol_reserva" onchange="checkSymbols()">
                <option value="BUSD">BUSD</option>
                <option value="USDC">USDC</option>
                <option value="USDT">USDT</option>
                
            </select>
        </div>
    </div>

    
    <div class="form-group" id="btnAddOperacion">
        <button onclick="crearBot()" class="btn btn-success" >Crear Bot</button>
    </div>
</div>



<script language="javascript" >

    $(document).ready( function () {

    });

    function crearBot()
    {
        CtrlAjax.sendCtrl("app","BotSW","crear");
        
    }

    function checkSymbols()
    {
        var symbol_estable = $('#symbol_estable option:selected').val();
        var symbol_reserva = $('#symbol_reserva option:selected').val();
        if (symbol_reserva == symbol_estable)
        {
            alert('Las StableCoin Operar y para Reserva deben ser diferentes');
            return false;
        }
        return true;
    }

</script>
