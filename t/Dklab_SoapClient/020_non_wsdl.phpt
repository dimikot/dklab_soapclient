--TEST--
Dklab_SoapClient: generic non-wsdl call
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

print_r($nonWsdlClient->scalarMethod("Vasily", "Pupkin"));

?>
--EXPECT--
Vasily, Pupkin
