<div class="container">
    <ul class="nav justify-content-end ">
      <li class="nav-item">
        <a href="app.bot.crearOperacion+" class="nav-link " >Nueva <b>Operacion Estandard</b></a>
      </li>
      <li class="nav-item menu-admin">
        <a href="app.bot.crearOperacion+tipo=1" class="nav-link " >Nueva <b class="text-success">Martingala LONG</b></a>
      </li>
      <li class="nav-item menu-admin">
        <a href="app.bot.crearOperacion+tipo=2" class="nav-link " >Nueva <b class="text-danger">Martingala SHORT</b></a>
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