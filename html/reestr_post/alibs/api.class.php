<?php
require_once "alibs/parser.class.php";
require_once "alibs/region.class.php";
class CApi extends CParser
{
    var $msg = false;
    var $captcha = false;
    var $log = false;
    var $rcount = false;
    var $from_reesrt = false;
    var $rq_statuses = false;
    var $RG          = false;
    //var $reg_codes  = false;
    //var $regions    = false;
    //var $adj_regs   = false;

    /**
    * @desc
    */
    function preapareData()
    {
        //$this->ReadCookFromFile();
        $this->msg = array();
        $this->setRequestStatuses();
        $this->RG = new CRegion($this->db_host,$this->db_user,$this->db_pass,$this->db_name);
    }

    /**
    * @desc
    */
    function setRequestStatuses()
    {
        $this->rq_statuses = array(
            "new"       => "Документ еще не заказан",
            "ok"        => "Выполнен",
            "processed" => "В работе",
            "error"     => "Ошибка при обработке",
        );
    }


    /**
    * @desc Есть ли кадастровый номер уже в списке
    */
    function checkRequest($cad_no, $document)
    {
        $limit = time()-600; // 10 минут ограничение
        $document = trim(addslashes($document));
        $query = "SELECT `id` FROM `requests` WHERE `cad_num` like '".$cad_no."' AND `created_at` >= '".date("Y-m-d H:i:s",$limit)."' AND `document` LIKE '".$document."'";
        if ($this->GetRow($query))
        {
            return 0;
        }
        return 1;
    }

    /**
    * @desc Есть ли кадастровый номер уже в списке
    */
    function getStatusByCadNo($cad_no)
    {
        $query = "SELECT `status` FROM `requests` WHERE `cad_num` like '".$cad_no."'";
        if ($row = $this->GetRow($query))
        {
            return $row['status'];
        }
        return "";
    }

    /**
    * @desc
    */
    function getStatusByID($id)
    {
        $id = intval($id);
        $query = "SELECT `status` FROM `requests` WHERE `id` = ".$id;
        if ($row = $this->GetRow($query))
        {
            return $row['status'];
        }
        return "";
    }

    /**
    * @desc
    */
    function getRequestByID($id,$code)
    {
        $id   = intval($id);
        $code = trim(addslashes($code));
        $query = "SELECT * FROM `requests` WHERE `id` = ".$id;

        //Временно делаем проверку без кода
        //$query .= " AND `code` LIKE '".$code."'";

        if ($row = $this->GetRow($query))
        {
            $row['fpath'] = ($row['file'])? PROJECT_URL.FILES_FOLDER.$row['request_num']."/".$row['file'] : NULL ;
            return $row;
        }
        return array();
    }

    /**
    * @desc
    */
    function getRequestByIDCheckCode($code,$id)
    {
        $id = intval($id);
        $code = trim($code);

        //Временно делаем проверку без кода
        //$query = "SELECT * FROM `requests` WHERE `id` = ".$id." AND `code` LIKE '".$code."'";
        $query = "SELECT * FROM `requests` WHERE `id` = ".$id;

        if ($row = $this->GetRow($query))
        {
            $row['fpath'] = ($row['file'])? PROJECT_URL.FILES_FOLDER.$row['file'] : NULL ;
            return $row;
        }
        return array();
    }

    /**
    * @desc
    */
    function getAdjacentRegions()
    {
        $this->adj_regs = array();
        $query = "SELECT * FROM `adjacent_regions` WHERE 1;";
        if ($res = $this->GetRows($query))
        {
            foreach ($res as $row)
            {
                $id1 = $row['region_id1'];
                $id2 = $row['region_id2'];
                if (!isset($this->adj_regs[$id1])) $this->adj_regs[$id1] = array();
                $this->adj_regs[$id1][$id2] = 1;
                if (!isset($this->adj_regs[$id2])) $this->adj_regs[$id2] = array();
                $this->adj_regs[$id2][$id1] = 1;
            }
        }
        return $this->adj_regs;
    }


