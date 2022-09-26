
<div class="container-fluid" >
    <div class="d-flex justify-content-between">
        <form>
            <div class="container" >
                
                <div class="row">
                    <div class="col">
                        Moneda
                    </div>
                    <div class="col">
                        Base
                    </div>
                    <div class="col">
                    </div>
                </div>
                <div class="row">
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="asset" onchange="refreshScale()">
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="assetQuote" value="USDT"  onchange="refreshScale()">
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <input class="form-control form-control-sm force-uppercase" id="scale" value="" placeholder="AUTOMATICO">
                        </div>
                    </div>
                    <div class="col">
                        <div class="input-group">
                            <button type="button" class="btn btn-sm btn-warning" onclick="analizar()">Analizar Ordenes de Mercado</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div id="resultado"></div>
</div>

<script type="text/javascript">


    $(document).ready(function() {
        
        $('#asset').focus();

        $('.force-uppercase').each(function () {
            $(this).change(function () {
                var str = $(this).val().toUpperCase();
                $(this).val(str);
            });
        });
    });

    function refreshScale() 
    {
        $('#scale').val('');
    }

    function analizar()
    {
        CtrlAjax.sendCtrl("app","cripto","depth");
    }

</script>
