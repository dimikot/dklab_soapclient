--TEST--
Dklab_SoapClient: empty response retry and then data timeout
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

echo $nonWsdlClientWithRetryAndTimeout->initDieForFirstTwoCalls() . "\n";
try {
	echo $nonWsdlClientWithRetryAndTimeout->dieForFirstTwoCalls("test", true) . "\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}

?>
--EXPECT--
Response is timed out

