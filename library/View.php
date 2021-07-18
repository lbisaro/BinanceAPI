<?php
class View
{
    private $html;
    private $tplName;
    private $path = VIEW_PATH;
    private $ext = ".tpl";
    private $vars;

    function View($template_file = null)
    {
        if ($template_file)
            $this->setTpl($template_file);
    }

    function __reset()
    {
        $this->html = null;
        $this->tplName = null;
        $this->vars = null;
    }

    function setTpl($template_file)
    {
        $template_file = str_replace('\\','/',$template_file);
        $this->__reset();
        if (!$template_file)
        {
            $this->html = '<div class="error"><li>[ ERROR ]<br/>Se debe especificar un nombre para la plantilla.</div>';
        }
        else
        {
            $this->tplName = $template_file;
            $tpl_file = $this->path . $this->tplName . $this->ext;
            if (!file_exists($tpl_file))
            {
                $this->html = '<div class="error"><li>[ ERROR ]<br/>No existe la plantilla <b>' . $tpl_file . '</b>.</div>';
            }
            elseif (!$fd = @fopen($tpl_file, 'r'))
            {
                $this->html = '<div class="error"><li>[ ERROR ]<br/>No se puede abrir la plantilla <b>' . $tpl_file . '</b>.</div>';
            }
            else
            {
                $tpl_file = $tpl_file;
                $this->html = fread($fd, filesize($tpl_file)) ;
                fclose($fd);
            }
        }
        $this->vars = array();
    }

    public function addVar($name,$value)
    {
        $this->vars[$name] = $value;
    }

    public function get($vars=array())
    {
        if (!empty($vars)) {
            $this->vars = $vars;
        }

        $html = $this->html;
        if (preg_match_all('/\{\{(.*?)\}\}/is',$html,$varArray))
            for ($i=0; $i < count($varArray[0]); $i++ )
                $html = str_replace( $varArray[0][$i],$this->vars[$varArray[1][$i]],$html);

        if (preg_match_all('/\{FOR(.*?)ENDFOR\}/is',$html,$forArray))
            for ($i=0; $i < count($forArray[0]); $i++ )
                $html = str_replace( $forArray[0][$i],$this->parseFor($forArray[1][$i]),$html);

       // if ($this->vars['DEBUG'])
       //     $html .= '<div id="view_debug">DEBUG.'.$this->tplName.'<hr/>'.$this->vars['DEBUG'].'</div>';

        $this->vars = array();

        if (strtoupper(mb_detect_encoding($this->html)) == 'UTF-8')
            $this->html = utf8_decode($this->html);

        return $html;
    }

    private function parseFor($toParse)
    {
        $it = explode("#",$toParse);
        $ret = '';
        if (is_array($this->vars[$it[1]]))
            foreach ($this->vars[$it[1]] as $val)
                $ret .= $it[0].$val.$it[2];
        return $ret;
    }

}
?>