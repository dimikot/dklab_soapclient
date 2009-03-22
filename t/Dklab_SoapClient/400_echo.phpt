--TEST--
Dklab_SoapClient: __call() test: unknown method call (server side)
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$native = new Dklab_SoapClient(null, array(
	'location' => dirname($location) . '/echo.php',
	'uri' => 'urn:schema',
));

print_r($native->someMethod(array("a", "b")));

?>
--EXPECT--
Array
(
    [0] => someMethod
    [1] => Array
        (
            [0] => Array
                (
                    [0] => a
                    [1] => b
                )

        )

)
