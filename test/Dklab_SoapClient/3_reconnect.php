<?php
require_once "../../lib/Dklab/SoapClient.php";
$client = new Dklab_SoapClient(null, array(
    'location' => "http://microsoft.com:8876", // non-existed address
    'uri' => 'urn:myschema',
	'response_validator' => 'responseValidator',
    'timeout' => 1,
));
echo "<pre>";
try {
    $client->someMethod();
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}

/**
 * Must return true if the response is valid, false if not and we need 
 * to reconnect, or throw an exception if attemts limit is reached.
 */
function responseValidator($response, $numberOfAttempt)
{
	if ($response['http_code'] != 200 || !strlen($response['body'])) {
		if ($numberOfAttempt < 3) {
			echo date("r") . ": Failed after $numberOfAttempt attempts, retrying...\n";
			return false;
		} else {
			throw new SoapFault("Client", date("r") . ": Exception: failed after $numberOfAttempt attempts!");
		}
	}
	return true;
}
