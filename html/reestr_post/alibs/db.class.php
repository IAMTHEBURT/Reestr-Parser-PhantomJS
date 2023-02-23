<?php
class CDB
{
	var $db_host            = false;
	var $db_user            = false;
	var $db_pass            = false;
	var $db_name            = false;
	var $db_encode          = 'utf8';
	var $db                 = false;
	var $insert_id          = 0;
	var $filters 			= false;
    var $dirs               = false;
    var $dirsall            = false;
	
	/**
	* @desc Конструктор
	*/
	function __construct($host, $user, $pass, $name)
	{
		$this->SetDBParams($host, $user, $pass, $name);
	}
	
	/**
	* @desc 
	*/
	function SetDBParams($host, $user, $pass, $name)
	{
		$this->db_host  = $host;
		$this->db_user  = $user;
		$this->db_pass  = $pass;
		$this->db_name  = $name;    
	}
	
	/**
	* @desc   Соединение с БД
	*/
	function Connect()
	{
		
		$this->db = mysqli_connect($this->db_host, $this->db_user, $this->db_pass, $this->db_name);
		mysqli_query($this->db, "SET NAMES " .$this->db_encode. ";");
	}
	
	/**
	* @desc   Закрытие соединения с БД
	*/
	function CloseConnect()
	{
		mysqli_close($this->db);
	}
	
	/**
	* @desc Возвр id посл вставл записи из auto_increment
	*/
	function Query($query)
	{
		return mysqli_query($this->db, $query);
	}
	
	/**
	* @desc Возвр id посл вставл записи из auto_increment
	*/
	function GetInsertId ()
	{
		return $this->insert_id;
	}
	
	/**
	* @desc   Выводит ассоц массив результата запроса SELECT
	*/
	function GetRows($query)
	{
		if ($res = mysqli_query($this->db, $query))
		{
			$row    = array();
			while ($rw = mysqli_fetch_assoc($res))
			{
				$row[]  = $rw;
			}
			return $row;
		}
		else
		{
			echo mysqli_error($this->db);
		}
		return false;        
	}
	
	/**
	* @desc 
	*/
	function GetFields($query, $field)
	{
		$res = array();
		$rows = $this->GetRows($query);
		foreach ($rows as $row)
		{
			if (isset($row[$field])) $res[] = trim($row[$field]);
		}
		return $res;
	}
	
	/**
	* @desc   Выводит ассоц массив результата запроса SELECT
	*/
	function GetRow($query)
	{
		if ($res = mysqli_query($this->db, $query))
		{
			$rw = mysqli_fetch_assoc($res);
			return $rw;
		}
		echo mysqli_error($this->db);
		return false;        
	}
	
	/**
	* @desc Доб-е зап в БД
	*/
	function Insert($table, $data)
	{   
		$fields     = array();
		foreach ($data as $key => $value)
			$fields[] = "`".$key."`";
		$query              = "INSERT INTO `" .$table. "` (" .implode(',', $fields). ") VALUES ('" .implode('\',\'',$data). "')" ;
		$result             = mysqli_query($this->db,$query);
		$this->insert_id    = mysqli_insert_id($this->db);
		echo mysqli_error($this->db);
		return $result;        
	}
	
	/**
	* @desc Доб-е зап в БД
	*/
	function InsertMult($table, $data, $up_params = false)
	{   
		$fields = array();
		if (count($data) <= 0)
		{
			return false;
		}
		
		$tmp    = array_shift($data);
		$keys   = array_keys($tmp);
		array_unshift($data, $tmp);
		
		$values = array();
		foreach ($data as $key => $info)
		{
			$params = array();
			foreach ($keys as $key)
			{
				$params[] = addslashes(trim($info[$key]));
			}
			$values[] = "('".implode("', '", $params)."')";
		}
		if (count($values) > 0)
		{
			$query = "INSERT IGNORE INTO `".$table."` (`".implode('`,`', $keys). "`) VALUES ".implode(', ', $values);
			if ($up_params) {
				$up = array();
				foreach ($up_params as $p) $up[] = $p.' = VALUES('.$p.')';
				$query .= ' ON DUPLICATE KEY UPDATE '.implode(',', $up);
			}
			$result             = mysqli_query($this->db, $query);
			$this->insert_id    = mysqli_insert_id($this->db);
			return $result;
		}
		return false;
	}
	