     /**
    * @desc
    */
    /*function getRegions()
    {
        $regions = array(
            "01" => "Адыгея",
            "02" => "Башкортостан",
            "03" => "Бурятия",
            "04" => "Алтайский",
            "05" => "Дагестан",
            "06" => "Ингушетия",
            "07" => "Кабардино",
            "08" => "Калмыкия",
            "09" => "Карачаево",
            "10" => "Карелия",
            "11" => "Коми",
            "12" => "Марий",
            "13" => "Мордовия",
            "14" => "Якутия",
            "15" => "Осетия",
            "16" => "Татарстан",
            "17" => "Тыва",
            "18" => "Удмуртская",
            "19" => "Хакасия",
            "20" => "Чеченская",
            "21" => "Чувашская",
            "22" => "Алтайский",
            "23" => "Краснодарский",
            "24" => "Красноярский",
            "25" => "Приморский",
            "26" => "Ставропольский",
            "27" => "Хабаровский",
            "28" => "Амурская",
            "29" => "Архангельская",
            "30" => "Астраханская",
            "31" => "Белгородская",
            "32" => "Брянская",
            "33" => "Владимирская",
            "34" => "Волгоградская",
            "35" => "Вологодская",
            "36" => "Воронежская",
            "37" => "Ивановская",
            "38" => "Иркутская",
            "39" => "Калининградская",
            "40" => "Калужская",
            "41" => "Камчатский",
            "42" => "Кемеровская",
            "43" => "Кировская",
            "44" => "Костромская",
            "45" => "Курганская",
            "46" => "Курская",
            "47" => "Ленинградская",
            "48" => "Липецкая",
            "49" => "Магаданская",
            "50" => "Московская",
            "51" => "Мурманская",
            "52" => "Нижегородская",
            "53" => "Новгородская",
            "54" => "Новосибирская",
            "55" => "Омская",
            "56" => "Оренбургская",
            "57" => "Орловская",
            "58" => "Пензенская",
            "59" => "Пермский",
            "60" => "Псковская",
            "61" => "Ростовская",
            "62" => "Рязанская",
            "63" => "Самарская",
            "64" => "Саратовская",
            "65" => "Сахалинская",
            "66" => "Свердловская",
            "67" => "Смоленская",
            "68" => "Тамбовская",
            "69" => "Тверская",
            "70" => "Томская",
            "71" => "Тульская",
            "72" => "Тюменская",
            "73" => "Ульяновская",
            "74" => "Челябинская",
            "75" => "Забайкальский",
            "76" => "Ярославская",
            "77" => "Москва",
            "78" => "Петербург",
            "79" => "Еврейская",
            "80" => "Забайкальский",
            "81" => "Пермский",
            "82" => "Камчатский",
            "83" => "Ненецкий",
            "84" => "Красноярский",
            "85" => "Иркутская",
            "86" => "Ханты",
            "87" => "Чукотский",
            "88" => "Красноярский",
            "89" => "Ямало",
            "90" => "Крым",
            "91" => "Севастополь",
        );
        $this->regions   = array();
        $this->reg_codes = array();
        $query = "SELECT * FROM `regions` WHERE 1";
        if ($res = $this->GetRows($query))
        {
            foreach ($res as $row)
            {
                $id   = $row['id'];
                $num  = $row['num'];
                $name = $row['name'];
                $this->regions[$id] = $row;
                $this->reg_codes[$num] = $row;//$name;
            }
        }
        $this->getAdjacentRegions();
        return $this->reg_codes;
    }

     /**
    * @desc
    */
    /*function getRegion($cadno)
    {
        if (!$this->reg_codes) $this->reg_codes = $this->getRegions();
        if ($cadno)
        {
            $code_arr = explode(":",$cadno);
            $code     = array_shift($code_arr);
            if (isset($this->reg_codes[$code])) return $this->reg_codes[$code];
        }
        return false;
    }


    */


    /* Получает ключ для клиента по его коду */
    function getEgrnKey($cadno, $apikey){
        $query = "SELECT `id` FROM `clients` WHERE `is_active` = 1 AND `apikey` LIKE '$apikey'";
        if ( !($row = $this->GetRow($query)) )
        {
            return false;
        }

        //Получили id клиента
        $clientid = $row['id'];

        //Если сутки уже был заказ на такой кадномер и его ключ принадлежит этому клиенту, присваиваем этот ключ
        $limit = time() - 60*1440; // 1440 минут (сутки) ограничение
        $query = "SELECT `code` FROM `requests` WHERE `cad_num` LIKE '$cadno' AND `created_at` >= '".date("Y-m-d H:i:s",$limit)."'";

        //Если есть такой запрос
        if ( $row = $this->GetRow($query) )
        {
            $code = $row['code'];

            //Проверяем принадлежит ли он этому клиенту
            $query = "SELECT `egrn_key` FROM `keys` WHERE `client_id` LIKE $clientid";
            if ( $row = $this->GetRow($query) )
            {
                return $row['egrn_key'];
            }
        }

        //Получаем все коды этого клиента
        $query = "SELECT `egrn_key` FROM `keys` WHERE `client_id` = $clientid";
        if ( !($row = $this->GetRows($query)) )
        {
            return false;
        }

        //Отдаем рандомный ключ
        $key = array_rand ( $row );
        return $row[$key]['egrn_key'];

    }


