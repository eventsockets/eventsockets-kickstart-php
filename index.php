<html>
<head>
	<title></title>
	<link href="/assets/css/style.css" rel="stylesheet" type="text/css" />
</head>
<body>
	<h2>Setup instructions</h2>
	<ol>
		<li>Sign up for a free sandbox account at <a href="http://www.eventsockets.com">http://www.eventsockets.com</a>
			or login to your account if you have already signed up before and locate the sandbox
			application.</li>
		<li>View the security credentials and take a note of the security credentials (applicationKey,signatureKey
			and clusterKey).</li>
		<li>Configure the Subscription EndPoint url to point to this web (eg. if this web project
			is accessible at http://www.mydomain.com the url should point to http://www.mydomain.com/endpoints/subscription.php
			and make sure that this url is accessible from the internet (otherwise you will
			not be able to authenticate any channel subscription requests)</li>
		<li>Modify config.php (change applicationKey,signatureKey and clusterKey with your own
			keys which you found in step 2).</li>
		<li>Browse the examples (best experienced with more than two browser instances).</li>
	</ol>
	<hr />
	<h2>Examples</h2>
	<ul>
		<li><a href="/examples/public.php">Public channel example</a> </li>
		<li><a href="/examples/private.php">Private channel example</a> </li>
		<li><a href="/examples/presence.php">Presence channel example</a> </li>
	</ul>
	<br />
</body>
</html>
