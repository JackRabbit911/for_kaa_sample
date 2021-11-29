<?php

namespace WN\Core\Exception;

class WnException extends \Exception
{
	protected $http_status;

	public function __construct($message = "", array $variables = NULL, $http_status = 503)
	{
		$message = empty($variables) ? $message : strtr($message, $variables);
		$this->http_status = (int) $http_status;
		parent::__construct($message, 0, NULL);
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
