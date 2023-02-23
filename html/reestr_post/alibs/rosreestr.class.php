<?php
require_once "alibs/db.class.php";
require_once "alibs/captcha.php";
class Rosreestr extends CDB
{
    //stream_id
    var $sid         = 0;
    //load_page parameters
    var $content     = false;
    var $postfields  = false;
    var $cookie      = false;
    var $cookie_file = false;
    var $referer     = false;
    var $agent       = false;
    //auth
    var $auth_cook_file      = false;
    var $auth_page_file      = false;
    var $auth_request_file   = false;
    var $auth_phantom_script = false;
    var $auth_url            = false;

    var $auth_post_data      = "";
    var $auth_post_url       = "";
    var $auth_post_key       = "";
    var $phantom_cmd         = false;
    var $capture_file        = false;
    var $active_order_statuses = array();


    /**
    * @desc Конструктор
    */
    function __construct($host, $user, $pass, $name)
    {
        parent::__construct($host, $user, $pass, $name);
        $this->Connect();
        //auth params
        $this->auth_page_file      = "page.html";
        $this->auth_request_file   = "requests.txt";
        //$this->auth_cook_file      = "--cookies-file=ph_cook.txt";
        $this->auth_cook_file      = " ";
        $this->auth_phantom_script = PROJECT_PATH."script_phantom_auth.js";
        $this->phantom_cmd         = "/usr/local/bin/phantomjs";
        $this->auth_url            = "https://rosreestr.gov.ru/wps/portal/p/cc_present/ir_egrn";
        $this->postfields          = "";
        $this->cookie              = "";
        $this->agent               = "Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/74.0.3729.169 Chrome/74.0.3729.169 Safari/537.36";
        $this->referer             = "https://rosreestr.gov.ru/wps/portal/p/cc_present/ir_egrn";
        $this->cookie_file         = "cookiejar.json";
        $this->cookie = array();
        $this->capture_file        =  CIMAGE;
        $this->setActiveORderStatuses();
    }

    /**
    * @desc Параметры командной строки
    */
    function setActiveORderStatuses()
    {
       $this->active_order_statuses = array(
           'cоздана',
           'в работе',
           'на проверке',
       );
    }

    /**
    * @desc Параметры командной строки
    */
    function setSID($sid)
    {
        $this->sid = intval($sid);

        $apf = "tmp/".$this->sid."page.html";
        @unlink($apf);
        $this->auth_page_file    = "tmp/".$this->sid."page.html";

        $cf  = "tmp/".$this->sid."cookiejar.json";
        @unlink($cf);
        $this->cookie_file       = "tmp/".$this->sid."cookiejar.json";

        $this->capture_file      = $this->sid."capture.png";

        $arf = "tmp/".$this->sid."requests.txt";
        @unlink($arf);
        $this->auth_request_file = "tmp/".$this->sid."requests.txt";
    }

    /**
    * @desc Параметры командной строки списка заявок
    */
    function setPSID($sid)
    {
        $this->sid = intval($sid);

        $apf = "tmp/p".$this->sid.$this->auth_page_file;
        @unlink($apf);
        $this->auth_page_file    = "tmp/p".$this->sid.$this->auth_page_file;

        $cf       = "tmp/p".$this->sid.$this->cookie_file;
        @unlink($cf);
        $this->cookie_file       = "tmp/p".$this->sid.$this->cookie_file;

        $this->capture_file      = $this->sid.$this->capture_file;

        $arf                    = "tmp/p".$this->sid.$this->auth_request_file;
        @unlink($arf);
        $this->auth_request_file = "tmp/p".$this->sid.$this->auth_request_file;
    }

     /**
    * @desc request list for check
    */
    function GetCheckListFromDB($rid=0)
    {
        $res   = array();
        $rid = intval($rid);
        $sk = 3600 * CHECK_TIME_HOURS; //60*60*HOURS //Сейчас 3 часа
        $tm = time() - $sk; //6 часов = 6*60*60 = 21600
        $dt = date("Y-m-d H:i:s",$tm);
        $query = "SELECT * FROM `requests` WHERE `request_num`<>'' AND `request_num` IS NOT NULL AND (`url` LIKE '' OR `url` IS NULL ) ";
        if ($rid > 0)
        {
            $query .= " AND `id` = ".$rid;
        }
        else
        {
            //6 часов ограничение на повторный запрос
            $query .= " AND (`order_updated` IS NULL OR `order_updated`<='".$dt."')";
        }
        //$query .= " ORDER BY `order_updated` ASC ";
        $query .= " ORDER BY `order_updated` DESC ";
        $query .= " LIMIT 1200 "; //Вернуть на 200
        if ($rows = $this->GetRows($query))
        {
            foreach ($rows as $row)
            {
                $id   = $row['id'];
                $code = $row['code'];
                if (!isset($res[$code])) $res[$code] = array();
                $res[$code][$id] = $row;
            }
        }
        return $res;
    }


