<style type="text/css">
    table tfoot {
        font-weight: bolder;
    }
</style>
<div class="container">
{{alertas}}
{{data}}
</div>
<div class="container">
  <ul class="nav nav-tabs">
    <li class="nav-item" id="tab_compras">
      <a class="nav-link" href="#" onclick="activarTab('compras')">Estado de Compras</a>
    </li>
    <li class="nav-item" id="tab_capitalDisponible">
      <a class="nav-link" href="#" onclick="activarTab('capitalDisponible')">Gestion del capital</a>
    </li>
    <li class="nav-item" id="tab_billetera">
      <a class="nav-link" href="#" onclick="activarTab('billetera')">Disposicion de la Billetera</a>
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
        activarTab('compras');
        $('.nav-tabs a').click(function(event) {
          event.preventDefault();
        });
    });

    function activarTab(id)
    {
        $('.nav-tabs a').removeClass('active');
        $('.tabs').hide();
        $('#'+id).show();
        $('#tab_'+id+' a').addClass('active');
    }


</script>
