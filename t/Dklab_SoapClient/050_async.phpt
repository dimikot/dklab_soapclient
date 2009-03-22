--TEST--
Dklab_SoapClient: simultaneous call to a number of methods
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$nItr = 10;
$delay = 1;

$t0 = microtime(true);
$results = array();
for ($i = 0; $i < $nItr; $i++) {
	$results[] = $nonWsdlClient->async->slowMethod($i, $delay);
}
foreach ($results as $r) {
	echo $r->getResult() . "\n";
}
$dt = microtime(true) - $t0;

echo "Total execution time is about one request time: " . ($dt < $delay * 2? "yes; much less than " . ($nItr * $delay) . " seconds" : "no; took $dt s") . "\n";

?>
--EXPECT--
Request #0 done
Request #1 done
Request #2 done
Request #3 done
Request #4 done
Request #5 done
Request #6 done
Request #7 done
Request #8 done
Request #9 done
Total execution time is about one request time: yes; much less than 10 seconds
