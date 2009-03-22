<?php
ini_set('display_errors', 1);

class EchoServer
{
	public function __call($name, $args)
	{
		return array($name, $args);
	}
}

$soapServer = new SoapServer(null, array('uri' => 'urn:schema'));
$soapServer->setObject(new EchoServer());
$soapServer->handle();
