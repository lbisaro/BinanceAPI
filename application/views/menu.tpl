<script language="javascript" >

    {{server_entorno}}

    $(document).ready( function () {

        //jsMenuAdmin
        {{jsMenuAdmin}}

        
        
    
    });

</script>

<nav class="navbar fixed-top navbar-expand-lg navbar-default ">
  <a class="navbar-brand mb-2" href="app.Cripto.estadoDeCuenta+">
    <img src="public/images/cripto_menu.png" style="width: 36px;" alt="Estado de cuenta">
  </a>
  <span class="navbar-brand" >{{title}}</span>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="sr-only  rounded" >Toggle navigation</span>
      <span class="glyphicon glyphicon-menu-hamburger text-white"></span>
  </button>

      
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    
    <ul class="navbar-nav ml-auto ">
        
        {{mainMenu}}
        <!--
        <li class="nav-item">
          <a class="nav-link rounded" href="app.cripto.variacionPrecio+">Variacion del precio</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Graficos
          </a>
          <div class="dropdown-menu" aria-labelledby="navbarDropdown">
            <a class="dropdown-item" href="app.cripto.compararPorcentaje+">Comparar porcentajes</a>
            <a class="dropdown-item" href="app.cripto.operaciones+">Operaciones</a>
            
            <a class="dropdown-item" href="#">Another action</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="#">Something else here</a>
            
          </div>
        </li>        
        -->
        <li class="nav-item">
          <a class="nav-link rounded" href="app.Bot.trade+"><span class="glyphicon glyphicon-fire"></span> Trade</a>
        </li>        
        <li class="nav-item">
          <a class="nav-link rounded" href="app.Cripto.estadoDeCuenta+"><span class="glyphicon glyphicon-btc"></span> Estado de cuenta</a>
        </li>        
        <li class="nav-item">
          <a class="nav-link rounded" href="app.Bot.estadisticas+"><span class="glyphicon glyphicon-signal"></span> Estadisticas</a>
        </li>   
        <!--     
        <li class="nav-item menu-admin">
          <a class="nav-link rounded" href="app.Cripto.compararPorcentaje+"><span class="glyphicon glyphicon-sort"></span> Compara %</a>
        </li>  
        -->      
        <li class="nav-item">
          <a class="nav-link rounded" href="app.bot.operaciones+"><span class="glyphicon glyphicon-modal-window"></span> Bot</a>
        </li>        
        <!--
        <li class="nav-item">
          <a class="nav-link rounded" href="app.botAT.home+"><span class="glyphicon glyphicon-modal-retweet"></span> Bot Arbitraje</a>
        </li>        
        -->
        <li class="nav-item menu-admin">
          <a class="nav-link rounded" href="app.cripto.tickers+"><span class="glyphicon glyphicon-cog"></span> Tickers</a>
        </li>        
        <li class="nav-item menu-admin">
          <a class="nav-link rounded" href="app.bot.log+"><span class="glyphicon glyphicon-th-list"></span> Log</a>
        </li>        
        <li class="nav-item menu-admin">
          <a class="nav-link rounded" href="test.test.home+"><span class="glyphicon glyphicon-th-list"></span> BackTesting</a>
        </li>        
        <li class="nav-item menu-admin">
          <a class="nav-link rounded" href="usr.usr.usuarios+"><span class="glyphicon glyphicon-cog"></span> Usuarios</a>
        </li>         
        <li class="nav-item">
          <a class="nav-link rounded" href="usr.usr.perfil+"><span class="glyphicon glyphicon-user"></span> Cuenta</a>
        </li>
        <li class="nav-item">
          <a class="nav-link rounded" href="bsbot.bsbot.home+"><span class="glyphicon glyphicon glyphicon-tasks"></span> BSBot</a>
        </li>
        <li class="nav-item">
          <a class="nav-link rounded" href="usr.usr.logout+"><span class="glyphicon glyphicon-log-out"></span> Salir</a>
        </li>
    </ul>

  </div>
</nav>

  