--TEST--
Dklab_SoapClient: connection timeout handling
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$timeout = 2;

$conn = new Dklab_SoapClient(null, array(
	'location' => "http://microsoft.com:8877", // non-existed port
	'uri' => 'urn:schema',
	'connection_timeout' => $timeout,
));

$t0 = microtime(true);
try {
    $result = $conn->async->someMethod();
    $dt1 = microtime(true) - $t0;
    echo "Method scheduled.\n";
    echo "Scheduled in about 0 seconds: " . ($dt1 < 0.5? "yes" : "no, $dt1 seconds") . "\n";
} catch (Exception $e) {
    echo "Call: " . $e->getMessage() . "\n";
}

$t0 = microtime(true);
try {
    $result->getResult();
} catch (Exception $e) {
    $dt2 = microtime(true) - $t0;
    echo "Result: " . $e->getMessage() . "\n";
    echo "Result fetch time is about $timeout seconds: " . ($dt2 < $timeout + 0.5? "yes" : "no, $dt2 seconds") . "\n";
}
?>
--EXPECT--
Method scheduled.
Scheduled in about 0 seconds: yes
Result: Could not connect to host
Result fetch time is about 2 seconds: yes
