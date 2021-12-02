<?php
include_once(LIB_PATH."Html.php");
/**
 * Clase utilizada para generar Tablas HTML con formato DataGrid.
 *
 * @package myLibrary
 */
class HtmlTableDg
{

    const ROWS_PER_PAGE     = 20;

    const FOOTER_CLASS      = 'footer';
    const SEPARATOR_CLASS   = 'separator';

    const IMG_TOP           = 'GoTop.gif';
    const IMG_TOP_OFF       = 'GoTopOff.gif';
    const IMG_LAST          = 'GoLast.gif';
    const IMG_LAST_OFF      = 'GoLastOff.gif';
    const IMG_NEXT          = 'GoNext.gif';
    const IMG_NEXT_OFF      = 'GoNextOff.gif';
    const IMG_BOTTOM        = 'GoBottom.gif';
    const IMG_BOTTOM_OFF    = 'GoBottomOff.gif';
    const IMG_ORDER_ASC     = 'dg_order_asc.gif';
    const IMG_ORDER_DES     = 'dg_order_des.gif';
    const IMG_GOTOPAGE      = 'GoToPage.gif';

    protected $id;
    protected $width;
    protected $class;
    protected $headers = array();
    protected $caption;
    protected $rows = array();
    protected $colProps = array();
    protected $ajaxCaller;

    protected $qtyCols = 0;

    function __Construct($id=null,$width='100%',$class='DG table table-hover table-striped')
    {
        $this->reset();
        $this->id    = $id;
        $this->width = $width;
        $this->class = $class;
    }

    function setId($id)
    {
        $this->id    = $id;
    }

    function setWidth($width)
    {
        $this->width = $width;
    }

    function reset()
    {
        $this->id = null;
        $this->width = null;
        $this->headers = array();
        $this->caption = null;
        $this->rows = array();
        $this->colProps = array();
        $this->ajaxCaller = null;
        $this->qtyCols = 0;
    }

    function setCaption($caption)
    {
        $this->caption = $caption;
    }

    /**
     * Agrega el encabezado y las propiedades de una columna
     * de la tabla.
     *
     * @param string $header -> Nombre del encabezado de la columna
     * @param string $class  -> Nombre del atributo class del objeto DG.th
     * @param string $width  -> [% px em] establece la propiedad style.width de la columna incluyendo los datos de las filas
     * @param string $align  -> [left - right - center] Establece la propiedad style.text-align de la columna incluyendo los datos de las filas
     * @param string $order  ->
     */
    function addHeader($header,$class=null,$width=null,$align=null,$order=null)
    {
        if (!$header)
            $header = '&nbsp';

        $this->qtyCols++;
        $this->colProps[$this->qtyCols]['width'] = $width;
        $this->colProps[$this->qtyCols]['align'] = $align;
        $this->colProps[$this->qtyCols]['class'] = $class;
        $this->colProps[$this->qtyCols]['order'] = $order;

        $this->headers[$this->qtyCols]           = $header;
    }

    /**
     * Agrega filas y sus propiedades a la tabla.
     *
     * @param string $aData  -> Array con la cantidad de datos a poner en cada columna
     * @param string $class  -> Nombre del atributo class del objeto DG.td de cada elemento de la fila
     * @param string $height -> [% px em] establece la propiedad style.height de la fila
     * @param string $valign -> [top - middle - bottom] Establece la propiedad style.vertical-align de la fila
     * @param string $id     -> Establece el id del objeto DG.tr correspondiente a la fila
     * @param string $trAttr -> Array de atributos que seran asignados al tag <tr> (Ej.: onclick, onmouseover, etc...)
     */
    function addRow($aData,$class=null,$height='25px',$valign='middle',$id=null,$trAttr=null,$isFooterRow=false)
    {
        if (!is_array($aData))
            $aData = array(($aData?$aData:'&nbsp;'));

        $rowNum = count($this->rows)+1;

        $this->rows[$rowNum]['aData']  = $aData;
        $this->rows[$rowNum]['class']  = $class;
        $this->rows[$rowNum]['height'] = $height;
        $this->rows[$rowNum]['valign'] = $valign;
        $this->rows[$rowNum]['id']     = $id;
        $this->rows[$rowNum]['trAttr'] = $trAttr;
        
        $this->rows[$rowNum]['footerRow'] = $isFooterRow;

    }

