--TEST--
Dklab_SoapClient: server exception handling
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

try {
	$nonWsdlClient->throwException("Text");
} catch (Exception $e) {
	echo "Exception: " . get_class($e) . ", {$e->getMessage()}\n";
}

?>
--EXPECT--
Exception: SoapFault, Text
