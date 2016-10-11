<?php
class MageLine_Sellsy_Model_Connection extends Mage_Core_Model_Abstract
{
 	
	private static $instance;
	
	private $header;
	
	public function __construct() {
				
		$api_url	                 = Mage::helper('sellsy')->getApiUrl();
		$this->api_url					= $api_url;
		$oauth_access_token          = Mage::helper('sellsy')->getUserToken();
		$oauth_access_token_secret   = Mage::helper('sellsy')->getUserSecret();
		$oauth_consumer_key          = Mage::helper('sellsy')->getConsumerToken();
		$oauth_consumer_secret       = Mage::helper('sellsy')->getConsumerSecret();
 
		$encoded_key = rawurlencode($oauth_consumer_secret).'&'.rawurlencode($oauth_access_token_secret);
		$oauth_params = array (
			'oauth_consumer_key' => $oauth_consumer_key,
			'oauth_token' => $oauth_access_token,
			'oauth_nonce' => md5(time()+rand(0,1000)),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'PLAINTEXT',
			'oauth_version' => '1.0',
			'oauth_signature' => $encoded_key
		);
		//Zend_debug::dump($oauth_params);die;
		$this->header = array($this->getHeaders($oauth_params), 'Expect:');
 
	}
    
    public function requestApi($requestSettings, $showJSON=false){
		
		$params = array( 
			'request' => 1, 
			'io_mode' =>  'json', 
			'do_in' => json_encode($requestSettings)
		); 
		
		$options = array(
			CURLOPT_HTTPHEADER	=> $this->header,
			CURLOPT_URL			=> $this->api_url,
			CURLOPT_POST		=> 1,
			CURLOPT_POSTFIELDS	=>  $params,
			CURLOPT_RETURNTRANSFER => true,
		);
		
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$response = curl_exec($curl);
		curl_close($curl);
 
		
		$back = json_decode($response);
		
		if ($showJSON){
			self::debug($back); exit;
		}
		/*
		if (strstr($response, 'oauth_problem')){
			sellsyTools::storageSet('oauth_error', $response);
		}
		
		if ($back->status == 'error'){
			sellsyTools::storageSet('process_error', $back->error);
		} 
		*/
		return $back;
		
	}
	
		private function getHeaders($oauth) {
		$part = 'Authorization: OAuth ';
		$values = array();
		foreach ($oauth as $key => $value)
			$values[] = "$key=\"" . rawurlencode($value) . "\"";

		$part .= implode(', ', $values);
		return $part;
	}

}