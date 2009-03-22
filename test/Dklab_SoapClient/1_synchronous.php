<?php
require_once "../../lib/Dklab/SoapClient.php";
$client = new Dklab_SoapClient(null, array(
    'location' => "http://dklab.ru/lib/Dklab_SoapClient/demo/test/Dklab_SoapClient/soapserver.php",
    'uri' => 'urn:myschema',
    'timeout' => 3,
));
$data = $client->getComplexData(array("abc")); // call MyServer::getComplexData()
$text = $client->slowMethod(1);                // call MyServer::slowMethod()

echo "<pre>";
print_r($data);
print_r($text);
