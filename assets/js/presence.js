var connectionManager = new EventSockets.ConnectionManager();
var connection = null;

function setupConnection(clusterKey, applicationKey, secure) {

	connection = connectionManager.create({
		clusterKey: clusterKey,
		applicationKey: applicationKey,
		debug: false,
		secure: secure,
		maximumReconnectAttempts: 5,
		authenticationEndPoint: new EventSockets.AuthenticationEndPoint('/endpoints/authentication.php')
	});

	connection.bind("connect", true, function (connectionArg) {
		writeToLog("Connected");
	});

	connection.bind("connect", false, function (connectionArg) {
		writeToLog("Connect failure: {0}", connectionArg.error);
	});

	connection.bind("reconnect", true, function (connectionArg) {
		writeToLog("Reconnected");
	});

	connection.bind("reconnect", false, function (connectionArg) {
		writeToLog("Reconnect failure: {0}", connectionArg.error);
	});

	connection.bind("disconnect", true, function (connectionArg) {
		writeToLog("Disconnected");
	});

	connection.bind("disconnect", false, function (connectionArg) {
		writeToLog("Disconnect failure: {0}", connectionArg.error);
	});
}

function setupButtons() {

	$("#connect").click(function () {
		connection.connect();
	});

	$("#disconnect").click(function () {
		connection.disconnect();
	});

	$("#subscribe").click(function () {

		var channel = connection.create("presence", "channel");

		channel.bind("subscription", true, function (channelArg) {
			writeToLog("Subscribed to {0}", channelArg.channel.getFullName());
		});

		channel.bind("subscription", false, function (channelArg) {
			writeToLog("Subscription failure for {0} : {1}", channelArg.channel.getFullName(), channelArg.error);
		});

		channel.bind("resubscription", true, function (channelArg) {
			writeToLog("Resubscribed to {0}", channelArg.channel.getFullName());
		});

		channel.bind("resubscription", false, function (channelArg) {
			writeToLog("Resubscription failure for {0} : {1}", channelArg.channel.getFullName(), channelArg.error);
		});

		channel.bind("subscriptions", true, function (channelArg) {
			var subscriptions = channelArg.data;
			subscriptions.forEach(function (socketUuid, socketData) {
				writeToLog("socket.uuid({0}) is subscribed to channel with socket.data({1})", socketUuid, socketData);
			});
		});

		channel.bind("unsubscriptions", true, function (channelArg) {
			var unsubscriptions = channelArg.data;
			unsubscriptions.forEach(function (socketUuid) {
				writeToLog("socket.uuid({0}) unsubscribed from channel.", socketUuid);
			});
		});

		channel.bind("unsubscription", true, function (channelArg) {
			writeToLog("Unsubscribed from {0}", channelArg.channel.getFullName());
		});

		channel.bind("event", true, function (channelArg) {

			switch (channelArg.event.name) {

				case "onTextMessage":
					writeToLog("Event received --> {0} {1} {2}", channelArg.channel.getFullName(), channelArg.event.name, channelArg.data.getValue("event.payload"));
					break;

				case "onJsonMessage":
					var msg = JSON.parse(channelArg.data.getValue("event.payload"));
					writeToLog("Event received --> {0} {1} {2}", channelArg.channel.getFullName(), channelArg.event.name, msg.message);
					break;

				default:
					writeToLog("Unknown event received --> {0} {1} {2}", channelArg.channel.getFullName(), channelArg.event.name, channelArg.data.getValue("event.payload"));
					break;
			}

		});

		connection.subscribe(channel);
	});

	$("#rest").click(function () {
		$.ajax({ url: 'rest.php?channelPrefix=presence&nocache=' + Math.floor(Math.random() * 99999999) });
	});

	$("#unsubscribe").click(function () {
		connection.channels.forEach(function (key, value) {
			connection.unsubscribe(value);
		});
	});

	$("#trigger").click(function () {
		var channel = connection.channels.getValue("presence.channel");
		if (channel != null && channel.state.current == EventSockets.ChannelState.Subscribed) {
			channel.trigger("onTextMessage", $("#txtEvent").val());
			$("#txtEvent").val('');
		}
		else {
			writeToLog("You cannot trigger an event since you are not subscribed to the channel.");
		}
	});
}

function writeToLog(message) {
	$("#log").html(String.format.apply(message, arguments) + "<br>" + $("#log").html());
}