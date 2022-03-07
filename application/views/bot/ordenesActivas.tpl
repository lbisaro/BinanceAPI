
<style type="text/css">
    .data {
        font-weight: bolder;
        color: #555;
    }

    .separator {
        font-weight: bolder;
        font-size: 1.2em;
    }
</style>

<div class="container">
    <ul class="nav justify-content-end ">
      <li class="nav-item">
        <button id="toogleVentaBtn" onclick="toogleVenta()" class="btn btn-success " >Ver todas</a>
      </li>
    </ul>
   
</div>
<div class="container tabs" id="ordenesActivas">
    {{ordenesActivas}}
</div>





<script type="text/javascript">
    
    var soloVenta = false;
    $(document).ready( function () {

        //CtrlAjax.sendCtrl("app","bot","toogleAutoRestart");
        toogleVenta();
    });

    function toogleVenta()
    {
        if (soloVenta)
        {
            soloVenta = false;
            $('#toogleVentaBtn').html('Ver Operaciones para venta');
            $('.separator').show();
            $('.porcDown').show();
        }
        else
        {
            soloVenta = true;
            $('#toogleVentaBtn').html('Ver todas');
            $('.separator').hide();
            $('.porcDown').hide();
        }
    }

</script>