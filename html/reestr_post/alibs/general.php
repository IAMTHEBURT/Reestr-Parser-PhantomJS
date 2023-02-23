<?php
    /**
    * @desc Параметры командной строки
    */
    function shrGetParams($argv, $request)
    {
        $filters = $request;
        if (isset($argv)) foreach ($argv as $value)
        {
            if (preg_match("~([^\=]+)\=([^\=]+)~is", $value, $match))
            {
                $filters[trim($match[1])] = trim($match[2]);
            }
        }
        return $filters;
    }
    
    /**
    * @desc 
    */
    function getWorkTime($time)
    {
    	$minutes = intval($time / 60);
    	$seconds = $time - $minutes * 60;
    	$str = array();
    	if ($minutes > 0) $str[]= $minutes.' минут';
    	if ($seconds > 0) $str[]= $seconds.' секунд';
    	return implode(' ', $str);
    }
?>
