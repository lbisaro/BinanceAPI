<style type="text/css">
    .list-group-item {
        cursor: pointer;
    }
</style>



<div class="container-fluid">
    <div class="row">
        <div class="col">
            <h3>Status</h3>
            <code class="text-primary">{{status}}</code>
        </div>
        <div class="col-9">
            <h3>Contenido del log</h3>
            <div class="row">
                <div class="col-4">
                    <label for="idusuario">Usuario</label>
                </div>
                <div class="col-4">
                    <label for="symbol">Moneda</label>
                </div>
                <div class="col-4">
                    <label for="idoperacion">Operacion</label>
                </div>
            </div>  
            <div class="row">
                <div class="col-4">
                    <select id="idusuario" class="form-control form-control-sm" onchange="show();">
                        <option value="0">Todos</option>
                        {{idusuario_options}}
                    </select>
                </div>
                <div class="col-4">
                    <select id="symbol" class="form-control form-control-sm" onchange="show();">
                        <option value="0">Todos</option>
                        {{symbol_options}}
                    </select>
                </div>
                <div class="col-4">
                    <select id="idoperacion" class="form-control form-control-sm" onchange="show();">
                        <option value="0">Todas</option>
                        {{idoperacion_options}}
                    </select>
                </div>
            </div>    
        </div>
    </div>
    <div class="row">
        <div class="col">
            <h3>Archivos</h3>
            {{files}}
        </div>
        <div class="col-9" id="contenido">
            <div class="alert alert-light" role="alert">
                Seleccione un archivo de la lista para ver el contenido.    
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="file"/>
<script type="text/javascript">
    
    $(document).ready( function () {
        $('.list-group-item').each(function () {
            $(this).on('click', function (event) {
              event.preventDefault();
            })
        });
    });


    function show(file)
    {
        if (file)
            $('#file').val(file);
        CtrlAjax.sendCtrl("app","bot","showLog");
    }

    function activate(file)
    {
        $('.list-group-item').each(function () {
            var id = $(this).attr('id');
            if (id == file)
                $(this).addClass('active');
            else
                $(this).removeClass('active');
        });
    }

</script>