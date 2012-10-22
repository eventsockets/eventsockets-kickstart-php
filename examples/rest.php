<?php

	require('../config.php');
	require('../lib/EventSockets.API.php');
	require('../model/dummyobject.php');

	use EventSockets\API\ApplicationConfig as ApplicationConfig;
	use EventSockets\API\Tools\Rest as Rest;
	use EventSockets\API\Model\Message as Message;
	use EventSockets\API\Model\MessageArg as MessageArg;

	// Get the channelPrefix
	$channelPrefix = $_GET['channelPrefix'];
	
	// Make sure we got channelPrefix
	if(trim($channelPrefix) !== "" and $channelPrefix !== null)
	{
		// Construct a message
		$message = new Message();

		// Add current timestamp to the message (which is mandatory when signing envelope)
		$message->messageData->add("unix.timestamp",time());

		// Construct a messageArg (to trigger a text event called onTextMessage)
		$messageArg1 = new MessageArg($channelPrefix,"channel","client","onTextMessage");
		$messageArg1->eventData->add("event.payload","Hello World");
		$message->messageArgs->add($messageArg1);

		// Construct a second messageArg (to trigger a JSON event called onJsonMessage)
		$messageArg2 = new MessageArg($channelPrefix,"channel","client","onJsonMessage");
		$messageArg2->eventData->add("event.payload",json_encode(new DummyObject("Hello World!")));
		$message->messageArgs->add($messageArg2);

		// Setup an ApplicationConfig using your own keys (defined in config.php)
		$appConfig = new ApplicationConfig(
			EVENTSOCKETS_CLUSTERKEY,
			EVENTSOCKETS_APPLICATIONKEY,
			EVENTSOCKETS_SIGNATUREKEY,
			EVENTSOCKETS_SECURE
		);

		// Send message using the REST API
		Rest::send($appConfig,$message);
	}
  
?>