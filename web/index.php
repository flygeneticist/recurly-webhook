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
		$app['monolog']->addDebug('logging output.');
		$post_xml = file_get_contents("php://input");
		$notification = new Recurly_PushNotification($post_xml);
		//each webhook is defined by a type
		if ($notification->type) {
			echo 'Got an XML notification from Recurly:<br>'.$notification->type;
			updateChartmogul($post_xml);
			return header("Status: 200");
		} else {
			echo "Error occured!";
			return header("Status: 500");
		}
	});

$app->run();

// HELPER FUNCTIONS AND CLASSES
class Recurly_PushNotification {
	/* Notification type:
	 *   [new_account updated_account canceled_account
	 *    new_subscription updated_subscription canceled_subscription expired_subscription
	 *    successful_payment failed_payment successful_refund void_payment]
	 */
	var $type;
	var $account;
	var $subscription;
	var $transaction;
	function __construct($post_xml) {
		$this->parseXml($post_xml);
	}

	function parseXml($post_xml) {
		if (!@simplexml_load_string($post_xml)) {
			return;
		}
		$xml = new SimpleXMLElement($post_xml);

		$this->type = $xml->getName();

		foreach ($xml->children() as $child_node) {
			switch ($child_node->getName()) {
				case 'account':
					$this->account = $child_node;
					break;
				case 'subscription':
					$this->subscription = $child_node;
					break;
				case 'transaction':
					$this->transaction = $child_node;
					break;
			}
		}
	}
}

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
