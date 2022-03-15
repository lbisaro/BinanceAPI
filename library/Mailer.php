<?php
require("PHPMailer/class.phpmailer.php");
require("PHPMailer/class.smtp.php");

/**
 * @package MyLibrary_External
 *
 * @see http://phpmailer.worxware.com/
 * @see http://phpmailer.worxware.com/index.php?pg=examples
 */
class Mailer extends phpmailer
{


    public $Mailer = 'smtp';
    /*
    public $SMTPDebug  = 1;                // enables SMTP debug information (for testing)
                                           // 1 = errors and messages
                                           // 2 = messages only
    */

    public $FromName   = 'Bisaro Cripto';
  
/**
    public $From       = 'lbisaro@outlook.com.ar';
    public $SMTPSecure = "STARTTLS";                 
    public $Port       = "587";              
    public $SMTPAuth   = true; 
    public $Host       = "smtp.office365.com";
    public $Username   = "lbisaro@outlook.com.ar"; 
    public $Password   = "Fmn#361612@";         

    public $From       = 'criptalknode@gmail.com';
    public $SMTPAuth   = true;
    public $SMTPSecure = "tls";                 // sets the prefix to the server
    public $Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
    public $Port       = 587;                   // set the SMTP port for the GMAIL server
    public $Username   = "criptalknode@gmail.com";  // GMAIL username
    public $Password   = "Criptalk#2021";
    */


    public $From       = 'leonardo.bisaro@gmail.com';
    public $SMTPAuth   = true;
    public $SMTPSecure = "tls";                 // sets the prefix to the server
    public $Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
    public $Port       = 587;                   // set the SMTP port for the GMAIL server
    public $Username   = "leonardo.bisaro@gmail.com";  // GMAIL username
    public $Password   = "Fmn#361612";
   

    function __Contruct()
    {
        parent::__Contruct();
    }

    function Send()
    {
        /*
        if (empty($this->ReplyTo))
            die('ERROR CRITICO: Se debe especificar el Mailer::ReplyTo del mensaje.');
        */
    
        return parent::Send();
    }

    function getFolder()
    {
        return TMP_PATH;
    }

    function getFilePrefix()
    {
        return 'mailer.saveMail_';
    }

    function getOutboxMails()
    {
        $folder = $this->getFolder();
        
        $res = array();

        if(substr($folder, -1) != "/") $folder .= "/";

        $dir = @dir($folder) or die("getFileList: Error abriendo el directorio $folder para leerlo");
        while(($file = $dir->read()) !== false) 
        {
            $prfx = $this->getFilePrefix();
            if (is_readable($folder . $file) && substr($file,0,strlen($prfx)) == $prfx ) 
            {
                $key = filemtime($folder . $file);
                $res[$key] = array( 'folder' => $folder,
                                    'file' => $file,
                                    'tmpID' => str_replace($prfx,'',$file),
                                    'size' => toDec((filesize($folder . $file)/1000)).'Kb',
                                    'date' => date('d-m-Y H:i',filemtime($folder . $file))
                                    );
            }
        }
        ksort($res);
        $dir->close();
        return $res;
    }

    function getInstance($tmpID)
    {
        $mail = Mailer::getFromFile($tmpID);
        if (!empty($mail))
            return $mail;
        return null;

    }

    function getStrTo()
    {
        $str = '';
        if (!empty($this->to))
            foreach ($this->to as $it)
                $str .= ($str?';':'').$it[1].($it[0]&&$it[0]!=$it[1] ? '['.$it[0].']' : '');

        return $str;
    }

    function getStrCc()
    {
        $str = '';
        if (!empty($this->cc))
            foreach ($this->cc as $it)
                $str .= ($str?';':'').$it[1].($it[0]&&$it[0]!=$it[1] ? '['.$it[0].']' : '');

        return $str;
    }

    function getStrBcc()
    {
        $str = '';
        if (!empty($this->bcc))
            foreach ($this->bcc as $it)
                $str .= ($str?';':'').$it[1].($it[0]&&$it[0]!=$it[1] ? '['.$it[0].']' : '');

        return $str;
    }

    function saveToFile()
    {
        $tmpID = date('U').'_'.rand(1000,9000);
        $tmpFile = $this->getFolder().$this->getFilePrefix().$tmpID;

        $dump = serialize($this);
        file_put_contents($tmpFile, $dump);
        return $tmpID;        
    }

    function getFromFile($tmpID)
    {
        $tmpFile = $this->getFolder().$this->getFilePrefix().$tmpID;
        $dump = @file_get_contents($tmpFile);
        if (!empty($dump))
        {
            $mail = unserialize($dump);
            $mail->fileInfoSize = toDec((filesize($tmpFile)/1000)).'Kb';
            $mail->fileInfoDate = date('d-m-Y H:i',filemtime($tmpFile));
            return $mail;
        }
        return null;
    }

    function deleteFile($tmpID)
    {
        $tmpFile = $this->getFolder().$this->getFilePrefix().$tmpID;
        @unlink($tmpFile);
        return true;
    }

}

?>