<?php

namespace Fuel\Core;

class RequestException extends HttpNotFoundException {}

abstract class Request_Driver
{
	/**
	 * @var  string  URL resource to perform requests upon
	 */
	protected $resource = '';

	/**
	 * @var  array  parameters to pass
	 */
	protected $params = array();

	/**
	 * @var  array  driver specific options
	 */
	protected $options = array();

	/**
	 * @var  Response  the response object after execute
	 */
	protected $response;

	public function __construct($resource, array $options)
	{
		$this->resource  = $resource;

		foreach ($options as $key => $value)
		{
			if (method_exists($this, 'set_'.$key))
			{
				$this->{'set_'.$key}($value);
			}
		}
	}

	/**
	 * Set the parameters to pass with the request
	 *
	 * @param   array  $params
	 * @return  Request_Driver
	 */
	public function set_params(array $params)
	{
		$this->params = $params;
		return $this;
	}

	/**
	 * Sets options on the driver
	 *
	 * @param   array  $options
	 * @return  Request_Curl
	 */
	public function set_options(array $options)
	{
		$this->options = $options;
		return $this;
	}

	/**
	 * Sets a single option/value
	 *
	 * @param   int|string  $option
	 * @param   mixed       $value
	 * @return  Request_Curl
	 */
	public function set_option($option, $value)
	{
		return $this->set_options(array($option => $value));
	}

	/**
	 * Add a single parameter/value or an array of parameters
	 *
	 * @param   string|array  $param
	 * @param   mixed         $value
	 * @return  Request_Driver
	 */
	public function add_param($param, $value = null)
	{
		if ( ! is_array($param))
		{
			$param = array($param, $value);
		}

		foreach ($param as $key => $val)
		{
			\Arr::set($this->params, $key, $val);
		}
		return $this;
	}

	/**
	 * Executes the request upon the URL
	 *
	 * @param   array  $additional_params
	 * @param   array  $query_string
	 * @return  Response
	 */
	abstract public function execute(array $additional_params);

	/**
	 * Reset before doing another request
	 *
	 * @return  Request_Curl
	 */
	protected function set_defaults()
	{
		$this->options   = array();
		$this->params    = array();
		return $this;
	}

	/**
	 * Fetch the response
	 *
	 * @return  Response
	 */
	public function response()
	{
		return $this->response;
	}

	/**
	 * Returns the body as a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->response();
	}
}
