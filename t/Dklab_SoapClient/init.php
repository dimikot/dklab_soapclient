<?php
chdir(dirname(__FILE__));
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);

include_once "../../lib/Dklab/SoapClient.php";

// WSDL client.
$client = new Dklab_SoapClient("fixture/schema.wsdl");
if (!preg_match('/<soap:address\s+location="(.*?)"/s', file_get_contents('fixture/schema.wsdl'), $m)) {
	die("Cannot find target location in WSDL file!");
}
$location = dirname($m[1]) . "/nonwsdl.php";

// Non-WSDL client
$nonWsdlClient = new Dklab_SoapClient(null, array(
	'location' => $location,
	'uri' => 'urn:schema',
));

// Non-WSDL client with retries
$nonWsdlClientWithRetry = new Dklab_SoapClient(null, array(
	'location' => $location,
	'uri' => 'urn:schema',
	'response_validator' => 'responseValidator',
));

// Non-WSDL client with retries and timeout
$nonWsdlClientWithRetryAndTimeout = new Dklab_SoapClient(null, array(
	'location' => $location,
	'uri' => 'urn:schema',
	'response_validator' => 'responseValidator',
	'timeout' => 1,
));


function responseValidator($response, $tries)
{
	if ($response['http_code'] != 200 || !strlen($response['body'])) {
		if ($tries < 3) {
			echo "URL " . basename($response['url']) . " failed after $tries attempts, retrying...\n";
			return false;
		} else {
			throw new SoapFault("Client", "URL " . basename($response['url']) . " failed after $tries attempts!");
		}
	}
	return true;
}
