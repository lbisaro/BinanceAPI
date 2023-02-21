<div class="container">
    <h2 class="text-info">BSBots Activos</h2>
    <table class="table table-hover table-sm" >
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

<div class="container">
    <h2 class="text-info">Pagos</h2>
    {{pagos}}
</div>

<script language="javascript" >

    $(document).ready( function () {

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