    /* Получает ключ для клиента по его коду, но берет самый свободный*/
    function getEgrnKeyWithMinOrders($cadno, $apikey){
        $query = "SELECT `id` FROM `clients` WHERE `is_active` = 1 AND `apikey` LIKE '$apikey'";
        if ( !($row = $this->GetRow($query)) )
        {

            return false;
        }

        //Получили id клиента
        $clientid = $row['id'];

        //Если сутки уже был заказ на такой кадномер и его ключ принадлежит этому клиенту, присваиваем этот ключ
        /*$limit = time() - 60*1440; // 1440 минут (сутки) ограничение
        $query = "SELECT `code` FROM `requests` WHERE `cad_num` LIKE '$cadno' AND `created_at` >= '".date("Y-m-d H:i:s",$limit)."'";

        //Если есть такой запрос
        if ( $row = $this->GetRow($query) )
        {
            $code = $row['code'];

            //Проверяем принадлежит ли он этому клиенту
            $query = "SELECT `egrn_key` FROM `keys` WHERE `client_id` LIKE $clientid";
            if ( $row = $this->GetRow($query) )
            {
                return $row['egrn_key'];
            }
        }*/

        //Получаем такой код, к которому меньше всего привязано новых заказов
        $query = "SELECT keys.egrn_key, COUNT(requests.code) AS count
                  FROM `keys` LEFT JOIN `requests`
                  ON keys.egrn_key = requests.code AND requests.request_num is NULL AND requests.status != 'stop'
                  WHERE keys.client_id = $clientid AND keys.is_active = 1
                  GROUP BY keys.id
                  ORDER BY count ASC LIMIT 1
                  ";

        if ( !($row = $this->GetRow($query)) )
        {
            return false;
        }

        return $row['egrn_key'];
    }

    /**
    * @desc Получим данные по кадастровому номеру
    */
    function createRequest($info)
    {
        $cadno  = isset($info->cadno) ? trim($info->cadno) : false;
        $code   = isset($info->apikey)  ? trim($info->apikey)  : false;
        $document = isset($info->document)  ? trim(mb_strtolower($info->document,"UTF-8"))  : false;
        //$region = isset($info->region)? trim($info->region): false;
        $region = $this->RG->getRegion($cadno);

        if (!$cadno || !$region || !$code || !$document) {
            $this->wmsg('Нет данных!', NL);
            return false;
        }

        /* Начало дополнения с кодами (нам нужно чтобы у одного апикей могло быть несколько кодов) */
        //Получаем код росреестра для заказа этого клиентиа
        $egrnCode = $this->getEgrnKeyWithMinOrders($cadno, $code);

        if (!$egrnCode) {
            $this->wmsg('Нет найден ключ для этого клиента!', NL);
            return false;
        }
        $code = $egrnCode;
        /* Конец дополнения с кодами */


        $doc_types = array("xzp"=>"выписка об основных характеристиках и правах","sopp"=>"выписка о переходе прав на объект недвижимости",);
        if (!isset($doc_types[$document]))
        {
            $this->wmsg('Ошибочный тип выписки!', NL);
            return false;
        }

        if (!preg_match("~\w{8}\-\w{4}\-\w{4}\-\w{4}\-\w{12}~is",$code)) {
            $this->wmsg('Ошибочный формат apikey!', NL);
            return false;
        }

        $data = array(
            'cad_num'   => addslashes(trim($cadno)),
            'code'      => addslashes(trim($code)),
            'region'    => addslashes(trim($region['name'])),
            'document'  => addslashes(trim($document)),
            'status'    => 'new',
        );
        if (!$this->checkRequest($data['cad_num'],$document))
        {
            $this->wmsg('Номер был добавлен в список менее 10 минут назад!', NL);
            return false;
        }
        $ok = $this->Insert('requests', $data);
        $info = array('id' => $this->insert_id);
        return $info;
    }

