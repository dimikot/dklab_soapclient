--TEST--
Dklab_SoapClient: timeout of a slow response
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$timeout = 2;
$delay = 4;

$nonWsdlClient = new Dklab_SoapClient(null, array(
    'location' => $location,
    'uri' => 'urn:schema',
    'timeout' => $timeout,
));

$resultSlow = $nonWsdlClient->async->slowMethod(0, $delay);

$t0 = microtime(true);
try {
	echo $resultSlow->getResult() . "\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}
$dt = microtime(true) - $t0;
echo "Result fetch time is about $timeout seconds: " . (abs($dt - $timeout) < 0.5? "yes" : "no, $dt seconds") . "\n";

?>
--EXPECT--
Response is timed out
Result fetch time is about 2 seconds: yes
