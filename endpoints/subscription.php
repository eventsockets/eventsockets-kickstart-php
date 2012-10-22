<?php

	require('../config.php');
	require('../lib/EventSockets.API.php');

	use EventSockets\API\ApplicationConfig as ApplicationConfig;
	use EventSockets\API\Tools\Security as Security;
	use EventSockets\API\Model\Envelope as Envelope;
	use EventSockets\API\Model\Message as Message;
	use EventSockets\API\Model\MessageArg as MessageArg;

	// Get application key
	$applicationKey = $_GET['applicationKey'];
 
	// Get HTTP POST stream containing the JSON envelope request
	$post_data = file_get_contents('php://input');

	// Deserialize envelope from HTTP POST stream
	$requestEnvelope = Envelope::fromJson($post_data);

	// Deserialize message from envelope
	$requestMessage = Message::fromJson($requestEnvelope->message);

	// Prepare a message to be sent in response
	$responseMessage = new Message();

	// Add current timestamp to the message (which is mandatory when signing envelope)
	$responseMessage->messageData->add("unix.timestamp",time());
	
	// Iterate each subscription request
	$requestMessage->messageArgs->each(function($requestMessageArg) use($responseMessage){

			$socketId = $requestMessageArg->eventData->get("socket.id");
			$socketUUID = $requestMessageArg->eventData->get("socket.uuid");
			$channelPrefix = $requestMessageArg->channelPrefix;
			$channelName = $requestMessageArg->channelName;
			$eventPrefix = $requestMessageArg->eventPrefix;
			$eventName = $requestMessageArg->eventName;

			// Make sure that messageArg is a channel subscription request
			if($eventPrefix == "channel" && $eventName == "subscribe")
			{
				// Construct a messageArg
				$responseMessageArg = new MessageArg($channelPrefix,$channelName,$eventPrefix,$eventName);
          
				// Append socketId, no need to send socket.uuid)
				$responseMessageArg->eventData->add("socket.id",$socketId);

				// In this example any subscription requests are allowed (set to false or ignore adding key/value to deny)
				$responseMessageArg->eventData->add("channel.subscription","true");
				
				switch($channelPrefix)
				{
					case "public":

						// Public channel subscriptions will never be sent out as channel subscription request do not need any authentication. 
						// If client subscribes to a public and private channel at the same time only the private channel request will be sent 
						// to the subscription endpoint

					case "private":
      
							// Allow subscriber to trigger events within the request channel (set to false or ignore adding key/value to deny)
						$responseMessageArg->eventData->add("channel.trigger","true");

						break;

					case "presence":

							// Allow subscriber to trigger events within the request channel (set to false or ignore adding key/value to deny)
						$responseMessageArg->eventData->add("channel.trigger","true");

						// Describe the subscriber (socket.id is echoed in this example, in real life you might send a JSON object with username, email etc..)
						$responseMessageArg->eventData->add("socket.data",$socketId);

						break;
    
				}
				// Add messageArg to response message
				$responseMessage->messageArgs->add($responseMessageArg);
			}
	});
	
	// Setup an ApplicationConfig using your own keys (defined in config.php)
	$appConfig = new ApplicationConfig(EVENTSOCKETS_CLUSTERKEY,EVENTSOCKETS_APPLICATIONKEY,EVENTSOCKETS_SIGNATUREKEY,EVENTSOCKETS_SECURE);
	
	// Write the signed JSON envelope to the output stream with correct status code and content-type
	header("HTTP/1.0 202 Accepted");
	header("Content-Type: text/html; charset=utf-8");

	echo $responseMessage->sign($appConfig)->ToJson();
?>