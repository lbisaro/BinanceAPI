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
        <div class="col-9"><h3>Contenido del log</h3></div>
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
        CtrlAjax.sendCtrl("app","bot","showLog","file="+file);
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