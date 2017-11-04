<?php 
/**
 * call whmcs API
 * @param [str] action
 * @param [array] options
 * @param [enum] 'json'|'xml'
 */
class WhmcsLib
{
	var $response;

	var $accesskey = 'qwerty';
	var $username  = 'admin';
	var $password  = '8hp06o8xFyM7miOOd7N/zA==';

	var $whmcsUrl  = 'http://ram.esy.es';

	private $postfields = array();
	
	function __construct($action = null, $options = null, $type = 'json')
	{
		
		$this->postfields = array(
			'username'     => $this->username,
			'password'     => md5($this->password),
			'action'       => $action,
			'accesskey'    => $this->accesskey,
			'responsetype' => 'json',
		);

		$this->postfields = array_merge($options, $this->postfields);


		if($type == 'json'){
			
			$this->call();

		}else{

			$this->callxml();
		}

	}
	
	

	private function call() {
		// Call the API
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->whmcsUrl.'/includes/api.php');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->postfields));
		$response = curl_exec($ch);
		if (curl_error($ch)) {
			die('Unable to connect: '.curl_errno($ch).' - '.curl_error($ch));
		}
		curl_close($ch);

		// Attempt to decode response as json
		$this->response = json_decode($response, true);
	}

	private function callxml() {
		$query_string = "";
		foreach ($this->postfields AS $k => $v)$query_string .= "$k=" .urlencode($v)."&";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->whmcsUrl.'/includes/api.php');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->postfields));
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
		$xml = curl_exec($ch);
		
		if (curl_error($ch) || !$xml) {$xml = '<whmcsapi><result>error</result>'.
			'<message>Connection Error</message><curlerror>'.
			curl_errno($ch).' - '.curl_error($ch).'</curlerror></whmcsapi>';
		}

		curl_close($ch);

		$this->response = $this->whmcsapi_xml_parser($xml);# Parse XML
	}



	public function render($arr)
	{
		echo '<pre>';
		print_r($arr);
		echo '</pre>';
	}	


	public function whmcsapi_xml_parser($rawxml) 
	{
		$xml_parser = xml_parser_create();
		xml_parse_into_struct($xml_parser, $rawxml, $vals, $index);
		xml_parser_free($xml_parser);
		$params      = array();
		$level       = array();
		$alreadyused = array();
		$x           = 0;
		foreach ($vals as $xml_elem) {
			if ($xml_elem['type'] == 'open') {
				if (in_array($xml_elem['tag'], $alreadyused)) {
					$x++;
					$xml_elem['tag'] = $xml_elem['tag'].$x;
				}
				$level[$xml_elem['level']] = $xml_elem['tag'];
				$alreadyused[]             = $xml_elem['tag'];
			}
			if ($xml_elem['type'] == 'complete') {
				$start_level = 1;
				$php_stmt    = '$params';
				while ($start_level < $xml_elem['level']) {
					$php_stmt .= '[$level['.$start_level.']]';
					$start_level++;
				}
				$php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
				@eval($php_stmt);
			}
		}
		return ($params);
	}
}