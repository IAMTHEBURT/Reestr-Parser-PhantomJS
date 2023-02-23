<?php
include_once 'pdf_module/PDF.php';

class CVisual extends CParser
{
    var $project_path   = false;
    var $files_folder   = false;
    var $action         = false;
    var $captcha        = false;

    /**
    * @desc
    */
    function __construct($project_path, $files_folder)
    {
        $this->project_path = $project_path;
        $this->files_folder = $files_folder;
    }

    /**
    * @desc Параметры командной строки
    */
    function getRowsForXML($limit = 100, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND file IS NOT NULL";
        $query .=" AND file <> ''";
        $query .=" AND xml IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$limit;
        return $this->GetRows($query);
    }

    /**
    * @desc Параметры командной строки
    */
    function getRowsForHTML($limit = 50, $skip = 0, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND file IS NOT NULL";
        $query .=" AND file <> ''";
        $query .=" AND xml IS NOT NULL";
        $query .=" AND html IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$skip.', '.$limit;
        return $this->GetRows($query);
    }

    /**
    * @desc Параметры командной строки
    */
    function getRowsForReportHTML($limit = 50, $skip = 0, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND xml IS NOT NULL";
        $query .=" AND report_html IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$skip.', '.$limit;
        return $this->GetRows($query);
    }


    function getTestRows($limit = 50, $skip = 0, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        //$query .=" AND id = 223108";
        $query .=" AND request_num = '80-188377875'";
        $query .=" ORDER BY ID ".$order;
        return $this->GetRows($query);
    }

    /**
    * @desc Параметры командной строки
    */
    function getRowsForReportPDF($limit = 100, $skip = 0, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND report_html IS NOT NULL";
        $query .=" AND report_pdf IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$skip.', '.$limit;
        return $this->GetRows($query);
    }

    /**
    * @desc Параметры командной строки
    */
    function getRowsForPDF($limit = 200, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND xml IS NOT NULL";
        $query .=" AND html IS NOT NULL";
        $query .=" AND pdf IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$limit;
        return $this->GetRows($query);
    }


    /**
    * @desc Выборка документов для визуальной подписи
    */
    function getRowsForSignPDF($limit = 200, $order = "DESC")
    {
        $query = "SELECT *";
        $query .=" FROM requests";
        $query .=" WHERE 1";
        $query .=" AND pdf IS NOT NULL";
        $query .=" AND sign_pdf IS NULL";
        $query .=" ORDER BY ID ".$order;
        $query .=" LIMIT ".$limit;
        return $this->GetRows($query);
    }


    /**
    * @desc
    */
    function makeXML($info)
    {
        $info   = (object) $info;
        $dir    = $this->project_path.$this->files_folder.trim($info->request_num);

        // unzip
        if (!$zip = $this->getFileFromDirMask($dir, "~Response\-\d+.*?\.zip~is")) {
            //$ok = $this->Query("DELETE FROM requests WHERE id=".$info->id);
            //print $ok ? 'delOK ' : 'delFAIL ';
            print 'NO_ZIP1 ';
            return false;
        }
        print 'unzip... ';
        if (!$this->unzip($dir.'/'.$zip, $dir)) { print 'UNZIP_ERROR '; return false; }
        print 'ok ';
        usleep(50000);

        // unzip2
        if (!$zip2 = $this->getFileFromDirMask($dir, "~out\_docs.*?\.zip~is")) { print 'NO_ZIP2 '; return false; }
        $this->delFilesFromDir($dir, 'xml');
        print 'unzip2... ';
        if (!$this->unzip($dir.'/'.$zip2, $dir)) { print 'UNZIP2_ERROR '; return false; }
        print 'ok ';

        // xml
        if (!$xml = $this->getFileFromDirMask($dir, "~.*?\.xml~is")) { print 'NO_XML '; return false; }
        print 'xmlOK ';

        // update
        $data = array('xml' => addslashes(trim($xml)));
        $ok = $this->Update('requests', $data, 'id='.$info->id);
        print $ok ? 'dbOK ' : 'dbFAIL ';

        return $xml;

        /*
        print NL;
        var_dump($xml);
        print NL;
        var_dump($zip);
        print NL;
        var_dump($zip2);
        print NL;
        var_dump($dir);
        print NL;
        $this->printArray($info);
        */
    }