    /**
    * @desc Получим данные по кадастровому номеру
    */
    function getOrderFile($info)
    {
        /*
        INVALID_FORMAT - неверный запрашиваемый формат
        NOT_COMPLETED - заказ еще в работе
        ORDER_NOT_FOUND - заказ с таким id не найден или не принадлежит переданному  apikey
        */
        $id   = isset($info->order_id) ? intval($info->order_id) : false;
        $ext  = isset($info->format) ?   trim($info->format) : false;
        $code  = isset($info->apikey) ?    trim($info->apikey): false;
        if ((!$id)||(!$ext)||(!$code)) {
           $this->wmsg("Нет данных",NL);
            return false;
        }
        $ext = mb_strtolower($ext,"UTF-8");
        $formats = array(
            "zip"  => "zip",
            "pdf"  => "pdf",
            "html" => "html",
            "xml"  => "xml",
            "report_html"  => "html",
            "report_pdf"  => "pdf",
        );

        if (!isset($formats[$ext])) {
            $err = array(
                "error_code" => "INVALID_FORMAT",
                "error_text" => "Неверный запрашиваемый формат",
            );
            $this->wmsg($err,NL);
            return false;
        }
        if (!$request = $this->getRequestByIDCheckCode($code,$id))
        {
            $err = array(
                "error_code" => "ORDER_NOT_FOUND",
                "error_text" => "Заказ с таким id не найден или не принадлежит переданному  apikey",
            );
            $this->wmsg($err,NL);
            return false;
        }
        if ((!$rq_num = $request['request_num'])||(!$request['file']))
        {
            $err = array(
                "error_code" => "NOT_COMPLETED",
                "error_text" => "Заказ еще в работе!",
            );
            $this->wmsg($err,NL);
            return false;
        }
        $dir = PROJECT_PATH.FILES_FOLDER.$rq_num;
        $fname = (isset($request[$ext]))? $request[$ext] : "";
        if ($ext == "zip") $fname = $request['file'];

        if ($ext == "pdf") $fname = $request['sign_pdf'];

        if ($ext == "report_pdf") $fname = $request['report_pdf'];

        if ($ext == "report_html") $fname = $request['report_html'];

        $path = $dir."/".$fname;
        //print $fname." ".$path;
        if ((!$fname)||(!file_exists($path)))
        //if (!$fname = $this->getFileFromDir($dir,$ext))
        {
            $err = array(
                "error_code" => "FILE_NOT_FOUND",
                "error_text" => "Файл с таким форматом не найден!",
            );
            $this->wmsg($err,NL);
            return false;
        }
        //var_dump($fname);

        $file = file_get_contents($path);
        $url = FILES_FOLDER.$rq_num."/".$fname;
        $known_mime_types=array(
            "htm" => "text/html",
            "exe" => "application/octet-stream",
            "zip" => "application/zip",
            "doc" => "application/msword",
            "jpg" => "image/jpg",
            "php" => "text/plain",
            "xls" => "application/vnd.ms-excel",
            "ppt" => "application/vnd.ms-powerpoint",
            "gif" => "image/gif",
            "pdf" => "application/pdf",
            "report_pdf" => "application/pdf",
            "txt" => "text/plain",
            "html"=> "text/html",
            "report_html"=> "text/html",
            "png" => "image/png",
            "jpeg"=> "image/jpg"
        );
        $type = $known_mime_types[$ext];
        header('Content-Type: '.$type);
        header("Content-Transfer-Encoding: Binary");
        header("Content-disposition: attachment; filename=\"".$fname."\"");
        readfile ($path);
        exit();
        //echo $file;

        //print $url;
        /*
        $data = array(
        '<a href="'.$url.'" target="_blank" download="'.$url.'">'.$url.'</a>',
        );
        return $data;
        */
        //print '<a href="'.$url.'" target="_blank" download="'.$url.'">'.$fname.'</a>';
        //header("Location: ".$url);

        //print $file;
        //var_dump($fname);
        die;

    }

