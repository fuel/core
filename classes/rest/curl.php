<?php
/**
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


// ------------------------------------------------------------------------

/**
 * Curl Class
 *
 * Generic driver for cURL requests. Requires libcurl to be available!
 *
 * @package		Fuel
 * @category	Core
 * @author		Harro Verton
 * @based-on	Phil Sturgeon's CodeIgniter cURL library
 */
class Rest_Curl extends \Rest_Driver {

	/**
	 * Class constructor
	 *
	 * @param    void
	 * @return   void
	 */
	public function __construct()
	{
		// check if we have libcurl available
		if ( ! function_exists('curl_init'))
		{
			throw new \RestException('Your PHP installation doesn\'t have cURL enabled. Rebuild PHP with --with-curl');
		}
	}

	/**
	 * create a new connection
	 *
	 * @access	public
	 * @return	void
	 */
	public function create($url)
	{
		// If no a protocol in URL, assume its a local link
		! preg_match('!^\w+://! i', $url) and $url = Uri::create($url);

		$this->url = $url;
		$this->session = curl_init($this->url);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * authenticate to an http server
	 *
	 * @access	public
	 * @return	void
	 */
	public function http_login($username = '', $password = '', $type = 'any')
	{
		$this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
		$this->option(CURLOPT_USERPWD, $username . ':' . $password);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * execute a request
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute()
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
		if ( ! ini_get('safe_mode') && !ini_get('open_basedir'))
		{
			// Ok, follow location is not set already so lets set it to true
			if ( ! isset($this->options[CURLOPT_FOLLOWLOCATION]))
			{
				$this->options[CURLOPT_FOLLOWLOCATION] = true;
			}
		}

		if ( ! empty($this->headers))
		{
			$this->option(CURLOPT_HTTPHEADER, $this->headers);
		}

		$this->options();

		// Execute the request & and hide all output
		$this->response = curl_exec($this->session);
		$this->info = curl_getinfo($this->session);

		// Request failed
		if ($this->response === false)
		{
			$this->error_code = curl_errno($this->session);
			$this->error_string = curl_error($this->session);

			curl_close($this->session);
			$this->set_defaults();

			return false;
		}
		else
		{
			// Request successful
			curl_close($this->session);
			$response = $this->response;
			$this->set_defaults();

			return $response;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * get request
	 *
	 * @access	public
	 * @return	void
	 */
	public function get($params = array(), array $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$params = http_build_query($params, null, '&');
		}

		// Add in the specific options provided
		$this->options($options);

		$this->http_method('get');
	}

	// --------------------------------------------------------------------

	/**
	 * post request
	 *
	 * @access	public
	 * @return	void
	 */
	public function post($params = array(), array $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$params = http_build_query($params, null, '&');
		}

		// Add in the specific options provided
		$this->options($options);

		$this->http_method('post');

		$this->option(CURLOPT_POST, true);
		$this->option(CURLOPT_POSTFIELDS, $params);
	}

	// --------------------------------------------------------------------

	/**
	 * put request
	 *
	 * @access	public
	 * @return	void
	 */
	public function put($params = array(), array $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$params = http_build_query($params, null, '&');
		}

		// Add in the specific options provided
		$this->options($options);

		$this->http_method('put');
		$this->option(CURLOPT_POSTFIELDS, $params);

		// Override method, I think this overrides $_POST with PUT data but... we'll see eh?
		$this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
	}

	// --------------------------------------------------------------------

	/**
	 * delete request
	 *
	 * @access	public
	 * @return	void
	 */
	public function delete($params = array(), array $options = array())
	{
		// If its an array (instead of a query string) then format it correctly
		if (is_array($params))
		{
			$params = http_build_query($params, null, '&');
		}

		// Add in the specific options provided
		$this->options($options);

		$this->http_method('delete');

		$this->option(CURLOPT_POSTFIELDS, $params);
	}

	// --------------------------------------------------------------------

	/**
	 * set driver options
	 *
	 * @access	public
	 * @return	void
	 */
	public function option($code, $value)
	{
		if (is_string($code) && !is_numeric($code))
		{
			$code = constant('CURLOPT_' . strtoupper($code));
		}

		$this->options[$code] = $value;

		return $this;
	}

	// --------------------------------------------------------------------

	private function http_method($method)
	{
		$this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);

		return $this;
	}

	// --------------------------------------------------------------------

	private function options(array $options = array())
	{
		// Merge options in with the rest - done as array_merge() does not overwrite numeric keys
		foreach ($options as $option_code => $option_value)
		{
			$this->option($option_code, $option_value);
		}

		// Set all options provided
		curl_setopt_array($this->session, $this->options);

		return $this;
	}

}
