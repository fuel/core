<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

/**
 * Input class instance
 *
 * The input class allows you to access HTTP parameters, load server variables
 * and user agent details.
 *
 * @package   Fuel
 * @category  Core
 * @link      http://docs.fuelphp.com/classes/input.html
 */
class Input_Instance
{
	/**
	 * @var  $request  Active instance of Request
	 */
	protected $request = null;

	/**
	 * @var  $detected_uri  The URI that was detected automatically
	 */
	protected $detected_uri = null;

	/**
	 * @var  $detected_ext  The URI extension that was detected automatically
	 */
	protected $detected_ext = null;

	/**
	 * @var  string  $raw  raw PHP input
	 */
	protected $raw_input = null;

	/**
	 * @var  array  $get  All GET input
	 */
	protected $input_get = array();

	/**
	 * @var  array  $post  All POST input
	 */
	protected $input_post = array();

	/**
	 * @var  array  $put  All PUT input
	 */
	protected $input_put = array();

	/**
	 * @var  array  $post  All DELETE input
	 */
	protected $input_delete = array();

	/**
	 * @var  array  $input  All PATCH input
	 */
	protected $input_patch = array();

	/**
	 * @var  $json  parsed request body as json
	 */
	protected $input_json = array();

	/**
	 * @var  $xml  parsed request body as xml
	 */
	protected $input_xml = array();

	/**
	 *
	 */
	public function __construct(Request $new = null, Input_Instance $input = null)
	{
		// store the associated request
		$this->request = $new;

		// get php raw input
		$this->raw_input = file_get_contents('php://input');

		// was an input instance passed?
		if ($input)
		{
			// fetch parent request input data
			$this->input_get = $input->input_get;
			$this->input_post = $input->input_post;
			$this->input_put = $input->input_put;
			$this->input_patch = $input->input_patch;
			$this->input_delete = $input->input_delete;
			$this->input_json = $input->input_json;
			$this->input_xml = $input->input_xml;
		}
		else
		{
			// fetch global input data
			$this->hydrate();
		}
	}

	/**
	 * Detects and returns the current URI based on a number of different server
	 * variables.
	 *
	 * @throws \FuelException
	 * @return  string
	 */
	public function uri()
	{
		// don't run URI detection twice
		if ($this->detected_uri !== null)
		{
			return $this->detected_uri;
		}

		// process the Request URI if we have one
		if ($this->request and is_string($this->request->uri))
		{
			$uri =  '/'.$this->request->uri;
		}

		// We want to use PATH_INFO if we can.
		elseif ( ! empty($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];
		}

		// Only use ORIG_PATH_INFO if it contains the path
		elseif ( ! empty($_SERVER['ORIG_PATH_INFO']) and ($path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['ORIG_PATH_INFO'])) != '')
		{
			$uri = $path;
		}

		// Fall back to parsing the REQUEST URI
		elseif (isset($_SERVER['REQUEST_URI']))
		{
			$uri = '';

			if ( ! empty($_SERVER['REQUEST_URI']))
			{
				$uri = strpos($_SERVER['SCRIPT_NAME'], $_SERVER['REQUEST_URI']) !== 0 ? $_SERVER['REQUEST_URI'] : '';
			}
		}

		// deal with CLI requests
		elseif (\Fuel::$is_cli)
		{
			if (($uri = \Cli::option('uri')) !== null)
			{
				$this->detected_uri = $uri;
			}
			elseif ($uri = \Cli::option(1) and strpos($uri, '/') === 0)
			{
				$this->detected_uri = $uri;
			}
			else
			{
				$this->detected_uri = '';
			}

			return $this->detected_uri;
		}
		else
		{
			throw new \FuelException('Unable to detect the URI.');
		}

