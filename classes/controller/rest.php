<?php

namespace Fuel\Core;

abstract class Controller_Rest extends \Controller {

	/**
	 * @var  null|string  Set this in a controller to use a default format
	 */
	protected $rest_format = null;

	/**
	 * @var  array  contains a list of method properties such as limit, log and level
	 */
	protected $methods = array();
	
	/**
	 * @var  string  the detected response format
	 */
	protected $format = null;

	/**
	 * @var  array  List all supported methods
	 */
	protected $_supported_formats = array(
		'xml' => 'application/xml',
		'rawxml' => 'application/xml',
		'json' => 'application/json',
		'serialized' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv'
	);

	public function before()
	{
		parent::before();

		\Config::load('rest', true);

		if (\Config::get('rest.auth') == 'basic')
		{
			$this->_prepare_basic_auth();
		}
		elseif (\Config::get('rest.auth') == 'digest')
		{
			$this->_prepare_digest_auth();
		}

		// Some Methods cant have a body
		$this->request->body = null;

		// Which format should the data be returned in?
		$this->request->lang = $this->_detect_lang();
	}

	/**
	 * Router
	 *
	 * Requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 *
	 * @param  string
	 * @param  array
	 */
	public function router($resource, array $arguments)
	{
		$pattern = '/\.(' . implode('|', array_keys($this->_supported_formats)) . ')$/';

		// Check if a file extension is used
		if (preg_match($pattern, $resource, $matches))
		{
			// Remove the extension from arguments too
			$resource = preg_replace($pattern, '', $resource);

			$this->format = $matches[1];
		}
		else
		{
			// Which format should the data be returned in?
			$this->format = $this->_detect_format();
		}

		// If they call user, go to $this->post_user();
		$controller_method = strtolower(\Input::method()) . '_' . $resource;

		// If method is not available, set status code to 404
		if (method_exists($this, $controller_method))
		{
			call_user_func_array(array($this, $controller_method), $arguments);
		}
		else
		{
			$this->response->status = 404;
			return;
		}
	}

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response
	 *
	 * @param  array
	 * @param  int
	 */
	protected function response($data = array(), $http_code = 200)
	{
		if (empty($data))
		{
			$this->response->status = 404;
			return;
		}

		$this->response->status = $http_code;

		// If the format method exists, call and return the output in that format
		if (method_exists('Format', 'to_'.$this->format))
		{
			// Set the correct format header
			$this->response->set_header('Content-Type', $this->_supported_formats[$this->format]);

			$this->response->body(Format::factory($data)->{'to_'.$this->format}());
		}

		// Format not supported, output directly
		else
		{
			$this->response->body((string) $data);
		}
	}

	/**
	 * Detect format
	 *
	 * Detect which format should be used to output the data
	 *
	 * @return  string
	 */
	protected function _detect_format()
	{
		// A format has been passed as an argument in the URL and it is supported
		if (\Input::get_post('format') and $this->_supported_formats[\Input::get_post('format')])
		{
			return \Input::get_post('format');
		}

		// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
		if (\Config::get('rest.ignore_http_accept') === false and \Input::server('HTTP_ACCEPT'))
		{
			// Check all formats against the HTTP_ACCEPT header
			foreach (array_keys($this->_supported_formats) as $format)
			{
				// Has this format been requested?
				if (strpos(\Input::server('HTTP_ACCEPT'), $format) !== false)
				{
					// If not HTML or XML assume its right and send it on its way
					if ($format != 'html' and $format != 'xml')
					{
						return $format;
					}

					// HTML or XML have shown up as a match
					else
					{
						// If it is truly HTML, it wont want any XML
						if ($format == 'html' and strpos(\Input::server('HTTP_ACCEPT'), 'xml') === false)
						{
							return $format;
						}

						// If it is truly XML, it wont want any HTML
						elseif ($format == 'xml' and strpos(\Input::server('HTTP_ACCEPT'), 'html') === false)
						{
							return $format;
						}
					}
				}
			}
		} // End HTTP_ACCEPT checking

		// Well, none of that has worked! Let's see if the controller has a default
		if ( ! empty($this->rest_format))
		{
			return $this->rest_format;
		}

		// Just use the default format
		return \Config::get('rest.default_format');
	}

