<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2014 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;


class Response
{

	/**
	 * @var  array  An array of status codes and messages
	 *
	 * See http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
	 * for the complete and approved list, and links to the RFC's that define them
	 */
	public static $statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
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
		418 => 'I\'m a Teapot',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	/**
	 * Creates an instance of the Response class
	 *
	 * @param   string  $body    The response body
	 * @param   int     $status  The HTTP response status for this response
	 * @param   array   $headers Array of HTTP headers for this reponse
	 *
	 * @return  Response
	 */
	public static function forge($body = null, $status = 200, array $headers = array())
	{
		$response = new static($body, $status, $headers);

		// fire any response created events
		\Event::instance()->has_events('response_created') and \Event::instance()->trigger('response_created', '', 'none');

		return $response;
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
	 *
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

		if (\Config::get('response.redirect_with_wildcards', true))
		{
			strpos($url, '*') !== false and $url = \Uri::segment_replace($url);
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

		$response->send(true);
		exit;
	}

	/**
	 * Redirects back to the previous page, if that page is within the current
	 * application. If not, it will redirect to the given url, and if none is
	 * given, back to the application root
	 *
	 * @param   string  $url     The url
	 * @param   string  $method  The redirect method
	 * @param   int     $code    The redirect status code
	 *
	 * @return  void
	 */
	public static function redirect_back($url = '', $method = 'location', $code = 302)
	{
		// do we have a referrer?
		if ($referrer = \Input::referrer())
		{
			// is it within our website? And not equal to the current url?
			if (strpos($referrer, \Uri::base()) === 0 and $referrer != \Uri::current())
			{
				// redirect back to where we came from
				static::redirect($referrer, $method, $code);
			}
		}

		// no referrer or an external link, do a normal redirect
		static::redirect($url, $method, $code);
	}

	/**
	 * @var  int  The HTTP status code
	 */
	public $status = 200;

	/**
	 * @var  array  An array of HTTP headers
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
	 *
	 * @return  Response
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
	 *
	 * @return  Response
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
	 * Gets header information from the queue
	 *
	 * @param   string  The header name, or null for all headers
	 *
	 * @return  mixed
	 */
	public function get_header($name = null)
	{
		if (func_num_args())
		{
			return isset($this->headers[$name]) ? $this->headers[$name] : null;
		}
		else
		{
			return $this->headers;
		}
	}

	/**
	 * Sets (or returns) the body for the response
	 *
	 * @param   string  The response content
	 *
	 * @return  Response|string
	 */
	public function body($value = false)
	{
		if (func_num_args())
		{
			$this->body = $value;
			return $this;
		}

		return $this->body;
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
				if (is_int($name) and is_array($value))
				{
					 isset($value[0]) and $name = $value[0];
					 isset($value[1]) and $value = $value[1];
				}

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
	 * @param   bool  $send_headers  Whether or not to send the defined HTTP headers
	 *
	 * @return  void
	 */
	public function send($send_headers = false)
	{
		$body = $this->__toString();

		if ($send_headers)
		{
			$this->send_headers();
		}

		if ($this->body != null)
		{
			echo $body;
		}
	}

	/**
	 * Returns the body as a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->body;
	}
}
