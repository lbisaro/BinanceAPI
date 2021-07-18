<!doctype html>
<html lang="es">
  <head>
    <meta charset="iso-8859-1">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{title}}">
    <meta name="author" content="Leonardo Bisaro">
    <link rel="icon" href="public/images/favicon.ico?v4">

    <title>{{title}}</title>

    {FOR#head#
         ENDFOR}

  </head>

  <body class="text-center" onload="{{onloadJs}}">
    
    <div id="loading_msg"  class="ac" style="display:none;">
        <img src="public/images/loading_grey.gif" alt="Cargando ... "/ ><br/>
        <span style="padding-left:5px; color:#444547; font-size: 14px;" id="ldng_txt"></span>
    </div>

    <form class="form-signin" action="index.php" name="mainForm" id="mainForm" onsubmit="return false;" >

