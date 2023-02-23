<?php
require_once "alibs/db.class.php";
class CParser extends CDB
{
	var $agent      = false;
	var $_cookie    = array();    
	var $responce   = false;
	var $header     = false;
    var $cook_file  = false;
    
	var $proxy      = false;
    var $proxy_list	= false;
    var $rand_proxy	= false;
	
    /**
    * @desc 
    */
    function Get($url, $cook = false, $header = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($cook) curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($this->_cookie));
        $this->AddProxy($ch);
        $this->responce = curl_exec($ch);
        $http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->GetCookieFromHeader($this->responce);
        return $http_code;
    }
    
	/**
	* @desc 
	*/
	function LoadPageSimple($url, $try = 5, $cook = false)
	{
		$count = 1;
		$dinfo = parse_url($url);
		
		while ($count <= $try)
		{
			$this->rand_proxy = $this->proxy ? $this->proxy_list[array_rand($this->proxy_list)] : false;
			
			print 'П'.$count.' ';
			$code = $this->LoadSitePage($url, 1, $cook);
			print $code.' ';
			
			//$rep_codes = array(0, 301, 302, 400);
			$rep_codes = array(301, 302, 400);
			$r = 3;
			while (in_array($code, $rep_codes) && ($r > 0))
			{
				//sleep(1);
				$r--;
				$url = $this->GetHeaderLocation($this->responce);
				if (!preg_match("~https?\:\/\/~is", $url)) {
					$url = $dinfo['scheme']."://".$dinfo['host'].$url;
				}
				$code = $this->LoadSitePage($url, 1, $cook);
				print $code.' ';
			}
			if ($code == 200)
			{
				//$this->Charset();
				return true;
			}
			if ($code == 404)
			{
				//$this->Charset();
				return '404';
			}
			$count++;
			sleep(rand(2,3));
		}
		return false;
		
	}

    /**
    * @desc 
    */
    function LoadSitePage($url, $header, $cook = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, $header);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
        if ($cook) curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($this->_cookie));
        $this->AddProxy($ch);
        $this->responce = curl_exec($ch);
        $http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $this->GetCookieFromHeader($this->responce);
        return $http_code;
    }
    
	/**
	* @desc 
	*/
	function GetActive($url, $timeout = 5)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		$content = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
        print 'h'.$http_code.' ';
        if ($http_code == 200)
        {
            return $content;
        }
        return false;
	}
	
	/**
	* @desc 
	*/
	function Post($url, $header, $data)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, $header);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$this->AddProxy($ch);
		$this->responce = curl_exec($ch);
		$http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_code;
	}
	
	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function RequestFollowLocation($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($ch, CURLOPT_COOKIE, $this->CookToStr($this->_cookie));
		$this->AddProxy($ch);
		
		$this->responce = curl_exec($ch);
		$http_code      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		$this->GetCookieFromHeader($this->responce);
		return $http_code;
	}

	/**
	* @desc 
	*/
	function AddProxy(&$ch)
	{
		if ($this->proxy && $this->rand_proxy) {
			$proxy = $this->rand_proxy;
			/*
			curl_setopt($ch, CURLOPT_PROXY, trim($proxy['ip']));
			curl_setopt($ch, CURLOPT_PROXYPORT, trim($proxy['port']));
			curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, trim($proxy['login']).':'.trim($proxy['password']));
			*/
            curl_setopt($ch, CURLOPT_PROXY, trim($proxy['ip']));
            curl_setopt($ch, CURLOPT_PROXYPORT, trim($proxy['port']));
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
		}
	}

	/**
	* @desc 
	*/
	function CookToStr($cook)
	{
		$str = array();
		foreach ($cook as $key => $val)
		{
			$str[] = trim($key).'='.trim($val);
		}
		return implode(";", $str);
	}
    
    /**
    * @desc 
    */
    function ReadCookFromFile()
    {
        if (is_file($this->cook_file))
        {
            $content = trim(file_get_contents($this->cook_file));
            $this->_cookie = json_decode($content);
            $this->_cookie = (array) $this->_cookie;
        }
    }
    
    /**
    * @desc 
    */
    function WriteCookToFile()
    {
        if ($f = fopen($this->cook_file, 'w'))
        {
            $content = json_encode($this->_cookie);
            fwrite($f, $content);
            fclose($f);
        }
    }
	


































	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function SendCURLRequest($url, &$response, &$header, $cookie_file = false, $tmp_cookie_file = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		if ($cookie_file) curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); 
		if ($tmp_cookie_file) curl_setopt($ch, CURLOPT_COOKIEJAR, $tmp_cookie_file);
		$this->AddProxy($ch);

		$response    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

		$header      = substr($response, 0, $header_size);
		$response    = substr($response, $header_size);
		curl_close($ch);
		if ($cookie_file && $tmp_cookie_file)
		{
			$this->Append2Cookie($cookie_file, $tmp_cookie_file);
		}
		//$this->GetCookieFromHeader($header);
		return $http_code;
	}
	
	/**
	* @desc 
	*/
	function AjaxRequest($url, &$cookie, &$responce, &$header, $ref = false, $follow = 0)
	{
		$cookies = $this->CookToStr($cookie);

		$ch = curl_init();
		$options = array
		(
			CURLOPT_RETURNTRANSFER  => 1,
			CURLOPT_BINARYTRANSFER  => 1,
			CURLOPT_AUTOREFERER     => 0,
			CURLOPT_SSL_VERIFYPEER  => 0,
			CURLOPT_SSL_VERIFYHOST  => 0,
			CURLOPT_TIMEOUT         => 10,
			
			CURLOPT_FOLLOWLOCATION  => $follow,
			CURLOPT_HEADER          => 1,
			CURLOPT_COOKIE          => $cookies,
			CURLOPT_USERAGENT       => $this->agent,
			CURLOPT_URL             => $url,
			CURLOPT_HTTPHEADER      => array('X-Requested-With' => 'XMLHttpRequest'),
		);
		
		if ($ref) $options[CURLOPT_REFERER] = $ref;
		curl_setopt_array($ch, $options);
		$this->AddProxy($ch);
		
		$responce    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header      = substr($responce, 0, $header_size);
		$responce    = substr($responce, $header_size);
		curl_close($ch);
		
		return $http_code;
	}
	
	/**
	* @desc 
	*/
	function AjaxRequest2($url, $follow = 0)
	{
		$ch = curl_init();
		$options = array
		(
			CURLOPT_RETURNTRANSFER  => 1,
			CURLOPT_BINARYTRANSFER  => 1,
			CURLOPT_AUTOREFERER     => 0,
			CURLOPT_SSL_VERIFYPEER  => 0,
			CURLOPT_SSL_VERIFYHOST  => 0,
			CURLOPT_TIMEOUT         => 10,
			
			CURLOPT_FOLLOWLOCATION  => $follow,
			CURLOPT_HEADER          => 1,
			CURLOPT_COOKIE          => $this->CookToStr($this->_cookie),
			CURLOPT_USERAGENT       => $this->agent,
			CURLOPT_URL             => $url,
		);
		
		curl_setopt_array($ch, $options);
		$this->AddProxy($ch);
		$this->responce    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$this->GetCookieFromHeader($this->responce);
		
		return $http_code;
	}
	
	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function SendCURLRequestFollowLocation2($url, &$response, &$header, $cookie = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:23.0) Gecko/20100101 Firefox/23.0');
		
		if ($cookie) curl_setopt($ch, CURLOPT_COOKIE, $this->CookToArray($cookie)); 

		$response    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

		$header      = substr($response, 0, $header_size);
		$response    = substr($response, $header_size);
		curl_close($ch);
		return $http_code;
	}

	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function SendCURLRequestWithoutHeader($url, &$response, $cookie_file = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:23.0) Gecko/20100101 Firefox/23.0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		if ($cookie_file)
		{
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); 
		}
		$response    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		return $http_code;
	}

	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function SendCURLRequestWithoutHeader2($url, &$response, $cookie_file = false, $tmp_cookie_file = false)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		$this->AddProxy($ch);

		if ($cookie_file) curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); 
		if ($tmp_cookie_file) curl_setopt($ch, CURLOPT_COOKIEJAR, $tmp_cookie_file);

		$response    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		//$this->GetCookieFromHeader($response);
		if ($cookie_file && $tmp_cookie_file)
		{
			$this->Append2Cookie($cookie_file, $tmp_cookie_file);
		}
		return $http_code;
	}

	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function SendGetRequest($url, &$response, &$header)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->agent);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);

		$response    = curl_exec($ch);
		$http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		
		$header      = substr($response, 0, $header_size);
		$response    = substr($response, $header_size);

		curl_close($ch);
		return ($http_code == 200);
	}

	/**
	* @desc Функция для парсинга header запроса
	*/
	function GetHeaderParam(&$header, $param)
	{
		$header_params = split("[\n]", $header);
		foreach ($header_params as $header_param)
		{
			$parts = explode(":", $header_param, 2);
			if (isset($parts[0]) && isset($parts[1]))
			{
				if (strtolower(trim($parts[0])) == $param)
				{
					return trim($parts[1]);
				}
			}
		}
		return false;
	}
	
	/**
	* @desc Функция для парсинга header запроса
	*/
	function GetHeaderLocation(&$header)
	{
		if (preg_match("~Location:\s+([^\s]+)~is", $header, $match))
		{
			return trim($match[1]);
		}
		return false;
	}
	
	/**
	* @desc 
	*/
	function GetPregMatchAllValues($t, &$str, $no, $delete = false)
	{           
		$res = array();
		if (preg_match_all($t, $str, $match))
		{
			if (isset($match[$no]))
			{
				foreach ($match[$no] as $v)
				{
					$res[] = trim($v);
				}
			}
		}
		if ($delete)
		{
			$str = preg_replace($t, '', $str);
		}
		return $res;
	}

	/**
	* @desc 
	*/
	function GetPregMatchValue($t, &$str, $no, $default = '', $delete = false)
	{           
		$res = preg_match($t, $str, $match) ? trim($match[$no]) : $default;
		if ($delete)
		{
			$str = preg_replace($t, '', $str);
		}
		return $res;
	}
	
	/**
	* @desc 
	*/
	function GetCookieFromHeader($str)
	{
		$cook = array();
		if (preg_match_all("~Set-Cookie:(.*?)\r\n~is", $str, $match))
		{
			foreach ($match[1] as $key => $c)
			{
				$params = explode(';', trim($c));
				reset($params);
				foreach ($params as $param)
				{
					$key = trim(substr($param, 0, stripos($param, '=')));
					$val = trim(substr($param, stripos($param, '=') + 1));
					$cook[$key] = $val;
				}
			}
		}
		foreach ($cook as $key => $value)
		{
			$this->_cookie[$key] = $value;
		}
		
		unset($this->_cookie['expires']);
		unset($this->_cookie['max-age']);
		unset($this->_cookie['path']);
		unset($this->_cookie['']);
		unset($this->_cookie['']);
		unset($this->_cookie['Expires']);
		unset($this->_cookie['Max-Age']);
		unset($this->_cookie['Path']);
		unset($this->_cookie['Domain']);
		unset($this->_cookie['domain']);
		$this->WriteCookToFile();
		
		return $this->_cookie;
	}
	
	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function CookieToArray($file)
	{
		$result     = array();
		if (is_file($file))
		{
			$content     = file_get_contents($file);
			$rows         = explode("\n", $content);
			foreach ($rows as $row)
			{
				$fields = explode("\t", $row);
				if (count($fields) >= 7)
				{
					$result[trim($fields[5])] = trim($row);
				}
			}
		}
		return $result;
	}
	
	/**
	* @desc Функция для отправления CURL-запроса
	*/
	function Append2Cookie($master, $slave)
	{
		$ma    = $this->CookieToArray($master);
		$sa    = $this->CookieToArray($slave);
		foreach ($sa as $key => $value)
		{
			$ma[$key] = $value;
		}
		@unlink($master);
		if ($f = fopen($master, "w"))
		{
			fwrite($f, implode("\n", $ma)."\n");
			fclose($f);
		}
	}
	
}
?>
