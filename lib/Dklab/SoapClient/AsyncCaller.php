<?php
namespace Dklab\SoapClient;
/**
 * Object is accessed via $dklabSoapClient->async->someMethod().
 */
class AsyncCaller
{
	/**
	 * @var \Dklab\SoapClient $_client
	 */
	private $_client;

    public function __construct($client)
    {
        $this->_client = $client;
    }

    public function __call($functionName, $arguments)
    {
        return $this->_client->__soapCall($functionName, $arguments, array('async' => true));
    }
}