    // function GetDataFromDB($rid, $code = false)
    // {
    //     $data = false;
    //     $tm = time()-60;//1 min
    //     $dt = date("Y-m-d H:i:s",$tm);
    //     $tm2 = time()-3600;//60 min
    //     $dt2 = date("Y-m-d H:i:s",$tm2);
    //     $query = "SELECT * ";
    //     $query .= " FROM `requests`";
    //     $query .= " WHERE 1 ";
    //     $query .= " AND `status` like 'processed' AND `updated_at`>='".$dt2."' ";
    //
    //     if ($code)
    //     {
    //         $query .= " AND `code` = '$code'";
    //     }
    //
    //     $query .= " AND `busy` = ".$this->sid." ORDER BY `created_at` ASC";
    //
    //     if ($row = $this->GetRow($query))
    //     {
    //         print "\nprocess N".$this->sid." exist!";
    //         return false;
    //     }
    //
    //     $query = "SELECT * ";
    //     $query .= " FROM `requests`";
    //     $query .= " WHERE 1 ";
    //     if ($rid) $query .= " AND `id`= ".$rid." ";
    //     $cond = " AND (`request_num` IS NULL) AND (`code`<>'') AND (`cad_num`<>'') AND (`region`<>'') AND ((`status` like 'new') OR (`status` like 'error' AND `updated_at`<='".$dt."') OR (`status` like 'processed' AND `updated_at`<='".$dt2."'))";
    //     $query .= $cond;
    //
    //     if ($code)
    //     {
    //         $query .= " AND `code` = '$code'";
    //     }
    //
    //     $query1 = $query." AND `busy` = ".$this->sid." ORDER BY `created_at` ASC";
        //var_dump($query1);
        //var_dump( $this->GetRow($query1) ); die();

        /*if (!$row = $this->GetRow($query1))
        {
            SELECT * FROM `requests` JOIN `keys` ON requests.code = keys.egrn_key WHERE keys.used_at <= NOW() - 300 AND requests.busy IS NULL LIMIT 1

            //$uquery = "UPDATE `requests` SET `busy`=".$this->sid." WHERE `busy` IS NULL LIMIT 1";
            $udata = array("busy"=>$this->sid);

            if($code){
                $ucond = "`busy` IS NULL AND `code` = '$code' AND `status` LIKE 'new' LIMIT 1";
            }else{
                $ucond = "`busy` IS NULL AND `status` LIKE 'new' LIMIT 1";
            }

            if ($rid) $ucond = " `id`=".$rid." AND ".$ucond;
            $this->Update("requests",$udata,$ucond);
            $row = $this->GetRow($query1);
        }*/

        /* Начало дополнения с кодами, нам необходимо проверить, чтобы этот ключ был последний раз использован не более 5 минут назад */
        // if($row){
        //   if ( $this->keyCanBeUsed($row['code']) == false){
        //     print "\code N ".$row['code']." can not be used time limit is not be reached yet!";
        //     return false;
        //   }
        // }
        /* Конец дополнения с кодами


        return $row;
    }*/

    //Проверяет прошло больше 5 минут или нет
    function keyCanBeUsed($key){
      $query = "SELECT `used_at` FROM `keys` WHERE `egrn_key` LIKE '$key'";
      if ( !($row = $this->GetRow($query)) )
      {
          return false;
      }

      //Если ключ еще не использовался
      if($row['used_at'] == NULL){
        return true;
      }

      $used_at = strtotime($row['used_at']);
      $limit = 60*5; //5 минут
      $now = time();

      if( ($now - $used_at) > $limit){
        return true;
      }else{
        return false;
      }
    }

