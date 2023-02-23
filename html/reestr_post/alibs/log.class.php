<?php
class CLog
{
    var $file     = false;
    var $fname    = false;
    var $list     = false;
    
    /**
    * @desc 
    */
    function __construct($filename=false)
    {
        if ($filename)
        {
            $this->fname = $filename;  
        } 
        else
        {
            $this->fname = "log_".date("d-m-Y").".log";
        }
        $this->file = "tmp/".$this->fname;
        $this->list = array();
    }
    
    /**
    * @desc
    */
    function AddToLog($text)
    {
        $this->list[] = $text;
    }
    
    /**
    * @desc
    */
    function WriteList()
    {
        //$text = "Cats chase mice";
        $text = "\n\n";
        $text .= implode("\n",$this->list);
        $fh = fopen($this->file, "a");
        fwrite($fh, $text);
        fclose($fh);
    }
    
    
}

?>