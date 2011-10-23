<?php

namespace Fuel\Core;

class Request_Curl extends Request_Driver
{
	/**
	 * @var  array  http headers set for the request
	 */
	protected $headers = array();

	/**
	 * @var  string  to preserve the original resource url when using get
	 */
	protected $preserve_resource;

	/**
	 * Extends parent constructor to detect availability of cURL
	 *
	 * @param   string  $resource
	 * @param   array   $options
	 * @throws  \RuntimeException
	 */
	public function __construct($resource, array $options)
	{
		// check if we have libcurl available
		if ( ! function_exists('curl_init'))
		{
			throw new \RuntimeException('Your PHP installation doesn\'t have cURL enabled. Rebuild PHP with --with-curl');
		}

		parent::__construct($resource, $options);
	}

	/**
	 * Change the HTTP method
	 *
	 * @param   string  $method
	 * @return  Request_Curl
	 */
	public function set_method($method)
	{
		$this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
		return $this;
	}

	/**
	 * set a request http header
	 *
	 * @param   string  $header
	 * @param   string  $header
	 * @return  Request_Curl
	 */
	public function set_header($header, $content = null)
	{
		if (is_null($content))
		{
			$this->headers[] = $header;
		}
		else
		{
			$this->headers[$header] = $content;
		}

		return $this;
	}

	/**
	 * Collect all headers and parse into consistent string
	 *
	 * @return  array
	 */
	public function get_headers()
	{
		$headers = array();
		foreach ($this->headers as $key => $value)
		{
			$headers = is_int($key) ? $value : $key.': '.$value;
		}

		return $headers;
	}

	/**
	 * Fetch the connection, create if necessary
	 *
	 * @return  \resource
	 */
	protected function connection()
	{
		// If no a protocol in URL, assume its a local link
		! preg_match('!^\w+://! i', $this->resource) and $this->resource = Uri::create($this->resource);

		return curl_init($this->resource);
	}

	/**
	 * Authenticate to an http server
	 *
	 * @param   string  $username
	 * @param   string  $password
	 * @param   string  $type
	 * @return  Request_Curl
	 */
	public function http_login($username = '', $password = '', $type = 'any')
	{
		$this->set_option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
		$this->set_option(CURLOPT_USERPWD, $username . ':' . $password);

		return $this;
	}

	/**
	 * Overwrites driver method to set options driver specifically
	 *
	 * @param   int|string  $code
	 * @param   mixed       $value
	 * @return  Request_Curl
	 */
	public function set_options($options)
	{
		foreach ($options as $key => $val)
		{
			if (is_string($key) && ! is_numeric($key))
			{
				$key = constant('CURLOPT_' . strtoupper($key));
			}

			$this->options[$key] = $val;
		}

		return $this;
	}

	public function execute(array $additional_params)
	{
		// Set two default options, and merge any extra ones in
		if ( ! isset($this->options[CURLOPT_TIMEOUT]))
		{
			$this->options[CURLOPT_TIMEOUT] = 30;
		}
		if ( ! isset($this->options[CURLOPT_RETURNTRANSFER]))
		{
			$this->options[CURLOPT_RETURNTRANSFER] = true;
		}
		if ( ! isset($this->options[CURLOPT_FAILONERROR]))
		{
			$this->options[CURLOPT_FAILONERROR] = true;
		}

		// Only set follow location if not running securely
		if ( ! ini_get('safe_mode') && ! ini_get('open_basedir'))
		{
			// Ok, follow location is not set already so lets set it to true
			if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION]))
			{
				$this->options[CURLOPT_FOLLOWLOCATION] = true;
			}
		}

		if ( ! empty($this->headers))
		{
			$this->set_option(CURLOPT_HTTPHEADER, $this->headers);
		}

		if ( ! empty($this->options[CURLOPT_CUSTOMREQUEST]))
		{
			$this->{'method_'.strtolower($this->options[CURLOPT_CUSTOMREQUEST])}();
		}
		else
		{
			$this->method_get();
		}

		$connection = $this->connection();

		curl_setopt_array($connection, $this->options);

		// Execute the request & and hide all output
		$this->response = \Response::forge(curl_exec($connection));
		$this->response->headers = curl_getinfo($connection);
		$this->response->status  = $this->response->headers['http_code'];

		// Request failed
		if ($this->response === false)
		{
			$this->set_defaults();
			throw new \RequestException(curl_error($connection), curl_errno($connection));
		}
		else
		{
			// Request successful
			curl_close($connection);
			$this->set_defaults();

			return $this->response()->body;
		}
	}

	/**
	 * Extends parent to reset headers as well
	 *
	 * @return  Request_Curl
	 */
	protected function set_defaults()
	{
		parent::set_defaults();
		$this->headers = array();

		if ( ! empty($this->preserve_resource))
		{
			$this->resource = $this->preserve_resource;
			$this->preserve_resource = null;
		}

		return $this;
	}

	/**
	 * GET request
	 *
	 * @param   array  $params
	 * @param   array  $options
	 * @return  void
	 */
	protected function method_get()
	{
		$this->preserve_resource = $this->resource;
		$this->resource = \Uri::create($this->resource, array(), $this->params);
	}

	/**
	 * POST request
	 *
	 * @param   array  $params
	 * @return  void
	 */
	protected function method_post()
	{
		$params = http_build_query($this->params, null, '&');

		$this->set_option(CURLOPT_POST, true);
		$this->set_option(CURLOPT_POSTFIELDS, $params);
	}

	/**
	 * PUT request
	 *
	 * @param   array  $params
	 * @return  void
	 */
	protected function method_put()
	{
		$params = http_build_query($this->params, null, '&');

		$this->set_option(CURLOPT_POSTFIELDS, $params);

		// Override method, I think this makes $_POST DELETE data but... we'll see eh?
		$this->set_header('X-HTTP-Method-Override', 'PUT');
	}

	/**
	 * DELETE request
	 *
	 * @param   array  $params
	 * @return  void
	 */
	protected function method_delete()
	{
		$params = http_build_query($this->params, null, '&');

		$this->set_option(CURLOPT_POSTFIELDS, $params);

		// Override method, I think this makes $_POST DELETE data but... we'll see eh?
		$this->set_header('X-HTTP-Method-Override', 'DELETE');
	}
}