     /**
    * @desc request data
    */
    function GetDataFromDB($rid, $code = false)
    {
        $data = false;
        $tm = time()-60;//1 min
        $dt = date("Y-m-d H:i:s",$tm);
        $tm2 = time()-3600;//60 min
        $dt2 = date("Y-m-d H:i:s",$tm2);
        $query = "SELECT * ";
        $query .= " FROM `requests`";
        $query .= " WHERE 1 ";
        $query .= " AND `status` like 'processed' AND `updated_at`>='".$dt2."' ";

        if ($code)
        {
            $query .= " AND `code` = '$code'";
        }

        $query .= " AND `busy` = ".$this->sid." ORDER BY `created_at` ASC";

        if ($row = $this->GetRow($query))
        {
            print "\nprocess N".$this->sid." exist!";
            return false;
        }

        $query = "SELECT * ";
        $query .= " FROM `requests`";
        $query .= " WHERE 1 ";
        if ($rid) $query .= " AND `id`= ".$rid." ";
        $cond = " AND (`request_num` IS NULL) AND (`code`<>'') AND (`cad_num`<>'') AND (`region`<>'') AND ((`status` like 'new') OR (`status` like 'error' AND `updated_at`<='".$dt."') OR (`status` like 'processed' AND `updated_at`<='".$dt2."'))";
        $query .= $cond;

        if ($code)
        {
            $query .= " AND `code` = '$code'";
        }

        $query1 = $query." AND `busy` = ".$this->sid." ORDER BY `created_at` ASC";
        //var_dump($query1);
        //var_dump( $this->GetRow($query1) ); die();

        if (!$row = $this->GetRow($query1))
        {
            //$uquery = "UPDATE `requests` SET `busy`=".$this->sid." WHERE `busy` IS NULL LIMIT 1";
            $udata = array("busy"=>$this->sid);

            if($code){
                $ucond = "`busy` IS NULL AND `code` = '$code' AND `status` LIKE 'new' LIMIT 1";
            }else{
                $ucond = "`busy` IS NULL AND `status` LIKE 'new' LIMIT 1";
            }

            if ($rid) $ucond = " `id`=".$rid." AND ".$ucond;
            $this->Update("requests",$udata,$ucond);
            $row = $this->GetRow($query1);
        }


        /* Начало дополнения с кодами, нам необходимо проверить, чтобы этот ключ был последний раз использован не более 5 минут назад */
        if($row){
           if ( $this->keyCanBeUsed($row['code']) == false){
             print "\code N ".$row['code']." can not be used time limit is not be reached yet!";
             return false;
           }
        }
        /* Конец дополнения с кодами */

        return $row;
    }

    /**
    * @desc
    */
    function UpdateRowData($id,$data)
    {
        $cond = "id=".$id;
        $data['updated_at'] = date("Y-m-d H:i:s");
        $this->Update("requests",$data,$cond);
        return true;
    }


    /*
      Обновляет время последнего использования ключа
    */
    function UpdateKeyLastUsed($key){
      $cond = "egrn_key='$key'";
      $data['used_at'] = date("Y-m-d H:i:s");
      $this->Update("keys",$data,$cond);
      return true;
    }


    /**
    * @desc
    */
    function UpdateRowStatusData($id,$data)
    {
        $cond = "id=".$id;
        $data['order_updated'] = date("Y-m-d H:i:s");
        $this->Update("requests",$data,$cond);
        return true;
    }

    /**
    * @desc
    */
    function UpdateRowStatus($id,$status)
    {
        $up_arr = array("status"=>$status);
        $this->UpdateRowData($id,$up_arr);
        return true;
    }

    /**
    * @desc
    */
    function ReadCapture($url)
    {
        $headers = array(
            "Sec-Fetch-Mode"=> "no-cors",
            "Referer"=>"https://rosreestr.gov.ru/wps/portal/p/cc_present/ir_egrn",
            'Connection: Close'
        );
        if (!$this->Get($url,$this->cookie,$headers))
        {
            print "load image error!";
            print NL."Cookie=";var_dump($this->cookie);
            return "";
        }
        $resp = $this->content;
        $im = imagecreatefromstring($resp);
        if ($im !== false) {
            imagepng($im,$this->capture_file);
            imagedestroy($im);
        }
        $captcha = new Captcha(CAPTCHA_DOMAIN, CAPTCHA_KEY, false);
        $captcha->log = false;
        $ctext = $captcha->bypassCaptcha($this->capture_file);
        return $ctext;
    }


