<?php
// SOAP class to be used for request handling.
class MyServer
{
    public function getComplexData($some)
    {
        return array("obj" => (object)array("prop" => $some), "some" => "thing");
    }
    public function slowMethod($sleep)
    {
        sleep($sleep);
        return "slept for $sleep seconds";
    }
}
// Create and run the server.
$soapServer = new SoapServer(null, array('uri' => 'urn:myschema'));
$soapServer->setObject(new MyServer());
$soapServer->handle();