    /**
    * @desc
    */
    function makePDF($info)
    {
        $info   = (object) $info;
        $dir    = $this->project_path.$this->files_folder.trim($info->request_num);

        // html
        print 'html... ';
        $html = $dir.'/'.trim($info->html);
        if (!is_file($html)) { print 'NO_HTML '; return false; }
        print 'OK ';

        // pdf
        print 'pdf... ';
        $pdf_file = $info->request_num.'.pdf';
        $pdf = $dir.'/'.$pdf_file;
        @unlink($pdf);
        //$params = array('wkhtmltopdf --quiet', $html, $pdf);
        $params = array(
            'xvfb-run --auto-servernum --server-num=1',
            //'--server-args="-screen 0, 1024x768x24"',
            'wkhtmltopdf --quiet',
            $html,
            $pdf
        );
        $cmd = implode(' ', $params);

        echo shell_exec($cmd);

        // check
        if (!is_file($pdf)) { print 'FAIL '; return false; }
        print 'OK ';

        // update
        $data = array('pdf' => addslashes(trim($pdf_file)));
        $ok = $this->Update('requests', $data, 'id='.$info->id);
        print $ok ? 'dbOK ' : 'dbFAIL ';

        return true;
    }

    /**
    * @desc
    */
    function makeReportPDF($info)
    {
        $info   = (object) $info;
        $dir    = $this->project_path.$this->files_folder.trim($info->request_num);

        // report pdf
        print 'report pdf... ';
        $html = $dir.'/'.trim($info->report_html);
        if (!is_file($html)) { print 'NO_HTML '; return false; }
        print 'OK ';

        // pdf
        print 'pdf... ';
        $pdf_file = $info->request_num.'_report.pdf';
        $pdf = $dir.'/'.$pdf_file;
        @unlink($pdf);
        //$params = array('wkhtmltopdf --quiet', $html, $pdf);
        $params = array(
            'xvfb-run --auto-servernum --server-num=1',
            //'--server-args="-screen 0, 1024x768x24"',
            'wkhtmltopdf --quiet',
            $html,
            $pdf
        );
        $cmd = implode(' ', $params);

        echo shell_exec($cmd);

        // check
        if (!is_file($pdf)) { print 'FAIL '; return false; }
        print 'OK ';

        // update
        $data = array('report_pdf' => addslashes(trim($pdf_file)));
        $ok = $this->Update('requests', $data, 'id='.$info->id);
        print $ok ? 'dbOK ' : 'dbFAIL ';

        return true;
    }

    /* Делаем обычный PDF подписанным */
    function makeSignPDF($info)
    {
        print_r($info);
        $info   = (object) $info;
        $dir    = $this->project_path.$this->files_folder.trim($info->request_num);

        $source_pdf_file = $info->request_num.'.pdf';
        $new_pdf_file = $info->request_num.'_signed.pdf';

        $fullPathToFile = $dir.'/'.$source_pdf_file;
        $fullPathToNewFile = $dir.'/'.$new_pdf_file;
		    //$fullPathToFile = "original.pdf"; // Путь до исходного pdf

    		$pdf = new PDF($fullPathToFile);
    		$pdf->AddPage();

    		if($pdf->numPages>1) {
    			for($i=2;$i<=$pdf->numPages;$i++) {
    				$pdf->_tplIdx = $pdf->importPage($i);
    				$pdf->AddPage();
    			}
    		}
    		$pdf->Output($fullPathToNewFile, 'F'); // Заменить на путь, куда будет сохраняться pdf


        // check
        if (!is_file($fullPathToNewFile)) { print 'FAIL '; return false; }
        print 'OK ';

        // update
        $data = array('sign_pdf' => addslashes(trim($new_pdf_file)));
        $ok = $this->Update('requests', $data, 'id='.$info->id);
        print $ok ? 'dbOK ' : 'dbFAIL ';

        return true;
    }

