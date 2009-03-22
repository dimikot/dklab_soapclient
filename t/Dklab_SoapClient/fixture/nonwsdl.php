<?php
ini_set('display_errors', 1);
require_once "MyServer.php";

$soapServer = new SoapServer(null, array('uri' => 'urn:schema'));
$soapServer->setObject(new MyServer());
$soapServer->handle();
