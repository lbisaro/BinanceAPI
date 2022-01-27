{{divPop}}
{{actionBar}}

{{tabs}}
{{hidden}}
<div id="PopUpContainer" ></div>

<!-- TradingView Widget BEGIN -->
<div class="container">
    <div class="row">
        <div class="col">
            {{data}}            
        </div>
        <div class="col">
            <!-- TradingView Widget BEGIN -->
            <div class="tradingview-widget-container">
              <div class="tradingview-widget-container__widget"></div>
              <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
              {
              "interval": "5m",
              "width": "425",
              "isTransparent": false,
              "height": "450",
              "symbol": "BINANCE:{{symbol}}",
              "showIntervalTabs": true,
              "locale": "es",
              "colorTheme": "light"
            }
              </script>
            </div>
            <!-- TradingView Widget END -->
        </div>
    </div>   
</div>

</div>
<!-- TradingView Widget END -->
<script language="javascript" >

    $(document).ready( function () {

    });


    //CtrlAjax.sendCtrl("mod","ctrl","act");

</script>