    function addFooter($aData,$class=self::FOOTER_CLASS,$height='25px',$valign='middle',$id=null,$trAttr=null)
    {
        $this->addRow($aData,$class,$height,$valign,$id,$trAttr,$isFooterRow=true);
    }

    function countRows()
    {
        return count($this->rows);
    }

    function addSeparator($text,$class=self::SEPARATOR_CLASS,$height='30px',$valign='middle',$id=null,$trAttr=null)
    {
        $this->addRow(array($text),$class,$height,$valign,$id,$trAttr);
    }


    function get()
    {
        if ($this->qtyCols < 1)
            if (count($this->rows) > 0)
                foreach ($this->rows as $row)
                    if ($this->qtyCols < count($row['aData']))
                        $this->qtyCols = count($row['aData']);


        $header = '';
        if (count($this->headers) > 0)
        {
            $header .= '<tr>';
            $i=1;
            foreach ($this->headers as $hdr)
            {
                $style = $class = '';
                if ($this->colProps[$i]['width'])
                    $style .= 'width: '.$this->colProps[$i]['width'].'; ';
                if ($this->colProps[$i]['align'])
                    $style .= 'text-align: '.$this->colProps[$i]['align'].'; ';
                if ($style)
                    $style = ' style="'.$style.'" ';

                if ($this->colProps[$i]['class'])
                    $class = ' class="'.$this->colProps[$i]['class'].'" ';

                if ($this->colProps[$i]['order'])
                    $hdr = $this->__getHeaderSetOrder($hdr,$this->colProps[$i]['order']);

                $header .= '<th '.$class.' '.$style.' >'.$hdr.'</th>';
                $i++;
            }
            $header .= '</tr>';
        }

        $caption = '';
        if ($this->caption)
            $caption = '<h2>'.$this->caption.'</h2>';

        $rows = '';
        $footer = '';
        if (count($this->rows)>0)
        {
            foreach ($this->rows as $row)
            {
                $id = $trStyle = $height = $valign = $class = $trAttr = '';
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

                if (is_array($row['trAttr']))
                {
                    foreach ($row['trAttr'] as $prm => $val)
                        $trAttr .= ' '.$prm.'="'.$val.'" ';
                }



                $i=1;

                $tr = '<tr '.$id.' '.$class.' '.$trStyle.' '.$trAttr.'>';
                foreach ($row['aData'] as $data)
                {
                    $tdId = $tdClass = $tdStyle = '';
                    if ($row['id'])
                        $tdId .= ' id="'.$row['id'].'_'.$i.'" ';
                    if ($this->colProps[$i]['align'])
                        $tdStyle .= ' text-align: '.$this->colProps[$i]['align'].'; ';
                    if ($valign)
                        $tdStyle .= ' '.$valign.' ';
                    if ($height)
                        $tdStyle .= ' '.$height.' ';
                    if ($tdStyle)
                        $tdStyle = ' style="'.$tdStyle.'" ';
                    if (strstr($class,'separator') === false  && $this->colProps[$i]['class'])
                        $tdClass = ' class="'.$this->colProps[$i]['class'].' " ';

                    $colspan='';
                    if ($i == count($row['aData']) && count($row['aData']) < $this->qtyCols)
                        $colspan = ' colspan="'.($this->qtyCols - ($i-1)).'" ';

                    $tr .= '<td '.$tdId.' '.$tdClass.' '.$tdStyle.' '.$colspan.' >'.($data?$data:'&nbsp;').'</td>';
                    $i++;
                }
                $tr .= '</tr>';
                
                if ($row['footerRow'])
                    $footer .= $tr;
                else
                    $rows .= $tr;
            }
        }

        $table = '';
        if ($header || $caption || $rows)
        {
            $table = '<div class="container">';
            $table .= $caption;
            $table .= '<table class="'.$this->class.'" '.
                            ($this->id?' id="'.$this->id.'" ':'').' '.
                            ($this->width?' style="width:'.$this->width.'" ':'').
                            ' >';
            $table .= '<thead>'.$header.'</thead>';
            $table .= '<tbody>'.$rows.'</tbody>';
            $table .= '<tfoot>'.$footer.'</tfoot>';
            $table .= '</table>';
            $table .= '</div>';
        }

        return $table;
    }

