<?php
$first_page = '	<style>
p.pagebreak{page-break-after: always;}
h1, .doc_border {
	width: 800px;
	font-size: 30px;
	margin: 0 auto;
}
.cadastral {
	width: 750px;
	margin: 0 auto;
}
.main_info, .cadastral table {
	width: 100%;
	margin: 0 auto;
}
.cadastral table tr{
	width: 100%;
}
.doc_border {
	margin-top: 20px;
	border: 1px solid #303965;
}
.main_info {
	width: 750px;
	margin: 0 auto;
}
.main_info table {
	text-align: center;
	width: 100%;
}
</style>
<br> <br> <br> <br> <br> <br>
<h1>Отчет об анализе объекта недвижимости на основании выписки "Об основных характеристиках и зарегистрированных правах на объект недвижимости"</h1>
<br> <br> <br> <br> <br> <br>
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
						Сведения и ограничения на объекте:<br> <strong>${obrem}</strong>
				</td>
				<td>
					Операции и сделки с объектом недвижимости: <br><strong>${oper}</strong>
				</td>
				<td>
					Данные о собственниках в ЕГРН:<br><strong>${pravo} </strong>
				</td>
			</tr>
		</table>
	</div>
<br>
<p class="pagebreak"></p>';

	function str_replace_once($search, $replace, $text) {
		$pos = strpos($text, $search);
		return $pos!==false ? substr_replace($text, $replace, $pos, strlen($search)) : $text;
	}

	function add_first_page($file, $first_page) {
		//$file = str_replace_once('<td colspan="2">Кадастровый номер: </td>', '<td colspan="2" id="get_cadast">Кадастровый номер: </td>', $file);
		//$file = str_replace_once('<th class="left vtop">Адрес:</th>', '<th class="left vtop" id="get_adress">Адрес:</th>', $file);
		//$file = str_replace_once('<td width="50%" colspan="2">Правообладатель (правообладатели):</td>', '<td width="50%" colspan="2" id="get_pravo">Правообладатель (правообладатели):</td>', $file);
		//$file = str_replace_once('<td>вид:</td>', '<td id="get_obrem">вид:</td>', $file);

		// $file = str_replace('<body>', $first_page, $file);
		return $file;
	};

?>