     /**
    * @desc
    */
    function ReadCookieFromFile($file)
    {
        $cook = (array) json_decode(file_get_contents($file));
        $str = array();
        if (is_array($cook))
        {
            foreach ($cook as $ck)
            {
                $ck = (array)$ck;
                $str[$ck['name']] = $ck['value'];
            }
            if (isset($str['_ym_uid'])) unset($str['_ym_uid']);
            if (isset($str['_ym_d'])) unset($str['_ym_d']);
            if (isset($str['yandexuid'])) unset($str['yandexuid']);
            if (isset($str['yp'])) unset($str['yp']);
            if (isset($str['_ym_visorc_18809125'])) unset($str['_ym_visorc_18809125']);
            if (isset($str['__utmz'])) unset($str['__utmz']);
            if (isset($str['__utmc'])) unset($str['__utmc']);
            if (isset($str['__utmb'])) unset($str['__utmb']);
            if (isset($str['__utma'])) unset($str['__utma']);
            if (isset($str['__utmt'])) unset($str['__utmt']);
            if (isset($str['yp'])) unset($str['yp']);
            if (isset($str['i'])) unset($str['i']);
            if (isset($str['yabs-sid'])) unset($str['yabs-sid']);
            if (isset($str['_ym_isad'])) unset($str['_ym_isad']);
            if (isset($str['yuidss'])) unset($str['yuidss']);

        }
        return $str;
    }

     /**
    * @desc
    */
    function CookToStr($cook)
    {
        $str = array();
        if (is_array($cook))
        {
            foreach ($cook as $key => $val)
            {
                $str[] = trim($key).'='.trim($val);
            }
        }
        return implode("; ", $str);
    }

