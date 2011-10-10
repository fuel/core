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



abstract class Rest_Driver {

	/**
	 * @var	array	http headers set for the request
	 */
	 protected $headers = array();

	/**
	 * @var	array	connection options
	 */
	 protected $options = array();

	/**
	 * @var	string	requests response
	 */
	 protected $response = null;

	/**
	 * @var	string	url to connect to
	 */
	 protected $url = null;

	/**
	 * @var	integer	last error code returned by the request
	 */
	protected $error_code = null;

	/**
	 * @var	string	last error string returned by the request
	 */
	protected $error_string = '';

	/**
	 * @var
	 */
	protected $session = null;

	// --------------------------------------------------------------------

	/**
	 * create a new connection
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function create($url);

	// --------------------------------------------------------------------

	/**
	 * authenticate to an http server
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function http_login($user, $pass, $auth);

	// --------------------------------------------------------------------

	/**
	 * execute a request
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function execute();

	// --------------------------------------------------------------------

	/**
	 * get request
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function get($params = array(), array $options = array());

	// --------------------------------------------------------------------

	/**
	 * post request
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function post($params = array(), array $options = array());

	// --------------------------------------------------------------------

	/**
	 * put request
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function put($params = array(), array $options = array());

	// --------------------------------------------------------------------

	/**
	 * delete request
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function delete($params = array(), array $options = array());

	// --------------------------------------------------------------------

	/**
	 * set driver options
	 *
	 * @access	public
	 * @return	void
	 */
	abstract function option($code, $value);

	// --------------------------------------------------------------------

	/**
	 * set a request http header
	 *
	 * @access	public
	 * @return	void
	 */
	public function http_header($header, $content = null)
	{
		$this->headers[] = $content ? $header . ': ' . $content : $header;
	}

	// --------------------------------------------------------------------

	/**
	 * access driver information
	 *
	 * @access	public
	 * @return	void
	 */
	public function info($key = null)
	{
		if (is_null($key))
		{
			return $this->info;
		}
		elseif(array_key_exists($key, $this->info))
		{
			return $this->info[$key];
		}
		else
		{
			return false;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * debug the request
	 *
	 * @access	public
	 * @return	void
	 */
	public function debug_request()
	{
		return array(
			'url' => $this->url
		);
	}

	// --------------------------------------------------------------------

	/**
	 * fetch the last error string
	 *
	 * @access	public
	 * @return	void
	 */
	public function error_string()
	{
		return $this->error_string;
	}

	// --------------------------------------------------------------------

	/**
	 * fetch the last error code
	 *
	 * @access	public
	 * @return	void
	 */
	public function error_code()
	{
		return $this->error_code;
	}

	// --------------------------------------------------------------------

	/**
	 * reset the connection to it's defaults
	 *
	 * @param	void
	 * @return	void
	 */
	protected function set_defaults()
	{
		$this->response = '';
		$this->headers = array();
		$this->options = array();
		$this->error_code = null;
		$this->error_string = '';
		$this->session = null;
	}
}
