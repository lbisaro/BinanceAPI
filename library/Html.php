<?php
/**
 * Clase utilizada para generar tags HTML.
 *
 * @package myLibrary
 */
class Html
{
    /**
    * Devuelve un tag html input, armado en base a los parametros recibidos.
    *
    * <ul>
    * <li>En caso de enviar atributos en el array attr, cuyo key sea: name, type
    * o value, no seran asignados, ya que se respetara lo enviado en los
    * parametros de la funcion.
    * <li>En caso de enviar el atributo id este será asignado tal cual se enviar,
    * y en caso contrario el id sera igual al parametro idNombre.
    * </ul>
    *
    * @param string $idNombre -> name y id del input. -El Id puede ser reemplazado si se envia dentro de attr()
    * @param string $value -> value por default del input
    * @param string $type -> valor por default del input
    * @param array  $attr -> atributos del input puede ser Array (nombre=>dato)
    * @return string INPUT
    */
    static function getTagInput($idNombre, $value='', $type='text', $attrPrm=array())
    {
        /**
        * Agrega al array de atributos los parametros recibidos,
        * y de esta manera se evita que se agreguen atributos repetidos,
        * dado si existia anteriormente de esta forma se sobreescribe el key del array
        */

        if (empty($type))
            $type = 'text';

        if (empty($idNombre))
            $idNombre = $type.'_'.microtime().'_';

        $attr=array();
        $attr['type'] = strtolower($type);

        if ($attrPrm['maxlength'] && !$attrPrm['size'])
            $attr['size'] = $attrPrm['maxlength'];

        if (is_array($attrPrm))
            foreach ($attrPrm as $k => $v)
                if (strtolower($k) != 'type' )
                    $attr[strtolower($k)] = $v;
        if (!empty($value))
            $attr['value'] = $value;
        else
            $attr['value'] = '';

        if (!$attr['id'])
            $attr['id'] = $idNombre;

        if (!$attr['name'])
            if (!$attr['id'])
                $attr['name'] = $idNombre;
            else
                $attr['name'] = $attr['id'];

        if ($attr['type'] == 'checkbox' || $attr['type'] == 'radio')
            $attr['style'] = 'border:0px; background-color:transparent; '.$attr['style'];

        if (($attr['type'] == 'checkbox') && $value)
            $attr['checked'] = ' CHECKED ';

        if ($attr['disabled'])
            $attr['class'] .= ' disabled';

        if (!empty($attr['help']) && empty($attr['title']))
            $attr['title'] = $attr['help'];


        /**
        * Este parametro genera que al presionar [ENTER]
        * en un tag INPUT, pase el foco al proximo elemento del formulario
        *
        * @see functions.js
        */
        if (empty($attr['disabled']) && $attr['type'] != 'hidden'  && $attr['type'] != 'textarea')
            $attr['onkeypress'] = 'if (event.keyCode == 13) {goNextElement(this); return false;} '.(!empty($attr['onkeypress'])?$attr['onkeypress']:'');
        if($attr['type']=='textarea')
        {
            $attr['rows'] = ($attr['rows']? $attr['rows']:'4');
            $attr['cols'] = ($attr['cols']? $attr['cols']:'60');

            $input = '<textarea ';
            foreach($attr as $key => $val)
            {
                if (strtolower($key) != 'value')
                    $input .= strtolower($key).'="'.$val.'"  ';
            }
            $input .= ' >';
            $input .= $attr['value'];
            $input .= '</textarea>';

            return $input;
        }

        $input = '<input ';
        foreach($attr as $key=> $val)
        {
            if (strtolower($key)=='autocomplete')
                $input .= strtolower($key).'="'.strtolower(trim($val)).'"  ';
            else
                $input .= strtolower($key).'="'.$val.'"  ';
        }
        $input .= ' />';

        return $input;
    }

    /**
    * Devuelve un tag html img, armado en base a los parametros recibidos.
    */
    static function getTagImg($idNombre, $imgPath, $attr=array())
    {
        $attr['src']   = $imgPath;

        if (!$attr['id'])
            $attr['id'] = $idNombre;

        $img = '<img ';

        foreach($attr as $key => $val)
            $img .= $key.' = "'.$val.'"  ';

        $img .= ' />';

        return $img;
    }

