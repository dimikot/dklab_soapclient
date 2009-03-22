--TEST--
Dklab_SoapClient: empty response retry and then ok
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$nonWsdlClientWithRetry->initDieForFirstTwoCalls();
$result = $nonWsdlClientWithRetry->async->dieForFirstTwoCalls("test");
echo $result->getResult() . "\n";

?>
--EXPECT--
URL nonwsdl.php failed after 1 attempts, retrying...
URL nonwsdl.php failed after 2 attempts, retrying...
Request #test done
