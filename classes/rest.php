<?php
/**
 * Fuel
 *
 * Fuel is a fast, lightweight, community driven PHP5 framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;


class RestException extends \Fuel_Exception {}

// ------------------------------------------------------------------------

/**
 * Rest Class
 *
 * Make REST requests to RESTful services with simple syntax.
 *
 * @package		Fuel
 * @category	Core
 * @author		Harro Verton
 * @based-on	Phil Sturgeon's CodeIgniter REST client
 */
class Rest {

	/**
	 * @var     object    Rest
	 */
	protected static $_instance = null;

	/**
	 * @var     array     contains references to all instantiations of Rest
	 */
	protected static $_instances = array();

	/**
	 * @var     array    supported response formats
	 */
	protected static $supported_formats = array(
		'xml' => 'application/xml',
		'json' => 'application/json',
		'serialize' => 'application/vnd.php.serialized',
		'php' => 'text/plain',
		'csv' => 'text/csv',
	);

	/**
	 * @var     array    mimetype format autodetection
	 */
	protected static $auto_detect_formats = array(
		'application/xml' => 'xml',
		'text/xml' => 'xml',
		'application/json' => 'json',
		'text/json' => 'json',
		'text/csv' => 'csv',
		'application/csv' => 'csv',
		'application/vnd.php.serialized' => 'serialize',
	);

