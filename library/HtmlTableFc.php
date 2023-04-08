<?php
include_once(LIB_PATH."Html.php");
/**
 * Clase utilizada para generar Tablas HTML con formato FC (Ficha).
 *
 * @package myLibrary
 */
class HtmlTableFc
{

    protected $id;
    protected $width;
    protected $colWidth = array();
    protected $caption;
    protected $rows = array();

    protected $qtyCols = 0;

    function __Construct($id=null,$width='100%')
    {
        $this->id    = $id;
        $this->width = $width;
    }

    function reset()
    {
        $this->id=null;
        $this->width=null;
        $this->colWidth = array();
        $this->caption=null;
        $this->rows = array();
        $this->qtyCols = 0;
    }

    function setId($id)
    {
        $this->id    = $id;
    }

    function setWidth($width)
    {
        $this->width = $width;
    }
    

    function setCaption($caption)
    {
        $this->caption = $caption;
    }

    /**
     * Mediante la funcion se setea el width de las columnas que forman
     * la tabla.
     *
     * @param array $aWidth -> array de strings con el width de cada columna
     */
    function setColWidth($aWidth)
    {
        $this->colWidth = $aWidth;
    }

    /**
     * Agrega filas y sus propiedades a la tabla.
     *
     * @param string $aData  -> Array con la cantidad de datos a poner en cada columna
     * @param string $class  -> Nombre del atributo class del objeto FC.td de cada elemento de la fila
     * @param string $height -> [% px em] establece la propiedad style.height de la fila
     * @param string $valign -> [top - middle - bottom] Establece la propiedad style.vertical-align de la fila
     * @param string $id     -> Establece el id del objeto DG.tr correspondiente a la fila
     */
    function addRow($aData,$class=null,$height='25px',$valign='top',$id=null)
    {
        if (empty($aData))
            return false;

        if (!is_array($aData))
            $aData = array($aData);

        /** Se agrega este filtro para que los campos devueltos por el model que llegan en un array [label,data,id]
          * sean mostrados en dos columnas tipo TH y TD, ambas con el ID correspondiente al campo.
          */
        if (count($aData) == 3 && isset($aData['label']) && isset($aData['data']) && isset($aData['id']))
        {
            if (!$id)
                $id = $aData['id'];
            unset($aData['id']);
        }
        $rowNum = count($this->rows)+1;

        $qtyCols = count($aData);
        if ($qtyCols > $this->qtyCols)
            $this->qtyCols = $qtyCols;


        $this->rows[$rowNum]['aData']  = $aData;
        $this->rows[$rowNum]['class']  = $class;
        $this->rows[$rowNum]['height'] = $height;
        $this->rows[$rowNum]['valign'] = $valign;
        $this->rows[$rowNum]['id']     = $id;
    }

    function get()
    {
        if ($this->qtyCols < 1)
            if (count($this->rows) > 0)
                foreach ($this->rows as $row)
                    if ($this->qtyCols < count($row['aData']))
                        $this->qtyCols = count($row['aData']);


        $caption = '';
        if ($this->caption)
            $caption = '<h4 class="text-info table_dg_caption">'.$this->caption.'</h4>';

        $rows = '';
        $rowType = 'th';
        if (count($this->rows)>0)
        {
            foreach ($this->rows as $row)
            {
                $id = $trStyle = $height = $valign = $class = '';
                if ($row['id'])
                    $id .= ' id="'.$row['id'].'" ';
                if ($row['height'])
                    $height .= ' height: '.$row['height'].'; ';
                if ($row['valign'])
                    $valign .= ' vertical-align: '.$row['valign'].'; ';
                if ($row['class'])
                    $class .= ' class="'.$row['class'].'" ';
                if ($height)
                    $trStyle .= ' style="'.$height.'" ';

                $i=1;
                $rows .= '<tr '.$id.' '.$class.' '.$trStyle.' >';
                foreach ($row['aData'] as $data)
                {
                    $tdId = $tdStyle = '';

                    $colspan='';
                    if ($i == count($row['aData']) && count($row['aData']) < $this->qtyCols)
                        $colspan = ' colspan="'.($this->qtyCols - ($i-1)).'"';

                    if ($i == count($row['aData'])) // El ultimo td del tr no debe ser th
                         $rowType = 'td';

                    if ($row['id'])
                        $tdId .= ' id="'.$row['id'].'_'.$i.'" ';
                    if ($valign)
                        $tdStyle .= ' '.$valign.' ';
                    if ($height)
                        $tdStyle .= ' '.$height.' ';
                    if ($this->colWidth[$i-1] && $i != count($row['aData']))
                        $tdStyle .= ' width: '.$this->colWidth[$i-1].'; ';

                    if ($tdStyle)
                        $tdStyle = ' style="'.$tdStyle.'" ';
                    $rows .= '<'.$rowType.' '.$tdId.' '.$class.' '.$tdStyle.' '.$colspan.'>'.($data?$data:'&nbsp;').'</'.$rowType.'>';

                    $i++;
                    if ($rowType == 'th')
                        $rowType = 'td';
                    else
                        $rowType = 'th';



                }
                $rows .= '</tr>';
            }
        }

        $table = '';
        if ($caption || $rows)
        {
            $table = '<div class="container">';
            $table .= $caption;
            $table .= '<table class="FC table " '.
                            ($this->id?' id="'.$this->id.'" ':'').' '.
                            ($this->width?' style="width:'.$this->width.'" ':'').
                            ' >';
            $table .= $header;
            $table .= $rows;
            $table .= '</table>';
            $table .= '</div>';
        }

        return $table;
    }

    function addBtSubmit($onClick,$label='Grabar',$class='submit')
    {
        $attr['onclick'] = "blockButton($(this));";
        $attr['class'] = 'submit f8_button';
        $label .= ' [F8]';
        $this->addRow(array('&nbsp;',Html::getTagButton($label,$onClick,null,$attr)),$class);
    }

    function addSeparator($text,$class='')
    {
        $this->addRow(array($text),'separator'.($class?' '.$class:''));
    }
}
?>
