<!doctype html>
<html lang="es">
    <head>
        <meta charset="iso-8859-1">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="">
        <meta name="author" content="Leonardo Bisaro">
        <meta name="generator" content="Leonardo Bisaro">
        <link rel="icon" href="public/images/favicon.ico?v4">
        <meta http-equiv="Content-Type" content="text/html; charset={{charset}}" />

        <title>{{title}}</title>

        {FOR#head#
         ENDFOR}

        <style>
            div.main {
              margin-top: 80px;
              margin-bottom: 40px;
            }
        </style>
    </head>

  <body>
    <script type="text/javascript">
        $(document).ready( function () {
        $('table.DG tfoot tr td').each(function () {
            $(this).css('font-weight','bolder');
        })
        {{onloadJs}}


            /** Setear en php.ini los siguientes parametros
            html_errors = On
            error_prepend_string = "<div class='php_error'>"
            error_append_string = "</div>"
            */

            var php_errors = $('.php_error');
            if (php_errors.length>0)
            {
                var php_e = '';
                php_errors.each(function() {
                    php_e += '<p>'+$(this).text()+'</p>';
                    $(this).remove();
                    
                });

                var php_e_html = `<div id="php_e_modal" class="modal" tabindex="-1">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title">PHP Debugging</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                        </button>
                      </div>
                      <div class="modal-body">
                        <code>
                        `+php_e+`
                        </code>
                      </div>
                    </div>
                  </div>
                </div>`;
                $('body').append(php_e_html);  
                $('#php_e_modal').modal({show:true});              
                
            }
        });
    </script>
    <div class="main">
        <form>
            <div class="container-flex">
                
    <!-- end: header.tpl-->
