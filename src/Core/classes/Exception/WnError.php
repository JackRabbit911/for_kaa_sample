<?php

namespace WN\Core\Exception;

class WnError extends \Error
{
	protected $http_status;

	public function __construct($message = "", array $variables = NULL, $http_status = 503)
	{
		// 1/0;
		$message = empty($variables) ? $message : strtr($message, $variables);
		$this->http_status = (int) $http_status;
		// error_reporting(0);
		parent::__construct($message, 0, NULL);
		// Handler::logger($this);
	}

	public function response()
	{
		Handler::response($this);
	}

	public function getHTTPStatus()
	{
		return $this->http_status;
	}
}