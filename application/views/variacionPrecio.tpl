<style type="text/css">
    body {
        background-color: #454d55;
    }
</style>

<div class="container-fluid" id="variacion_del_precio">
    <div class="d-flex justify-content-between">
        <div>
            <div id="last_update" class="text-success"></div>  
            <div class="progress">
              <div id="updatePB" class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
            </div>    
        </div>
    </div>
    
    <table class="table table-sm table-dark table-striped" id="tbl_precios">
        <thead>
            <tr class="strong">
                <th>Moneda</th>
                <th class="text-right">Precio USD</th>
                <th class="text-right">% 1m</th>
                <th class="text-right">% 3m</th>
                <th class="text-right">% 5m</th>
                <th class="text-right">% 15m</th>
                <th class="text-right">% 30m</th>
                <th class="text-right">% 1h</th>
                <th class="text-right">% 6h</th>
            </tr>
        </thead>
        <tbody>
            
            <!-- La tabla se completa con info provista por websocket -->
            
        </tbody >
    </table>
</div>
<script>
var updateProgress=0;
var i1,i2;
$(document).ready(function() {
    $('#tbl_precios').tablesorter({ sortList: [[3,1]] });
    readPrecios();
    i1 = setInterval(readPrecios,60000); //Refresca la tabla cada 1 minuto
    i2 = setInterval(function () {
        updateProgress += parseInt(100/60);
        $('#updatePB').css('width',updateProgress+'%');
        $('#updatePB').attr('aria-valuenow',updateProgress);
    },1000);
    
});

function readPrecios()
{
    updateProgress=0;
    $.getJSON( 'app.CriptoAjax.variacionPrecio+', function( data ) {
        if (data.tickers)
        {
            var tbody = $('#tbl_precios tbody');
            tbody.html('');
            $('#last_update').html(`Actualizado <strong>${data.updatedStr}</strong>`);
            if (data.tickers)
            {
                $.each(data.tickers, function(i, ticker) {
                    tbody.append(`
                        <tr id="ticker_${ticker.tickerid}">
                            <td class="fr"><a href="https://www.binance.com/es/trade/${ticker.name}_USDT?type=spot" class="badge badge-warning" style="width: 50px" target="_blank">
                                ${ticker.name}</a>
                                <!-- 
                                &nbsp;<span style="cursor:pointer;" class="glyphicon glyphicon-random" onclick="showChart('${ticker.tickerid}')"></span>
                                &nbsp;<span style="cursor:pointer;" class="glyphicon ${(!ticker.fav ? 'glyphicon-star-empty text-secondary' : 'glyphicon glyphicon-star text-warning')}"  onclick="toogleFav('${ticker.tickerid}')"></span>
                                -->
                            </td>
                            <td class="text-right">
                                ${(ticker.price?ticker.price:'')}</td>
                            <td class="text-right ${(ticker.perc_1m>0?'text-success':'text-danger')}">
                                ${(ticker.perc_1m?ticker.perc_1m:'')}</td>
                            <td class="text-right ${(ticker.perc_3m>0?'text-success':'text-danger')}">
                                ${(ticker.perc_3m?ticker.perc_3m:'')}</td>
                            <td class="text-right ${(ticker.perc_5m>0?'text-success':'text-danger')}">
                                ${(ticker.perc_5m?ticker.perc_5m:'')}</td>
                            <td class="text-right ${(ticker.perc_15m>0?'text-success':'text-danger')}">
                                ${(ticker.perc_15m?ticker.perc_15m:'')}</td>
                            <td class="text-right ${(ticker.perc_30m>0?'text-success':'text-danger')}">
                                ${(ticker.perc_30m?ticker.perc_30m:'')}</td>
                            <td class="text-right ${(ticker.perc_1h>0?'text-success':'text-danger')}">
                                ${(ticker.perc_1h?ticker.perc_1h:'')}</td>
                            <td class="text-right ${(ticker.perc_6h>0?'text-success':'text-danger')}">
                                ${(ticker.perc_6h?ticker.perc_6h:'')}</td>
                        </tr>`);
                });
            }
            else
            {
                $('#tbl_precios tbody').html('<tr><td colspan="7"><div class="alert alert-warning" role="alert">No hay registros disponibles.</div></td></tr>');
            }
            $("#tbl_precios").trigger("update");
        }
    });
}
</script>