		// Remove the base URL from the URI
		$base_url = parse_url(\Config::get('base_url'), PHP_URL_PATH);
		if ($uri !== '' and $base_url !== '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
		{
			$uri = substr($uri, strlen($base_url) - 1);
		}

		// If we are using an index file (not mod_rewrite) then remove it
		$index_file = \Config::get('index_file');
		if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
		{
			$uri = substr($uri, strlen($index_file));
		}

		// When index.php? is used and the config is set wrong, lets just
		// be nice and help them out.
		if ($index_file and strncmp($uri, '?/', 2) === 0)
		{
			$uri = substr($uri, 1);
		}

		// in case of incorrect rewrites, we may need to cleanup and
		// recreate the QUERY_STRING and $_GET
		if (strpos($uri, '?') !== false)
		{
			// log this issue
			\Log::write(\Fuel::L_DEBUG, 'Your rewrite rules are incorrect, change "index.php?/$1 [QSA,L]" to "index.php/$1 [L]"!');

			// reset $_GET
			$_GET = array();

			// lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?'
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set everything correctly
			if ( ! empty($matches))
			{
				// first bit is the real uri
				$uri = $matches[1];

				// second bit is the real query string
				$_SERVER['QUERY_STRING'] = $matches[2];
				parse_str($matches[2], $_GET);

				// update GET variables
				$_GET = \Security::clean($_GET);
				$this->input_get = $_GET;
			}

		}

		// Deal with any trailing dots
		$uri = rtrim($uri, '.');

		// Do we have a URI and does it not end on a slash?
		if ($uri and substr($uri, -1) !== '/')
		{
			// Strip the defined url suffix from the uri if needed
			$ext = strrchr($uri, '.');
			$path = $ext === false ? $uri : substr($uri, 0, -strlen($ext));

			// Did we detect something that looks like an extension?
			if ( ! empty($ext))
			{
				// if it has a slash in it, it's a URI segment with a dot in it
				if (strpos($ext, '/') === false)
				{
					$this->detected_ext = ltrim($ext, '.');

					$strip = \Config::get('routing.strip_extension', true);
					if ($strip === true or (is_array($strip) and in_array($ext, $strip)))
					{
						$uri = $path;
					}
				}
			}
		}

		// Do some final clean up of the uri
		$this->detected_uri = \Security::clean_uri($uri, true);

		// And return it
		return $this->detected_uri;
	}

	/**
	 * Detects and returns the current URI extension
	 *
	 * @return  string
	 */
	public function extension()
	{
		$this->detected_ext === null and $this->uri();

		return $this->detected_ext;
	}

	/**
	 * Get the request body interpreted as JSON.
	 *
	 * @param   mixed  $index
	 * @param   mixed  $default
	 * @return  array  parsed request body content.
	 */
	public function json($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_json : \Arr::get($this->input_json, $index, $default);
	}