	/**
	* @desc Доб-е зап в БД
	*/
	function Replace($table, $data)
	{   
		$fields     = array();
		foreach ($data as $key => $value)
			$fields[] = "`".$key."`";
		$query              = "REPLACE INTO `" .$table. "` (" .implode(',', $fields). ") VALUES ('" .implode('\',\'',$data). "')" ;
		$result             = mysqli_query($this->db,$query);
		$this->insert_id    = mysqli_insert_id($this->db);
		echo mysqli_error($this->db);
		return $result;        
	}
	
	/**
	* @desc Доб-е зап в БД
	*/
	function Delete($table, $cond)
	{   
		$query  = "DELETE FROM `" .$table. "` WHERE ".$cond;
		$result = mysqli_query($this->db,$query);
		echo mysqli_error($this->db);
		return $result;        
	}
	
	/**
	* @desc Обговление записи в БД
	*/
	function Update($table, $data, $cond = 1, $log = true)      //$log - выв ли сообщ об ошибке
	{
		$set_arr = array();
		foreach ($data as $nm => $val)
		{
			$set_arr[]  = '`'.$nm.'` = \''.addslashes($val).'\'';
		}
		$query  = "UPDATE `" .$table. "` SET " .implode(', ', $set_arr). " WHERE " .$cond;              
		if ((!$result = mysqli_query($this->db, $query)) && $log)
		{
			echo mysqli_error($this->db); 
		}
        return true;
	}
	
	/**
	* @desc Обрезка пробелов 
	*/
	function ClearSpaces($str)
	{
		return trim(preg_replace("~\s+~is", " ", $str));
	}
	
	/**
	* @desc 
	*/
	function GetDBError()
	{
		return mysqli_error($this->db);
	}
	
	/**
	* @desc 
	*/
	function setFilters($data)
	{
		$filters = array(
			'name'		=> false,
			'enable'	=> false,
            'dir'       => false,
		);
		
		foreach ($filters as $key => $value) {
			if (isset($data[$key])) {
				$v = trim($data[$key]);
				$filters[$key] = (strlen($v) > 0) ? $v : false;
			}
		}
		$this->filters = $filters;
	}
    
    /**
    * @desc 
    */
    function getUnderDirs(&$udirs, $dir)
    {
        $udirs[$dir] = 1;
        foreach ($this->dirsall as $key => $d) {
            if ($dir == $d['parent']) {
                $udirs[$key] = 1;
                $this->getUnderDirs($udirs, $key);
            }
        }
    }
	
    /**
    * @desc 
    */
    function getReportRows($costtype)
    {
        $query = "SELECT P.id, P.title, P.alias, P.barcode";
        $query .= ", C.cost_val as price";
        $query .= ", M.uptime, M.id AS rowid, M.emanual, M.murl, M.prices, M.maxprice, M.avgprice";
        $query .= " FROM pfx_product AS P";
        $query .= " LEFT JOIN aa_market AS M ON M.pid = P.id";
        $query .= " LEFT JOIN pfx_product_x_cost AS C ON (C.product_id = P.id AND C.cost_id = ".$costtype.")";
        $query .= " WHERE 1";
        //$query .= " AND C.cost_id = ".$costtype;
        $query .= " AND P.public > 0";
        if ($this->filters['name']) {
            $query .= " AND (P.title LIKE '%".trim($this->filters['name'])."%'";
            $query .= " OR P.barcode LIKE '%".trim($this->filters['name'])."%'";
            $query .= " OR P.id = '".trim($this->filters['name'])."')";
        }
        if ($this->filters['enable']) $query .= " AND M.emanual > 0";
        if ($this->filters['dir']) {
            $dir = trim($this->filters['dir']);
            $udirs = array();
            $this->getUnderDirs($udirs, $dir);
            $query .= " AND P.maindir IN ('".implode("','", array_keys($udirs))."')";
        }
        $query .= " ORDER BY P.title";
        $query .= " LIMIT 10";
        return $this->GetRows($query);
    }
    