	/**
	 * Detect language(s)
	 *
	 * What language do they want it in?
	 *
	 * @return  null|array|string
	 */
	protected function _detect_lang()
	{
		if (!$lang = \Input::server('HTTP_ACCEPT_LANGUAGE'))
		{
			return null;
		}

		// They might have sent a few, make it an array
		if (strpos($lang, ',') !== false)
		{
			$langs = explode(',', $lang);

			$return_langs = array();
			$i = 1;
			foreach ($langs as $lang)
			{
				// Remove weight and strip space
				list($lang) = explode(';', $lang);
				$return_langs[] = trim($lang);
			}

			return $return_langs;
		}

		// Nope, just return the string
		return $lang;
	}

	// SECURITY FUNCTIONS ---------------------------------------------------------

	protected function _check_login($username = '', $password = null)
	{
		if (empty($username))
		{
			return false;
		}

		$valid_logins = & \Config::get('rest.valid_logins');

		if (!array_key_exists($username, $valid_logins))
		{
			return false;
		}

		// If actually null (not empty string) then do not check it
		if ($password !== null and $valid_logins[$username] != $password)
		{
			return false;
		}

		return true;
	}

	protected function _prepare_basic_auth()
	{
		$username = null;
		$password = null;

		// mod_php
		if (\Input::server('PHP_AUTH_USER'))
		{
			$username = \Input::server('PHP_AUTH_USER');
			$password = \Input::server('PHP_AUTH_PW');
		}

		// most other servers
		elseif (\Input::server('HTTP_AUTHENTICATION'))
		{
			if (strpos(strtolower(\Input::server('HTTP_AUTHENTICATION')), 'basic') === 0)
			{
				list($username, $password) = explode(':', base64_decode(substr(\Input::server('HTTP_AUTHORIZATION'), 6)));
			}
		}

		if ( ! static::_check_login($username, $password))
		{
			static::_force_login();
		}
	}

	protected function _prepare_digest_auth()
	{
		$uniqid = uniqid(""); // Empty argument for backward compatibility
		// We need to test which server authentication variable to use
		// because the PHP ISAPI module in IIS acts different from CGI
		if (\Input::server('PHP_AUTH_DIGEST'))
		{
			$digest_string = \Input::server('PHP_AUTH_DIGEST');
		}
		elseif (\Input::server('HTTP_AUTHORIZATION'))
		{
			$digest_string = \Input::server('HTTP_AUTHORIZATION');
		}
		else
		{
			$digest_string = "";
		}

		/* The $_SESSION['error_prompted'] variabile is used to ask
		  the password again if none given or if the user enters
		  a wrong auth. informations. */
		if (empty($digest_string))
		{
			static::_force_login($uniqid);
		}

		// We need to retrieve authentication informations from the $auth_data variable
		preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
		$digest = array_combine($matches[1], $matches[2]);

		if ( ! array_key_exists('username', $digest) or ! static::_check_login($digest['username']))
		{
			static::_force_login($uniqid);
		}

		$valid_logins = & \Config::get('rest.valid_logins');
		$valid_pass = $valid_logins[$digest['username']];

		// This is the valid response expected
		$A1 = md5($digest['username'] . ':' . \Config::get('rest.realm') . ':' . $valid_pass);
		$A2 = md5(strtoupper(\Input::method()) . ':' . $digest['uri']);
		$valid_response = md5($A1 . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $A2);

		if ($digest['response'] != $valid_response)
		{
			header('HTTP/1.0 401 Unauthorized');
			header('HTTP/1.1 401 Unauthorized');
			exit;
		}
	}

	protected function _force_login($nonce = '')
	{
		header('HTTP/1.0 401 Unauthorized');
		header('HTTP/1.1 401 Unauthorized');

		if (\Config::get('rest.auth') == 'basic')
		{
			header('WWW-Authenticate: Basic realm="' . \Config::get('rest.realm') . '"');
		}
		elseif (\Config::get('rest.auth') == 'digest')
		{
			header('WWW-Authenticate: Digest realm="' . \Config::get('rest.realm') . '" qop="auth" nonce="' . $nonce . '" opaque="' . md5(\Config::get('rest.realm')) . '"');
		}

		exit('Not authorized.');
	}

}

