<?php
/*
    f628a54b-1e78-496f-88e5-93e52407b921
    Кадастровый номер 77:07:0005001:6894
    Регион Москва
*/

    chdir('/var/www/html/reestr_post');
    require_once "config.php";

    @$params = shrGetParams($argv, $_REQUEST);
    if (isset($params['debug'])) {
        define('NL', "<br>");
        define('SP', "&nbsp;");
    } else {
        define('NL', "\n");
        define('SP', " ");
    }
    if (!isset($params['sid'])) die('Не задан sid!'.NL);
    $sid = intval($params['sid']);
    $rid = (isset($params['rid']))? intval($params['rid']) : 0;
    $code = (isset($params['code']))? $params['code'] : false;

    sleep($sid);

    $LOG = new CLog();

    $RS = new Rosreestr(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    $RS->auth_phantom_script = PROJECT_PATH."script_phantom_auth.js";
    $RS->setSID($sid);
    $row = $RS->GetDataFromDB($rid, $code);

    if (!$row)
    {
        $LOG->AddToLog("--- Start prc.N".$sid." [".date("H:i:s d-m-Y")."] - ROWS PROCESSED OR EMPTY ---");
        $LOG->WriteList();
        die("Empty new avaliable requests!");
    }

    $up_arr = array("status"=>'processed',"order_description"=>"");
    $RS->UpdateRowData($row['id'],$up_arr);
    $LOG->AddToLog("=== Start id=".$row['id']." [".date("H:i:s d-m-Y").", prc.N".$sid." ] ===");
    //$RS->UpdateRowStatus($row['id'],'processed');
    //$LOG = new CLog();
    //$LOG->AddToLog("=== Start id=".$row['id']." [".date("H:i:s d-m-Y").", prc.N".$sid." ] ===");


    $auth_code = $row['code'];
    $cad_no    = $row['cad_num'];
    $region    = $row['region'];
    $doc       = ($row['document'])? trim(mb_strtolower($row['document'])) : $row['document'];
    $document  = ($doc == 'sopp')? "sopp" : "xzp";


    if (!$auth_key = $RS->PhantomAuth())
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: PHANTOM_AUTH_ERROR");
        $LOG->WriteList();
        print "\nPHANTOM_AUTH_ERROR!";die("STOPPED!");
    }
    //url for image
    $file_arr = file($RS->auth_page_file);
    $file = implode("\n",$file_arr);
    $t = "~\<base\s+href\s*=\s*[\"\']([^\"\']+)~isu";
    if (!$base_url = $RS->SearchText($t,$file,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_BASE_URL");
        $LOG->WriteList();
        print "\nNOT_FOUND_BASE_URL!";die("STOPPED!");
    }

 /*********** Авторизация ***********/
    //нулевой запрос, дублирует фантом
    print "\nAuth_process...\n";
    print "\nrow_id=";var_dump($row['id']);
    print "\nurl=";var_dump($RS->auth_post_url);
    $url = $RS->auth_post_url;
    print "\nauth_data=";var_dump($RS->auth_post_data);
    $RS->postfields = $RS->auth_post_data;
    $RS->cookie = $RS->ReadCookieFromFile($RS->cookie_file);
    $cook = $RS->CookToStr($RS->cookie);
    //print "\ncook=";var_dump($cook);
    $res = $RS->ApiRequest($url,$RS->postfields,$RS->cookie);
    print "\n\nauth_first / content_1=";var_dump($res);

    //пустой запрос
    $postfields = $RS->auth_post_key."PID12blurs0PID12ci";
    $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
    print "\n\nauth_blur / content_2=";var_dump($res);

    //focus
    $postfields = $RS->auth_post_key."PID12focuss";
    $res = $RS->ApiRequest($url,$postfields,"",2000000);//2 sec
    print "\n\nauth_focus / content_3=";var_dump($res);

    //insert
    $postfields = $RS->auth_post_key."36PID12ci".$auth_code."PID12curTexts";
    $res = $RS->ApiRequest($url,$postfields,"",1000000);//1 sec
    print "\n\nauth_insert / content_4=";var_dump($res);

    //send 1/3
    $postfields = $RS->auth_post_key."8PID12ciPID12blurs";
    $res = $RS->ApiRequest($url,$postfields,"",2000000);//2 sec
    print "\nauth_blur / content_5=";var_dump($res);

    //send 2/3
    $postfields = $RS->auth_post_key."PID14focuss";
    $res = $RS->ApiRequest($url,$postfields,"",500000);//0.5 sec
    print "\nnauth_focus / content_6=";var_dump($res);

    //send 3/3
    $postfields = $RS->auth_post_key."truePID30stateb1,750,189,false,false,false,false,1,25,7PID30mousedetailss";
    $res = $RS->ApiRequest($url,$postfields,"",500000);//0.5 sec
    print "\nnauth_click / content_7=";var_dump($res);

 /*********** Клик по табу поиск ***********/
    $t = "~\{[^\}]+(PID\d+)[^\}]+поиск\s+объектов~isu";
    if (!$pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."]  ERROR: NOT_AUTHORISED");
        $LOG->WriteList();
        print "\nNOT_AUTHORISED!";die("STOPPED!");
    }

    $postfields = $RS->auth_post_key."125PID0heighti755PID0widthi1802PID0browserWidthi225PID0browserHeightitrue".$pid."disabledOnClickbtrue".$pid."stateb1,564,87,false,false,false,false,1,129,15".$pid."mousedetailss";
    print "\npostfields=";var_dump($postfields);
    $tres = $RS->ApiRequest($url,$postfields,"",2000000); //2 sec
    print "\nsearch_click / content_8=";var_dump($tres);
    //usleep(3000000);//3 sec

 /*********** Заполнение полей поиска объекта ***********/
    $t = "~\{[^\}]+(PID\d+)[^\}]+Кадастровый\s+номер~isu";
    if (!$pid = $RS->SearchText($t,$tres,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."]  ERROR: NOT_FOUND_CADNO_CODE");
        $LOG->WriteList();
        print "\nNOT_FOUND_CADNO_CODE!";die("STOPPED!");
    }

    //ins cad_no
    $postfields = $RS->auth_post_key."783PID0heighti755PID0widthi1802PID0browserWidthi225PID0browserHeighti".$cad_no."".$pid."texts18".$pid."ci";
    //print "\npostfields=";var_dump($postfields);
    $res = $RS->ApiRequest($url,$postfields,"",2000000); //2 sec
    print "\nins_cadno / content_9=";var_dump($res);

    //ins region
    $t = "~\{[^\}]+(PID\d+)[^\}]+[\"\']Регион[\"\']~isu";
    if (!$pid = $RS->SearchText($t,$tres,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."]  ERROR: NOT_FOUND_REGION_CODE");
        $LOG->WriteList();
        print "\nNOT_FOUND_REGION_CODE!";die("STOPPED!");
    }

    $postfields = $RS->auth_post_key."".$region."".$pid."filters0".$pid."pagei";
    //print "\npostfields=";var_dump($postfields);
    $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
    print "\nins_region / content_10=";var_dump($res);

    //sel region
    $t = "~\{[^\}]+[\"\']([^\"\']+[\s\-]\(*)*".$region."[^\}]+[\"\']key[\"\']\s*\:\s*[\"\'](\d+)[\"\']~isu";
    if (!$kid = $RS->SearchText($t,$res,2))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_REGIONS_SEL");
        $LOG->WriteList();
        print "\nNOT_FOUND_REGIONS_SEL!";die("STOPPED!");
    }
    $postfields = $RS->auth_post_key."".$kid."".$pid."selectedc";
    //print "\npostfields=";var_dump($postfields);
    $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
    print "\nsel_region / content_11=";var_dump($res);

    /*********** Клик по найти ***********/
    $t = "~\{[^\}]+(PID\d+)[^\}]+[\"\']Найти[\"\']~isu";
    if (!$pid = $RS->SearchText($t,$tres,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_SEARCH_BUTTON");
        $LOG->WriteList();
        print "\nNOT_FOUND_SEARCH_BUTTON!";die("STOPPED!");
    }

    $postfields = $RS->auth_post_key."750PID0heighti754PID0widthi2402PID0browserWidthi350PID0browserHeightitrue".$pid."stateb1,1066,252,false,false,false,false,1,38,18".$pid."mousedetailss";
    //print "\npostfields=";var_dump($postfields);
    $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
    print "\nsearch_obj_button_click / content_12=";var_dump($res);

    //получение списка объектов
    $t = "~\{[^\}]+(PID\d+)[^\}]+поиск\s+объектов~isu";
    if (!$pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_AUTHORISED-2");
        $LOG->WriteList();
        print "\nNOT_AUTHORISED!";die("STOPPED!");
    }

    $postfields = $RS->auth_post_key."1081".$pid."positionxi123".$pid."positionyi";
    //print "\npostfields=";var_dump($postfields);
    $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
    print "\n get_object_list / content_13=";var_dump($res);
    //usleep(3000000);//3 sec
    if ($RS->CheckEmptyApiResponse($res))
    {
        print "\nwaiting...";
        $t=0;
        $stop = false;
        while(!$stop)
        {
            $t++;
            print " REQUEST_".$t." ";
            if ($t>50) $stop = true;
            $postfields = $RS->auth_post_key."";
            $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
            if (!$RS->CheckEmptyApiResponse($res))
            {
                $stop = true;
                $t = "~[\"\']найдено~isu";
                if (!preg_match($t,$res))
                {
                    $t = "~Не\s+найдены\s+данные\,\s+удовлетворяющие\s+Вашему\s+запросу~isu";
                    if (preg_match($t,$res))
                    {
                        $RG = new CRegion(DB_HOST,DB_USER,DB_PASS,DB_NAME);
                        if ($reg1 = $RG->getRegion($cad_no))
                        {
                            if ($region == $reg1['name'])
                            {
                                if (isset($RG->adj_regs[$reg1['id']]))
                                {
                                    $rchids = array_keys($RG->adj_regs[$reg1['id']]);
                                    $new_rgid = array_shift($rchids);
                                    if (!empty($RG->regions[$new_rgid]['name'] ))
                                    $new_rgnm = $RG->regions[$new_rgid]['name'];
                                    $up_arr = array("status"=>'error','region'=>$new_rgnm);
                                    $RS->UpdateRowData($row['id'],$up_arr);
                                    $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND. REGION CHANGED TO '".$new_rgnm."'!");
                                    $LOG->WriteList();
                                    print "\nOBJECTS_NOT_FOUND!";
                                    die("\nCHANGE REGION TO '".$new_rgnm."'!");
                                }
                            }
                        }
                        $up_arr = array("status"=>'stop','region'=>'',"order_description"=>"Не найдены данные, удовлетворяющие Вашему запросу. Попробуйте изменить запрос");
                        $RS->UpdateRowData($row['id'],$up_arr);
                        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND!YOU_NEED_CHANGE_REQUEST! ATTEMPTS STOPPED.");
                        $LOG->WriteList();
                        print "\nOBJECTS_NOT_FOUND!YOU_NEED_CHANGE_REQUEST!";
                        die("\nSTOPPED!");
                    }
                    else
                    {
                        $RS->UpdateRowStatus($row['id'],'error');
                        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND!");
                        $LOG->WriteList();
                        print "\nOBJECTS_NOT_FOUND!";
                        die("\nSTOPPED!");
                    }
                }
                $t = "~необходимо\s+уточнить\s+параметры\s+запроса[^\]]+[\]]+\s*\,\s*\[[^\]]+(PID\d+)~isu";
                if (!$pid = $RS->SearchText($t,$res,1))
                {
                    $RS->UpdateRowStatus($row['id'],'error');
                    $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: LIST_NOT_LOADED!");
                    $LOG->WriteList();
                    print "\nNOT_LOADED_LIST!";die("STOPPED!");
                }

                $postfields = $RS->auth_post_key."12".$pid."pagelengthi0".$pid."firstToBeRenderedi199".$pid."lastToBeRenderedi0".$pid."firstvisiblei15".$pid."reqfirstrowi185".$pid."reqrowsi
";
                $res2 = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
            }
        }

    }
    else
    {
        $t = "~[\"\']найдено~isu";
        if (!preg_match($t,$res))
        {
            $t = "~Не\s+найдены\s+данные\,\s+удовлетворяющие\s+Вашему\s+запросу~isu";
            if (preg_match($t,$res))
            {
                $RG = new CRegion(DB_HOST,DB_USER,DB_PASS,DB_NAME);
                if ($reg1 = $RG->getRegion($cad_no))
                {
                    if ($region == $reg1['name'])
                    {
                        if (isset($RG->adj_regs[$reg1['id']]))
                        {
                            $rchids = array_keys($RG->adj_regs[$reg1['id']]);
                            $new_rgid = array_shift($rchids);
                            if (!empty($RG->regions[$new_rgid]['name'] ))
                            $new_rgnm = $RG->regions[$new_rgid]['name'];
                            $up_arr = array("status"=>'error','region'=>$new_rgnm);
                            $RS->UpdateRowData($row['id'],$up_arr);
                            $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND. REGION CHANGED TO '".$new_rgnm."'!");
                            $LOG->WriteList();
                            print "\nOBJECTS_NOT_FOUND!";
                            die("\nCHANGE REGION TO '".$new_rgnm."'!");
                        }
                    }
                }
                $up_arr = array("status"=>'stop','region'=>'',"order_description"=>"Не найдены данные, удовлетворяющие Вашему запросу. Попробуйте изменить запрос");
                $RS->UpdateRowData($row['id'],$up_arr);
                $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND!YOU_NEED_CHANGE_REQUEST! ATTEMPTS STOPPED.");
                $LOG->WriteList();
                print "\nOBJECTS_NOT_FOUND!YOU_NEED_CHANGE_REQUEST!";
                die("\nSTOPPED!");
            }
            else
            {
                $RS->UpdateRowStatus($row['id'],'error');
                $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: OBJECTS_NOT_FOUND!");
                $LOG->WriteList();
                print "\nOBJECTS_NOT_FOUND!";
                die("\nSTOPPED!");
            }
        }
    }
    $res_clear =  preg_replace("~\<span[^\>]+display\:none.+?\<\\\/span\>~isu","",$res); //для поиска по кад.номеру
    $t = "~[\"\']найдено[^\]]+[\]]+\s*\,\s*\[[^\]\}]+(PID\d+)~isu";
    if (!$pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_LIST_PID!");
        $LOG->WriteList();
        print "\nNOT_FOUND_LIST_PID!";die("STOPPED!");
    }


    $t = "~[\"\']tr[\"\'].+?\]\]~isu";
    if (! preg_match_all($t,$res_clear,$matches))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_OBJ_ROWS!");
        $LOG->WriteList();
        print "\n!";die("NOT_FOUND_OBJ_ROWS!");
    }

    print "\nlist / content_14=";var_dump($res);

    $rownum = 0;
    $t = "~".addslashes($cad_no)."~isu";
    print "\nt=";var_dump($t);
    foreach($matches[0] as $match)
    {
        $rownum++;
        $text = $match;
        print "\nrow[".$rownum."]=".$text;
        if ($RS->SearchText($t,$text,0))
        {
            print "\nFOUND!STOP_FOREACH!";
            break;
        }
    }

    print "\nresult_rownum=";var_dump($rownum);

    $postfields = $RS->auth_post_key."951PID0heighti755PID0widthi1802PID0browserWidthi363PID0browserHeighti0".$pid."firstvisiblei".$rownum."".$pid."clickedKeys4".$pid."clickedColKeys1,985,324,false,false,false,false,8,-1,-1".$pid."clickEventstrue".$pid."clearSelectionsb".$rownum."".$pid."selectedc";
    $res = $RS->ApiRequest($url,$postfields,"",2000000); //2 sec
    print "\nlist_click / content_15=";var_dump($res);


    $t = "~\{[^\}]+height[^\}]+width[^\}]+image[^\}]+[\"\']src[\"\']\s*\:\s*[\"\']([^\"\']+)~isu";
    if (!$cpt_url = $RS->SearchText($t,$res,1))
    {
        $t = "~Запрос\s+сведений\s+по\s+аннулированным\s+объектам\s+невозможен~isu";
        if ($RS->SearchText($t,$res))
        {
            print "\nSTOPPED_ANNULIRED_OBJECT!";
            $up_arr = array("status"=>'stop',"order_description"=>"Запрос сведений по аннулированным объектам невозможен");
            $RS->UpdateRowData($row['id'],$up_arr);
            $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: ERROR_ANNULIRED_OBJECT! ATTEMPTS STOPPED.");
            $LOG->WriteList();
            die("STOPPED!");
        }
        else
        {
            $RS->UpdateRowStatus($row['id'],'error');
            $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_CAPTCHA_URL ");
            $LOG->WriteList();
            print "\nNOT_FOUND_CAPTHA_URL!";die("STOPPED!");
        }
    }
    $cpt_url = stripslashes($cpt_url);
    $img_url = $base_url.$cpt_url;
    print "\nimg_url=".$img_url;

    $t = "~\{[^\}]+[\"\'](PID\d+)[^\}]+srv-field~isu";
    if (!$cinp_pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_CAPTCHA_INPUT_PID ");
        $LOG->WriteList();
        print "\nNOT_FOUND_CAPTHA_INPUT_PID!";die("STOPPED!");
    }

    $t = "~\{[^\}]+[\"\'](PID\d+)[^\}]+[\"\']отправить\s+запрос~isu";
    if (!$btn_pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_CPTFORMSENT_BUTTON_PID ");
        $LOG->WriteList();
        print "\nNOT_FOUND_CPTFORMSENT_BUTTON_PID!";die("STOPPED!");
    }

    $t = "~\[[^\]]+(PID\d+)[^\]]+optiongroup~isu";
    if (!$radio_pid = $RS->SearchText($t,$res,1))
    {
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_RADIO_PID ");
        $LOG->WriteList();
        print "\nNOT_FOUND_RADIO_PID!";die("STOPPED!");
    }

    $sopp_key = "";
    if ($document == "sopp")
    {
        $t = "~сведения\s+о\s+переходе\s+прав[^\}]+key[\"\']\s*\:\s*[\"\'](\d+)~isu";
        if (!$sopp_key = $RS->SearchText($t,$res,1))
        {
            $RS->UpdateRowStatus($row['id'],'error');
            $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_SOPP_KEY ");
            $LOG->WriteList();
            print "\nNOT_FOUND_SOPP_KEY!";die("STOPPED!");
        }
    }


 /*********** save capture ******************/
    $ctext = $RS->ReadCapture($img_url);
    if (!$ctext) {
        echo 'CAPTCHA_PROBLEM';
        $up_arr = array("status"=>'error',"order_description"=>"Ошибка распознания капчи");
        $RS->UpdateRowData($row['id'],$up_arr);
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: READ_CAPTCHA_PROBLEM");
        $LOG->WriteList();
        //$RS->UpdateRowStatus($row['id'],'error');
        die("STOPPED!");
    }
    print "\ncapture_word=".$ctext;