    public function setOrder($order)
    {
        $this->order = $order;
    }

    public function setAjaxCaller($ajaxUrl)
    {
        $this->ajaxCaller = $ajaxUrl;
    }

    /**
     * Devuelve el HTML con los controles de navegacion entre paginas de la tabla
     *
     * @return HTML
     */
    public function getPageNav($start,$rowsPerPage,$rowsTotal)
    {

        $this->start = $start;
        $this->rowsPerPage = $rowsPerPage;
        $this->rowsTotal = $rowsTotal;
        $order = $this->order;

        $pageTotal = $pageNum = 0;

        if($rowsTotal < 1)
            $pgNum = 0;

        if ($rowsPerPage > 0)
        {
            $pageTotal = ceil($rowsTotal/$rowsPerPage);
            $pageNum = ceil(($start+1)/$rowsPerPage);
        }

        $nextStart = $prevStart = $start;

        if ($pageNum < $pageTotal)
            $nextStart = $pageNum * $rowsPerPage;

        if ($pageNum > 1)
            $prevStart = ($pageNum-2) * $rowsPerPage;

        $pagination['pageNum']=$pageNum;
        $pagination['pageTotal']=$pageTotal;
        $pagination['rowsTotal']=$rowsTotal;

        if ($pageTotal>1)
        {
            /** Boton para ir al inicio */
            if($pageNum > 1)
            {
                $action = 'onclick="DG_setStart_'.$this->id.'(0);xajaxSendForm(\'DG_render_'.$this->id.'\');"';
                $action = "onclick=\"DG_cmd_exec('".$this->id."','setstart','0');\"";
                $bt = $this->__getPageNavBt('DG_navButton_on',$action,'Ir a la primera pagina',IMG_PATH.self::IMG_TOP);
            }
            else
            {
                $bt = $this->__getPageNavBt('DG_navButton_off','','',IMG_PATH.self::IMG_TOP_OFF);
            }
            $pagination['pagTop'].= $bt;

            /** Boton para ir al previo */
            if($pageNum > 1)
            {
                $action = "onclick=\"DG_cmd_exec('".$this->id."','setstart','".$prevStart."');\"";
                $bt = $this->__getPageNavBt('DG_navButton_on',$action,'Ir a la pagina anterior',IMG_PATH.self::IMG_LAST);
            }
            else
            {
                $bt = $this->__getPageNavBt('DG_navButton_off','','',IMG_PATH.self::IMG_LAST_OFF);
            }
            $pagination['pagPrev'].= $bt;


            /** Boton para ir al siguiente */
            if($pageTotal > $pageNum)
            {
                $action = "onclick=\"DG_cmd_exec('".$this->id."','setstart','".$nextStart."');\"";
                $bt = $this->__getPageNavBt('DG_navButton_on',$action,'Ir a la pagina siguiente',IMG_PATH.self::IMG_NEXT);
            }
            else
            {
                $bt = $this->__getPageNavBt('DG_navButton_off','','',IMG_PATH.self::IMG_NEXT_OFF);
            }
            $pagination['pagPrev'].= $bt;

            /** Boton para ir al ultimo */
            if($pageTotal > $pageNum)
            {
                $action = "onclick=\"DG_cmd_exec('".$this->id."','setstart','".(($pageTotal-1)* $rowsPerPage)."');\"";
                $bt = $this->__getPageNavBt('DG_navButton_on',$action,'Ir a la ultima pagina',IMG_PATH.self::IMG_BOTTOM);
            }
            else
            {
                $bt = $this->__getPageNavBt('DG_navButton_off','','',IMG_PATH.self::IMG_BOTTOM_OFF);
            }
            $pagination['pagPrev'].= $bt;


            /** Input para seleccionar el numero de página */
            $okp =  "if (gebId('pageTotal').value && (gebId('pageTotal').value>0) && (gebId('pageTotal').value<=". $pageTotal .")) ";
            $okp .= "{ DG_cmd_exec('".$this->id."','gotopage',gebId('pageTotal').value); } else { if (gebId('pageTotal').value) alert('ERROR: No existe la página '+gebId('pageTotal').value); } ";
            $okp .= " gebId('pageTotal').value='';";
            $pagination['goToPage'] .= '<input id="pageTotal" name="pageTotal" size="3" onchange="'.$okp.'" >';
            $pagination['goToPage'] .= $this->__getPageNavBt('DG_navButton_on','onclick="'.$okp.'"','Ir a la pagina',IMG_PATH.self::IMG_GOTOPAGE);

        }
        if ($rowsTotal>0)
            $pagination['pagina']="Pagina: $pageNum/$pageTotal";

        $pagination['rowsTotal']=$rowsTotal;

        $html .= '
        <div class="DG_pagination" >
            <input type="hidden" id="DG_cmnd_'.$this->id.'"         name="DG_cmnd_'.$this->id.'" />
            <input type="hidden" id="DG_prm_'.$this->id.'"          name="DG_prm_'.$this->id.'" />
            <input type="hidden" id="DG_order_'.$this->id.'"        name="DG_order_'.$this->id.'"        value="'.$order.'"/>
            <input type="hidden" id="DG_start_'.$this->id.'"        name="DG_start_'.$this->id.'"        value="'.$start.'" />
            <input type="hidden" id="DG_rowsPerPage_'.$this->id.'"  name="DG_rowsPerPage_'.$this->id.'"  value="'.$rowsPerPage.'" />
            <input type="hidden" id="DG_rowsTotal_'.$this->id.'"    name="DG_rowsTotal_'.$this->id.'"    value="'.$rowsTotal.'" />
            <input type="hidden" id="DG_ajaxCaller_'.$this->id.'"   name="DG_ajaxCaller_'.$this->id.'"   value="'.$this->ajaxCaller.'"/>
            <table >
                <tr>
                    <td>
                        '.$pagination['pagTop'].'
                        '.$pagination['pagPrev'].'
                        '.$pagination['pagNext'].'
                        '.$pagination['pagBottom'].'
                    </td>
                    <td align="center">
                        '.$pagination['goToPage'].'
                    </td>
                    <td align="right">
                    '.$pagination['pagina'].' | Registros encontrados: '.$pagination['rowsTotal'].'
                    </td>
                </tr>
            </table>
        </div>';
        return $html;

    }

    protected function __getPageNavBt($class,$action,$title,$image)
    {
        $html = '
        <span class="'.$class.'" '.$action.' title="'.$title.'" >
            <img src="'.$image.'" />
        </span>';

        return $html;
    }

    protected function __getHeaderSetOrder($titulo,$order)
    {

        $DG_order = $this->order;

        if ($DG_order == $order)
        {
            $setOrder = str_replace(',' , ' DESC ,' , $order).' DESC';
            $txtSetOrder = ' descendente';
        }
        else
        {
            $setOrder = $order;
            $img = '<img src="public/images/dg_order_des.gif">';
            $txtSetOrder = ' ascendente';
        }

        $img = '';
        if ($DG_order == $order)
        {
            $img = '<img src="public/images/dg_order_asc.gif">';
        }
        elseif ($DG_order == str_replace(',' , ' DESC ,' , $order).' DESC')
        {
            $img = '<img src="public/images/dg_order_des.gif">';
        }

        $html = '
        <div title="Ordenar la lista por Documento en forma  '.$txtSetOrder.'"
            onclick="DG_cmd_exec(\''.$this->id.'\',\'setorder\',\''.$setOrder.'\');">
            '.$titulo.$img.'
        </div>';

        return $html;
    }
}
?>
