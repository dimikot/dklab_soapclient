<?php
require_once "../../lib/Dklab/SoapClient.php";
$client = new Dklab_SoapClient(null, array(
    'location' => "http://dklab.ru/lib/Dklab_SoapClient/demo/test/Dklab_SoapClient/soapserver.php",
    'uri' => 'urn:myschema',
    'timeout' => 1,
));
try {
    // 4 is greater than timeout, so an exception will happen.
    $t0 = microtime(true);
    $client->slowMethod(3);
} catch (Exception $e) {
    echo $e->getMessage() . sprintf(" in %.2fs", microtime(true) - $t0) . "\n";
}