    /**
     * Devuelve un tag html input formateado para ingresar fechas,
     * armado en base a los parametros recibidos, agregando una imagen CALENDAR
     * mediante la cual se puede hacer click y seleccionar la fecha desde un dhtml.calendar
     *
     * <b>NOTA:</b>Dado que esta funcion extiende de Html::getTagInput(),
     * los parametros que recibe funcionan del mismo modo.
     */
    static function getTagInputFecha($idNombre , $value='' , $type='text' , $attr=array())
    {
        $attr['size'] = '10';
        $attr['maxlength'] = '10';
        $attr['onchange'] = 'value = mkFecha(this.value);';

        $input = Html::getTagInput($idNombre, $value , 'text' , $attr);
        $input .= ' <img src="'.IMG_PATH.'calendar.gif" style="cursor:pointer;" '.
                  ' onclick="mousePos(event);displayCalendar(document.forms[0].'.$idNombre.','."'".'dd/mm/yyyy'."'".',document.forms[0].'.$idNombre.',false,false,false);" />';
        return $input;
    }

    /**
     * Devuelve un tag html input formateado para ingresar fechas,
     * armado en base a los parametros recibidos, agregando una imagen CALENDAR
     * mediante la cual se puede hacer click y seleccionar la fecha desde un dhtml.calendar
     *
     * <b>NOTA:</b>Dado que esta funcion extiende de Html::getTagInput(),
     * los parametros que recibe funcionan del mismo modo.
     */
    static function getTagInputHora($idNombre , $value='' , $type='text' , $attr=array())
    {
        $attr['size'] = '5';
        $attr['maxlength'] = '5';
        $attr['onchange'] = 'value = mkHora(this.value);';

        $input = Html::getTagInput($idNombre, $value , 'text' , $attr);
        return $input;
    }

    /**
     * Devuelve un tag html input formateado para ingresar direcciones de e-mail,
     * armado en base a los parametros recibidos.
     *
     * <b>NOTA:</b>Dado que esta funcion extiende de Html::getTagInput(),
     * los parametros que recibe funcionan del mismo modo.
     */
    static function getTagInputMail($idNombre , $value='' , $type='text' , $attr=array())
    {
        $attr['onblur'] = "if(this.value && !check_email(this.value)) { alert('Debe ingresar una direccion de mail valida.'); this.value=''; }";
        if (!isset($attr['size']))
            $attr['size'] = '40';

        $input = Html::getTagInput($idNombre, $value , 'text' , $attr);
        return $input;
    }

    /**
     * Devuelve un tag html input formateado para ingresar direcciones de e-mail,
     * armado en base a los parametros recibidos.
     *
     * <b>NOTA:</b>Dado que esta funcion extiende de Html::getTagInput(),
     * los parametros que recibe funcionan del mismo modo.
     */
    static function getTagInputCuit($idNombre , $value='' , $type='text' , $attr=array())
    {
        $attr['onkeypress'] = "return mkCUIT(this,event,value);";
        $attr['onblur'] = "mkCUIT(this,event,value); if(this.value && !validarCUIT(this.value)){ this.value = ''; alert('CUIT no valido!'); this.focus();}";
        $attr['size'] = '13';
        $attr['maxlength'] = '13';

        $input = Html::getTagInput($idNombre, $value , 'text' , $attr);
        return $input;
    }

