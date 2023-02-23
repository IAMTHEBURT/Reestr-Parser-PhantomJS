<?php
    chdir('/var/www/html/reestr_post');
    require_once "config.php";
    require_once "alibs/visual.class.php";

    @$params = shrGetParams($argv, $_REQUEST);
    if (isset($params['test'])) { define('NL', "<br>"); define('SP', "&nbsp;"); print '<meta charset="utf-8">'; }
    else { define('NL', "\n"); define('SP', " "); }
    $cnt    = isset($params['cnt']) ? intval(trim($params['cnt'])) : 100;
    $skip   = isset($params['skip']) ? intval(trim($params['skip'])) : 0;
    $mode   = isset($params['mode']) ? trim($params['mode']) : false;
    $order   = isset($params['order']) ? trim($params['order']) : "DESC";

    $P = new CVisual(PROJECT_PATH, FILES_FOLDER);

    $P->SetDBParams(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $P->Connect();

    switch ($mode) {
        case 'xml' : {
            $rows = $P->getRowsForXML($cnt, $order);
            print 'Записей - '.count($rows);
            foreach ($rows as $row) {
                print NL.$row['id'].'... ';
                $P->makeXML($row);
                sleep(1);
            }
            break;
        }
        case 'html' : {
            $rows = $P->getRowsForHTML($cnt, $skip, $order);
            print 'Записей - '.count($rows);
            if (count($rows) > 0) {
                $P->captcha = new Captcha(CAPTCHA_DOMAIN, CAPTCHA_KEY, false);
                $P->cook_file = TMP_PATH.'/cook.txt';
                //if (!$P->prepareCookie()) { print 'COOK_ERROR'; break; }
                //$P->ReadCookFromFile();
                foreach ($rows as $row) {
                    print NL.$row['id'].'... ';
                    $P->makeHTML($row);
                    sleep(rand(1,3));
                }
            }
            break;
        }
        case 'pdf' : {
            $rows = $P->getRowsForPDF();
            print 'Записей - '.count($rows);
            foreach ($rows as $row) {
                print NL.$row['id'].'... ';
                $P->makePDF($row);
                sleep(1);
            }
            break;
        }
        //PDF с подписью
        case 'sign_pdf' : {
            $rows = $P->getRowsForSignPDF();
            print 'Записей - '.count($rows);
            foreach ($rows as $row) {
                print NL.$row['id'].'... ';
                $P->makeSignPDF($row);
                sleep(1);
            }
            break;
        }
        //PDF в видет отчета (без подписей реестра)
        case 'report_pdf' : {
            $rows = $P->getRowsForReportPDF(100);
            //$rows = $P->getTestRows();
            print 'Записей - '.count($rows);
            foreach ($rows as $row) {
                print NL.$row['id'].'... ';
                $P->makeReportPDF($row);
                sleep(1);
            }
            break;
        }

    }
    print NL;
    $P->CloseConnect();


?>
