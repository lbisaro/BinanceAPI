      


      <img class="mb-4" src="public/images/cripto_login.png" alt="" height="100">
      <h1 class="h3 mb-3 font-weight-normal">Abrir sesion</h1>
      <label for="login_username" class="sr-only">Usuario / Email</label>
      <input type="text" id="login_username" name="login_username" class="form-control" placeholder="Usuario/Email" required autofocus>
      <label for="login_password" class="sr-only">Password</label>
      <input type="password" id="login_password" name="login_password" class="form-control" placeholder="Password" required>
      <div id="login_msg" class=" mb-3 font-weight-normal text-center"></div>
      <button class="btn btn-lg btn-primary btn-block" type="submit" onclick="CtrlAjax.sendCtrl('Usr','Usr','login');">Acceder</button>
      <p class="mt-5 mb-3 text-muted">
          Cripto Web
          <br/>
          &copy; 2021
      </p>

