--TEST--
Dklab_SoapClient: first timed out, second does not wait
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$timeout = 2;

$conn = new Dklab_SoapClient(null, array(
	'location' => "http://microsoft.com:8877", // non-existed port
	'uri' => 'urn:schema',
	'connection_timeout' => $timeout,
));

$resultWithTimeout = $conn->async->someMethod();
$resultFast = $nonWsdlClient->async->scalarMethod("Vasily", "Pupkin");

$t0 = microtime(true);
echo $resultFast->getResult() . "\n";
$dt = microtime(true) - $t0;
echo "Result fetch time is about 0 seconds: " . ($dt < 0.5? "yes" : "no, $dt seconds") . "\n";
?>
--EXPECT--
Vasily, Pupkin
Result fetch time is about 0 seconds: yes
