<?php
namespace Dklab\SoapClient;
/**
 * Background processed HTTP request.
 * Used internally.
 */
class Request
{
    /**
     * Shared curl_multi manager.
     *
     * @var Curl
     */
    private static $_curl = null;

    /**
     * True if this request already contain a response.
     *
     * @var bool
     */
    private $_isSynchronized = false;

    /**
     * Request parameters.
     *
     * @var array
     */
    private $_request = null;

    /**
     * Result of the request (if $_isSynchronized is true).
     *
     * @var mixed
     */
    private $_result = null;

    /**
     * cURL request handler.
     *
     * @var stdClass
     */
    private $_handler = null;

    /**
     * SOAP client object which created this request.
     *
     * @var \Dklab\SoapClient
     */
    private $_client = null;

    /**
     * Arguments to call __soapCall().
     *
     * @var array
     */
    private $_callArgs = null;

    /**
     * URL which is requested.
     *
     * @var string
     */
    private $_url;

    /**
     * Create a new asynchronous cURL request.
     *
     * @param \Dklab\SoapClient $client
     * @param array $request             Information about SOAP request.
     * @param array $callArgs            Arguments to call __soapCall().
     * @param array $clientOptions       SoapClient constructor options.
     */
    public function __construct(\Dklab\SoapClient $client, $request, $callArgs, $clientOptions)
    {
        if (!self::$_curl) {
            self::$_curl = new Curl();
        }
        $this->_client = $client;
        $this->_request = $request;
        $this->_callArgs = $callArgs;
        $this->_url = $request['location'];
        // Initialize curl request and add it to the queue.
        $curlOptions = array();
        $curlOptions[CURLOPT_URL] = $request['location'];
        $curlOptions[CURLOPT_POST] = 1;
        $curlOptions[CURLOPT_POSTFIELDS] = $request['request'];
        $curlOptions[CURLOPT_RETURNTRANSFER] = 1;
        $curlOptions[CURLOPT_HTTPHEADER] = array();
        // SOAP protocol encoding is always UTF8 according to RFC.
        $curlOptions[CURLOPT_HTTPHEADER][] = "Content-Type: application/soap+xml; charset=utf-8";
        // adding SoapAction Header
	if (isset($request['action'])) {
	    $curlOptions[CURLOPT_HTTPHEADER][] = 'SOAPAction: "' . $request['action'] . '"';
	}
        // Timeout handling.
        if (isset($clientOptions['timeout'])) {
            $curlOptions[CURLOPT_TIMEOUT] = $clientOptions['timeout'];
        }
        if (isset($clientOptions['connection_timeout'])) {
            $curlOptions[CURLOPT_CONNECTTIMEOUT] = $clientOptions['connection_timeout'];
        }
        // Response validator support.
        if (isset($clientOptions['response_validator'])) {
            $curlOptions['response_validator'] = $clientOptions['response_validator'];
        }
        // HTTP_HOST substitution support.
        if (isset($clientOptions['host'])) {
            $curlOptions[CURLOPT_HTTPHEADER][] = "Host: {$clientOptions['host']}";
        }
        // HTTP basic auth.
        if (isset($clientOptions['login']) && isset($clientOptions['password']) ) {
            $curlOptions[CURLOPT_USERPWD] = $clientOptions['login'] . ":" . $clientOptions['password'];
        }
        // Cookies.
        if ($request['cookies']) {
            $pairs = array();
            foreach ($request['cookies'] as $k => $v) {
                $pairs[] = urlencode($k) . "=" . urlencode($v);
            }
            $curlOptions[CURLOPT_COOKIE] = join("; ", $pairs);
        }
        $this->_handler = self::$_curl->addRequest($curlOptions);
    }

