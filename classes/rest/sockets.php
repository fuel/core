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



class Rest_Sockets extends \Rest_Driver
{

	/**
	 * Class constructor
	 *
	 * @param    void
	 * @return   void
	 */
	public function __construct()
	{
		throw new Exception('Rest_Sockets driver is not implemented yet!');
	}


	/**
	 * access driver information
	 *
	 * @access	public
	 * @return	void
	 */
	public function info($key = null);

	// --------------------------------------------------------------------

	/**
	 * create a new connection
	 *
	 * @access	public
	 * @return	void
	 */
	public function create($url);

	// --------------------------------------------------------------------

	/**
	 * authenticate to an http server
	 *
	 * @access	public
	 * @return	void
	 */
	public function http_login($user, $pass, $auth);

	// --------------------------------------------------------------------

	/**
	 * execute a request
	 *
	 * @access	public
	 * @return	void
	 */
	public function execute();

	// --------------------------------------------------------------------

	/**
	 * get request
	 *
	 * @access	public
	 * @return	void
	 */
	public function get(array $params = array());

	// --------------------------------------------------------------------

	/**
	 * post request
	 *
	 * @access	public
	 * @return	void
	 */
	public function post(array $params = array());

	// --------------------------------------------------------------------

	/**
	 * put request
	 *
	 * @access	public
	 * @return	void
	 */
	public function put(array $params = array());

	// --------------------------------------------------------------------

	/**
	 * delete request
	 *
	 * @access	public
	 * @return	void
	 */
	public function delete(array $params = array());

	// --------------------------------------------------------------------

	/**
	 * set driver options
	 *
	 * @access	public
	 * @return	void
	 */
	public function option($code, $value);

	// --------------------------------------------------------------------

	/**
	 * debug the request
	 *
	 * @access	public
	 * @return	void
	 */
	public function debug_request();

	// --------------------------------------------------------------------

	/**
	 * fetch the last error string
	 *
	 * @access	public
	 * @return	void
	 */
	public function error_string();

	// --------------------------------------------------------------------

	/**
	 * fetch the last error code
	 *
	 * @access	public
	 * @return	void
	 */
	public function error_code();

	// --------------------------------------------------------------------

	/**
	 * set a request http header
	 *
	 * @access	public
	 * @return	void
	 */
	public function http_header();
}