    /**
    * @desc
    */
    function Get($url, $cook = false, $header = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($cook) curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($this->_cookie));
        $this->AddProxy($ch);
        $this->responce = curl_exec($ch);
        $http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        print 'h'.$http_code.' ';
        curl_close($ch);
        $this->GetCookieFromHeader($this->responce);
        return $http_code;
    }

    /**
    * @desc
    */
    function getPage($url, $try = 3, $header = 1, $cook = false)
    {
        $count = 1;
        while ($count <= $try) {
            //print 'п'.$count.' ';
            $code = $this->Get($url, $cook, $header);
            $this->responce = trim($this->responce);
            if (($code == 200) && (strlen($this->responce) > 0)) {
                return true;
            }
            $count++;
            sleep(1);
        }
        return false;
    }

    /**
    * @desc
    */
    function writeResponceToFile($file)
    {
        $f = fopen($file, 'w');
        fwrite($f, $this->responce);
        fclose($f);
    }

    /**
    * @desc
    */
    function prepareCookie()
    {
        $this->_cookie = array();

        print NL.'cookie... ';
        $url = 'https://rosreestr.gov.ru/wps/portal/cc_vizualisation';
        if (!$this->getPage($url, 3, 1, false)) { print 'START_LOAD_ERROR'; return false; }
        //$this->writeResponceToFile(TMP_PATH.'/1.txt');
        //$this->responce = file_get_contents(TMP_PATH.'/1.txt');

        //$this->_cookie['JSESSIONID'] = "0000RktfpEPX4b-ZYLqsiyucGdx:1794n43l7";
        if (!isset($this->_cookie['JSESSIONID'])) { print 'NO_JSESSIONID '; return false; }

        //$this->action = '/wps/portal/p/cc_ib_portal_services/cc_vizualisation/!ut/p/c5/hY1LDoIwAETP4gk6VdqybdD0o2BVjMCm6cKQJgIujOe34lqdWb68GdKR1DE8Yx8ecRrDjTSk456xsjBbRaE2Z8C4XIiaWaiKJ95yb5R1euaHXeLF-kSdFIDDH_vy_uMelEmqM5h9uWKQsqhtKY5L5NmH_9qf_S-RIJWehiu5Dw2i6xcv-PuBCA!!/dl3/d3/L0lDU0lKSWdrbUNTUS9JUFJBQUlpQ2dBek15cXpHWUEhIS80QkVqOG8wRmxHaXQtYlhwQUh0Qi83XzAxNUExSDQwSTAwUkQwQThHSElSTEQyMDA1L2E6eVFzMzU3NjAwMDIvc2EucnUuZmNjbGFuZC5pYm1wb3J0YWwuc3ByaW5nLnBvcnRsZXQuZGlzcGF0Y2hlci5EaXNwYXRjaGVyQWN0aW9uRXZlbnQtTVVMVElQQVJU/?PC_7_015A1H40I00RD0A8GHIRLD2005000000_ru.fccland.ibmportal.spring.portlet.handler.BeanNameParameterHandlerMapping-PATH=%2fResponseCheckFormController#';
        $t = "~action\s*\=\s*[\"\']\s*(\/wps\/portal[^\"\']+)[\"\']~is";
        $this->action = $this->GetPregMatchValue($t, $this->responce, 1, false);
        if (!$this->action) { print 'NO_ACTION '; return false; }

        //var_dump($this->action);
        //var_dump($this->_cookie);
        //die;
        print 'OK ';
        return true;
    }

    /**
    * @desc
    */
    function passCaptcha()
    {
        $url = 'https://rosreestr.gov.ru/wps/PA_FCCLPGURCckPortApp/ru.fccland.pgu.response.check?ru.fccland.ibmportal.spring.portlet.handler.BeanNameParameterHandlerMapping-PATH=%2Fru.fccland.pgu.simplecaptcha.controller.SimpleCaptchaController&ru.fccland.ibmportal.spring.portlet.dispatcher.DispatcherServiceServlet.directRequest=x&refresh=true&time='.time().rand(100, 999);

        if (!$this->Get($url, 1, 0)) { print 'UPLOAD_ERROR '; return false; }
        $img = TMP_PATH.'/'.time().rand(10000, 90000).'.png';
        $this->writeResponceToFile($img);
        //$img = TMP_PATH.'/156645470763077.png';

        $this->captcha->log = false;
        $text = $this->captcha->bypassCaptcha($img);
        @unlink($img);
        if (!$text) { print 'FAIL '; return false; }
        print $text.' ';
        return $text;
    }

    /**
    * @desc
    */
    function getFileName($file)
    {
        $info = pathinfo($file);
        if (is_array($info) && isset($info['basename'])) {
            return trim($info['basename']);
        }
        return '';
    }

    /**
    * @desc
    */
    function getFileText($file)
    {
        if (is_file($file)) {
            return file_get_contents($file);
        }
        return '';
    }

    /**
    * @desc
    */
    function build_post_data($delimiter, $fields)
    {
        $data = '';
        $eol = "\r\n";

        foreach ($fields as $name => $f) {
            $f = (object) $f;
            switch ($f->type) {
                case 'param' : {
                    $data .= $delimiter.$eol;
                    $data .= 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol;
                    $data .= $f->v.$eol;
                    break;
                }
                case 'file' : {
                    $fname = $this->getFileName($f->v);
                    $ftext = $this->getFileText($f->v);

                    $data .= $delimiter.$eol;
                    $data .= 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $fname. '"' . $eol;
                    if (isset($f->ctype)) $data .= 'Content-Type: '.$f->ctype.$eol;
                    $data .= $eol;
                    $data .= $ftext . $eol;
                    break;
                }
            }
        }
        $data .= $delimiter."--".$eol;

        return $data;
    }

    /**
    * @desc
    */
    function makeHTML($info)
    {
        $info   = (object) $info;
        $dir    = $this->project_path.$this->files_folder.trim($info->request_num);

        // xml
        $xml = $dir.'/'.trim($info->xml);
        if (!is_file($xml)) { print 'NO_XML '; return false; }

        // Куки
        $this->_cookie = array();
        //print 'start... ';
        $url = 'https://rosreestr.gov.ru/wps/portal/cc_vizualisation';
        if (!$this->getPage($url, 3, 1, false)) { print 'START_LOAD_ERROR'; return false; }
        //$this->writeResponceToFile(TMP_PATH.'/1.txt');
        //$this->responce = file_get_contents(TMP_PATH.'/1.txt');

        //$this->_cookie['JSESSIONID'] = "0000RktfpEPX4b-ZYLqsiyucGdx:1794n43l7";
        if (!isset($this->_cookie['JSESSIONID'])) { print 'NO_JSESSIONID '; return false; }

        //$this->action = '/wps/portal/p/cc_ib_portal_services/cc_vizualisation/!ut/p/c5/hY1LDoIwAETP4gk6VdqybdD0o2BVjMCm6cKQJgIujOe34lqdWb68GdKR1DE8Yx8ecRrDjTSk456xsjBbRaE2Z8C4XIiaWaiKJ95yb5R1euaHXeLF-kSdFIDDH_vy_uMelEmqM5h9uWKQsqhtKY5L5NmH_9qf_S-RIJWehiu5Dw2i6xcv-PuBCA!!/dl3/d3/L0lDU0lKSWdrbUNTUS9JUFJBQUlpQ2dBek15cXpHWUEhIS80QkVqOG8wRmxHaXQtYlhwQUh0Qi83XzAxNUExSDQwSTAwUkQwQThHSElSTEQyMDA1L2E6eVFzMzU3NjAwMDIvc2EucnUuZmNjbGFuZC5pYm1wb3J0YWwuc3ByaW5nLnBvcnRsZXQuZGlzcGF0Y2hlci5EaXNwYXRjaGVyQWN0aW9uRXZlbnQtTVVMVElQQVJU/?PC_7_015A1H40I00RD0A8GHIRLD2005000000_ru.fccland.ibmportal.spring.portlet.handler.BeanNameParameterHandlerMapping-PATH=%2fResponseCheckFormController#';
        $t = "~action\s*\=\s*[\"\']\s*(\/wps\/portal[^\"\']+)[\"\']~is";
        $this->action = $this->GetPregMatchValue($t, $this->responce, 1, false);
        if (!$this->action) { print 'NO_ACTION '; return false; }

        // капча
        print 'captcha... ';
        $ctext = false;
        $try = 1;
        while ($try <= 3) {
            print 'п'.$try.' ';
            if ($ctext = $this->passCaptcha()) break;
            $try++;
        }
        if (!$ctext) { print 'FAIL '; return false; }
        //$ctext = '34782';

        // запрос
        $fields = array(
            'formSubmittedStr'  => array('type' => 'param', 'v' => 'true'),
            'xml_file'          => array('type' => 'file',  'v' => $xml,    'ctype' => 'text/xml'),
            'sig_file'          => array('type' => 'file',  'v' => false,   'ctype' => 'application/octet-stream'),
            'captchaText'       => array('type' => 'param', 'v' => $ctext),
        );
        $boundary = uniqid();
        $delimiter = '----WebKitFormBoundaryfRh' . $boundary;
        $post_data = $this->build_post_data('--'.$delimiter, $fields);
        //print NL.NL.nl2br($post_data);

        $url = 'https://rosreestr.gov.ru'.$this->action;
        $ref = 'https://rosreestr.gov.ru/wps/portal/cc_vizualisation';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 55);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
        curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($this->_cookie));
        curl_setopt($ch, CURLOPT_REFERER, $ref);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept:text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,/;q=0.8',
            'Accept-Language:ru-RU,ru;q=0.8,en-US;q=0.6,en;q=0.4',
            'Content-Length:'.strlen($post_data),
            'Content-Type:multipart/form-data; boundary='.$delimiter,
            'X-Requested-With: XMLHttpRequest',
        ));
        print 'post... ';
        $this->responce = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        print 'h'.$http_code.' ';
        curl_close($ch);
        if ($http_code != 200) { print 'ERROR! '; return false; }

        //$this->writeResponceToFile(TMP_PATH.'/2.txt');
        //$this->responce = file_get_contents(TMP_PATH.'/2.txt');

        // html
        print 'html... ';
        $t = "~window\.open\([\"\']\s*(\/wps\/[^\"\']+)[\"\']~is";
        $html_url = $this->GetPregMatchValue($t, $this->responce, 1, false);
        if (!$html_url) { print 'NO_HTML_URL '; return false; }

        // load
        $url = 'https://rosreestr.gov.ru'.trim($html_url);
        if (!$this->getPage($url, 3, 0, 1)) { print 'LOAD_ERROR '; return false; }

        // save
        $html_file = $info->request_num.'.html';
        $html = $dir.'/'.$html_file;
        @unlink($html);

        if (strlen($this->responce) < 10){
          print 'SIZE_ERROR ';
          return false;
        }


        $this->cleanFromRecipient();

        $this->writeResponceToFile($html);

        // update
        $data = array('html' => addslashes(trim($html_file)));
        $ok = $this->Update('requests', $data, 'id='.$info->id);
        print $ok ? 'dbOK ' : 'dbFAIL ';

        /*
        print NL;
        var_dump($this->_cookie);
        print NL;
        var_dump($this->action);
        print NL;
        var_dump($xml);
        print NL.NL;
        $this->printArray($info);
        print NL.NL.htmlspecialchars($this->responce);
        die;
        */
    }


    function cleanFromRecipient(){
      //Загружаем документ
      $html = $this->responce;
      $doc = new DOMDocument();
      $doc->loadaHTML($html);

      $trs = $doc->getElementsByTagName('tr');
      $nodeId = false;

      foreach ($trs as $key => $tr) {
          //Ищем получаетля и берем последний ключ (<tr> его содержащий)
          $pos = strpos($tr->nodeValue, "олучатель");
          if($pos !== false){
            $nodeId = $key;
          }
      }

      //Если таковой найден / удаляем его и всех его деток
      if($nodeId){
        $element = $trs[$nodeId];
        $element->parentNode->removeChild($element);
        $html = $doc->saveHTML();
      }

      $this->responce = $html;
    }


































    /**
    * @desc
    */
    function getFileFromDir($dir, $ext)
    {
        if (!is_dir($dir)) { print 'NO_DIR '.$dir.' '; return false; }
        $files = scandir($dir);
        foreach ($files as $f) {
            $t = "~\.".$ext."\s*$~is";
            if (preg_match($t, $f)) {
                return $f;
            }
        }
        return false;
    }

    /**
    * @desc
    */
    function getFileFromDirMask($dir, $t)
    {
        if (!is_dir($dir)) { print 'NO_DIR '.$dir.' '; return false; }
        $files = scandir($dir);
        foreach ($files as $f) {
            if (preg_match($t, $f)) {
                return $f;
            }
        }
        return false;
    }

    /**
    * @desc
    */
    function delFilesFromDir($dir, $ext)
    {
        //print NL.NL;
        //var_dump($dir);
        $files = scandir($dir);
        foreach ($files as $f) {
            //print NL.$f;
            $t = "~\.".$ext."\s*$~isu";
            if (preg_match($t, $f)) {
                //print ' - delete!!! ';
                $file = $dir.'/'.$f;
                //print $file;
                @unlink($file);
            }
        }
        return false;
    }

    /**
    * @desc
    */
    function printArray(&$a, $nl = '<br>', $padding = '')
    {
        foreach ($a as $k => $v) {
            print $nl.$padding.$k.' => ';
            if (is_array($v) || is_object($v)) {
                $this->printArray($v, $nl, $padding.str_repeat(SP, 5));
            }
            else {
                print htmlspecialchars($v);
            }
        }
    }

    /**
    * @desc
    */
    function unzip($zip, $dest)
    {
        if (!is_file($zip)) {
            print 'NO_FILE ';
            return false;
        }

        $params = array('unzip', '-q', $zip, '-d', $dest);
        $cmd    = implode(' ', $params);
        echo shell_exec($cmd);
        return true;
    }

}
?>
