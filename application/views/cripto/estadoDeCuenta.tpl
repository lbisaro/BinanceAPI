<style type="text/css">
    table tfoot {
        font-weight: bolder;
    }
</style>
<div class="container">
{{alertas}}
{{data}}
<div class="container">
    <h4 class="info">
        Billetera: <b>USD {{totalUSD}}</b>
    </h4>
</div>
</div>
<div class="container">
  <ul class="nav nav-tabs">
    <li class="nav-item" id="tab_compras">
      <a class="nav-link" href="#" onclick="activarTab('compras',true)">Estado de Compras</a>
    </li>
    <li class="nav-item" id="tab_capitalDisponible">
      <a class="nav-link" href="#" onclick="activarTab('capitalDisponible',true)">Gestion del capital</a>
    </li>
    <li class="nav-item" id="tab_billetera">
      <a class="nav-link" href="#" onclick="activarTab('billetera',true)">Disposicion de la Billetera</a>
    </li>
    <!--
    <li class="nav-item">
      <a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true">Disabled</a>
    </li>
    -->
  </ul>
</div>
<div class="container tabs" id="compras">
    {{tab_compras}}
</div>
<div class="container tabs" id="capitalDisponible">
    {{tab_capitalDisponible}}
    <div class="container">
        {{tab_capitalDisponible_analisis}}
    </div>
</div>
<div class="container tabs" id="billetera">
    {{tab_billetera}}
</div>

<script type="text/javascript">
    
    $(document).ready( function () {
        activarTab('{{activeTab}}',false);
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
    });

    function activarTab(id,update)
    {
        $('.nav-tabs a').removeClass('active');
        $('.tabs').hide();
        $('#'+id).show();
        $('#tab_'+id+' a').addClass('active');
        if (update)
            CtrlAjax.sendCtrl("usr","usr","setConfig","set=cripto.estadoDeCuenta.tab&str="+id);
    }


</script>