    /**
    * Devuelve un tag html select, armado en base a los parametros recibidos.
    * <ul>
    * <li>En caso de enviar atributos en el array attr, cuyo key sea name,
    * no sera asignado, ya que se respetara lo enviado en los parametros de la funcion.
    * <li>En caso de enviar el atributo id este será asignado tal cual se enviar,
    * y en caso contrario el id sera igual al parametro idNombre.
    * </ul>
    *
    * @param array $option
    *
    *   Para un grupo unico de opciones: armar el array con el Value y el Texto
    *   de los tags option, en un array Array (value=>text)
    *   <br>E.G.:
    *   <ul>
    *       <li>$option[1]="Primera opcion";
    *       <li>$option[2]="Segunda opcion";
    *       <li>$option[3]="Tercera opcion";
    *   </ul>
    *
    *   Para opciones separadas en grupos, armar el array como un array cuyo key
    *   sea el nombre del grupo, y el value tenga dentrotro array con el Value y el Texto
    *   de los tags option, en un array Array (value=>text)
    *   <br>E.G.:
    *   <ul>
    *       <li>$option['grupo1'][1]="Primera opcion grupo 1";
    *       <li>$option['grupo1'][2]="Segunda opcion grupo 1";
    *       <li>$option['grupo1'][3]="Tercera opcion grupo 1";
    *       <li>$option['grupo2'][14]="Primera opcion grupo 2";
    *       <li>$option['grupo2'][15]="Segunda opcion grupo 2";
    *   </ul>
    *
    *
    * @param string $idNombre
    * @param array $attr -> atributos del input puede ser Array (nombre=>dato)
    * @param string $selectedValue -> valor a ser marcado como SELECTED
    * @return string SELECT
    */
    static function getTagSelect($idNombre, $option = array(), $selectedValue = null, $attr = array())
    {
        if (!$attr['id'])
            $attr['id'] = $idNombre;
        if (!$attr['size'])
            $attr['size'] = "1";
        if (!$attr['name'])
            if (!$attr['id'])
                $attr['name'] = $idNombre;
            else
                $attr['name'] = $attr['id'];
        /**
        * Este parametro genera que al presionar [ENTER]
        * en un tag INPUT, pase el foco al proximo elemento del formulario
        *
        * Ver functions.js
        */
        $attr['onkeypress'] .= 'if (event.keyCode == 13) {goNextElement(this); return false;}';

        $select = '<select ';

        foreach ($attr as $key => $value)
            $select .= ' '.$key.'="'.$value.'" ';

        $select .= ' >';

        foreach($option as $value => $text)
        {
            if (is_array($text))
            {
                if ($value)
                    $select .= '<optgroup label="'.$value.'">';
                foreach ($text as $valueGrp => $textGrp)
                    $select .= '<option value="'.$valueGrp.'" '.($valueGrp == $selectedValue?' SELECTED ':'').'>'.$textGrp.'</option>';
            }
            else
            {
                $select .= '<option value="'.$value.'" '.($value == $selectedValue?' SELECTED ':'').'>'.$text.'</option>';
            }
        }
        $select .= '</select>';

        return $select;
    }

    /**
    * Devuelve un tag html input RADIO, armado en base a los parametros recibidos.
    * <ul>
    * <li>En caso de enviar atributos en el array attr, cuyo key sea name,
    * no sera asignado, ya que se respetara lo enviado en los parametros de la funcion.
    * <li>En caso de enviar el atributo id este será asignado tal cual se enviar,
    * y en caso contrario el id sera igual al parametro idNombre.
    * </ul>
    *
    * @param array $option
    *
    *   Para un grupo unico de opciones: armar el array con el Value y el Texto
    *   de los tags option, en un array Array (value=>text)
    *   <br>E.G.:
    *   <ul>
    *       <li>$option[1]="Primera opcion";
    *       <li>$option[2]="Segunda opcion";
    *       <li>$option[3]="Tercera opcion";
    *   </ul>
    *
    * @param string $idNombre
    * @param array $attr -> atributos del input puede ser Array (nombre=>dato)
    * @param string $selectedValue -> valor a ser marcado como CHECKED
    * @return string SELECT
    */
    static function getTagRadio($idNombre, $option = array(),$selectedValue = '** NA **',$attr = array())
    {
        if (!$attr['id'])
            $attr['id'] = $idNombre;
        if (!$attr['name'])
            if (!$attr['id'])
                $attr['name'] = $idNombre;
            else
                $attr['name'] = $attr['id'];

        if (!$attr['size'])
            $attr['size'] = "1";

        /**
        * Este parametro genera que al presionar [ENTER]
        * en un tag INPUT, pase el foco al proximo elemento del formulario
        *
        * Ver functions.js
        */
        $attr['onkeypress'] .= 'if (event.keyCode == 13) {goNextElement(this); return false;}';

        $radioSelect = '
        <!-- Radio Select ['.$attr['name'].'] --> ';

        foreach($option as $value => $text)
        {
            if ($value == $selectedValue)
                $attr['checked'] = ' CHECKED ';
            else
                unset($attr['checked']);

            $radioSelect .= '
            <span class="radioSelect">'.self::getTagInput($idNombre,$value,'radio',$attr).' '.$text.'</span>';

        }
        $radioSelect .= '
        <!-- FIN - Radio Select ['.$attr['name'].'] --> ';

        return $radioSelect;
    }

