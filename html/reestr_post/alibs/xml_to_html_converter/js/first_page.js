return true;

let cadast = document.getElementById('get_cadast').nextElementSibling.textContent;
let adress = document.getElementById('get_adress').nextElementSibling.textContent;
let pravo = document.getElementById('get_pravo');
let brem = document.getElementById('get_obrem');
let oper = 'Возможны';

if (pravo) {
	pravo = pravo.nextElementSibling.nextElementSibling.textContent;
	if (pravo == 'данные о правообладателе отсутствуют') {
		pravo = 'Не обнаружены';
	}
} else {
	pravo = 'Не указаны';
}
if (brem) {
	brem = brem.nextElementSibling.textContent;
	operation = "Невозможны";
} else {
	brem = 'Не обнаружены';
}

let str_first_page = `	<style>
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
		border: 3px solid #1f44ff;
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
	<h1>Отчет об анализе объекта недвижимости по выписке "Об основных характеристиках и зарегистрированных правах"</h1>
	<br> <br> <br> <br> <br> <br>
		<div class="cadastral">
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
		<div class="main_info">
			<table>
				<tr>
					<td>
							Сведения и ограничения на объекте:<br> <strong>${brem}</strong>
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
	<p class="pagebreak"></p>`;



let elem = document.body.firstElementChild;
let first_page = document.createElement('div');
first_page.innerHTML = str_first_page;
document.body.insertBefore(first_page, elem);

// console.log(cadast, adress, 'pravo = ', pravo, 'obrem = ', brem);

