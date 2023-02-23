return true;

let cadast = document.getElementById('get_cadast').nextElementSibling.textContent;
let adress = document.getElementById('get_adress').nextElementSibling.textContent;
let pravo = document.getElementById('get_pravo');



let recever = document.getElementById('reciver').parentNode;
let gos_reg = document.getElementById('gos_reg').parentNode;
console.log(reciver.parentNode.classList.add('d_none'));
console.log(gos_reg.parentNode.classList.add('d_none'));


if (pravo) {
	pravo = pravo.nextElementSibling.textContent;
	pravo = 'Доступны';
	console.log(pravo);

} else {
	pravo = 'Не указаны';
}

let str_first_page_pravo = `<style>
.file {
	margin: 0 auto;
}
.container_pravo {
	width: 17cm;
	margin: 0 auto;
}
h1, .doc_border {
	/* margin-top: 200px; */
	/* width: 17px; */
	font-size: 30px;
	margin: 0 auto;
	text-align: center;
}
.cadastral {
	width: 90%;
	margin: 0 auto;
}
.main_info, .cadastral table {
	width: 100%;
	margin: 0 auto;
}
.cadastral table tr{
	width: 100%;
}
.cadastral table tr td{
	width: 45%;
}
.doc_border {
	margin-top: 20px;
	border: 3px solid #1f44ff;
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
		<br> <br> <br> <br> <br> <br>
	<h1>Отчет об анализе объекта недвижимости по выписке "Переход права"</h1>
	<br> <br> <br> <br> <br> <br> <br> <br> <br> <br>
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
						Данные о собственниках в ЕГРН:<br><strong>${pravo}</strong>
					</td>
				</tr>
			</table>

		</div>
	<br>
	<br>
	<br>
	<br><br>
</div>
<p class="pagebreak"></p>`;



let elem = document.body.firstElementChild;
let first_page_pravo = document.createElement('div');
first_page_pravo.innerHTML = str_first_page_pravo;
document.body.insertBefore(first_page_pravo, elem);

// console.log(cadast, adress, 'pravo = ', pravo, 'obrem = ', brem);



