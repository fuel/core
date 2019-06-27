<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

abstract class Controller_Rest extends \Controller
{
	/**
	 * @var  null|string  Set this in a controller to use a default format
	 */
	protected $rest_format = null;

	/**
	 * @var  array  contains a list of method properties such as limit, log and level
	 */
	protected $methods = array();

	/**
	 * @var  integer  status code to return in case a not defined action is called
	 */
	protected $no_method_status = 405;

	/**
	 * @var  integer  status code to return in case the called action doesn't return data
	 */
	protected $no_data_status = 204;

	/**
	 * @var  string  authentication to be used for this controller
	 */
	protected $auth = null;

	/**
	 * @var  string  the detected response format
	 */
	protected $format = null;

	/**
	 * @var  integer  default response http status
	 */
	protected $http_status = 200;

	/**
	 * @var  string  xml basenode name
	 */
	protected $xml_basenode = null;

	/**
	 * @var  array  List all supported methods
	 */
	protected $_supported_formats = array(
		'xml' => 'application/xml',
		'rawxml' => 'application/xml',
		'json' => 'application/json',
		'jsonp'=> 'text/javascript',
		'serialized' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'html' => 'text/html',
		'csv' => 'application/csv',
	);

	public function before()
	{
		parent::before();

		// Some Methods cant have a body
		$this->request->body = null;

		// Which format should the data be returned in?
		$this->request->lang = $this->_detect_lang();

		$this->response = \Response::forge();
	}

	public function after($response)
	{
		// If the response is an array
		if (is_array($response))
		{
			// set the response
			$response = $this->response($response);
		}

		// If the response is a Response object, we will use their
		// instead of ours.
		if ( ! $response instanceof \Response)
		{
			$response = $this->response;
		}

		return parent::after($response);
	}

	/**
	 * Router
	 *
	 * Requests are not made to methods directly The request will be for an "object".
	 * this simply maps the object and method to the correct Controller method.
	 *
	 * @param  string $resource
	 * @param  array $arguments
	 * @return bool|mixed
	 */
	public function router($resource, $arguments)
	{
		\Config::load('rest', true);

		// If no (or an invalid) format is given, auto detect the format
		if (is_null($this->format) or ! array_key_exists($this->format, $this->_supported_formats))
		{
			// auto-detect the format
			$this->format = array_key_exists(\Input::extension(), $this->_supported_formats) ? \Input::extension() : $this->_detect_format();
		}

		// Get the configured auth method if none is defined
		$this->auth === null and $this->auth = \Config::get('rest.auth');

		//Check method is authorized if required, and if we're authorized
		if ($this->auth == 'basic')
		{
			$valid_login = $this->_prepare_basic_auth();
		}
		elseif ($this->auth == 'digest')
		{
			$valid_login = $this->_prepare_digest_auth();
		}
		elseif (method_exists($this, $this->auth))
		{
			if (($valid_login = $this->{$this->auth}()) instanceOf \Response)
			{
				return $valid_login;
			}
		}
		else
		{
			$valid_login = false;
		}

		//If the request passes auth then execute as normal
		if(empty($this->auth) or $valid_login)
		{
			// If they call user, go to $this->post_user();
			$controller_method = strtolower(\Input::method()) . '_' . $resource;

			// Fall back to action_ if no rest method is provided
			if ( ! method_exists($this, $controller_method))
			{
				$controller_method = 'action_'.$resource;
			}

			// If method is not available, set status code to 404
			if (method_exists($this, $controller_method))
			{
				return call_fuel_func_array(array($this, $controller_method), $arguments);
			}
			else
			{
				$this->response->status = $this->no_method_status;
				return;
			}
		}
		else
		{
			$this->response(array('status'=> 0, 'error'=> 'Not Authorized'), 401);
		}
	}

	/**
	 * Response
	 *
	 * Takes pure data and optionally a status code, then creates the response
	 *
	 * @param   mixed
	 * @param   int
	 * @return  object  Response instance
	 */
	protected function response($data = array(), $http_status = null)
	{
		// set the correct response header
		if (method_exists('Format', 'to_'.$this->format))
		{
			$this->response->set_header('Content-Type', $this->_supported_formats[$this->format]);
		}

		// no data returned?
		if ((is_array($data) and empty($data)) or ($data == ''))
		{
			// override the http status with the NO CONTENT status
			$http_status = $this->no_data_status;
		}

		// make sure we have a valid return status
		$http_status or $http_status = $this->http_status;

		// If the format method exists, call and return the output in that format
		if (method_exists('Format', 'to_'.$this->format))
		{
			// Handle XML output
			if ($this->format === 'xml')
			{
				// Detect basenode
				$xml_basenode = $this->xml_basenode;
				$xml_basenode or $xml_basenode = \Config::get('rest.xml_basenode', 'xml');

				// Set the XML response
				$this->response->body(\Format::forge($data)->{'to_'.$this->format}(null, null, $xml_basenode));
			}
			else
			{
				// Set the formatted response
				$this->response->body(\Format::forge($data)->{'to_'.$this->format}());
			}
		}

		// Format not supported, but the output is an array or an object that can not be cast to string
		elseif (is_array($data) or (is_object($data) and ! method_exists($data, '__toString')))
		{
			if (\Fuel::$env == \Fuel::PRODUCTION)
			{
				// not acceptable in production
				if ($http_status == 200)
				{	$http_status = 406;
				}
				$this->response->body('The requested REST method returned an array or object, which is not compatible with the output format "'.$this->format.'"');
			}
			else
			{
				// convert it to json so we can at least read it while we're developing
				$this->response->body('The requested REST method returned an array or object:<br /><br />'.\Format::forge($data)->to_json(null, true));
			}
		}

		// Format not supported, output directly
		else
		{
			$this->response->body($data);
		}

		// Set the reponse http status
		$http_status and $this->response->status = $http_status;

		return $this->response;
	}

