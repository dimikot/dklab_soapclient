--TEST--
Dklab_SoapClient: simultaneous call to a number of methods with retries
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$nItr = 4;
$delay = 1;

$results = array();
for ($i = 0; $i < $nItr; $i++) {
	$results[] = $nonWsdlClientWithRetry->async->slowMethodWithFail($i, $delay, $i % 2 == 0);
}

$t0 = microtime(true);
$results[1]->getResult() . "\n";
$dt1 = microtime(true) - $t0;

$t0 = microtime(true);
foreach ($results as $r) {
	try {
		$r->getResult() . "\n";
	} catch (SoapFault $e) {
		echo $e->getMessage() . "\n";
	}
}
$dt2 = microtime(true) - $t0;

echo "Non-retried request execution time is about one request time: " . ($dt1 < $delay + 0.5? "yes" : "no; took $dt1 s") . "\n";
echo "Total execution time is about 3 * request time: " . ($dt2 < $delay * 3 + 0.5? "yes" : "no; took $dt2 s") . "\n";

?>
--EXPECT--
URL nonwsdl.php failed after 1 attempts, retrying...
URL nonwsdl.php failed after 1 attempts, retrying...
URL nonwsdl.php failed after 2 attempts, retrying...
URL nonwsdl.php failed after 2 attempts, retrying...
URL nonwsdl.php failed after 3 attempts!
URL nonwsdl.php failed after 3 attempts!
Non-retried request execution time is about one request time: yes
Total execution time is about 3 * request time: yes