    /**
    * @desc
    */
    function GetParamsFromRequestFile()
    {
        print "\nsearch post_info!";
        if (file_exists($this->auth_request_file))
        {
            $file = file($this->auth_request_file);
            $file = implode("\n",$file);

            if (preg_match_all("~headers.+?url[^\}]+\}~is",$file,$requests))
            {
                print "\nheaders find!";
                $last_request = array_pop($requests[0]);
                if (preg_match("~url[\"\']\s*\:\s*[\"\']([^\"\']+)~is",$last_request,$umatch))
                {
                    print "\nurl find!";
                    $url = $umatch[1];
                    $this->auth_post_url = $url;
                }
                if (preg_match("~postdata[\"\']\s*\:\s*[\"\']([^\"\']+)~is",$last_request,$pmatch))
                {
                    $auth_post_data = $pmatch[1];
                    $auth_post_data =  preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                    }, $auth_post_data);
                    $this->auth_post_data = $auth_post_data;
                    if (preg_match("~postdata[\"\']\s*\:\s*[\"\']([^\\\]+)~is",$last_request,$kmatch))
                    {
                        $this->auth_post_key =  $kmatch[1];
                        return $kmatch[1];
                    }
                    else
                    {
                        print "\npostkey not found!";
                    }
                }
                else
                {
                    print "\npostdata not found!";
                }
            }
            else
            {
                print "\nheaders not found!";
            }
        }
        else
        {
            print "\nfile not found! file=".$this->auth_request_file;
        }
        return false;
    }

    /**
    * @desc Запрос
    *       mks = 1 mln sec
    */
    function ApiRequest($url,$postdata,$cookie="",$sleep_mks=0)
    {
        if ($sleep_mks = intval($sleep_mks))
        {
            $sec = $sleep_mks/1000000;
            print " SLEEP_".$sec." ";
            usleep($sleep_mks);
        }
        $this->postfields = $postdata;
        if ($cookie) $this->cookie = $cookie;
        $this->LoadPage($url);
        return $this->content;
    }

    /**
    * @desc
    */
    function CheckEmptyApiResponse($text)
    {
        $t = "~for\(\;\;\).+~isu";
        if (preg_match($t,$text,$matches))
        {
            $resp = $matches[0];
            if (mb_strlen($resp,"UTF-8")<80)
            {
                $t = "~for\(\;\;\)\s*\;\s*\[\{\s*[\"\']changes[\"\']\s*\:\[\]\,\s*[\"\']meta[\"\']\s*\:\s*\{\}\s*\,\s*[\"\']resources[\"\']\s*\:\s*\{\}\s*\,\s*[\"\']locales[\"\']\s*\:\s*\[\]\s*\}\]~isu";
                if (preg_match($t,$resp))
                {
                    return true;
                }
            }
        }
        return false;
    }

    /**
    * @desc
    */
    function SearchText($tmpl,$text,$key=0)
    {
        $res = "";
        if (preg_match($tmpl,$text,$match))
        {
            $res = $match[$key];
        }
        return $res;
    }

    /**
    * @desc
    */
    function PhantomAuth()
    {
        /*
        $page_file   = "page.html";
        $request_file = "requests.txt";
        if (file_exists($page_file)) unlink($page_file);
        if (file_exists($request_file)) unlink($request_file);

        $sess_cookie = "cookiejar.json";
        $cookie_file = "--cookies-file=ph_cook.txt";
        $script      = "/var/www/phantom_key/script_phantom.js";
        $url         = "https://rosreestr.ru/wps/portal/p/cc_present/ir_egrn";
        $ph_cmd      = "/usr/local/bin/phantomjs";
        $exec_cmd    = $ph_cmd.' '.$cookie_file.' '.$script.' '.$url." ".$page_file." ".$sess_cookie;
        */
        //----
        //if (file_exists($this->auth_page_file))    unlink($this->auth_page_file);
        //if (file_exists($this->auth_request_file)) unlink($this->auth_request_file);
        //if (file_exists($this->cookie_file))       unlink($this->cookie_file);
        //$this->auth_page_file      = "page.html";
        //$this->auth_request_file   = "requests.txt";

        $exec_cmd    = $this->phantom_cmd.' --ssl-protocol=any --ignore-ssl-errors=true   '.$this->auth_cook_file.' '.$this->auth_phantom_script.' '.$this->auth_url." ".$this->auth_page_file." ".$this->cookie_file." ".$this->auth_request_file;
        print "\nexec_cmd=";var_dump($exec_cmd);
        echo shell_exec($exec_cmd);
        $res = $this->GetParamsFromRequestFile();
        return $res;
    }

    /**
    * @desc
    */
    function Get($url, $cook = false, $header = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($cook) curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($cook));
        if ($header) curl_setopt($ch, CURLOPT_HTTPHEADER, $header );
        $this->content = curl_exec($ch);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        //die;
        if ($http_code == 200)
        {
            return true;
        }
        return false;
    }

     /**
    * @desc GET copy file
    */
    function downloadZipFile($url, $filepath){
        $fp = fopen($filepath, 'w+');
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $cookie = $this->CookToStr($this->cookie);
        if ($this->cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);

        curl_close($ch);
        fclose($fp);

        return (filesize($filepath) > 0)? true : false;
    }

    function downloadZipFile2($url, $filepath){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        $cookie = $this->CookToStr($this->cookie);
        if ($this->cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);

        $raw_file_data = curl_exec($ch);

        if(curl_errno($ch)){
        echo 'error:' . curl_error($ch);
        }
        curl_close($ch);

        file_put_contents($filepath, $raw_file_data);
        return (filesize($filepath) > 0)? true : false;
    }


     /**
    * @desc POST Загрузка страницы. Выдает содержимое - текст
    */
    function LoadPage($url)
    {
        //var_dump($this->postfields);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->postfields);

        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_COOKIEJAR, "curl_cook.txt");
        //curl_setopt($ch, CURLOPT_COOKIEFILE, "curl_cook.txt");
        //curl_setopt( $ch, CURLOPT_COOKIESESSION, true );


        curl_setopt($ch, CURLOPT_HEADER,         1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $cookie = $this->CookToStr($this->cookie);
        if ($this->cookie) curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        //if ($this->cookie) curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        //curl_setopt($ch, CURLOPT_COOKIE, 'JSESSIONID_8=00008eybENUhmZ24Kt10N38eMJy:19a2vp8dd; __utma=224553113.274600071.1564506362.1564506362.1564506362.1; __utmc=224553113; __utmz=224553113.1564506362.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmb=224553113.1.10.1564506362; _ym_uid=1564506363169716416; _ym_d=1564506363; _ym_isad=2; _ym_visorc_18809125=w');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array
        (

            'Accept-Encoding: deflate, br',
            'Origin: https://rosreestr.gov.ru',
            'Accept: */*',
            'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive',
            'Content-Length: '.strlen($this->postfields),
            'Content-Type: text/plain;charset=UTF-8',
        ));
        $this->content = curl_exec($ch);
        //var_dump($content);
        $http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        //die;
        if ($http_code == 200)
        {
            return true;
        }
        return false;
    }



}

?>
