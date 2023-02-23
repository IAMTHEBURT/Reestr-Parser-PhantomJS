<?php
	chdir('/var/www/html/reestr_post');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require_once 'config.php';

    @$params = shrGetParams($argv, $_REQUEST);
    if (isset($params['test'])) { define('NL', "<br>"); define('SP', "&nbsp;"); print '<meta charset="utf-8">'; }
    else { define('NL', "\n"); define('SP', " "); }
    $test = isset($params['test']) ? 1 : 0;
    $action = isset($params['action']) ? trim($params['action']) : false;


    // Парсер
    $P = new CApi(DB_HOST, DB_USER, DB_PASS, DB_NAME, false);
    $P->Connect();
    $P->preapareData();
    $P->log = isset($params['log']);
    $params = (object) $params;


    $info = false;
    switch ($action) {
        case 'create' : $info = $P->createRequest($params); break;
        case 'status' : $info = $P->checkStatus($params); break;
				case 'report_status' : $info = $P->checkReportStatus($params); break;
        case 'file'   : $info = $P->getOrderFile($params); break;
    }

    $rdata = $P->getResultData($info);
    if ($test) $P->printArray($rdata, NL);
    else print json_encode($rdata);

    $P->CloseConnect();
?>
