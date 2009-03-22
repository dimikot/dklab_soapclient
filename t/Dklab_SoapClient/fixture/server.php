<?php
ini_set('display_errors', 1);
require_once "MyServer.php";

$soapServer = new SoapServer("schema.wsdl");
$soapServer->setObject(new MyServer());
$soapServer->handle();
