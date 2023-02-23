<?php
class Captcha
{
    var $key    = false;
    var $domain = false;
    var $proxy  = false;
    var $log    = false;
    
    /**
    * @desc 
    */
    function __construct($domain, $key, $proxy = false)
    {
        $this->domain = $domain;
        $this->key = $key;
        $this->proxy = $proxy;
    }
    
    /**
    * @desc Функция для загрузки капчи на Antigate
    */
    function LoadCaptcha($filename)
    {
        // Проверим наличие файла
        if (!file_exists($filename)) {
            return false;
        }
        
        // Данные
        $postdata = array (
            //'method'    => 'base64',
            //'body'      => base64_encode($filename), //полный путь к файлу
            'method'    => 'post', 
            //'file'      => '@'.$filename,
            //'file'      => $filename,
            'file'      => curl_file_create($filename),
            'key'       => $this->key, 
            //'ext'       => 'png',
            'phrase'    => 0,
            'regsense'  => 0,
            //'numeric'   => 1,
            //'min_len'   => 3,
            //'max_len'   => 7,
        );
        // Данные
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,               'https://'.$this->domain.'/in.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,    1);
        curl_setopt($ch, CURLOPT_TIMEOUT,           60);
        curl_setopt($ch, CURLOPT_POST,              1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,        $postdata);
        //curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        if ($this->proxy) {
            curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy['ip']));
            curl_setopt($ch, CURLOPT_PROXYPORT, trim($this->proxy['port']));
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['login'].':'.$this->proxy['pass']);
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, array
        (
            //'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            //'Accept-Encoding: gzip, deflate, br',
            //'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            //'Connection: keep-alive',
            //'Content-Length: '.strlen($pd),
            //'Content-Type: multipart/form-data; boundary=',
            //'User-Agent: Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/74.0.3729.169 Chrome/74.0.3729.169 Safari/537.36',
        ));
        
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) 
        {
            print curl_errno($ch).'<br>';
            print 'Ошибка при загрузке!<br>';
            return false;
        }
        curl_close($ch);
        
        if (strpos($result, "ERROR") === false)
        {
            $ex = explode("|", $result);
            if (isset($ex[1])) return $ex[1];
        }
        print $result.NL;
        return false;
    }

    /**
    * @desc Функция для проверки статуса одной капчи
    */
    function CheckCaptchaState($id)
    {
        $url = 'http://'.$this->domain.'/res.php?key='.$this->key.'&action=get&ids='.$id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if ($this->proxy)
        {
            curl_setopt($ch, CURLOPT_PROXY, trim($this->proxy['ip']));
            curl_setopt($ch, CURLOPT_PROXYPORT, trim($this->proxy['port']));
            curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['login'].':'.$this->proxy['pass']);
        }
        $response   = curl_exec($ch);
        $http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code == 200) {
            return $response;
        }
        $str = 'Запрос статуса капчи: Код ответа - '.$http_code;
        $this->wmsg($str);
        
        return false;
    }
    
    /**
    * @desc 
    */
    function bypassCaptcha($img)
    {
        //var_dump($this->log); var_dump($img); die;
        if (!$captcha_id = $this->LoadCaptcha($img)) { $this->wmsg('C_LOAD_ERROR!'); return false; }
        $this->wmsg('ID:'.$captcha_id);
        
        // Опрос статуса капчи
        $solution = false;
        $count = 0;
        while (!$solution && ($count <= 20)) {
            $count++;
            $state = $this->CheckCaptchaState($captcha_id);
            if ($state != 'CAPCHA_NOT_READY') { $solution = $state; break; }
            $this->wmsg('.');
            sleep(1);
        }
        if ($solution == false) { $this->wmsg('Капча не распознана!'); return false; }
        if (stristr($solution, 'ERROR')) { $this->wmsg('Капча не распознана №2!'); return false; }
        return $solution;
    }
    
    /**
    * @desc 
    */
    function wmsg($msg, $nl = true)
    {
        if ($this->log) {
            if ($nl) print NL;
            print $msg;
        }
    }
    
    
}

?>