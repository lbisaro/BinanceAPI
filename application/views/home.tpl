<div class="container col-10 pt-3">
    <form id="perfil">
        <h5 class="bd-title">
            <span class="glyphicon glyphicon-user"></span> Usuario
        </h5>
        <div class="form-group row">
            <div class="col-sm-10 my-auto">
                <strong>{{ayn}}</strong>
            </div>
        </div>    

        <h5 class="bd-title">
            <span class="glyphicon glyphicon-heart"></span> Autodiagnostico  
        </h5>

        <div class="form-group row">
            <label for="mail" class="col-sm-2 col-form-label">Ultimo autodiagnostico realizado</label>
            <div class="col-sm-10 my-auto">
                <div class="alert {{btnAutodiagnosticoClassMsg}}" role="alert">
                    {{ultimoAutodiagnostico}}
                </div>
            </div>
            <div class="col-sm-10 my-auto">
                <button type="button" class="btn {{btnAutodiagnosticoClassBtn}} btn-block" onclick="autodiagnostico();">{{btnAutodiagnosticoLabel}}</button>
            </div>

        </div>
    </form>
</div>


{{data}}

<script language="javascript" >

    $(document).ready( function () {

    });

</script>
