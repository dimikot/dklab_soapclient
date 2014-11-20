<?php
namespace Dklab\SoapClient;
/**
 * cURL multi-request manager.
 *
 * Also support connection retries and response validation. To
 * implement validation and connection retry, use 'response_validator'
 * key in addRequest() method with callback value.  The callback
 * is passed two arguments:
 *   - response data
 *   - number of connection attempts performed
 * It must:
 *   - return true if the response is valid;
 *   - return false if the response is invalid and the request
 *     needs to be retried;
 *   - throw an exception if maximum retry count is reached.
 */
class Curl
{
	/**
     * Emergency number of connect tries.
     * Used if a response validator function is broken.
     */
    const MAX_TRIES = 5;

    /**
     * Multi handler from curl_milti_init.
     *
     * @var resource
     */
    private $_handler;

    /**
     * Responses retrieved by key.
     *
     * @var array
     */
    private $_responses = array();

    /**
     * Active requests keyed by request key.
     * object(handle, copy, nRetries)
     *
     * @var array
     */
    private $_requests = array();

    /**
     * Create a new manager.
     */
    function __construct()
    {
        $this->_handler = curl_multi_init();
    }

    /**
     * Add a cURL request to the queue.
     * Request is specified by its cURL options.
     *
     * @param array $curlOptions   Options to pass to cURL.
     * @return string              Identifier of the added request.
     */
    public function addRequest($curlOptions)
    {
        // Extract custom options.
        $responseValidator = null;
        if (isset($curlOptions['response_validator']) && is_callable($curlOptions['response_validator'])) {
            $responseValidator = $curlOptions['response_validator'];
            unset($curlOptions['response_validator']);
        }

        // Create a cURL handler.
        $curlHandler = $this->_createCurlHandler($curlOptions);

        $key = (string)$curlHandler;
        // Add it to the queue. Note that we NEVER USE curl_copy_handle(),
        // because it seems to be buggy and corrupts the memory.
        $request = $this->_requests[$key] = (object)array(
            'handle'     => $curlHandler,
            'options'    => $curlOptions,
            'tries'      => 1,
            'validator'  => $responseValidator,
        );
        // Begin the processing.
        $this->_addCurlRequest($request, $key);
        return $key;
    }

    /**
     * Wait for a request termination and return its data.
     * In additional to curl_getinfo() results, the following keys are added:
     *   - "result":          cURL curl_multi_info_read() result code;
     *   - "headers":         HTTP response headers;
     *   - "body":            HTTP body;
     *   - "result_timeout":  null or ("connect" or "data") if a timeout occurred.
     *
     * @param string $key
     * @return array
     */
    public function getResult($key)
    {
        if (null !== ($response = $this->_extractResponse($key))) {
            return $response;
        }
        do {
			// Execute all the handles.
			$nRunning = $this->_execCurl(true);
            // Try to extract the response.
            if (null !== ($response = $this->_extractResponse($key))) {
                //echo sprintf("-- %d %d %d\n", count($this->_responses), count($this->_requests));
                return $response;
            }
        } while ($nRunning > 0);
        return null;
    }

    /**
     * Wait for a connection is established.
     * If a timeout occurred, this method does not throw an exception:
     * it is done within getResult() call only.
     *
     * @param string $key
     * @return void
     */
    public function waitForConnect($key)
    {
        // Perform processing cycle until the request is really sent
        // and we begin to wait for a response.
        while (1) {
            if (!isset($this->_requests[$key])) {
                // The request is already processed.
                return;
            }
            $request = $this->_requests[$key];
            if (curl_getinfo($request->handle, CURLINFO_REQUEST_SIZE) > 0) {
                // Request is sent (its size is defined).
                return;
            }
            // Wait for a socket activity.
            $this->_execCurl(true);
        }
    }

