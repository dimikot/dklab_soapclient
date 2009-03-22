--TEST--
Dklab_SoapClient: session support
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$native = new SoapClient(null, array(
	'location' => $location,
	'uri' => 'urn:schema',
));

$native->loginSession("Vasily");
echo "Native SoapClient said: " . $native->getLoginSession() . "\n";

$nonWsdlClient->loginSession("Vasily");
echo "Dklab_SoapClient said: " . $nonWsdlClient->getLoginSession() . "\n";

?>
--EXPECT--
Native SoapClient said: Vasily
Dklab_SoapClient said: Vasily