	/**
	 * Set the Response http status.
	 *
	 * @param   integer  $status  response http status code
	 * @return  void
	 */
	protected function http_status($status)
	{
		$this->http_status = $status;
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
		// A format has been passed as a named parameter in the route
		if ($this->param('format') and array_key_exists($this->param('format'), $this->_supported_formats))
		{
			return $this->param('format');
		}

		// A format has been passed as an argument in the URL and it is supported
		if (\Input::param('format') and array_key_exists(\Input::param('format'), $this->_supported_formats))
		{
			return \Input::param('format');
		}

		// Otherwise, check the HTTP_ACCEPT (if it exists and we are allowed)
		if ($acceptable = \Input::server('HTTP_ACCEPT') and \Config::get('rest.ignore_http_accept') !== true)
		{
			// If anything is accepted, and we have a default, return that
			if ($acceptable == '*/*' and ! empty($this->rest_format))
			{
				return $this->rest_format;
			}

			// Split the Accept header and build an array of quality scores for each format
			$fragments = new \CachingIterator(new \ArrayIterator(preg_split('/[,;]/', $acceptable)));
			$acceptable = array();
			$next_is_quality = false;
			foreach ($fragments as $fragment)
			{
				$quality = 1;
				// Skip the fragment if it is a quality score
				if ($next_is_quality)
				{
					$next_is_quality = false;
					continue;
				}

				// If next fragment exists and is a quality score, set the quality score
				elseif ($fragments->hasNext())
				{
					$next = $fragments->getInnerIterator()->current();
					if (strpos($next, 'q=') === 0)
					{
						list($key, $quality) = explode('=', $next);
						$next_is_quality = true;
					}
				}

				$acceptable[$fragment] = $quality;
			}

			// Sort the formats by score in descending order
			uasort($acceptable, function($a, $b)
			{
				$a = (float) $a;
				$b = (float) $b;
				return ($a > $b) ? -1 : 1;
			});

			// Check each of the acceptable formats against the supported formats
			$find = array('\*', '/');
			$replace = array('.*', '\/');
			foreach ($acceptable as $pattern => $quality)
			{
				// The Accept header can contain wildcards in the format
				$pattern = '/^' . str_replace($find, $replace, preg_quote($pattern)) . '$/';
				foreach ($this->_supported_formats as $format => $mime)
				{
					if (preg_match($pattern, $mime))
					{
						return $format;
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

		$valid_logins = \Config::get('rest.valid_logins');

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
			return false;
		}

		return true;
	}

	protected function _prepare_digest_auth()
	{
		// Empty argument for backward compatibility
		$uniqid = uniqid("");

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
			$digest_string = '';
		}

		// Prompt for authentication if we don't have a digest string
		if (empty($digest_string))
		{
			static::_force_login($uniqid);
			return false;
		}

		// We need to retrieve authentication informations from the $digest_string variable
		$digest_params = explode(',', $digest_string);
		foreach ($digest_params as $digest_param)
		{
			$digest_param = explode('=', trim($digest_param), 2);
			if (isset($digest_param[1]))
			{
				$digest[$digest_param[0]] = trim($digest_param[1], '"');
			}
		}

		// if no username, or an invalid username found, re-authenticate
		if ( ! array_key_exists('username', $digest) or ! static::_check_login($digest['username']))
		{
			static::_force_login($uniqid);
			return false;
		}

		// validate the configured login/password
		$valid_logins = \Config::get('rest.valid_logins');
		$valid_pass = $valid_logins[$digest['username']];

		// This is the valid response expected
		$A1 = md5($digest['username'] . ':' . \Config::get('rest.realm') . ':' . $valid_pass);
		$A2 = md5(strtoupper(\Input::method()) . ':' . $digest['uri']);
		$valid_response = md5($A1 . ':' . $digest['nonce'] . ':' . $digest['nc'] . ':' . $digest['cnonce'] . ':' . $digest['qop'] . ':' . $A2);

		if ($digest['response'] != $valid_response)
		{
			return false;
		}

		return true;
	}

	protected function _force_login($nonce = '')
	{
		// Get the configured auth method if none is defined
		$this->auth === null and $this->auth = \Config::get('rest.auth');

		if ($this->auth == 'basic')
		{
			$this->response->set_header('WWW-Authenticate', 'Basic realm="'. \Config::get('rest.realm') . '"');
		}
		elseif ($this->auth == 'digest')
		{
			$this->response->set_header('WWW-Authenticate', 'Digest realm="' . \Config::get('rest.realm') . '", qop="auth", nonce="' . $nonce . '", opaque="' . md5(\Config::get('rest.realm')) . '"');
		}
	}

}