    /**
     * Query cURL and store all the responses in internal properties.
     * Also deletes finished connections.
     *
     * @param int &$nRunning   If a new request is added after a retry, this
     *                         variable is incremented.
     * @return void
     */
    private function _storeResponses(&$nRunning = null)
    {
        while ($done = curl_multi_info_read($this->_handler)) {
            // Get a key and request for this handle.
            $key = (string)$done['handle'];
            $request = $this->_requests[$key];
            // Build the full response array and remove the handle from queue.
            $response = curl_getinfo($request->handle);
            $response['result'] = $done['result'];
            $response['result_timeout'] = $response["result"] === CURLE_OPERATION_TIMEOUTED? ($response["request_size"] <= 0? 'connect' : 'data') : null;
            @list($response['headers'], $response['body']) = preg_split('/\r?\n\r?\n/s', curl_multi_getcontent($request->handle), 2);
            curl_multi_remove_handle($this->_handler, $request->handle);
            // Process validation and possibly retry procedure.
            if (
                $response['result_timeout'] !== 'data'
                && $request->tries < self::MAX_TRIES
                && $request->validator
                && !call_user_func($request->validator, $response, $request->tries)
            ) {
                // Initiate the retry.
                $request->tries++;
                // It is safe to add the handle again back to perform a retry
                // (including timed-out transfers, not only timed-out connections).
                $this->_addCurlRequest($request, $key);
                $nRunning++;
            } else {
                // No tries left or this is a DATA timeout which is never retried.
                // Remove this request from queue and save the response.
                unset($this->_requests[$key]);
                $this->_responses[$key] = $response;
                curl_close($request->handle);
            }
        }
    }

    /**
     * Extract response data by its key. Note that a next call to
     * _extractResponse() with the same key will return null.
     *
     * @param string $key
     * @return mixed
     */
    private function _extractResponse($key)
    {
        if (isset($this->_responses[$key])) {
            $result = $this->_responses[$key];
            unset($this->_responses[$key]);
            return $result;
        }
        return null;
    }

    /**
     * Create a cURL handler by cURL options.
     * Do not use curl_copy_handle(), it corrupts the memory sometimes!
     *
     * @param array $curlOptions
     * @return resource
     */
    private function _createCurlHandler($curlOptions)
    {
        // Disable "100 Continue" header sending. This avoids problems with large POST.
    	$curlOptions[CURLOPT_HTTPHEADER][] = 'Expect:';
        // ALWAYS fetch with headers!
        $curlOptions[CURLOPT_HEADER] = 1;
        // The following two options are very important for timeouted reconnects!
        $curlOptions[CURLOPT_FORBID_REUSE] = 1;
        $curlOptions[CURLOPT_FRESH_CONNECT] = 1;
        // To be on a safe side, disable redirects.
        $curlOptions[CURLOPT_FOLLOWLOCATION] = false;
        // More debugging.
        $curlOptions[CURLINFO_HEADER_OUT] = true;
    	// Init and return the handle.
        $curlHandler = curl_init();
        curl_setopt_array($curlHandler, $curlOptions);
        return $curlHandler;
    }

    /**
     * Add a cURL request to the queue with initial connection.
     *
     * @param resource $h
     * @param string $key
     * @return void
     */
    private function _addCurlRequest(\stdClass $request, $key)
    {
    	// Add a handle to the queue.
    	$min = min(
    		isset($request->options[CURLOPT_TIMEOUT])? $request->options[CURLOPT_TIMEOUT] : 100000,
    		isset($request->options[CURLOPT_CONNECTTIMEOUT])? $request->options[CURLOPT_CONNECTTIMEOUT] : 100000
    	);
    	$request->timeout_at = microtime(true) + $min;
        curl_multi_add_handle($this->_handler, $request->handle);
        // Run initial processing loop without select(), because there are no
        // sockets connected yet.
        $this->_execCurl(false);
    }

    /**
     * Return the minimum delay till the next timeout happened.
     * This function may be optimized in the future.
     *
     * @return float
     */
    private function _getCurlNextTimeoutDelay()
    {
    	$time = microtime(true);
    	$min = 100000;
    	foreach ($this->_requests as $request) {
    		// May be negative value here in case when a request is timed out,
    		// it's a quite common case.
    		$min = min($min, $request->timeout_at - $time);
    	}
    	// Minimum delay is 1 ms to be protected from busy wait.
    	$min = max($min, 0.001);
    	return $min;
    }

    /**
     * Execute cURL processing loop and store all ready responses.
     *
     * @param bool    $waitForAction  If true, a socket action is waited before executing.
     * @return int    A number of requests left in the queue.
     */
    private function _execCurl($waitForAction)
    {
        $nRunningCurrent = null;
        if ($waitForAction) {
            curl_multi_select($this->_handler, $this->_getCurlNextTimeoutDelay());
        }
    	while (curl_multi_exec($this->_handler, $nRunningCurrent) == CURLM_CALL_MULTI_PERFORM);
        // Store appeared responses if present.
    	$this->_storeResponses($nRunningCurrent);
    	return $nRunningCurrent;
    }
}