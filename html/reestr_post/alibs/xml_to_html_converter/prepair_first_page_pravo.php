<?php
	$str_first_page_pravo = '<body>
	<style>
	p.pagebreak{page-break-after: always;}

	.file {
		width: 20cm;
		margin: 0 auto;
	}
	.container_pravo {
		width: 20cm;
		margin: 0 auto;
	}
	h1, .doc_border {
		font-size: 30px;
		margin: 0 auto;
		width: 80%;
		text-align: center;
	}
	.cadastral {
		width: 80%;
		margin: 0 auto;
	}
	.main_info, .cadastral table {
		width: 80%;
		margin: 0 auto;
	}
	.cadastral table tr{
		width: 100%;
	}
	.cadastral table tr td{
		width: 45%;
	}
	.doc_border {
		margin: 0 auto;
		width: 70%;
		border: 1px solid #303965;
	}
	.main_info {
		width: 100%;
		margin: 0 auto;
	}
	.main_info table {
		text-align: center;
		width: 100%;
	}
	</style>
	<div class="container_pravo">
		<br> <br> <br> <br> <br> <br><br> <br> <br> <br><br> <br> <br> <br> <br><br> <br>
		<h1>Отчет об анализе объекта недвижимости на основании выписки "Переход прав на объект недвижимости"</h1>
		<br> <br> <br> <br> <br> <br> <br> <br> <br> <br>

			<div class="cadastral" style="display: none;">
				<table>
					<tr>
						<td>
							<strong>Кадастровый номер:</strong> <br>${cadast}
						</td>
						<td>
							<strong>Адрес:</strong><br>${adress}
						</td>
					</tr>
				</table>
			</div>

			<br> <br> <br> <br> <br> <br> <br>
			<div class="doc_border"></div><br><br><br>
			<div class="main_info" style="display: none;">
				<table>
					<tr>
						<td>
							Данные о собственниках в ЕГРН:<br><strong>${pravo}</strong>
						</td>
					</tr>
				</table>
			</div>

	</div>
	<p class="pagebreak"></p>';
/* Добавляем ID для JS обработки */
	function add_first_page_pravo($file) {
		//$file = str_replace_once('<td colspan="2" width="31%">Кадастровый номер:</td>', '<td colspan="2" width="31%" id="get_cadast">Кадастровый номер:</td>', $file);
		//$file = str_replace_once('<td colspan="2">Адрес:</td>', '<td colspan="2" id="get_adress">Адрес:</td>', $file);
		//$file = str_replace_once('<td rowspan="1">правообладатель:</td>', '<td rowspan="1" id="get_pravo">правообладатель:</td>', $file);
		//$file = str_replace_once('<td rowspan="2">правообладатель:</td>', '<td rowspan="2" id="get_pravo">правообладатель:</td>', $file);
		//$file = str_replace_once('<td>вид:</td>', '<td id="get_obrem">вид:</td>', $file);
		return $file;
	};
	/* Добавляем первую страницу с переменными */

?>
