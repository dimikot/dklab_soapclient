--TEST--
Dklab_SoapClient: generic call
--FILE--
<?php
require dirname(__FILE__) . '/init.php';
print_r($client->scalarMethod("Vasily", "Pupkin"));

?>
--EXPECT--
Vasily, Pupkin
