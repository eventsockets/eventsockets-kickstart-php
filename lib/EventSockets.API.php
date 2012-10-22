<?php

	namespace EventSockets\API\Collections;
	{
		class Collection{

			private $array;
			
			function __construct() 
			{
				$this->array = array();
			}

			public function add($obj)
			{
				array_push($this->array,$obj);
			}

			public function each($func)
			{
				foreach($this->array as $obj){
					$func($obj);
				}
			}
		}

		class Dictionary{

			private $array;

			function __construct() 
			{
				$this->array = array();
			}

			public function add($key,$value)
			{
				$this->array[$key] = $value;
			}

			public function contains($key)
			{
				return array_key_exists($key, $this->array);
			}

			public function get($key)
			{
				return $this->array[$key];
			}

			public function remove($key)
			{
				unset($this->array[$key]);
			}
	
			public function each($func)
			{
				foreach($this->array as $k => $v){
					$func($k,$v);
				}
			}
		}
	}

	namespace EventSockets\API\Tools;
	{
		class Rest
		{
			static public function send($applicationConfig,$message)
			{
				$envelope_string = $message->sign($applicationConfig)->ToJson();

				$url = "%s://%s.%s.eventsockets.com/?applicationKey=%s&version=".$applicationConfig->Version;
				$formated_url = sprintf($url, ($applicationConfig->Secure != "true" ? "http":"https"), ($applicationConfig->Secure != "true" ? "api":"apis"), $applicationConfig->ClusterKey,$applicationConfig->ApplicationKey, $applicationConfig->Secure);
    
				$ch = curl_init($formated_url);   
    
				if($applicationConfig->Secure === "true"){

					// Got trouble with CA certificates? Download CA bundle and uncomment below (make sure path is correct)
					//curl_setopt ($ch, CURLOPT_CAINFO, "c:\\cabundle.crt");

					// To skip CA certificate bundle, turn off validation (make sure not doing this in production mode)
					//curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
					//curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0); 
				}

				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				curl_setopt($ch, CURLOPT_POSTFIELDS, $envelope_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
					'Content-Type: application/json',
					'Content-Length: ' . strlen($envelope_string))
				);
				curl_exec($ch);

				if(curl_errno($ch)){
					echo 'error:' . curl_error($ch);
				}
			}
		}
	}

	namespace EventSockets\API;
	{
		
		class ApplicationConfig
		{
			public $Version = "1.0";			
			public $ClusterKey;			
			public $ApplicationKey;			
			public $SignatureKey;			
			public $Secure = "false";			

			function __construct($clusterKey,$applicationKey,$signatureKey,$secure) {
				$this->ClusterKey = $clusterKey;
				$this->ApplicationKey = $applicationKey;
				$this->SignatureKey = $signatureKey;
				$this->Secure = $secure;
			}		
		}
	}

	namespace EventSockets\API\Model;
	{
		class Envelope{
			public $auth = "";
			public $message = "";

			static public function fromJson($json)
			{
				$array = json_decode($json,true);
				$envelope = new \EventSockets\API\Model\Envelope();
				$envelope->auth = $array["auth"];
				$envelope->message = $array["message"];

				return $envelope;
			}

			public function toJson()
			{
				return json_encode($this);
			}

		}
		class Message{
				
			public $messageArgs;
			public $messageData;

			function __construct() {
				$this->messageArgs = new \EventSockets\API\Collections\Collection();
				$this->messageData = new \EventSockets\API\Collections\Dictionary();
			}

			static public function fromJson($json)
			{
				$array = json_decode($json,true);

				$message = new \EventSockets\API\Model\Message();

				foreach($array["messageArgs"] as $mArgs)
				{
					$messageArg = new \EventSockets\API\Model\MessageArg($mArgs["channelPrefix"],$mArgs["channelName"],$mArgs["eventPrefix"],$mArgs["eventName"]);

					foreach($mArgs["eventData"] as $eData){
							$messageArg->eventData->add($eData["Key"],$eData["Value"]);
					}
					$message->messageArgs->Add($messageArg);
				}
				foreach($array["messageData"] as $mData){
						$message->messageData->add($mData["Key"],$mData["Value"]);
				}

				return $message;

			}

			public function toJson()
			{
				$array = array();
				$array["messageArgs"] = array();
				$array["messageData"] = array();
			
				$this->messageArgs->each(function($obj) use (&$array){

					$messageArgsArray = array();
					$messageArgsArray["channelPrefix"] = $obj->channelPrefix;
					$messageArgsArray["channelName"] = $obj->channelName;
					$messageArgsArray["eventPrefix"] = $obj->eventPrefix;
					$messageArgsArray["eventName"] = $obj->eventName;
					$messageArgsArray["eventData"] = array();

					$obj->eventData->each(function($k,$v) use (&$messageArgsArray){
						array_push($messageArgsArray["eventData"],new \EventSockets\API\Model\KeyValuePair($k,$v));
					});

					array_push($array["messageArgs"],$messageArgsArray);
				});
	
				$this->messageData->each(function($k,$v) use (&$array){
					array_push($array["messageData"],new \EventSockets\API\Model\KeyValuePair($k,$v));
				});

				return json_encode($array);
			}

			public function sign($applicationConfig)
			{
				$envelope = new \EventSockets\API\Model\Envelope();
				$envelope->message = $this->toJson();
				$envelope->auth=strtoupper(hash_hmac("sha256", $envelope->message, $applicationConfig->SignatureKey));
				return $envelope;
			}


		}

		class MessageArg{

			public $eventPrefix;
			public $eventName;
			public $channelPrefix;
			public $channelName;
			public $eventData;

			function __construct($channelPrefix,$channelName,$eventPrefix,$eventName) {
				$this->eventPrefix = $eventPrefix;
				$this->eventName = $eventName;
				$this->channelPrefix = $channelPrefix;
				$this->channelName = $channelName;
				$this->eventData = new \EventSockets\API\Collections\Dictionary();
			}
		}

		class KeyValuePair{

			public $Key = "";
			public $Value = "";

			public function __construct($key,$value){ 
				$this->Key = $key;
				$this->Value = $value;
			}
		}

	}

?>