<div class="container col-10 pt-3">
    <h5 class="bd-title">
        <span class="glyphicon glyphicon-user"></span> Cuenta  
    </h5>
    <form id="perfil">
        <div class="form-group row">
            <label for="ayn" class="col-sm-2 col-form-label">Nombre y Apellido</label>
            <div class="col-sm-10 my-auto">
                <strong>{{ayn}}</strong>
            </div>
        </div>    

        <div class="form-group row ">
            <label for="username" class="col-sm-2 col-form-label">Nombre de usuario</label>
            <div class="col-sm-10 my-auto">
                <strong>{{username}}</strong>
            </div>
        </div>  

        <div class="form-group row">
            <label for="mail" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10 my-auto">
                <strong>{{mail}}</strong>
            </div>
        </div>

        <div class="form-group row pass-form-hide">
            <label for="mail" class="col-sm-2 col-form-label">Password</label>
            <div class="col-sm-10">
                <button type="button" class="btn btn-link btn-sm" onclick="showPassForm();">Modificar Password</button>
            </div>
        </div>

        <div class="form-group row pass-form-show">
            <label for="oldpassword" class="col-sm-2 col-form-label">Password Actual</label>
            <div class="col-sm-10">
                <input type="password" class="form-control" id="oldpassword" placeholder="Password Actual">
            </div>
        </div>

        <div class="form-group row pass-form-show">
            <label for="password" class="col-sm-2 col-form-label">Nuevo Password</label>
            <div class="col-sm-10">
                <input type="password" class="form-control" id="password" placeholder="Nuevo Password">
                <input type="password" class="form-control" id="confpass" placeholder="Confirmar Password">
            </div>
        </div>

        <div class="form-group row pass-form-show">
            <div class="col-sm-2">&nbsp;</div>
            <div class="col-sm-10">
                <div id="message-error" class="invalid-feedback"></div>
                <button type="button" class="btn btn-primary" onclick="grabarPassword();">Grabar</button>
                <button type="button" class="btn btn-danger" onclick="hidePassForm();">Cancelar</button>
                <small id="confpass" class="form-text text-muted">
                    El Password debe tener entre 6 y 10 caracteres, 
                    al menos una letra mayuscula, 
                    al menos una letra minuscula, 
                    al menos un numero, 
                    <br/>No debe tener espacios ni caracteres especiales.
                    <br/>Debera renovarse cada 180 dias.
                    <br/>No se deben repetir las ultimas 6 contraseñas utilizadas anteriormente.
                </small>
                <input type="hidden" id="idusuario" value="{{idusuario}}">
            </div>
        </div>
    </form>
</div>


{{data}}

<script language="javascript" >

    $(document).ready( function () {
        $('.pass-form-show').hide();
        $('.password-policy').hide();
    });

    function showPassForm()
    {
        $('.pass-form-show').show();
        $('.pass-form-hide').hide();
        $('#oldpassword').focus();
    }

    function hidePassForm()
    {
        $('.pass-form-show').hide();
        $('.pass-form-hide').show();
    }

    function grabarPassword()
    {
        if ($('#password').val() != $('#confpass').val())
        {
            $('#message-error').html('<p>Los campos <strong>Nuevo Password</strong> y la <strong>Confirmar Password</strong> deben ser identicos.</p>');
            $('#message-error').show();
        }
        else
        {
            $('#message-error').html('');
            $('#message-error').hide();            
            CtrlAjax.sendCtrl("usr","usr","grabarPassword");
        }
        
    }

</script>
