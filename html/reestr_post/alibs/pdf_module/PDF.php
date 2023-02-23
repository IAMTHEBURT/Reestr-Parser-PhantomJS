<?php

require_once('vendor/autoload.php');

use setasign\Fpdi\Fpdi;

class PDF extends FPDI {

    var $_tplIdx;
	public $origFile = '';
	
	public function __construct($path){
		parent::__construct();
		$this->AddFont('Roboto-Regular', '', 'Roboto-Regular.php');
		$this->AddFont('Roboto-Light', '', 'Roboto-Light.php');
		$this->origFile = $path;
	}

    function Header() {

        if (is_null($this->_tplIdx)) {
            $this->numPages = $this->setSourceFile($this->origFile);
            $this->_tplIdx = $this->importPage(1);

        }
        $this->useTemplate($this->_tplIdx, 0, 0,200);

    }
	
	function _toUtf($text){
		return iconv('UTF-8', 'windows-1251', $text);
	}

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Roboto-Light', '', 6, '', true);
		
		
		if($this->PageNo() == $this->numPages){
			$this->Image('watermark_last.png', 14, $this->getY() - 40, -302); // Можете заменить на свои пути
			$this->Cell(180,10, $this->_toUtf('Страница '). $this->PageNo() . $this->_toUtf(' из ') . $this->numPages,0,0,'R');
		}else {
			$this->Image('watermark.png', 4, $this->getY() - 1, 35); // Можете заменить на свои пути
			$this->Cell(8, 10);
			$this->Cell(0,14, $this->_toUtf('Страница ') . $this->PageNo() . $this->_toUtf(' из ') . $this->numPages,0,0,'L');
		}
	}

}