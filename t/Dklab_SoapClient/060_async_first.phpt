--TEST--
Dklab_SoapClient: simultaneous call waiting for the first request only
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$delay = 1;
$t0 = microtime(true);
$results = array();
$results[] = $nonWsdlClient->async->slowMethod(0, $delay);
$results[] = $nonWsdlClient->async->slowMethod(1, $delay * 10);
echo $results[0]->getResult() . "\n";
$dt = microtime(true) - $t0;

echo "First request is done in $delay seconds: " . ($dt < $delay * 2? "yes" : "no") . "\n";

?>
--EXPECT--
Request #0 done
First request is done in 1 seconds: yes