	/**
	 * This method is deprecated...use forge() instead.
	 * 
	 * @deprecated until 1.2
	 */
	public static function factory($name = 'default', array $config = array())
	{
		\Log::warning('This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($name, $config);
	}

	/*
	 */
	public static function forge($name = 'default', array $config = array())
	{
		if ($exists = static::instance($name))
		{
			\Error::notice('REST client instance called "'.$name.'" already exists, cannot be overwritten.');
			return $exists;
		}

		static::$_instances[$name] = new static($name, $config);

		if ($name == 'default')
		{
			static::$_instance = static::$_instances[$name];
		}

		return static::$_instances[$name];
	}

	/**
	 * Return a specific instance, or the default instance (is created if necessary)
	 *
	 * @param	string	instance name
	 * @return	Rest
	 */
	public static function instance($instance = null)
	{
		if ($instance !== null)
		{
			if ( ! array_key_exists($instance, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$instance];
		}

		if (static::$_instance === null)
		{
			static::$_instance = static::forge();
		}

		return static::$_instance;
	}

	// ------------------------------------------------------------------------

	/**
	 * @var     array    configuration array
	 */
	protected $config = array(
		'method' => 'curl',
		'server' => null,
		'url' => null,
		'http_auth' => null,
		'http_user' => null,
		'http_pass' => null,
	);

	/**
	 * @var     string    format used for this request
	 */
	protected $format;

	/**
	 * @var     string    mimetype used for this request
	 */
	protected $mime_type;

	/**
	 * @var     string    mimetype used for this request
	 */
	protected $response_string;

	/**
	 * @var     object    communications driver used
	 */
	protected $driver = null;

	/**
	 * Class constructor
	 *
	 * @param   string
	 * @param   array
	 */
	protected function __construct($name, array $config = array())
	{
		// set a default config
		$this->config = array_merge($this->config, $config);

		isset($config['method']) and $this->config['method'] = $config['method'];

		isset($config['server']) and $this->config['server'] = $config['server'];

		if (substr($this->config['server'], -1, 1) != '/')
		{
			$this->config['server'] .= '/';
		}

		isset($config['http_auth']) and $this->config['http_auth'] = $config['http_auth'];
		isset($config['http_user']) and $this->config['http_user'] = $config['http_user'];
		isset($config['http_pass']) and $this->config['http_pass'] = $config['http_pass'];

		// config validation
		if (is_null($this->config['server']))
		{
			throw new \RestException('New REST instance created without specifying the server to connect to');
		}

		if (is_null($this->config['method']) or ! in_array($this->config['method'], array('curl', 'sockets', 'wrapper')))
		{
			throw new \RestException('New REST instance created without specifying a valid connection method');
		}

		// initialise the required driver
		$class = '\\Rest_'.ucfirst($this->config['method']);
		$this->driver = new $class();
	}

	/*
	 */
	public function get($uri, array $params = array(), $format = null)
	{
		if ($params)
		{
			$uri .= '?'.(is_array($params) ? http_build_query($params) : $params);
		}

		return $this->call('get', $uri, array(), $format);
	}

	/*
	 */
	public function post($uri, array $params = array(), $format = null)
	{
		return $this->call('post', $uri, $params, $format);
	}

	/*
	 */
	public function put($uri, array $params = array(), $format = null)
	{
		return $this->call('put', $uri, $params, $format);
	}

	/*
	 */
	public function delete($uri, array $params = array(), $format = null)
	{
		return $this->call('delete', $uri, $params, $format);
	}

	/*
	 */
	public function api_key($key, $name = 'X-API-KEY')
	{
		$this->driver->http_header($name, $key);
	}

	/*
	 */
	public function language($lang)
	{
		is_array($lang) and $lang = implode(', ', $lang);

		$this->driver->http_header('Accept-Language', $lang);
	}

	/*
	 * If a type is passed in that is not supported, use it as a mime type
	 */
	public function format($format)
	{
		if (array_key_exists($format, static::$supported_formats))
		{
			$this->format = $format;
			$this->mime_type = static::$supported_formats[$format];
		}
		else
		{
			$this->mime_type = $format;
		}

		return $this;
	}

	/*
	 * Return HTTP status code
	 */
	public function status()
	{
		return $this->info('http_code');
	}

	/*
	 * Return curl info by specified key, or whole array
	 */
	public function info($key = null)
	{
		return $this->driver->info($key);
	}

	/*
	 * Set custom options
	 */

	public function option($code, $value)
	{
		$this->driver->option($code, $value);
	}

	/**
	 * <text>
	 *
	 * @param   void
	 * @return  void
	 */
	public function debug()
	{
		$request = $this->driver->debug_request();

		echo "=============================================<br/>\n";
		echo "<h2>REST Test</h2>\n";
		echo "=============================================<br/>\n";
		echo "<h3>Request</h3>\n";
		echo $request['url']."<br/>\n";
		echo "=============================================<br/>\n";
		echo "<h3>Response</h3>\n";

		if ($this->response_string)
		{
			echo "<code>".nl2br(htmlentities($this->response_string))."</code><br/>\n\n";
		}
		else
		{
			echo "No response<br/>\n\n";
		}

		echo "=============================================<br/>\n";

		if ($this->driver->error_string())
		{
			echo "<h3>Errors</h3>";
			echo "<strong>Code:</strong> ".$this->driver->error_code()."<br/>\n";
			echo "<strong>Message:</strong> ".$this->driver->error_string()."<br/>\n";
			echo "=============================================<br/>\n";
		}

		echo "<h3>Call details</h3>";
		echo "<pre>";
		print_r($this->driver->info());
		echo "</pre>";
	}

	/**
	 * <text>
	 *
	 * @param   void
	 * @return  void
	 */
	private function format_response($response)
	{
		$this->response_string =& $response;

		// It is a supported format, so just run its formatting method
		if (array_key_exists($this->format, static::$supported_formats))
		{
			return $this->{"from_".$this->format}($response);
		}

		// Find out what format the data was returned in
		$returned_mime = $this->driver->info('content_type');

		// If they sent through more than just mime, stip it off
		if (strpos($returned_mime, ';'))
		{
			list($returned_mime)=explode(';', $returned_mime);
		}

		$returned_mime = trim($returned_mime);

		if (array_key_exists($returned_mime, static::$auto_detect_formats))
		{
			return $this->{'from_'.static::$auto_detect_formats[$returned_mime]}($response);
		}

		return $response;
	}

	/**
	 * Format XML for output
	 *
	 * @param   void
	 * @return  void
	 */
	private function from_xml($string)
	{
		return $string ? (array) simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA) : array();
	}

	/**
	 * Format HTML for output
	 *
	 * This function is DODGY! Not perfect CSV support but works with Fuel's REST_Controller
	 *
	 * @param   void
	 * @return  void
	 */
	private function from_csv($string)
	{
		$data = array();

		// Splits
		$rows = explode("\n", trim($string));
		$headings = explode(',', array_shift($rows));

		foreach( $rows as $row )
		{
			// The substr removes " from start and end
			$data_fields = explode('","', trim(substr($row, 1, -1)));

			if (count($data_fields) == count($headings))
			{
				$data[] = array_combine($headings, $data_fields);
			}

		}

		return $data;
	}

	/**
	 * Format JSON for output
	 *
	 * @param   void
	 * @return  void
	 */
	private function from_json($string)
	{
		return json_decode(trim($string));
	}

	/**
	 * Format Serialized array for output
	 *
	 * @param   void
	 * @return  void
	 */
	private function from_serialize($string)
	{
		return unserialize(trim($string));
	}

	/**
	 * Format raw PHP for output
	 *
	 * @param   void
	 * @return  void
	 */
	private function from_php($string)
	{
		 $string = trim($string);
		 $populated = array();
		 eval("\$populated = \"$string\";");
		 return $populated;
	}

	/**
	 * <text>
	 *
	 * @param   void
	 * @return  void
	 */
	private function call($method, $uri, array $params = array(), $format = null)
	{
		! is_null($format) and $this->format($format);

		$this->driver->http_header('Accept: '.$this->mime_type);

		// Initialize cURL session
		$this->driver->create($this->config['server'].$uri);

		// If authentication is enabled use it
		if ($this->config['http_auth'] != '' && $this->config['http_user'] != '')
		{
			$this->driver->http_login($this->config['http_user'], $this->config['http_pass'], $this->config['http_auth']);
		}

		// We still want the response even if there is an error code over 400
		$this->driver->option('failonerror', FALSE);

		// Call the correct method with parameters
		if (method_exists($this->driver, $method))
		{
			$this->driver->{$method}($params);
		}
		else
		{
			throw new \RestException('REST instance called with an undefined method "'.$method.'"');
		}

		// Execute and return the response from the REST server
		$response = $this->driver->execute();

		// Format and return
		return $this->format_response($response);
	}

}
