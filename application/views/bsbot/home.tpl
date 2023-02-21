<div class="container">
    <h4 class="text-info">BSBots Activos</h4>
    <table class="DG table table-hover table-sm" >
        <thead>
            <tr>
                <th>Fecha Compra</th>
                <th>Tipo de Bot</th>
                <th>Cantidad</th>
                <th>Estado</th>
                <th>Accion</th>
            </tr>
        </thead>
        <tbody>
            {{bots}}
            <tr class="form-group">
                <td>    
                    <input name="fecha" class="form-control form-control-sm" id="fecha" value="{{fecha}}" />
                </td>
                <td>
                    <select name="tipo" id="tipo" class="form-control form-control-sm">
                        <option value="bsbot64">BSBOT 64 dias</option>
                    </select>
                </td>
                <td>    
                    <input name="qty" class="form-control form-control-sm" id="qty" value="" />
                </td>
                <td>
                    &nbsp;
                </td>
                <td>
                    <button class="btn btn-sm btn-success" onclick="addBsbot()">Agregar</button>
                </td>            
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th>{{totalQty}}</th>
                <th>&nbsp;</th>
                <th>&nbsp;</th>
            </tr>        
        </tfoot>
    </table>
</div>

{{pagos}}

<script language="javascript" >

    $(document).ready( function () {
        $('input').keydown(function (e) {
            if (e.keyCode == 13) {
                e.preventDefault();
                return false;
            }
        });
    });

    function addBsbot()
    {
        CtrlAjax.sendCtrl("bsbot","bsbot","add");
    }
    function delBsbot(id)
    {
        CtrlAjax.sendCtrl("bsbot","bsbot","del","id="+id);
    }

</script>
