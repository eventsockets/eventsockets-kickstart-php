<?php

	require('../config.php');
	require('../lib/EventSockets.API.php');

	use EventSockets\API\ApplicationConfig as ApplicationConfig;
	use EventSockets\API\Tools\Security as Security;
	use EventSockets\API\Model\Message as Message;
	use EventSockets\API\Model\MessageArg as MessageArg;

	// In this authentication example everyone gets authenticated by default, in production mode you would verify the 
	// user by a session cookie or any other method to identify the user to either accept or deny the connection.

	// Fetch applicationKey and socketId
	$applicationKey = $_GET['applicationKey'];
	$socketId = $_GET['socketId'];
  
	// Construct a message for your authentication response
	$message = new Message();

	// Add current timestamp to the message (which is mandatory when signing envelope)
	$message->messageData->add("unix.timestamp",time());

	// Construct a messageArg
	$messageArg = new MessageArg("server","channel","connection","authenticate");

	// Append socket.id
	$messageArg->eventData->add("socket.id",$socketId);

	// Append socket.uuid 
	// In this example we echo the socket.id, normally you would pass your unique id for the current user, eg. primary key or username from your database
	$messageArg->eventData->add("socket.uuid",$socketId);

	// Add messageArg to message
	$message->messageArgs->add($messageArg);

	// Setup an ApplicationConfig using your own keys (defined in config.php)
	$appConfig = new ApplicationConfig(EVENTSOCKETS_CLUSTERKEY,EVENTSOCKETS_APPLICATIONKEY,EVENTSOCKETS_SIGNATUREKEY,EVENTSOCKETS_SECURE);

		// To allow the request you need to send the correct http status code (any other code than 202 will automatically deny the request, even if the signed envelope is valid)
	header("HTTP/1.0 202 Accepted");
	echo $message->sign($appConfig)->ToJson();
			
 
?>