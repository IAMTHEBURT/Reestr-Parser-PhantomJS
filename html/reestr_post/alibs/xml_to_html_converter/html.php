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

	$P = new CVisual(PROJECT_PATH, FILES_FOLDER);
	$P->SetDBParams(DB_HOST, DB_USER, DB_PASS, DB_NAME);
	$P->Connect();

	$rows = $P->getRowsForReportHTML(100, 0);
	//$rows = $P->getTestRows();

	print 'Записей - '.count($rows).PHP_EOL;

	foreach($rows as $row){
		//Номер документа
	  $report_number = $row['request_num'];
		print("Начинаю ".$row['request_num'].PHP_EOL);
	  //Имя XML
	  $xml_filename = $row['xml'];
		//Имя нового HTML
		$new_html_file = $report_number."_report.html";
	  //Куда сохраняем результат
	  $result_path = "/var/www/html/reestr_post/files/$report_number/$new_html_file";
		//Дирректория документа
	  $doc_dirrectory = "/var/www/html/reestr_post/files";
		//Путь к файлу XML
	  $path_to_xml = "$doc_dirrectory/$report_number/$xml_filename";


		if (filesize($path_to_xml) > 80000){
			print("Не можем обработать этот файл из-за большого размера");
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

	  //ЕСЛИ XZP
		if (strpos($str_content, 'Выписка из Единого государственного реестра недвижимости о переходе прав на объект недвижимости')) {
			$str_content = add_first_page_pravo($str_content);
			$html = str_get_html($str_content);
			/* Получаем данные по ID */
			$cadast = $html->find('#get_cadast', 0); // Номер кадастра
			if ($cadast) {$cadast = $cadast->next_sibling()->plaintext;} else {$cadast = 'Нет данных';}
			$adress = $html->find('#get_adress', 0); // Адресс
			if ($adress) {$adress = $adress->next_sibling()->plaintext;} else {$adress = 'Данные отсутсвуют';}
			$pravo = $html->find('#get_pravo', 0); // Правообладатель
			if ($pravo) {$pravo = 'Данные доступны';/*$pravo->next_sibling()->plaintext;*/} else {$pravo = 'Данные отсутсвуют';}
			$array_update_data = ['${cadast}' => $cadast,
														'${adress}' => $adress,
														'${pravo}' => $pravo,
														];
			// echo $str_content;
			$str_first_page_pravo = clean_xml($str_first_page_pravo, $array_update_data);
			/* Финальная чистка и добавление первой страницы */
			$str_content = clean_xml($str_content, $delete_str_pravo);
			$str_content = str_replace('<body>', $str_first_page_pravo, $str_content);
			// Удаляем Выписка выдана......
			// file_put_contents('pdf2.html', $str_content);
			$pos = mb_strpos( $str_content, '<td width="4%">3.</td>');

			if ($pos) {
				$str_content = mb_strimwidth($str_content, 0, $pos, '.  </table></html>');
			}
			file_put_contents($result_path, $str_content);
			print("File saved to $result_path");
			//print($str_content);
		}
	  //ЕСЛИ SOPP
	  else {
			$str_content = add_first_page($str_content, $first_page);
			$html = str_get_html($str_content);
			$obrem = strpos($str_content, 'вид:');
			/* Получаем данные по ID */
			if ($html) {
				$cadast = $html->find('#get_cadast', 0); // Номер кадастра
				$adress = $html->find('#get_adress', 0); // Адресс
				$pravo = $html->find('#get_pravo', 0); // Правообладатель
			}
			/* Получаем данные по ID */
			if ($cadast) {$cadast = $cadast->next_sibling()->plaintext;} else {$cadast = 'Нет данных';}
			if ($adress) {$adress = $adress->next_sibling()->plaintext;} else {$adress = 'Данные отсутсвуют';}
			if ($pravo) {$pravo = "Данные доступны";/*$pravo->next_sibling()->next_sibling()->plaintext*/} else {$pravo = 'Данные отсутсвуют';}
			$oper = 'Возможны';
			if ($obrem) {$obrem = "Есть обременения"; $oper = 'Не возможны';} else {$obrem = 'Не зарегистрировано';}

			$array_update_data = [	'${cadast}' => $cadast,
									'${adress}' => $adress,
									'${pravo}' => $pravo,
									'${obrem}' => $obrem,
									'${oper}' => $oper,
									];
			$first_page = clean_xml($first_page, $array_update_data);
			$str_content = str_replace_once('<table ', $first_page.'<table ', $str_content);

			$str_content = clean_xml($str_content, $delete_str);

			/* Сохраняем в директорию на сервере */
			file_put_contents($result_path, $str_content);
			print("File saved to $result_path");
			//print($str_content);
		}

		if (!is_file($result_path)) { print 'FAIL '; return false; }
		print 'OK ';

		$data = array('report_html' => addslashes(trim($new_html_file)));
		$ok = $P->Update('requests', $data, 'id='.$row['id']);
		print $ok ? 'dbOK ' : 'dbFAIL';

		print(PHP_EOL);
}
?>
