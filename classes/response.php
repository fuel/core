<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;


class Response
{

	/**
	 * @var  array  An array of status codes and messages
	 */
	public static $statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded'
	);

	public static function forge($body = null, $status = 200, array $headers = array())
	{
		return new static($body, $status, $headers);
	}

	/**
	 * Redirects to another uri/url.  Sets the redirect header,
	 * sends the headers and exits.  Can redirect via a Location header
	 * or using a refresh header.
	 *
	 * The refresh header works better on certain servers like IIS.
	 *
	 * @param   string  $url     The url
	 * @param   string  $method  The redirect method
	 * @param   int     $code    The redirect status code
	 * @return  void
	 */
	public static function redirect($url = '', $method = 'location', $code = 302)
	{
		$response = new static;

		$response->set_status($code);

		if (strpos($url, '://') === false)
		{
			$url = $url !== '' ? \Uri::create($url) : \Uri::base();
		}

		if ($method == 'location')
		{
			$response->set_header('Location', $url);
		}
		elseif ($method == 'refresh')
		{
			$response->set_header('Refresh', '0;url='.$url);
		}
		else
		{
			return;
		}

		\Event::shutdown();

		$response->send(true);
		exit;
	}

	/**
	 * @var  int  The HTTP status code
	 */
	public $status = 200;

	/**
	 * @var  array  An array of headers
	 */
	public $headers = array();

	/**
	 * @var  string  The content of the response
	 */
	public $body = null;

	/**
	 * Sets up the response with a body and a status code.
	 *
	 * @param  string  $body    The response body
	 * @param  string  $status  The response status
	 */
	public function __construct($body = null, $status = 200, array $headers = array())
	{
		foreach ($headers as $k => $v)
		{
			$this->set_header($k, $v);
		}
		$this->body = $body;
		$this->status = $status;
	}

	/**
	 * Sets the response status code
	 *
	 * @param   string  $status  The status code
	 * @return  $this
	 */
	public function set_status($status = 200)
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Adds a header to the queue
	 *
	 * @param   string  The header name
	 * @param   string  The header value
	 * @param   string  Whether to replace existing value for the header, will never overwrite/be overwritten when false
	 * @return  $this
	 */
	public function set_header($name, $value, $replace = true)
	{
		if ($replace)
		{
			$this->headers[$name] = $value;
		}
		else
		{
			$this->headers[] = array($name, $value);
		}

		return $this;
	}

	/**
	 * Sets (or returns) the body for the response
	 *
	 * @param   string  The response content
	 * @return  $this|string
	 */
	public function body($value = false)
	{
		if ($value === false)
		{
			return $this->body;
		}
		$this->body = $value;
		return $this;
	}

	/**
	 * Sends the headers if they haven't already been sent.  Returns whether
	 * they were sent or not.
	 *
	 * @return  bool
	 */
	public function send_headers()
	{
		if ( ! headers_sent())
		{
			// Send the protocol/status line first, FCGI servers need different status header
			if ( ! empty($_SERVER['FCGI_SERVER_VERSION']))
			{
				header('Status: '.$this->status.' '.static::$statuses[$this->status]);
			}
			else
			{
				$protocol = \Input::server('SERVER_PROTOCOL') ? \Input::server('SERVER_PROTOCOL') : 'HTTP/1.1';
				header($protocol.' '.$this->status.' '.static::$statuses[$this->status]);
			}

			foreach ($this->headers as $name => $value)
			{
				// Parse non-replace headers
				is_int($name) and is_array($value) and list($name, $value) = $value;

				// Create the header
				is_string($name) and $value = "{$name}: {$value}";

				// Send it
				header($value, true);
			}
			return true;
		}
		return false;
	}

	/**
	 * Sends the response to the output buffer.  Optionally will send the
	 * headers.
	 *
	 * @param   string  $send_headers  Whether to send the headers
	 * @return  $this
	 */
	public function send($send_headers = false)
	{
		$send_headers and $this->send_headers();

		if ($this->body != null)
		{
			echo $this->body;
		}
	}

	/**
	 * Returns the body as a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->body();
	}
}