	/**
	 * Get the request body interpreted as XML.
	 *
	 * @param   mixed  $index
	 * @param   mixed  $default
	 * @return  array  parsed request body content.
	 */
	public function xml($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_xml : \Arr::get($this->input_xml, $index, $default);
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @param   string $default
	 * @return  string
	 */
	public function method($default = 'GET')
	{
		// get the method from the current active request
		if ($this->request and $method = $this->request->get_method())
		{
			return $method;
		}

		// if called before a request is active, fall back to the global server setting
		if (\Config::get('security.allow_x_headers', false))
		{
			return \Input::server('HTTP_X_HTTP_METHOD_OVERRIDE', \Input::server('REQUEST_METHOD', $default));
		}

		return \Input::server('REQUEST_METHOD', $default);
	}

	/**
	 * Returns PHP's raw input
	 *
	 * @return  array
	 */
	public function raw()
	{
		return $this->raw_input;
	}

	/**
	 * Returns all of the GET, POST, PUT, PATCH or DELETE array's
	 *
	 * @return  array
	 */
	public function all()
	{
		return array_merge($this->input_get, $this->input_post, $this->input_put, $this->input_patch, $this->input_delete);
	}

	/**
	 * Gets the specified GET variable.
	 *
	 * @param   string  $index    The index to get
	 * @param   string  $default  The default value
	 * @return  string|array
	 */
	public function get($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_get : \Arr::get($this->input_get, $index, $default);
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function post($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_post : \Arr::get($this->input_post, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for put arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function put($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_put : \Arr::get($this->input_put, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for patch arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function patch($index = null, $default = null)
	{
		return (func_num_args() === 0) ? $this->input_patch : \Arr::get($this->input_patch, $index, $default);
	}

	/**
	 * Fetch an item from the php://input for delete arguments
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function delete($index = null, $default = null)
	{
		return (is_null($index) and func_num_args() === 0) ? $this->input_delete : \Arr::get($this->input_delete, $index, $default);
	}

	/**
	 * Fetch an item from either the GET, POST, PUT, PATCH or DELETE array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function param($index = null, $default = null)
	{
		return \Arr::get($this->all(), $index, $default);
	}

	/**
	 * Set additional input variables
	 *
	 * @param  string  $method  name of the HTTP method to set variables for, in lowercase
	 * @param  array   $input   assoc array of input fieldnames and values
	 *
	 * @return  void
	 */
	public function _set($method, array $input)
	{
		// make sure the method is lowercase
		$method = strtolower(trim($method));

		if ($method and property_exists($this, 'input_'.$method))
		{
			$this->{'input_'.$method} = array_merge($this->{'input_'.$method}, $input);
		}
		else
		{
			throw new \FuelException('Input method "'.$method.'" is not defined.');
		}
	}

	/**
	 * Hydrates the input array
	 *
	 * @return  void
	 */
	protected function hydrate()
	{
		// get the input method and unify it
		$method = strtolower($this->method());

		// get the content type from the header, strip optional parameters
		$content_header = \Input::headers('Content-Type');
		if (($content_type = strstr($content_header, ';', true)) === false)
		{
			$content_type = $content_header;
		}

		// fetch the raw input data
		$php_input = $this->raw();

		// handle form-urlencoded input
		if ($content_type == 'application/x-www-form-urlencoded')
		{
			// double-check if max_input_vars is not exceeded,
			// it doesn't always give an E_WARNING it seems...
			if ($method === 'get' or $method === 'post')
			{
				if ($php_input and ($amps = substr_count($php_input, '&')) > ini_get('max_input_vars'))
				{
					throw new \PhpErrorException(
						'Input truncated by PHP. Number of variables exceeded '.ini_get('max_input_vars').'. To increase the limit to at least the '.$amps.' needed for this HTTP request, change the value of "max_input_vars" in php.ini.',
						1,
						E_WARNING,
						__FILE__,
						__LINE__ + 7 // note: error points to accessing $_POST above!
					);

				}
			}
			else
			{
				// urldecode it if needed
				if (\Config::get('security.form-double-urlencoded', false))
				{
					$php_input = urldecode($php_input);
				}

				// other methods than GET and POST need to be parsed manually
				parse_str($php_input, $php_input);
			}
		}

		// handle multipart/form-data input
		elseif ($content_type == 'multipart/form-data')
		{
			// grab multipart boundary from content type header
			preg_match('/boundary=(.*)$/', $content_header, $matches);
			$boundary = $matches[1];

			// split content by boundary and get rid of last -- element
			$blocks = preg_split('/-+' . preg_quote($boundary, '/') . '/', $php_input);
			array_pop($blocks);

			// loop data blocks
			$php_input = array();
			foreach ($blocks as $id => $block)
			{
				// skip empty blocks
				if ( ! empty($block))
				{
					// parse uploaded files
					if (strpos($block, 'application/octet-stream') !== FALSE)
					{
						// match "name", then everything after "stream" (optional) except for prepending newlines
						preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
					}
					// parse all other fields
					else
					{
						// match "name" and optional value in between newline sequences
						preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
					}

					// store the result, if any
					$php_input[$matches[1]] = isset($matches[2]) ? $matches[2] : '';
				}
			}
		}

		// handle json input
		elseif ($content_type == 'application/json')
		{
			$this->input_json = $php_input = \Security::clean(\Format::forge($php_input, 'json')->to_array());
		}

		// handle xml input
		elseif ($content_type == 'application/xml' or $content_type == 'text/xml')
		{
			$this->input_xml = $php_input = \Security::clean(\Format::forge($php_input, 'xml')->to_array());
		}

		// GET and POST input were parsed by PHP
		$this->input_get = $_GET;
		$this->input_post = $_POST;

		// for other methods, we need valid input
		if ($php_input and is_array($php_input))
		{
			// store the parsed data based on the request method
			if ($method === 'put' or $method === 'patch' or $method === 'delete')
			{
				$this->{'input_'.$method} = $php_input;
			}
		}
	}
}