/********* insert capture code value  *************/
//input code element pid
    $postfields = $RS->auth_post_key."".$cinp_pid."focuss";
    $res = $RS->ApiRequest($url,$postfields,"",3000000); //3 sec
    print "\ncinp_focus / content_16=";var_dump($res);

    // <<<========== click on radio =========== >>>
    if ($document == "sopp")
    {
        $postfields = $RS->auth_post_key."".$sopp_key."".$radio_pid."selectedc";
        $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
        print "\nradio_click / content_17_a=";var_dump($res);

    }

    //ins code
    $postfields = $RS->auth_post_key."".$ctext."".$cinp_pid."texts".mb_strlen($ctext,"UTF-8")."".$cinp_pid."ci";
    $res = $RS->ApiRequest($url,$postfields,"",500000); //0.5 sec
    print "\ncinp_insert / content_17=";var_dump($res);

    $postfields = $RS->auth_post_key."".$cinp_pid."blurs";
    $res = $RS->ApiRequest($url,$postfields,"",500000); //0.5 sec
    print "\ncinp_blur / content_18=";var_dump($res);

    //click button
    $postfields = $RS->auth_post_key."true".$btn_pid."stateb1,597,222,false,false,false,false,1,71,16".$btn_pid."mousedetailss";
    $res = $RS->ApiRequest($url,$postfields,"",2000000); //2 sec
    print "\ncinp_blur / content_19=";var_dump($res);

    $t = "~номер\s+запроса\s*\<b[^\>]*\>([^\<]+)~isu";
    if (!$reques_num = $RS->SearchText($t,$res,1))
    {
        print "\nNOT_FOUND_REQUEST_NUM!";
        print "\n->set status=error!";
        $RS->UpdateRowStatus($row['id'],'error');
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] ERROR: NOT_FOUND_REQUEST_NUM");
        $LOG->WriteList();
    }
    else
    {
        print "\nЗапрос добавлен! NUM=".$reques_num;
        print "\n->set status=ok!";
        $num = addslashes(trim($reques_num));
        $up_arr = array("request_num"=>$num,"status"=>'ok',"order_status"=>"Ожидает заказа");
        $RS->UpdateRowData($row['id'],$up_arr);

        //Обновляем время последнего использования ключа
        $RS->UpdateKeyLastUsed($auth_code);
        
        $LOG->AddToLog("[".date("H:i:s d-m-Y")."] OK: REQUEST_NUM = ".$reques_num);
        $LOG->WriteList();
    }

    die("\n=== END! ===");

