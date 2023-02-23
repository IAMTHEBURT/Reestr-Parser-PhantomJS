<?php

	$xml_rosreestr = array(
		'https://portal.rosreestr.ru/xsl/EGRP/Reestr_Extract_Big/OKS/07/Common.xsl' => 'common1.xsl',
		'https://portal.rosreestr.ru/xsl/EGRP/Reestr_Extract_Big/ROOM/07/Common.xsl' => 'common2.xsl',
		'https://portal.rosreestr.ru/xsl/EGRP/Reestr_Extract_Big/ZU/07/Common.xsl' => 'common3.xsl',
		'https://portal.rosreestr.ru/xsl/EGRP/Reestr_Extract_List/07/Common.xsl' => 'common4.xsl',
	);

/* Удаляем строки из документа "Объект недвижимости" */
	$delete_str = array (
		'Выписка из ЕГРН об объекте недвижимости' => 'Отчет об объекте недвижимости', //замена title
		'<head>' => '<head><style> .d_none{display: none;}</style>',
		'полное наименование органа регистрации прав' => '',
		'Раздел 1' => '',
		'Выписка из Единого государственного реестра недвижимости об объекте недвижимости' => '',
		'Сведения о характеристиках объекта недвижимости' => '',
		'<table class="tbl_container"><tr><th><table class="tbl_section_topsheet">' => '<table class="tbl_container d_none"><tr><th><table class="tbl_section_topsheet d_none">',
		'ФГИС ЕГРН' => '', // Замена шапки на ФГИС

		'На основании запроса ' => 'Данный документ подготовлен на основании запроса в базу ФГИС ЕГРН ',
		'сообщаем, что согласно записям Единого государственного реестра недвижимости' => 'содержит всю информацию из выписки без изменений, но не подкреплен ЭЦП и не может быть использован при обращении в государственные учереждения.
		</br></br><span style="font-size: 11px;">Согласно 120-ФЗ "О внесении изменений в Федеральный закон О государственной регистрации недвижимости" от 30 апреля 2021</span>',

		'выписки:' => '',
		'Государственный регистратор' => '',
		'полное наименование должности' => '',
		'подпись' => '',
		'инициалы, фамилия' => '',
		'М.П.' => '<br>',
		'</body>' => '<script src="./js/first_page.js"></script></body>',

		'<p>Смагин Андрей Игоревич</p>' => '',
		'<p>Львов Даниил Сергеевич</p>' => '',
		'<p>Горева Ирина Михайловна</p>' => '',
		'<p>Смагин Андрей Игоревич</p>' => '',
		'<p>Львов Иван Сергеевич</p>' => '',
		'<p>Львов Даниил Сергеевич</p>' => '',
	);

/* Удаляем строки из документа о переходе прав на объект */
	$delete_str_pravo = array (
		'Выписка из ЕГРП о переходе прав на объект (версия 07)' => 'Отчет о переходе прав на объект',
		'<head>' => '<head><style> .d_none{display: none;}</style>',
		'</body>' => '<script src="./js/first_page_pravo.js"></script></body>',
		'ФГИС ЕГРН' => '',

		'На основании запроса ' => 'Данный документ подготовлен на основании запроса в базу ФГИС ЕГРН ',
		'сообщаем, что в Единый государственный реестр недвижимости внесены записи о государственной регистрации перехода прав на:' => 'содержит всю информацию из выписки без изменений, но не подкреплен ЭЦП и не может быть использован при обращении в государственные учереждения.
		</br></br><span style="font-size: 11px;">Согласно 120-ФЗ "О внесении изменений в Федеральный закон О государственной регистрации недвижимости" от 30 апреля 2021</span>',

		'<td rowspan="0">' => '<td>',
		'Выписка из Единого государственного реестра недвижимости о переходе прав на объект недвижимости' => '',
		'ФЕДЕРАЛЬНАЯ СЛУЖБА ГОСУДАРСТВЕННОЙ РЕГИСТРАЦИИ, КАДАСТРА И КАРТОГРАФИИ' => '',
		'Использование сведений, содержащихся в настоящей выписке, способами или в форме, которые наносят ущерб правам и законным интересам правообладателей, влечет ответственность, предусмотренную законодательством Российской Федерации.' => '',
		'<td colspan="2">Получатель' => '<td colspan="2" id="reciver">Получатель',
		'<td width="40%" class="ful">Государственный регистратор</td>' => '<td width="40%" class="ful" id="gos_reg">Государственный регистратор</td>',
		'Дата' => '',
		'№' => '',
		'width="1%"' => 'width="4%"',

		'<p>Смагин Андрей Игоревич</p>' => '',
		'<p>Львов Даниил Сергеевич</p>' => '',
		'<p>Горева Ирина Михайловна</p>' => '',
		'<p>Смагин Андрей Игоревич</p>' => '',
		'<p>Львов Иван Сергеевич</p>' => '',
		'<p>Львов Даниил Сергеевич</p>' => '',
		'<p>не указан</p>' => '',
	);

	function xsl_ros ($file, $arr) {
		foreach ($arr as $key => $value) {
			// echo 'foreach'.'<br>';
			if (strpos($file, $key)) {
				return __DIR__.'/'.$value;
			}
		}
		echo 'no XSL for current file';
		return false;
	}

	function clean_xml ($file, $arr) {
		foreach ($arr as $key => $value) {
			$file = str_replace($key, $value, $file);

		}
		/* Удаляем "получатель выписки" */
		$file = preg_replace('/<table border="0" cellspacing="0" cellpadding="0" width="100%">\n<tr>\n<td width="40%" class="ful" id="gos_reg">Государственный регистратор/','<table border="0" cellspacing="0" cellpadding="0" width="100%" class="d_none"><tr><td width="40%" class="ful">Государственный регистратор', $file);
		$file = preg_replace('/<tr>\n<td width="1%">3./','<tr class="d_none"><td width="1%">3.', $file);
		$file = preg_replace('/<tr>\n<th class="left vtop">Получатель/','<tr class="d_none">', $file);
		$file = preg_replace('/<table class="tbl_container"><tr><th>\n<br><br><table class="tbl_section_topsheet">/', '<table class="tbl_container d_none"><tr><th>\n<br><br><table class="tbl_section_topsheet d_none">', $file);
		$file = preg_replace('/<table class="tbl_container"><tr><th>\n<br><table class="tbl_section_topsheet">/', '<table class="tbl_container d_none"><tr><th><br><table class="tbl_section_topsheet d_none">', $file);

		return $file;
	}
?>
