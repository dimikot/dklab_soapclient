<?php
require_once "../../lib/Dklab/SoapClient.php";
$client = new Dklab_SoapClient(null, array(
    'location' => "http://dklab.ru/lib/Dklab_SoapClient/demo/test/Dklab_SoapClient/soapserver.php",
    'uri' => 'urn:myschema',
    'timeout' => 3,
));
// Send all the requests in parallel (note the "async" property).
$requests = array();
for ($i = 0; $i < 4; $i++) {
	$requests[] = $client->async->slowMethod(1);
}
// Now - print all results in 1 second, not in 4 seconds.
$t0 = microtime(true);
echo "<pre>";
foreach ($requests as $request) {
	echo $request->getResult() . "\n";
}
echo sprintf("Total time: %.2f seconds", microtime(true) - $t0);
