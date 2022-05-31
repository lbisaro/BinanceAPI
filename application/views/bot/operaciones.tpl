<div class="container">
    <ul class="nav justify-content-end ">
      <li class="nav-item">
        <a href="app.bot.crearOperacion+" class="nav-link " >Nueva Operacion Estandard</a>
      </li>
      <li class="nav-item menu-admin">
        <a href="app.bot.crearOperacion+tipo=1" class="nav-link " >Nueva Operacion Cruzado</a>
      </li>
      <li class="nav-item menu-admin">
        <a href="app.bot.ordenesActivas+" class="nav-link " >Ordenes Activas</a>
      </li>
    </ul>
   
</div>

<div class="container" id="operaciones">
    {{lista}}
</div>



<script type="text/javascript">
    
    $(document).ready( function () {
    });

    //CtrlAjax.sendCtrl("mod","ctrl","act");
    
</script>