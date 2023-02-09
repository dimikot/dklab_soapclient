<?php
namespace Dklab\SoapClient;
/**
 * Exception to mark recording calls to __doRequest().
 * Used internally.
 */
class DelayedException extends \Exception
{
}