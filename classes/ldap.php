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

class LDAP
{
	const ADLDAP_FOLDER = 'OU';
	const ADLDAP_CONTAINER = 'CN';
	const DEFAULT_PORT = 389;
	const DEFAULT_SSL_PORT = 636;
	private static $_config;
	private static $_connection;
	private static $_binding;
	private static $_base_dn;

	private function __construct() {}

	private static function clean($erase_config = false) {
		static::$_connection = null;
		static::$_binding = null;
		static::$_base_dn = null;
		if ($erase_config) {
			static::$_config = null;
		}
	}

	public static function load($reconnect = false, $config = null) {
		$response = false;
		if (static::ldap_supported()) {
			static::disconnect();
			if ($config === null || ! is_array($config) || empty($config)) {
				static::$_config = \Config::load('ldap', true);
			} else {
				static::$_config = $config;
			}
			$response = true;
			if ($reconnect) {
				$response &= static::connect();
			}
		} else {
			throw new \Fuel_Exception('LDAP is not supported.');
		}
		return $response;
	}

	public static function get_current_config() {
		return static::$_config;
	}

	public static function connect($reload_config = false, $config = null) {
		$response = false;
		if (static::$_connection === null || ! is_resource(static::$_connection)) {
			if ($reload_config == true || static::$_config === null || ! is_array(static::$_config) || empty(static::$_config)) {
				static::load(false, $config);
			}
			$domain_controller = trim(static::_random_domain_controller());
			if ($domain_controller !== "") {
				$host = $domain_controller;
				if (isset(static::$_config ['connection'] ['use_ssl']) && static::$_config ['connection'] ['use_ssl'] == true) {
					$host = "ldaps://" . $host;
					$port = ((isset(static::$_config ['connection'] ['port'])) ? static::$_config ['connection'] ['port'] : static::DEFAULT_SSL_PORT);
				} else {
					$port = ((static::$_config ['connection'] ['port']) ? static::$_config ['connection'] ['port'] : self::DEFAULT_PORT);
				}
				static::$_connection = ldap_connect($host, $port);
				if (static::is_connected()) {
					// Set some ldap options for correct communication
					ldap_set_option(static::$_connection, LDAP_OPT_PROTOCOL_VERSION, 3);
					ldap_set_option(static::$_connection, LDAP_OPT_REFERRALS, 0);
					// Start TLS if configured
					if (isset(static::$_config ['connection'] ['use_tls']) && static::$_config ['connection'] ['use_tls'] == true) {
						ldap_start_tls(static::$_connection);
					}
					$response = true;
				}
			} else {
				throw new \Fuel_Exception('There are no domain controllers in the configuration. Cannot connect.');
			}
		}
		return $response;
	}

	public static function bind($anonymous = false) {
		$response = false;
		if (static::is_connected(true)) {
			if ($anonymous) {
				$master_user = '';
				$master_pwd = '';
			} 

			// Prevent anonymous binding checking if a username and password has been configured
			else {
				if (! isset(static::$_config ['connection'] ['master_user']) || static::$_config ['connection'] ['master_user'] === '' || ! isset(static::$_config ['connection'] ['master_pwd']) || static::$_config ['connection'] ['master_pwd'] === '') {
					return false;
				} else {
					$domain_suffix = (isset(static::$_config ['domain_suffix']) ? '@' . static::$_config ['domain_suffix'] : '');
					$master_user = static::$_config ['connection'] ['master_user'] . $domain_suffix;
					$master_pwd = static::$_config ['connection'] ['master_pwd'];
				}
			}
			
			static::$_binding = ldap_bind(static::$_connection, $master_user, $master_pwd);
			if (static::$_binding) {
				static::$_base_dn = static::_find_base_dn();
				if (static::$_base_dn !== null) {
					$response = true;
				}
			}
		} else {
			throw new \Fuel_Exception('Cannot bind: there is no connection to LDAP server.');
		}
		return $response;
	}

	public static function authenticate($username, $password, $rebind_as_master = true) {
		$response = false;
		if (static::is_connected(true)) {
			if (is_string($username) && $username !== "" && is_string($password) && $password !== "") {
				$domain_suffix = (isset(static::$_config ['domain_suffix']) ? "@" . static::$_config ['domain_suffix'] : "");
				static::$_binding = @ldap_bind(static::$_connection, $username . $domain_suffix, $password);
				if (static::$_binding) {
					$response = true;
				}
				if ($rebind_as_master) {
					static::bind();
				}
			}
		} else {
			throw new \Fuel_Exception('Cannot authenticate: there is no connection to LDAP server.');
		}
		return $response;
	}

	public static function disconnect() {
		if (static::is_connected()) {
			ldap_unbind(static::$_connection);
		}
		static::clean();
	}

