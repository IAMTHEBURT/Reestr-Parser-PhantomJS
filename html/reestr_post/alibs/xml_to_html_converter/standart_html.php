<?php
	require_once __DIR__.'/xsl_rosreestr/xml_rosreestr.php';
	require_once __DIR__.'/prepair_first_page.php';
	require_once __DIR__.'/prepair_first_page_pravo.php';
	require_once __DIR__.'/lib/simple_parser/simple_html_dom.php';
	require_once __DIR__.'/lib/mpdf/autoload.php';

	chdir('/var/www/html/reestr_post');
  require_once "config.php";
  require_once "alibs/visual.class.php";

	error_reporting(E_ERROR | E_PARSE);


	@$params = shrGetParams($argv, $_REQUEST);
	if (isset($params['test'])) { define('NL', "<br>"); define('SP', "&nbsp;"); print '<meta charset="utf-8">'; }
	else { define('NL', "\n"); define('SP', " "); }
	$cnt    = isset($params['cnt']) ? intval(trim($params['cnt'])) : 100;
	$skip   = isset($params['skip']) ? intval(trim($params['skip'])) : 0;
	$mode   = isset($params['mode']) ? trim($params['mode']) : false;

	$P = new CVisual(PROJECT_PATH, FILES_FOLDER);
	$P->SetDBParams(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$P->Connect();


	$rows = $P->getTestRows($cnt, $skip);
	print 'Записей - '.count($rows).PHP_EOL;


	foreach($rows as $row){
		//Номер документа
	  $report_number = $row['request_num'];
		print("Начинаю ".$row['request_num'].PHP_EOL);
	  //Имя XML
	  $xml_filename = $row['xml'];
		//Имя нового HTML
		$new_html_file = $report_number.".html";
	  //Куда сохраняем результат
	  $result_path = "/var/www/html/reestr_post/files/$report_number/$new_html_file";
		//Дирректория документа
	  $doc_dirrectory = "/var/www/html/reestr_post/files";
		//Путь к файлу XML
	  $path_to_xml = "$doc_dirrectory/$report_number/$xml_filename";

		if (filesize($path_to_xml) > 211100000){
			print("Не можем обработать этот файл из-за большого размера \r\n");
			continue;
		}

		$str_content = file_get_contents($path_to_xml) or die("failed to load content");

		/** Преобразование в XML документ со стилями **/
		$xml = new DOMDocument;
		$xml->load($path_to_xml);

		$xsl = new DOMDocument;
		$xsl->load(xsl_ros($str_content, $xml_rosreestr));

		$proc = new XSLTProcessor;
		$proc->importStyleSheet($xsl);

	  // преобразование в XML в переменную str_content
		$str_content = $proc->transformToXML($xml);

		$html = str_get_html($str_content);

		$doc = new DOMDocument();
		$doc->loadHTML($html);

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




		$ps = $doc->getElementsByTagName('p');

		$nodeId = false;
		foreach ($ps as $key => $p) {
				//Ищем получаетля и берем последний ключ (<tr> его содержащий)
				$pos = strpos($p->nodeValue, "запроса от");
				if($pos !== false){
					$nodeId = $key;
				}
		}

		$html = str_replace("Львов Даниил Сергеевич", "", $html);
		$html = str_replace("Горева Ирина Михайловна", "", $html);
		$html = str_replace("Смагин Андрей Игоревич", "", $html);
		$html = str_replace("Львов Иван Сергеевич", "", $html);
		$html = str_replace("Львов Даниил Сергеевич", "", $html);

		$str_content = $html;

		if(strlen($str_content) < 20){
				print("Длина файла < 20 символов, пропускаю \r\n");
				continue;
		}

		file_put_contents($result_path, $str_content);
		print("File saved to $result_path");


		if (!is_file($result_path)) { print 'FAIL '; return false; }
		print 'OK ';

		$data = array('html' => addslashes(trim($new_html_file)));
		$ok = $P->Update('requests', $data, 'id='.$row['id']);
		print $ok ? 'dbOK ' : 'dbFAIL';

		print(PHP_EOL);
}
?>