    /**
    * @desc Получим данные по кадастровому номеру
    */
    function getFileFromDir($dir, $ext)
    {
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
    * @desc Получим данные по кадастровому номеру
    */
    function checkStatus($info)
    {
        $id     = isset($info->id) ? intval($info->id) : false;
        $code   = isset($info->apikey)  ? trim($info->apikey)  : false;
        if (!$id || !$code ) {
            $this->wmsg('Нет данных!', NL);
            return false;
        }
        if (!$request = $this->getRequestByID($id,$code))
        {
            $this->wmsg('Номера нет в списке или он не принадлежит данному коду!', NL);
            return false;
        }

        $status_code = trim(mb_strtolower($request['status']));
        //if (isset($this->rq_statuses[$status_code])) $request['status'] = $this->rq_statuses[$status_code];
        //$request['rq_status'] = $request['status'];
        $request['status'] = "";
        if ($status_code == "new")        $request['status'] = "Документ еще не заказан";
        if ($status_code == "ok")         $request['status'] = "В работе";
        if ($status_code == "processed")  $request['status'] = "Подача заявки";
        if ($status_code == "error")      $request['status'] = "Ошибка при обработке";
        if ($status_code == "stop")       $request['status'] = "Ошибка при обработке";
        if ($request['pdf'] && $request['sign_pdf'])              $request['status'] = "Завершен";
        if (preg_match("~проверка\s+не\s+пройдена~isu",$request['order_status'])) $request['status'] = "Завершено с ошибкой";
        /*
        "Документ еще не заказан" - вместо NEW
        "В работе" - вместо OK
        "Выполнен" - когда документ завершен
        "Ошибка при обработке" - когда росреестр вернул какую то ошибку
        */
        //$info = array('status' => $request['status'],'request_num'=>$request['request_num']);
        $info = $request;
        return $info;
    }

    /**
    * @desc Получим данные о готовности отчетов
    */
    function checkReportStatus($info)
    {

        $id     = isset($info->id) ? intval($info->id) : false;
        $code   = isset($info->apikey)  ? trim($info->apikey)  : false;
        if (!$id || !$code ) {
            $this->wmsg('Нет данных!', NL);
            return false;
        }
        if (!$request = $this->getRequestByID($id,$code))
        {
            $this->wmsg('Номера нет в списке или он не принадлежит данному коду!', NL);
            return false;
        }

        $status_code = trim(mb_strtolower($request['status']));
        //if (isset($this->rq_statuses[$status_code])) $request['status'] = $this->rq_statuses[$status_code];
        //$request['rq_status'] = $request['status'];
        $request['status'] = "";
        if ($status_code == "new")        $request['status'] = "Документ еще не заказан";
        if ($status_code == "ok")         $request['status'] = "В работе";
        if ($status_code == "processed")  $request['status'] = "Подача заявки";
        if ($status_code == "error")      $request['status'] = "Ошибка при обработке";
        if ($status_code == "stop")       $request['status'] = "Ошибка при обработке";
        if ($request['report_html'] && $request['report_pdf'])              $request['status'] = "Завершен";
        if (preg_match("~проверка\s+не\s+пройдена~isu",$request['order_status'])) $request['status'] = "Завершено с ошибкой";
        /*
        "Документ еще не заказан" - вместо NEW
        "В работе" - вместо OK
        "Выполнен" - когда документ завершен
        "Ошибка при обработке" - когда росреестр вернул какую то ошибку
        */
        //$info = array('status' => $request['status'],'request_num'=>$request['request_num']);
        $info = $request;
        return $info;
    }

    /**
    * @desc
    */
    function wmsg($msg, $nl = true)
    {
        if (is_array($msg))
        {
            foreach($msg as $mk=>$mv)
            {
                if ($this->log) {
                    if ($nl) print NL;
                    print $mk."=>".$mv;
                }
                if (is_string($mk))
                {
                    $this->msg[$mk] = $mv;
                }
                else
                {
                    $this->msg[] = $mv;
                }
            }
        }
        else
        {
            if ($this->log) {
                if ($nl) print NL;
                print $msg;
            }
            $this->msg[] = $msg;
        }
    }

    /**
    * @desc
    */
    function getResultData($info)
    {
        $data = new stdClass();
        $data->status   = $info ? 1 : 0;
        $data->data     = $info ? $info : NULL;
        $data->msg      = $this->msg;
        return $data;
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

}
?>
