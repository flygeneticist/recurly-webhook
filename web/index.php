<?php
require ('../vendor/autoload.php');

$app          = new Silex\Application();
$app['debug'] = true;

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
		'monolog.logfile' => 'php://stderr',
	));

// Our web handlers
$app->get('/', function () use ($app) {
		$app['monolog']->addDebug('logging output.');
		return 'Welcome to the daddydonkeylabs Recurly Webhook server!';
	});

$app->post('/', function () use ($app) {
		$post_xml = file_get_contents("php://input");
		$notification = new Recurly_PushNotification($post_xml);
		//each webhook is defined by a type
		if ($notification->type) {
			echo 'Got an XML notification from Recurly:<br>'.$notification->type;
			updateChartmogul($post_xml);
			return 'Done, 200';
		} else {
			return "Error occured!";
		}
	});

$app->run();

// HELPER FUNCTIONS
function updateChartmogul($xml) {
	echo "<h3>Building/Sending ChartMogul Request...</h3>";
	$url     = 'https://app.chartmogul.com/api/events/recurly/DTWfFDlBFn2CJRkCeE0cbw';
	$ch      = curl_init();
	$headers = array(
		"Accept: application/xml",
		"Content-Type: application/xml",
	);
	$options = array(
		CURLOPT_HTTPHEADER     => $headers,
		CURLOPT_URL            => $url,
		CURLOPT_FRESH_CONNECT  => true,
		CURLOPT_POST           => true,
		CURLOPT_BINARYTRANSFER => true,
		CURLOPT_POSTFIELDS     => $xml,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 30,
		CURLOPT_SSL_VERIFYPEER => false
	);

	curl_setopt_array($ch, $options);
	$data = curl_exec($ch);

	if (curl_errno($ch)) {
		// show me any errors
		echo "Here's the error:<br>";
		print("Error: ".curl_error($ch));
	} else {
		// show me the result
		echo "Here's the data:<br>";
		print_r($data);
		curl_close($ch);
	}
}

?>
