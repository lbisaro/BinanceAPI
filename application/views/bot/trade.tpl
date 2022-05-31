<style type="text/css">
    
    #tbl_orders {
        font-size: 0.9em;
    }

    #tbl_orders tbody tr td {
        padding: 2px 3px 2px 3px;
    }
    
</style>

<div class="container">
    <div class="row">
        <div class="col">
            <h4 class="text-info">{{symbol}}</h4>
        </div>
    </div>
    <div class="row">
        <div class="col" id="info">
            <h5 class="text-info">Billetera</h5>
            {{info}}
        </div>
        <div class="col" id="makeOrder">
            <h5 class="text-info">Ejecutar Orden</h5>
            <div class="container">            
                <div class="input-group">
                    <select id="op_side" class="form-control" onchange="refreshForm()">
                        <option value="buy" class="text-success" >Compra</option>
                        <option value="sell" class="text-danger" >Venta</option>
                    </select>
                </div>
                <div class="form-group">
                    <select id="op_type" class="form-control" onchange="refreshForm()">
                        <option value="limit">Limit</option>
                        <option value="market">Market</option>
                    </select>
                </div>
                <div class="form-group" id="input_op_price">
                    <label for="op_price">Precio</label>
                    <input type="text" class="form-control" id="op_price"  onchange="refreshForm()">
                </div>
                <div class="form-group">
                    <label for="op_qty">Unidades <b id="unitsInUsd" class="text-info"></b></label>
                    <input type="text" class="form-control" id="op_qty"  onchange="refreshForm()">
                </div>
                <div class="form-group">
                    <button id="op_btn" class="btn btn-block" onclick="ejecutarOrden()">Ejecutar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="orders">
    {{orders}}
</div>
{{divPop}}
{{actionBar}}
{{data}}
{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>

<script language="javascript" >

    $(document).ready( function () {
        refreshForm();
        $('#op_price').val(lunabusdPrice);
        
    });
    var lunabusdPrice = ({{lunabusdPrice}}+0);

    function refreshForm()
    {
        var op_type = $('#op_type option:selected').val();
        var op_side = $('#op_side option:selected').val();
        var op_price = $('#op_price').val();
        var op_qty = $('#op_qty').val();

        
        if (op_type == 'market')
        {
            $('#input_op_price').hide();
        }
        else
        {
            $('#input_op_price').show();
        }
        
        if (op_side == 'buy')
        {
            $('#op_btn').attr('class','btn btn-block btn-success');
            $('#op_btn').html('COMPRAR');
        }
        else
        {
            $('#op_btn').attr('class','btn btn-block btn-danger');
            $('#op_btn').html('VENDER');
        }

        price = (op_price>0?op_price:lunabusdPrice);
        htmlUnitsInUsd = '';
        if (op_qty>0 && price>0)
            htmlUnitsInUsd = ' ( USD '+toDec(price*op_qty)+' '+(op_type=='market'?' aprox.':'')+')';
        $('#unitsInUsd').html(htmlUnitsInUsd);
    }

    function ejecutarOrden()
    {
        var op_type = $('#op_type option:selected').val();
        var op_side = $('#op_side option:selected').val();
        var op_qty = $('#op_qty').val();
        var op_price = $('#op_price').val();

        err='';
        if (op_qty<=0)
            err = 'Se debe especificar una cantidad de unidades';
        if (op_type=='limit' && op_price<=0)
            err += (err?"\n":"")+'Se debe especificar el precio';

        if (!err)
        {
            if (confirm('Desea registar la orden '+(op_side+'-'+op_type).toUpperCase()+' en Binance?'))
            {
                prms = 'op_type='+op_type;
                prms += '&op_side='+op_side;
                prms += '&op_qty='+op_qty;
                if (op_type=='limit')
                    prms += '&op_price='+op_price;

                CtrlAjax.sendCtrl("app","bot","lunabusdOrder",prms);
                
            }
        }
        else
        {
            alert("ERROR!"+"\n"+err);
        }
    }

</script>
