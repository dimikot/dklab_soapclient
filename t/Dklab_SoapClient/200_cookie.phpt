--TEST--
Dklab_SoapClient: cookie support
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$native = new SoapClient(null, array(
	'location' => $location,
	'uri' => 'urn:schema',
));
$native->login("Vasily");
echo "Native SoapClient said: " . $native->getLogin() . "\n";

$nonWsdlClient->login("Vasily");
echo "Dklab_SoapClient said: " . $nonWsdlClient->getLogin() . "\n";

?>
--EXPECT--
Native SoapClient said: Vasily
Dklab_SoapClient said: Vasily
