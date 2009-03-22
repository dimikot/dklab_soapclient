--TEST--
Dklab_SoapClient: call to a method returning a structured data
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

print_r($nonWsdlClient->structMethod(3));

?>
--EXPECT--
Array
(
    [0] => Array
        (
            [a] => aaa
        )

    [1] => Array
        (
            [a] => aaa
        )

    [2] => Array
        (
            [a] => aaa
        )

)
