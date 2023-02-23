<?php
    chdir('/var/www/html/reestr_post');
    require_once "config.php";

    $RS = new Rosreestr(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    $RS->auth_phantom_script = PROJECT_PATH."script_phantom_auth.js";
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
    $RS->setSID($sid);
    print NL."SLEEP_".$sid;
    sleep($sid);
    $rows = $RS->GetCheckListFromDB($rid);
    if (!$rows) die("Empty requests list!");
    //var_dump($rows);
    print NL."КОЛИЧЕСТВО КОДОВ: ".count($rows);
    foreach ($rows as $auth_code=>$code_rows)
    {

        print NL."====code ->".$auth_code;
        /*=== auth === */
        for ($i=0;$i<5;$i++)
        {
            print NL."AUTH_TRY_".$i;

            $RS->setSID($sid);
            if (!$auth_key = $RS->PhantomAuth())
            {
                print "\nPHANTOM_AUTH_ERROR!";continue;//die("STOPPED!");
            }
            //url for download file
            $file_arr = file($RS->auth_page_file);
            $file     = implode("\n",$file_arr);
            $t = "~\<base\s+href\s*=\s*[\"\']([^\"\']+)~isu";
            if (!$base_url = $RS->SearchText($t,$file,1))
            {
                print "\nNOT_FOUND_BASE_URL!";continue;//die("STOPPED!");
            }

            //нулевой запрос, дублирует фантом
            print "\nAuth_process...\n";
            print "\nurl=";var_dump($RS->auth_post_url);
            $url = $RS->auth_post_url;
            print "\nauth_data=";var_dump($RS->auth_post_data);
            $RS->postfields = $RS->auth_post_data;
            $RS->cookie = $RS->ReadCookieFromFile($RS->cookie_file);
            $cook = $RS->CookToStr($RS->cookie);
            //print "\ncook=";var_dump($cook);
            $res = $RS->ApiRequest($url,$RS->postfields,$RS->cookie);
            //print "\n\nauth_first / content_1=";var_dump($res);

            //пустой запрос
            $postfields = $RS->auth_post_key."PID12blurs0PID12ci";
            $res = $RS->ApiRequest($url,$postfields,"",1000000); //1 sec
            //print "\n\nauth_blur / content_2=";var_dump($res);

            //focus
            $postfields = $RS->auth_post_key."PID12focuss";
            $res = $RS->ApiRequest($url,$postfields,"",2000000);//2 sec
            //print "\n\nauth_focus / content_3=";var_dump($res);

            //insert
            $postfields = $RS->auth_post_key."36PID12ci".$auth_code."PID12curTexts";
            $res = $RS->ApiRequest($url,$postfields,"",1000000);//1 sec
            //print "\n\nauth_insert / content_4=";var_dump($res);

            //send 1/3
            $postfields = $RS->auth_post_key."8PID12ciPID12blurs";
            $res = $RS->ApiRequest($url,$postfields,"",3000000);//3 sec
            //print "\nauth_blur / content_5=";var_dump($res);

            //send 2/3
            $postfields = $RS->auth_post_key."PID14focuss";
            $res = $RS->ApiRequest($url,$postfields,"",500000);//0.5 sec
            //print "\nnauth_focus / content_6=";var_dump($res);

            //send 3/3
            $postfields = $RS->auth_post_key."truePID30stateb1,750,189,false,false,false,false,1,25,7PID30mousedetailss";
            $res = $RS->ApiRequest($url,$postfields,"",500000);//0.5 sec
            //print "\nnauth_click / content_7=";var_dump($res);

             /*********** Клик по табу мои заявки ***********/
            $t = "~\{[^\}]+(PID\d+)[^\}]+мои\s+заявки~isu";
            if (!$pid = $RS->SearchText($t,$res,1))
            {
                print "\nNOT_AUTHORISED!";continue;//die("STOPPED!");
            }

            //9e88e88c-e3f6-4377-9a3d-1864a79fd3df125PID0heighti755PID0widthi1698PID0browserWidthi426PID0browserHeightitruePID36disabledOnClickbtruePID36stateb1,798,165,false,false,false,false,1,70,15PID36mousedetailss
            $postfields = $RS->auth_post_key."125PID0heighti755PID0widthi1698PID0browserWidthi426PID0browserHeightitrue".$pid."disabledOnClickbtrue".$pid."stateb1,798,165,false,false,false,false,1,70,15".$pid."mousedetailss";
            print "\npostfields=";var_dump($postfields);
            $tres = $RS->ApiRequest($url,$postfields,"",3000000); //3 sec
            //print "\nsearch_click / content_8=";var_dump($tres);

            $t = "~\{[^\}]+(PID\d+)[^\}]+обновить~isu";
            if (!$btn_pid = $RS->SearchText($t,$tres,1))
            {
                print "\nsearch_click / content_8=";var_dump($tres);
                print "\nNOT_FOUND_UPDATE_BUTTON!";
                continue;
                //die("STOPPED!");
            }

            $t = "~\{[^\}]+(PID\d+)[^\}]+[\"\']v[\"\']\s*\:\s*\{[\"\']text[\"\']~isu";
            if (!$inp_pid = $RS->SearchText($t,$tres,1))
            {
                print "\nNOT_FOUND_INPUT_FIELD!";
                continue;
                //die("STOPPED!");
            }
            break;
        }

        print NL." = = = = = = = = = = всего для проверки ".count($code_rows);
        $old_numlen = 0;
        $cnt = 0;
        foreach ($code_rows as $row)
        {
            $cnt++;
            print NL." id=".$row['id'];

            $num = $row['request_num'];

            $numlen = mb_strlen($num,"UTF-8");

            if ($cnt == 1) //первый запрос
            {
                //$postfields = $RS->auth_post_key."1286PID0heighti755PID0widthi1698PID0browserWidthi426PID0browserHeighti".$num."".$inp_pid."texts".$numlen."".$inp_pid."citrue".$btn_pid."stateb1,1075,192,false,false,false,false,1,25,6".$btn_pid."mousedetailss";
                $postfields = $RS->auth_post_key."1289PID0heighti755PID0widthi1698PID0browserWidthi426PID0browserHeighti".$num."".$inp_pid."texts".$numlen."".$inp_pid."citrue".$btn_pid."stateb1,1075,192,false,false,false,false,1,25,6".$btn_pid."mousedetailss";
            }
            elseif ($old_numlen == $numlen)
            {
                //af7a84fe-813d-45cc-8dd7-aaa53f5ad17b80-98691299PID339textstruePID349stateb1,1023,139,false,false,false,false,1,27,6PID349mousedetailss
                $postfields = $RS->auth_post_key."".$num."".$inp_pid."textstrue".$btn_pid."stateb1,1023,139,false,false,false,false,1,27,6".$btn_pid."mousedetailss";
            }
            else
            {
                //af7a84fe-813d-45cc-8dd7-aaa53f5ad17b80-101899810PID339texts12PID339citruePID349stateb1,1023,141,false,false,false,false,1,27,8PID349mousedetailss
                $postfields = $RS->auth_post_key."".$num."".$inp_pid."texts".$numlen."".$inp_pid."citrue".$btn_pid."stateb1,1023,141,false,false,false,false,1,27,8".$btn_pid."mousedetailss";


            }


            //$postfields = $RS->auth_post_key."1286PID0heighti755PID0widthi1698PID0browserWidthi426PID0browserHeighti".$num."".$inp_pid."texts".$numlen."".$inp_pid."citrue".$btn_pid."stateb1,1075,192,false,false,false,false,1,25,6".$btn_pid."mousedetailss";

            print "\npostfields=";var_dump($postfields);
            //print "\nurl=";var_dump($url);
            //print "\ncook=";var_dump($RS->CookToStr($RS->cookie));
            $res = $RS->ApiRequest($url,$postfields,"",3000000); //3 sec
            print "\nupdte_button_click / content_9=";var_dump($res);

            $old_numlen = $numlen;

            //получение данных объекта
            $t = "~всего\s+запросов\s*\:\s*(\d+)~isu";
            if (!$rqnt = $RS->SearchText($t,$res,1))
            {
                //$RS->UpdateRowStatus($row['id'],'error');
                print "\nNOT_FOUND_OBJECT_COUNT!".NL." CONTINUE! ";
                //die("STOP!");
                continue;
            }
            if ($rqnt>1)
            {
                print NL."OBJECT_COUNT > 1!".NL." CONTINUE! ";
                //die("STOP!");
                continue;
            }

            $t = "~[\"\']".$num."[\"\'].+?xmlns[^\}]+\}\s*\,\s*[\"\']([^\"\']+)~isu";
            if (!$status = $RS->SearchText($t,$res,1))
            {
                //$RS->UpdateRowStatus($row['id'],'error');
                print "\nNOT_FOUND_OBJECT!".NL." CONTINUE! ";
                //die("STOP!");
                continue;
            }

            $t = "~[\"\']description[\"\']\s*\:\s*[\"\']([^\"\']+)~isu";
            if ($descr = $RS->SearchText($t,$res,1))
            {
                $descr =  html_entity_decode($descr);
            }

            $t = "~[\"\']".$num."[\"\'].+?download[^\}]+[\"\']src[\"\']\s*\:\s*[\"\']([^\"\']+)~isu";
            if ($durl = $RS->SearchText($t,$res,1))
            {
                $durl = $base_url.stripslashes($durl);
            }
            /* ====== update status ====*/
            print NL."status=".$status;
            print NL."url=" .$durl;
            $status = html_entity_decode($status);
            $up_arr = array(
                'order_status'=> trim($status),
            );
            $up_arr['order_description'] = ($descr) ? trim($descr) : "";
            if ($durl)
            {
                $pinf = pathinfo($durl);
                $ext = $pinf['extension'];
                $ext = trim($ext,"=");
                $fname = "Response-".$num.".".$ext;
                $folder = FILES_FOLDER.$num."/";
                @mkdir($folder);
                @chmod($folder,0777);
                $new_pth = FILES_FOLDER.$num."/".$fname;
                $up_arr['url']  = trim($durl);
                $up_arr['file'] = trim($fname);
                print NL."file=";var_dump($fname);
                unlink($new_pth);
                print NL."new=".$new_pth;
                $drs  = $RS->downloadZipFile($durl,$new_pth);
                if (!$drs)
                {
                    $up_arr['url']  = '';   
                    $up_arr['order_status']  = 'Ошибка копирования файла';   
                }
                //$drs2 = $RS->downloadZipFile2($durl,$new_pth);
                //print NL."drs1=";var_dump($drs);
                //print NL."drs2=";var_dump($drs2);
                //die;
            }
            print NL."UPDATE_DATA!";
            $RS->UpdateRowStatusData($row['id'],$up_arr);

        }

    }



?>