//=======================================================
//
// ======================================================
    /*
    $RS->postfields = "70fcb6e4-e290-4200-97b8-ddc69e7ad18c";

     print "\npostfields=";var_dump($RS->postfields);
     $RS->cookie = "JSESSIONID_8=000048kUrbG1wMSzjPIrjkKeARY:1971jhhrc;";
     print "\ncook=";var_dump($RS->cookie);
     $url = "https://rosreestr.ru/wps/portal/p/cc_present/ir_egrn/!ut/p/z1/jY_BCsIwEEQ_KbNurV63NaQlaIgQrLlIThLQ6kH8frW3gtbubeA9ZlZF1anYp2c-p0e-9enyzsdYnkCNkBQLi3ZTQarG6XpfMgB1GANrtkvIVjvPvGMEVnGOb3yhqS7IGk8rSCuWmAzgaJ4_AQz-j5OPH8cVXz4YgKmJ_0ru1xA65PYFAlPB1Q!!/p0/IZ7_01HA1A42KODT90AR30VLN22003=CZ6_01HA1A42K0IDB0ABHOECR63000=NJUIDL=/?windowName=2";
     $RS->LoadPage($url);
     print "\ncontent1=";var_dump($RS->content);
     die;
     /*if (preg_match_all("~set\-cookie\:\s*([^\s]+)\=([^\s]+)~is",$RS->content,$matches,PREG_SET_ORDER))
     {
            foreach ($matches as $match)
            {
                $key = trim($match[1]);
                $val = trim($match[2]);
                $RS->cookie[$key] = $val;
            }
            $cook = $RS->CookToStr($RS->cookie);
            print "\nparsed_cook1=";var_dump($cook);
            $RS->LoadPage($url);
    }
    else
    {
        die;
    }
    print "\ncontent2=";var_dump($RS->content);die;
    die;
    */
    /*
    JSESSIONID_8=0000mWxyVrHEuIhINudg4QkPjKB:19ct8hikv; __utma=224553113.721453281.1564503669.1564503669.1564503669.1; __utmc=224553113; __utmz=224553113.1564503669.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmt=1; __utmb=224553113.1.10.1564503669; _ym_uid=1564503669553941616; _ym_d=1564503669; _ym_isad=2; _ym_visorc_18809125=w

    _ym_visorc_18809125=w; __utmz=224553113.1564409426.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmc=224553113; __utmb=224553113.1.10.1564503557; __utma=224553113.2047701840.1564409426.1564501161.1564503557.9; __utmt=1; JSESSIONID_8=0000nELBOE2AI9XfQUlugdd29hD:19a2vp8dd; _ym_isad=2; yp=1879769463.yrts.1564409463#1879769463.yrtsi.1564409463; i=ptA0mKUvFNO/u+eTaK+IZrFJ9rVETtWoANxzjVJWqHQhindYI8LbU3byrPIGu+96xPxuZSXvEGymlpYkg+h6RJm427s=; yabs-sid=1300302511564409463; yandexuid=6552834781564409463; _ym_uid=1564409426491607589; _ym_d=1564409426
    $RS->cookie = $RS->ReadCookieFromFile($RS->cookie_file);
    $cook = $RS->CookToStr($RS->cookie);
    var_dump($cook);
    die;

    */








?>