	/**
	* @desc 
	*/
	function getActiveProducts($costtype)
	{
		$query = "SELECT P.id, P.title, P.alias, P.barcode";
        $query .= ", C.cost_val as price";
        $query .= ", M.uptime, M.id AS rowid, M.emanual, M.murl, M.prices, M.maxprice, M.avgprice";
        $query .= " FROM pfx_product AS P";
        $query .= " LEFT JOIN aa_market AS M ON M.pid = P.id";
		$query .= " LEFT JOIN pfx_product_x_cost AS C ON C.product_id = P.id";
        $query .= " WHERE 1";
        $query .= " AND C.cost_id = ".$costtype;
		$query .= " AND P.public > 0";
        $query .= " AND M.emanual > 0";
        $query .= " ORDER BY P.title";
		return $this->GetRows($query);
	}
    
    /**
    * @desc 
    */
    function getDirs()
    {
        $query = 'SELECT * FROM pfx_product_dir WHERE public > 0 AND level <= 3 ORDER BY sortn';
        $rows = $this->GetRows($query);
        $result = array();
        foreach ($rows as $row) {
            $id     = $row['id'];
            $name   = $row['name'];
            $parent = $row['parent'];
            $result[$id] = array('name' => $name, 'parent' => $parent);
        }
        $this->dirs = $result;
        return true;
    }
    
    /**
    * @desc 
    */
    function getDirsAll()
    {
        $query = 'SELECT * FROM pfx_product_dir WHERE public > 0 ORDER BY sortn';
        $rows = $this->GetRows($query);
        $result = array();
        foreach ($rows as $row) {
            $id     = $row['id'];
            $name   = $row['name'];
            $parent = $row['parent'];
            $result[$id] = array('name' => $name, 'parent' => $parent);
        }
        $this->dirsall = $result;
        return true;
    }
    
    /**
    * @desc 
    */
    function printDirsOptions($dirs, $parent, $fdir, $cats)
    {
        foreach ($dirs as $did => $dir) {
            if ($dir['parent'] == $parent) {
                $selected = ($fdir == $did) ? ' selected' : '';
                $lcats = $cats;
                $lcats[] = $dir['name'];
                
                print '<option value="'.$did.'"'.$selected.'>'.implode(' -> ', $lcats).'</option>';
                $this->printDirsOptions($dirs, $did, $fdir, $lcats);
            }
                                                                                    
        } 
    }
    
