<?php
	require_once __DIR__ . '/vendor/autoload.php';

	$mpdf = new \Mpdf\Mpdf();
	$html = file_get_contents('./pdf.html');
	$mpdf->WriteHTML($html);
	$mpdf->Output();

?>