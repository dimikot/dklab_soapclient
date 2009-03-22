<?php
class MyServer
{
	function scalarMethod($firstName, $lastName)
	{
		return "$firstName, $lastName";
	}
	
	function structMethod($n)
	{
		return array_fill(0, $n, array("a" => "aaa"));
	}

	function objMethod($n)
	{
		return (object)array("prop" => $n, "obj" => (object)array("a" => "aaa"), "arr" => array("b" => "bbb"));
	}
	
	function slowMethod($n, $delay)
	{
		usleep($delay * 1000000);
		return "Request #$n done";
	}

	function slowMethodWithFail($n, $delay, $fail = false)
	{
		sleep($delay);
		if ($fail) exit(); // emulate empty page
		return "Request #$n done";
	}
	
	function initDieForFirstTwoCalls()
	{
		file_put_contents('/tmp/dieForFirstTwoCalls.txt', 0);
	}

	function dieForFirstTwoCalls($id, $useTimeout = false)
	{
		$n = intval(file_get_contents('/tmp/dieForFirstTwoCalls.txt')) + 1;
		file_put_contents('/tmp/dieForFirstTwoCalls.txt', $n);
		if ($n < 3) {
			if (!$useTimeout) {
				exit();
			} else {
				sleep(6);
			}
		}
		return "Request #$id done";
	}
		
	function login($name)
	{
		setcookie("login", $name, time() + 10000);
	}
	
	function getLogin()
	{
		return $_COOKIE['login'];
	}

	function loginSession($name)
	{
		session_start();
		$_SESSION["login"] = $name;
	}

	function getLoginSession()
	{
		session_start();
	    return @$_SESSION["login"];
	}
	
	function throwException($msg)
	{
		throw new SoapFault('Server', $msg);
	}
}
