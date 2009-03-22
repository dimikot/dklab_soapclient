--TEST--
Dklab_SoapClient: first timed out, second is slow
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$timeout = 2;
$delay = 4;

$conn = new Dklab_SoapClient(null, array(
	'location' => "http://microsoft.com:8877", // non-existed port
	'uri' => 'urn:schema',
	'connection_timeout' => $timeout,
));

$resultWithTimeout = $conn->async->someMethod();
$resultSlow = $nonWsdlClient->async->slowMethod(0, $delay);

$t0 = microtime(true);
echo $resultSlow->getResult() . "\n";
$dt = microtime(true) - $t0;
echo "Result fetch time is about $delay seconds: " . ($dt < $delay + 0.5? "yes" : "no, $dt seconds") . "\n";

$t0 = microtime(true);
try {
    $resultWithTimeout->getResult();
} catch (Exception $e) {
    $dt2 = microtime(true) - $t0;
    echo "Result with timeout: " . $e->getMessage() . "\n";
    echo "Result fetch time is about 0 seconds: " . ($dt2 < 0.5? "yes" : "no, $dt2 seconds") . "\n";
}

?>
--EXPECT--
Request #0 done
Result fetch time is about 4 seconds: yes
Result with timeout: Could not connect to host
Result fetch time is about 0 seconds: yes