    /**
     * Wait for the request termination and return its result.
     *
     * @return mixed
     */
    public function getResult()
    {
        if ($this->_isSynchronized) {
            return $this->_result;
        }
        $this->_isSynchronized = true;
        // Wait for a result.
        $response = self::$_curl->getResult($this->_handler);
        try {
	        if ($response['result_timeout'] == 'data') {
	            // Data timeout.
	            throw new \SoapFault("HTTP", "Response is timed out");
	        }
	        if ($response['result_timeout'] == 'connect') {
	            // Native SoapClient compatible message.
	            throw new \SoapFault("HTTP", "Could not connect to host");
	        }
	        if (!strlen($response['body'])) {
	        	// Empty body (case of DNS error etc.).
	        	throw new \SoapFault("HTTP", "SOAP response is empty");
	        }
	        // Process cookies.
	        foreach ($this->_extractCookies($response['headers']) as $k => $v) {
	            if ($this->_isCookieValid($v)) {
	                $this->_client->__setCookie($k, $v);
	            }
	        }
	        // Run the SOAP handler.
        	$this->_result = $this->_client->__soapCallForced($response['body'], $this->_callArgs);
        } catch (\Exception $e) {
        	// Add more debug parameters to SoapFault.
        	$e->location = $this->_request['location'];
        	//$e->request = $this->_callArgs;
        	//$e->response = $response;
        	throw $e;
        }
        return $this->_result;
    }

    /**
     * Wait for the connect is established.
     * It is useful when you need to begin a SOAP request and then
     * plan to execute a long-running code in parallel.
     *
     * @return
     */
    public function waitForConnect()
    {
        self::$_curl->waitForConnect($this->_handler);
    }

    /**
     * Allow to use lazy-loaded result by implicit property access.
     * Call getResult() and return its property.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getResult()->$key;
    }

    /**
     * Parse HTTP response headers and extract all the cookies.
     *
     * @param string $headers
     * @return array        Array(cookies, body)
     */
    private function _extractCookies($headers)
    {
        $cookies = array();
        foreach (preg_split('/\r?\n/s', $headers) as $header) {
            @list($headername, $headervalue) = explode(':', $header);
            if (strtolower($headername) == "set-cookie") {
                $cookie = $this->_parseCookieValue(trim($headervalue));
                $cookies[$cookie['name']] = $cookie['value'];
            }
        }
        return $cookies;
    }

    /**
     * Parse Set-Cookie: header value.
     *
     * @param string $headervalue
     * @return array
     */
    private function _parseCookieValue($headervalue)
    {
        $cookie = array(
            'expires' => null,
            'domain'  => null,
            'path'    => null,
            'secure'  => false
        );
        if (!strpos($headervalue, ';')) {
            // Only a name=value pair.
            list($cookie['name'], $cookie['value']) = array_map('trim', explode('=', $headervalue));
            $cookie['name']  = urldecode($cookie['name']);
            $cookie['value'] = urldecode($cookie['value']);
        } else {
            // Some optional parameters are supplied.
            $elements = explode(';', $headervalue);
            list($cookie['name'], $cookie['value']) = array_map('trim', explode('=', $elements[0]));
            $cookie['name']  = urldecode($cookie['name']);
            $cookie['value'] = urldecode($cookie['value']);
            for ($i = 1; $i < count($elements); $i++) {
                list($elName, $elValue) = array_map('trim', explode('=', $elements[$i]));
                if ('secure' == $elName) {
                    $cookie['secure'] = true;
                } elseif ('expires' == $elName) {
                    $cookie['expires'] = str_replace('"', '', $elValue);
                } elseif ('path' == $elName OR 'domain' == $elName) {
                    $cookie[$elName] = urldecode($elValue);
                } else {
                    $cookie[$elName] = $elValue;
                }
            }
        }
        return $cookie;
    }

    /**
     * Return true if the cookie is valid in a context of $this->_url.
     *
     * @param array $cookie
     * @return bool
     */
    private function _isCookieValid($cookie)
    {
        // TODO
        // Now we assume that all cookies are valid no mater on domein,
        // expires, path, secure etc.
        // Note that original SoapClient only checks: path, domain, secure,
        // but NOT expires.
        return true;
    }
}