	public static function query($filter = '', $attributes = '', $base_dn = '', $sizeLimit = 0) {
		$response = null;
		$filter = trim($filter);
		$filter = ((! is_string($filter) || $filter === '') ? '(objectClass=*)' : $filter);
		$default_fields = array(
			'objectguid', 'displayname'
		);
		$fields = ((is_string($attributes)) ? (($attributes == '') ? $default_fields : static::build_attr_array($attributes)) : ((is_array($attributes) && ! empty($attributes)) ? $attributes : $default_fields));
		
		$base_dn = ((is_string($base_dn) && (trim($base_dn) !== '')) ? $base_dn : static::$_base_dn);
		
		if (static::is_connected(true)) {
			if (static::is_bound(true)) {
				$sr = ldap_search(static::$_connection, $base_dn, $filter, $fields, 0, $sizeLimit);
				if (is_resource($sr)) {
					$response = ldap_get_entries(static::$_connection, $sr);
				}
			} else {
				throw new \Fuel_Exception('Cannot query: there is no binding to LDAP server.');
			}
		} else {
			throw new \Fuel_Exception('Cannot query: there is no connection to LDAP server.');
		}
		return $response;
	}

	public static function query_polished($filter = '', $attributes = array(), $base_dn = '', $sizeLimit = 0) {
		$response = static::query($filter, $attributes, $base_dn, $sizeLimit);
		$response = static::polish_entries($response);
		return $response;
	}

	public static function polish_entries($entries) {
		$response = $entries;
		// Get rid of the numeric indexes inside the results
		for($i = 0; $i < $response ['count']; $i ++) {
			for($j = 0; $j < $response [$i] ['count']; $j ++) {
				unset($response [$i] [$j]);
			}
		}
		return $response;
	}

	public static function build_filter($params = array()) {
		if (is_string($params) && $params != '') {
			$response = static::build_filter_enclose($params);
		} else {
			$response = static::build_filter_enclose(static::build_filter_level($params));
		}
		return $response;
	}

	private static function build_filter_enclose($filter) {
		$response = '';
		if (is_string($filter) && $filter != '') {
			if ($filter [0] == '(') {
				$response = $filter;
			} else {
				$response = "(" . $filter . ")";
			}
		}
		return $response;
	}

	private static function build_filter_level($params) {
		$response = '';
		if (is_array($params) && ! empty($params)) {
			foreach ( $params as $param ) {
				if (is_string($param)) {
					switch ($param) {
						case '&' :
						case '|' :
							$response .= $param;
							break;
						default :
							$response .= "(" . $param . ")";
							break;
					}
				} else {
					$response .= static::build_filter_enclose(static::build_filter_level($param));
				}
			}
		}
		return $response;
	}

	public static function build_attr_array($attributes) {
		$response = array();
		if (is_string($attributes)) {
			$attributes = trim($attributes);
			if ($attributes != "") {
				$response = explode(",", $attributes);
				$response = array_map('trim', $response);
			}
		}
		return $response;
	}

	public static function ldap_supported() {
		$response = false;
		if (function_exists('ldap_connect')) {
			$response = true;
		}
		return $response;
	}

	public static function is_connected($try_connect = false) {
		$response = false;
		if (isset(static::$_connection) && is_resource(static::$_connection)) {
			$response = true;
		} elseif ($try_connect) {
			$response = static::connect();
		}
		return $response;
	}

	public static function is_bound($try_bind = false) {
		$response = false;
		if (isset(static::$_binding) && static::$_binding == true) {
			$response = true;
		} elseif ($try_bind) {
			$response = static::bind();
		}
		return $response;
	}

	public static function guid_bin_to_str($guid) {
		$hex_guid = bin2hex($guid);
		$hex_guid_to_guid_str = '';
		for($k = 1; $k <= 4; ++ $k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 8 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++ $k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 12 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-';
		for($k = 1; $k <= 2; ++ $k) {
			$hex_guid_to_guid_str .= substr($hex_guid, 16 - 2 * $k, 2);
		}
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 16, 4);
		$hex_guid_to_guid_str .= '-' . substr($hex_guid, 20);
		return strtoupper($hex_guid_to_guid_str);
	}

	private static function _random_domain_controller() {
		$response = null;
		if (isset(static::$_config ['domain_controllers']) && is_array(static::$_config ['domain_controllers']) && ! empty(static::$_config ['domain_controllers'])) {
			$response = static::$_config ['domain_controllers'] [array_rand(static::$_config ['domain_controllers'])];
		}
		return $response;
	}

	private static function _find_base_dn() {
		$response = null;
		$namingContexts = static::_get_root_dse(array(
			'namingcontexts'
		));
		if ($namingContexts !== null) {
			if (isset($namingContexts [0] ['namingcontexts'] [0])) {
				$response = $namingContexts [0] ['namingcontexts'] [0];
			} else {
				$response = '';
			}
		} else {
			throw new \Fuel_Exception('Cannot find base dn.');
		}
		return $response;
	}

	private static function _get_root_dse($attributes = array("*", "+")) {
		$response = null;
		if (static::is_connected()) {
			$sr = @ldap_read(static::$_connection, null, 'objectClass=*', $attributes);
			if (is_resource($sr)) {
				$response = @ldap_get_entries(static::$_connection, $sr);
			}
		}
		return $response;
	}
}

/* End of file ldap.php */