
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
    <div class="dropdown" style="text-align: right;">
      <button class="btn btn-info dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-expanded="false">
        Filtrar Ordenes
      </button>
      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
        <span class="dropdown-item" style="cursor:pointer;" onclick="filtrar('all')">Todo</span>
        <span class="dropdown-item" style="cursor:pointer;" onclick="filtrar('para_liquidar')">Compras para liquidar</span>
        <span class="dropdown-item" style="cursor:pointer;" onclick="filtrar('compras')">Ordenes de compra</span>
        <span class="dropdown-item" style="cursor:pointer;" onclick="filtrar('ventas')">Ordenes de venta</span>
      </div>
    </div>
   
</div>
<div class="container tabs" id="ordenesActivas">
    {{ordenesActivas}}
</div>





<script type="text/javascript">
    
    $(document).ready( function () {

        //CtrlAjax.sendCtrl("app","bot","toogleAutoRestart");
        filtrar('para_liquidar');
    });

    function filtrar(filtro)
    {
        $('.orden').hide();
        if (filtro=='para_liquidar')
        {
            $('#ordenesActivas .table_dg_caption').html('Compras para liquidar');
            $('.para_liquidar').show();
        }
        else if (filtro=='ventas')
        {
            $('#ordenesActivas .table_dg_caption').html('Ordenes de venta');
            $('.side_sell').show();
        }
        else if (filtro=='compras')
        {
            $('#ordenesActivas .table_dg_caption').html('Ordenes de compra');
            $('.side_buy').show();
        }
        else
        {
            $('#ordenesActivas .table_dg_caption').html('Ordenes activas');
            $('.orden').show();
        }
    }

    

</script>