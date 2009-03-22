--TEST--
Dklab_SoapClient: simultaneous call waiting for the last slow request only
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$delay = 2 * 1; // must be multiplier of 2!
$t0 = microtime(true);
$results = array();
$results[] = $nonWsdlClient->async->slowMethod(0, $delay / 2);
$results[] = $nonWsdlClient->async->slowMethod(1, $delay);
echo $results[1]->getResult() . "\n";
$dt = microtime(true) - $t0;

echo "Last slow request is done in $delay + 0.5 seconds: " . ($dt < $delay + 0.5? "yes" : "no") . "\n";

?>
--EXPECT--
Request #1 done
Last slow request is done in 2 + 0.5 seconds: yes