    /**
    * @desc 
    */
    function makeXLS(&$rows, $link)
    {
        // Excel
        require_once 'alibs/PHPExcel.php';
        $XLS = new PHPExcel();
        $idn = new idna_convert();
        
        // Инфа
        //$XLS->getActiveSheet()->mergeCells('A1:E1');
        //$date1     = date('d.m.Y', strtotime($task['date1']));
        //$date2     = date('d.m.Y', strtotime($task['date2']));
        //$type     = trim($options[$task['type']]);
        //$text     = 'Даты: '.$date1.' - '.$date2.', Тип: '.$type;
        //$XLS->getActiveSheet()->setCellValue('A1', $text);
        
        // Заголовок
        $heads = array('№', 'SID', 'Обновлено', 'Артикул', 'Название', 'URL', 'Моя цена', 'Название 1', 'Цена 1', 'Название 2', 'Цена 2', 'Название 3', 'Цена 3', 'Ср.цена', 'Макс.цена', 'Ссылка');
        $count = 0;
        foreach ($heads as $head) {
            $col = chr(ord('A') + $count);
            $XLS->getActiveSheet()->setCellValue($col.'1', $head);
            $count++;
            $XLS->getActiveSheet()->getStyle($col)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);    
        }
        $XLS->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);    
        
        $XLS->getActiveSheet()->getColumnDimension('A')->setWidth(6);
        $XLS->getActiveSheet()->getColumnDimension('B')->setWidth(12);
        $XLS->getActiveSheet()->getColumnDimension('C')->setWidth(12);
        $XLS->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $XLS->getActiveSheet()->getColumnDimension('E')->setWidth(41);
        $XLS->getActiveSheet()->getColumnDimension('F')->setWidth(41);
        $XLS->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $XLS->getActiveSheet()->getColumnDimension('H')->setWidth(25);
        $XLS->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        $XLS->getActiveSheet()->getColumnDimension('J')->setWidth(25);
        $XLS->getActiveSheet()->getColumnDimension('K')->setWidth(10);
        $XLS->getActiveSheet()->getColumnDimension('L')->setWidth(25);
        $XLS->getActiveSheet()->getColumnDimension('M')->setWidth(10);
        $XLS->getActiveSheet()->getColumnDimension('N')->setWidth(12);
        $XLS->getActiveSheet()->getColumnDimension('O')->setWidth(12);
        $XLS->getActiveSheet()->getColumnDimension('P')->setWidth(41);
        
        $XLS->getActiveSheet()->getStyle('A')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('C')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('D')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('E')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('F')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('G')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('H')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('I')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('J')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('K')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('L')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('M')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('N')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('O')->getAlignment()->setWrapText(true);
        $XLS->getActiveSheet()->getStyle('P')->getAlignment()->setWrapText(true);
        
        // Строки
        $rowno = 1;
        $count = 0;
        $idn = new idna_convert();
        foreach ($rows as $row) {
            $rowno++;
            $count++;
            $date   = (is_null($row['uptime']) || preg_match("~0000\-00\-00~is", $row['uptime'])) ? '' : date("H:i", strtotime($row['uptime']));
            $purl   = 'http://matroskin.su/product/'.trim(trim($row['alias'], "/., ")).'/';
            $price  = number_format(trim($row['price']), 0, '.', '');
            $pstr   = array();
            $prices     = json_decode($row['prices']);
            if (is_array($prices)) foreach ($prices as $p) {
                $pstr[] = $p->p.' - '.$idn->decode($p->n);
            }
            $murl = (!is_null($row['murl'])) ? trim($row['murl']) : '';
            $avgprice   = intval(trim($row['avgprice']));
            $avgprice   = ($avgprice > 0) ? $avgprice : '';
            $maxprice   = intval(trim($row['maxprice']));
            $maxprice   = ($maxprice > 0) ? $maxprice : '';
            
            $XLS->getActiveSheet()->setCellValue('A'.$rowno, $count);
            $XLS->getActiveSheet()->setCellValue('B'.$rowno, $row['id']);
            $XLS->getActiveSheet()->setCellValue('C'.$rowno, $date);
            $XLS->getActiveSheet()->setCellValue('D'.$rowno, htmlspecialchars(trim($row['barcode'])));
            $XLS->getActiveSheet()->setCellValue('E'.$rowno, trim($row['title']));

            //$XLS->getActiveSheet()->setCellValue('F'.$rowno, $purl);
            $XLS->getActiveSheet()->setCellValue('F'.$rowno, $purl);
            $XLS->getActiveSheet()->getCell('F'.$rowno)->getHyperlink()->setUrl($purl);
            $XLS->getActiveSheet()->getCell('F'.$rowno)->getHyperlink()->setTooltip('Перейти');
            
            $XLS->getActiveSheet()->setCellValue('G'.$rowno, $price);
            if ($p = array_shift($prices)) {
                $XLS->getActiveSheet()->setCellValue('H'.$rowno, $idn->decode($p->n));
                $XLS->getActiveSheet()->setCellValue('I'.$rowno, $p->p);
            }
            if ($p = array_shift($prices)) {
                $XLS->getActiveSheet()->setCellValue('J'.$rowno, $idn->decode($p->n));
                $XLS->getActiveSheet()->setCellValue('K'.$rowno, $p->p);
            }
            if ($p = array_shift($prices)) {
                $XLS->getActiveSheet()->setCellValue('L'.$rowno, $idn->decode($p->n));
                $XLS->getActiveSheet()->setCellValue('M'.$rowno, $p->p);
            }
            $XLS->getActiveSheet()->setCellValue('N'.$rowno, $avgprice);
            $XLS->getActiveSheet()->setCellValue('O'.$rowno, $maxprice);
            
            $XLS->getActiveSheet()->setCellValue('P'.$rowno, $murl);
            $XLS->getActiveSheet()->getCell('P'.$rowno)->getHyperlink()->setUrl($murl);
            $XLS->getActiveSheet()->getCell('P'.$rowno)->getHyperlink()->setTooltip('Перейти');

            $XLS->getActiveSheet()->getRowDimension($rowno)->setRowHeight(50);
        }
        
        // Настройки
        //$XLS->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
        $XLS->getActiveSheet()->getRowDimension(1)->setRowHeight(20);
        $XLS->getActiveSheet()->setTitle('list');
        $XLS->setActiveSheetIndex(0);

        $file = date("Y_m_d_H_i").'.xls';
        $xlsWriter = PHPExcel_IOFactory::createWriter($XLS, 'Excel5');
        $xlsWriter->save(TMP_PATH.'/'.$file);
        header('location: '.$link.$file);
    }
}
?>
