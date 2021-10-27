
    <!-- start: footer.tpl-->
            </div>
        </form>
    </div> <!-- main -->

    <div id="loading_msg"  class="ac" style="display:none;">
        <img src="public/images/loading_grey.gif" alt="Cargando ... "/ ><br/>
        <span style="padding-left:5px; color:#444547; font-size: 14px;" id="ldng_txt"></span>
    </div>

    <script language="javascript" type="text/javascript">
        // End scripts
        {{jsScripts}}

        $(document).ready( function () {
            $("form").each( function () {
                $(this).submit(function(e){
                    e.preventDefault();
                });
            });
        });
        
    </script>

</body>

</html>