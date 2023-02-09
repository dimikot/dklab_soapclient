/**
 * Enhanced SOAP client with support of parallel requests and reconnects:
 *
 * 1. Can retry a connection if it is failed.
 * 2. Can perform multiple SOAP requests asynchronously, in parallel:
 *      $req1 = $client->async->someMethod1(); // called asynchronously
 *      $req2 = $client->async->someMethod2(); // called asynchronously
 *      $result3 = $client->someMethod(); // called synchronously, as usual
 *      $result1 = $req1->getResult();
 *      $result2 = $req1->getResult();
 * 3. Supports data fetch timeout processing.
 * 4. Supports connection timeout handling with reconnection if needed;
 * 
 * Additional supported options:
 *   - "timeout": cURL functions call timeout;
 *   - "connection_timeout": timeout for CONNECT procedure (may be less
 *     than "timeout"; if greater, set to "timeout");
 *   - "response_validator": callback to validate the response; must 
 *     return true if a response is valid, false - if invalid,
 *     and throw an exception if retry count is too high. Never called
 *     if a response data reading timed out.
 *   - "host": hostname used to pass in "Host:" header.
 * 
 * Additional SoapFault properties addigned after a fault:
 *   - "location": server URL which was called;
 *   - "request": calling parameters (the first is the procedure name);
 *   - "response": cURL-style response information as array.
 * 
 * Note that by default the interface is fully compatible with 
 * native SoapClient. You should use $client->async pseudo-property
 * to perform asyncronous requests. 
 * 
 * ATTENTION! Due to cURL or SoapCliend strange bug a crash is sometimes
 * caused on Windows. Don't know yet how to work-around it... This bug
 * is not clearly reproducible.
 *
 * @version 0.93
 */

namespace Dklab;

class SoapClient extends \SoapClient
{
    private $_recordedRequest = null;
    private $_hasForcedResponse = false;
    private $_forcedResponse = null;
    private $_clientOptions = array();
    private $_cookies = array();
    
    /**
     * Create a new object.
     * 
     * @see SoapClient
     */
    public function __construct($wsdl, $options = array())
    {
        $this->_clientOptions = is_array($options)? array() + $options : array();
        parent::__construct($wsdl, $options);
    }
    
    /**
     * Perform a raw SOAP request.
     * 
     * @see SoapClient::__doRequest
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0): ?string
    {
        if ($this->_hasForcedResponse) {
            // We forced a response, so return it.
            return $this->_forcedResponse;
        }
        // Record the request for later async sending.
        // Note the "" appended to the beginning of the string: this creates
        // string copies to work-around PHP's SoapClient bug with refs counting. 
        $this->_recordedRequest = array(
            'request'  => "" . $request,
            'location' => "" . $location,
            'action'   => "" . $action,
            'cookies'  => $this->_cookies,
        );
        throw new SoapClient\DelayedException();
    }
    
    /**
     * Perform a SOAP method call.
     * 
     * @see SoapClient::__call
     */
    public function __call($functionName, $arguments): mixed
    {
        return $this->__soapCall($functionName, $arguments);
    }
    
    /**
     * Perform a generic SOAP method call.
     * 
     * Depending on boolean $options['async'] it may be:
     *   - synchronous: the operation waits for a response, and result is returned
     *   - asynchronous: the operation is scheduled, but returned immediately
     *     the Request object which may be synchronized by getResult() call later.
     * 
     * @see SoapClient::__soapCall
     */
    public function __soapCall($functionName, $arguments, $options = array(), $inputHeaders = null, &$outputHeaders = null): mixed
    {
        $isAsync = false;
        if (!empty($options['async'])) {
            $isAsync = true;
            unset($options['async']);
        }
        $args = func_get_args();
        try {
        	// Unfortunately, we cannot use call_user_func_array(), because
        	// it does not support "parent::" construction. And we cannot
        	// call is "statically" because of E_STRICT.
            parent::__soapCall($functionName, $arguments, $options, $inputHeaders, $outputHeaders);
        } catch (SoapClient\DelayedException $e) {
        }
        $request = new SoapClient\Request($this, $this->_recordedRequest, $args, $this->_clientOptions);
        $this->_recordedRequest = null;
        if ($isAsync) {
            // In async mode - return the request.
            return $request;
        } else {
            // In syncronous mode (default) - wait for a result.
            return $request->getResult();
        }
    }
    
    /**
     * Set a cookie for this client.
     * 
     * @param string $name
     * @param string $value
     * @return void
     */
    public function __setCookie($name, $value = null): void
    {
        parent::__setCookie($name, $value);
        if ($value !== null) {
            $this->_cookies[$name] = $value;
        } else {
            unset($this->_cookies[$name]);
        }
    }
    
    /**
     * Perform a SOAP method call emulation returning as a method 
     * result specified XML response. This is needed for curl_multi.
     * 
     * @param string $forcedResponse  XML forced as a SOAP response.
     * @param array $origArgs         Arguments for __soapCall().
     * @return mixed                  SOAP result.
     */
    public function __soapCallForced($forcedResponse, $origArgs)
    {
        $this->_forcedResponse = $forcedResponse;
        $this->_hasForcedResponse = true;
        try {
        	// Unfortunately, we cannot use call_user_func_array(), because
        	// it does not support "parent::" construction. And we cannot
        	// call is "statically" because of E_STRICT.
            $result = parent::__soapCall($origArgs[0], $origArgs[1], isset($origArgs[2])? $origArgs[2] : array(), @$origArgs[3], $origArgs[4]);
            $this->_forcedResponse = null;
            $this->_hasForcedResponse = false;
            return $result;
        } catch (\Exception $e) {
            $this->_forcedResponse = null;
            $this->_hasForcedResponse = false;
            throw $e;
        }
    }
    
    /**
     * Support for ->async property with no cyclic references.
     * 
     * @param string $key
     * @return self
     */
    public function __get($key)
    {
        if ($key == "async") {
            return new SoapClient\AsyncCaller($this);
        } else {
            throw new \Exception("Attempt to access undefined property " . get_class($this) . "::$key");
        }
    }
}