    /**
    * Devuelve un html button formateado por defoult para el actionBar
    * se le debe pasar el texto del boton la accion del onclick
    * la imagen es opcional como los atributos.
    *
    * Mediante el parametro $attr, se pueden definir lo datos necesarios
    * para ejecutar el ajax:
    * <ul>
    * <li>$attr['ajaxCtrl'] -> Nombre del AjaxController en el que se encuentra el metodo a ejecutar.</li>
    * <li>$attr['ajaxAct'] -> Nombre del metodo a ejecutar definido en el ajaxCtrl</li>
    * <li>$attr['ajaxScript'] -> [Default indexAjax.php] ruta y nombre del script PHP que se debe ejecutar.</li>
    * <li>$attr['ajaxAddUrl'] -> [Ej.: '&nombre=Jose&edad=25'] variables a enviar por GET al script ajaxScript</li>
    * </ul>
    *
    * @param (string) $texto (el nombre/value del botton)
    * @param (string) $accion (la funcion del onclick)
    * @param (string) $img (ruta de la imagen a colocar) - por default:null
    * @param (string) $attr atributos del button debe ser Array (nombre=>dato)
    * @return html button
    */
    static function getTagButton($texto, $accion, $img=null, $attr=array())
    {

        if ($img)
            $attr['class'] .= ($attr['class'] ? ' ': '' ).'html_buttonImg';
        else
            $attr['class'] .= ($attr['class'] ? ' ': '' ).'html_button';

        if (strpos($attr['class'],'noPrint') === false)
            $attr['class'] .= ($attr['class'] ? ' ': '' ).'noPrint';

        $attr['onclick'] .= $accion;

        if (!$attr['id'])
            $attr['id'] = 'btn_'.$texto;

        //Formateando ID
        $attr['id'] = str_replace(array(' ','[',']'), '_', $attr['id']);
        $attr['id'] = str_replace('__', '_',$attr['id']);

        foreach($attr as $key=>$val)
            $button .= $key.' = "'.$val.'"  ';

        return '<button '.$button.' title="'.$texto.'" >
                    '.(!empty($img)?'<img src="'.$img.'" /> ':$texto).'
                </button>';
    }

   /**
    * Devuelve un tag html input formateado para ingresar datos, que seran
    * buscados mediante un cuadro similar al autocompletar.
    *
    * <b>NOTA:</b>Dado que esta funcion utiliza Html::getTagInput(),
    * los parametros que recibe funcionan del mismo modo.
    *
    * Se deben incluir los scripts ajax.js y ajaxSugesst.js al codigo HTML
    *
    * @see public/scripts/ajax.js
    * @see public/scripts/ajaxSuggest.js
    *
    * @param string $idNombre -> name y id del input. -El Id puede ser reemplazado si se envia dentro de attr()
    * @param string $urlAjax -> url mediante la cual se deben buscar el value del campo suggest
    * @param string $value -> value por default del input
    * @param string $type -> valor por default del input
    * @param array  $attr -> atributos del input puede ser Array (nombre=>dato)
    * @return html INPUT+codigo necesario
    */
    static function getTagInputSuggest($idNombre , $urlAjax, $value='' , $type='text' , $attr=array())
    {
        return self::getTagInputSuggestAjax($idNombre , $urlAjax, $value='' , $type='text' , $attr);
    }

    static function getTagInputSuggestAjax($idNombre , $urlAjax, $value='' , $type='text' , $attr=array())
    {
        $attr['autocomplete']="off";
        if (!isset($attr['size']))
            $attr['size'] = '50';

        if ($attr['onblur'])
            unset($attr['onblur']);

        if ($attr['help'] && !$attr['placeholder'])
            $attr['placeholder'] = $attr['help'];


        $attr['onkeyup']="suggestOnKeyUp('".$idNombre."');";

        $attr['class'] .= ' suggest';

        $script = '
        <script language="javascript" type="text/javascript" >
            var urlAjax'.$idNombre.' = "'.$urlAjax.'";
            var img_path_'.$idNombre.' = "'.IMG_PATH.'";
        </script>
        ';

        $ret .= '<div id="suggest_'.$idNombre.'_content" style="z-index:0;">
                    <table style="padding:0px;margin:0px;">
                        <tr >
                            <td  style="border:0px; padding:0px;margin:0px;">
                                '.Html::getTagInput($idNombre , $value , '', $attr).'
                                <div class="suggest_content" id="suggest_content" style="position:relative;" >
                                    <div class="suggest_options" id="suggest_'.$idNombre.'_list" >
                                </div>
                            </td>
                            <td width="90%" style="border:0px; padding:0px;margin:0px;padding-top:0px;padding-left:5px;">
                                '.Html::getTagImg('suggest_'.$idNombre.'_img', IMG_PATH.'suggest_icon.gif', array('title'=>'suggest','onClick'=>'gebId(\''.$idNombre.'\').focus();suggestSend(\''.$idNombre.'\')',array('height'=>'50px','border'=>'1px solid #ffffff'))).'
                            </td>
                        </tr>
                    </table>
                    '.$script.'
                 </div>';

        return $ret;

    }
}
?>
