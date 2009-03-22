--TEST--
Dklab_SoapClient: call to a method returning objects
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

print_r($nonWsdlClient->objMethod(3));

?>
--EXPECT--
stdClass Object
(
    [prop] => 3
    [obj] => stdClass Object
        (
            [a] => aaa
        )

    [arr] => Array
        (
            [b] => bbb
        )

)
