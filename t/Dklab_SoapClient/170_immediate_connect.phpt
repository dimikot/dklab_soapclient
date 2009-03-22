--TEST--
Dklab_SoapClient: async start causes immediate connect
--FILE--
<?php
require dirname(__FILE__) . '/init.php';

$timeout = 2;

$nonWsdlClient = new Dklab_SoapClient(null, array(
    'location' => $location,
    'uri' => 'urn:schema',
    'timeout' => $timeout,
));

$t0 = microtime(true);

$resultFirst = $nonWsdlClient->async->slowMethod(0, 10);
$resultFirst->waitForConnect();

// Connect must be performed before this sleep().
sleep(1);

try {
	echo $resultFirst->getResult() . "\n";
} catch (Exception $e) {
	echo "First timeout: " . $e->getMessage() . "\n";
}
$dt = microtime(true) - $t0;
echo "Time is about 2 seconds from the script start: " . (abs($dt - 2) < 0.5? "yes" : "no, $dt seconds") . "\n";

?>

--EXPECT--
First timeout: Response is timed out
Time is about 2 seconds from the script start: yes
