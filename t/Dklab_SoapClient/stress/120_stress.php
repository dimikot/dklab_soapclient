<?php
require dirname(__FILE__) . '/../init.php';

$nItr = 1;
$delay = 0;

for ($n = 0; $n < 1000; $n++) {
	$t0 = microtime(true);
	$results = array();
	for ($i = 0; $i < $nItr; $i++) {
		$results[] = $nonWsdlClient->slowMethod($i, $delay);
	}
	foreach ($results as $r) {
		$r->getResult() . "\n";
	}
	$dt = microtime(true) - $t0;
	echo "Total execution time is about one request time: " . ($dt < $delay + 0.5? "yes; much less than " . ($nItr * $delay) . " seconds" : "no; took $dt s") . "\n";
}

?>
