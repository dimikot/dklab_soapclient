--TEST--
Dklab_SoapClient: simultaneous call waiting for the last request only
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$delay = 1;
$t0 = microtime(true);
$results = array();
$results[] = $nonWsdlClient->async->slowMethod(0, $delay * 10);
$results[] = $nonWsdlClient->async->slowMethod(1, $delay);
echo $results[1]->getResult() . "\n";
$dt = microtime(true) - $t0;

echo "Last request is done in $delay seconds: " . ($dt < $delay * 2? "yes" : "no") . "\n";

?>
--EXPECT--
Request #1 done
Last request is done in 1 seconds: yes
