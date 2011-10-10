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

/**
 * Format class
 *
 * Help convert between various formats such as XML, JSON, CSV, etc.
 *
 * @package    Fuel
 * @category   Core
 * @author     Fuel Development Team
 * @copyright  2010 - 2011 Fuel Development Team
 * @link       http://fuelphp.com/docs/classes/format.html
 */
class Format {

	/**
	 * @var  array|mixed  input to convert
	 */
	protected $_data = array();

	/**
	 * This method is deprecated...use forge() instead.
	 *
	 * @deprecated until 1.2
	 */
	public static function factory($data = null, $from_type = null)
	{
		logger(\Fuel::L_WARNING, 'This method is deprecated.  Please use a forge() instead.', __METHOD__);
		return static::forge($data, $from_type);
	}

	/**
	 * Returns an instance of the Format object.
	 *
	 *     echo Format::forge(array('foo' => 'bar'))->to_xml();
	 *
	 * @param   mixed  general date to be converted
	 * @param   string  data format the file was provided in
	 * @return  Factory
	 */
	public static function forge($data = null, $from_type = null)
	{
		return new static($data, $from_type);
	}

	/**
	 * Do not use this directly, call forge()
	 */
	public function __construct($data = null, $from_type = null)
	{
		// If the provided data is already formatted we should probably convert it to an array
		if ($from_type !== null)
		{
			if (method_exists($this, '_from_' . $from_type))
			{
				$data = call_user_func(array($this, '_from_' . $from_type), $data);
			}

			else
			{
				throw new FuelException('Format class does not support conversion from "' . $from_type . '".');
			}
		}

		$this->_data = $data;
	}

	// FORMATING OUTPUT ---------------------------------------------------------

	/**
	 * To array conversion
	 *
	 * Goes through the input and makes sure everything is either a scalar value or array
	 *
	 * @param   mixed  $data
	 * @return  array
	 */
	public function to_array($data = null)
	{
		if ($data === null)
		{
			$data = $this->_data;
		}

		$array = array();

		if (is_object($data) and ! $data instanceof \Iterator)
		{
			$data = get_object_vars($data);
		}

		foreach ($data as $key => $value)
		{
			if (is_object($value) or is_array($value))
			{
				$array[$key] = $this->to_array($value);
			}
			else
			{
				$array[$key] = $value;
			}
		}

		return $array;
	}

	/**
	 * To XML conversion
	 *
	 * @param   mixed        $data
	 * @param   null         $structure
	 * @param   null|string  $basenode
	 * @return  string
	 */
	public function to_xml($data = null, $structure = null, $basenode = 'xml')
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1)
		{
			ini_set('zend.ze1_compatibility_mode', 0);
		}

		if ($structure == null)
		{
			$structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$basenode />");
		}

		// Force it to be something useful
		if ( ! is_array($data) AND ! is_object($data))
		{
			$data = (array) $data;
		}

		foreach ($data as $key => $value)
		{
			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				$key = (Inflector::singularize($basenode) != $basenode) ? Inflector::singularize($basenode) : 'item';
			}

			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

			// if there is another array found recrusively call this function
			if (is_array($value) || is_object($value))
			{
				$node = $structure->addChild($key);

				// recursive call if value is not empty
				if( ! empty($value))
				{
					$this->to_xml($value, $node, $key);
				}
			}

			else
			{
				// add single node.
				$value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, "UTF-8");

				$structure->addChild($key, $value);
			}
		}

		// pass back as string. or simple xml object if you want!
		return $structure->asXML();
	}

	/**
	 * To CSV conversion
	 *
	 * @param   mixed   $data
	 * @return  string
	 */
	public function to_csv($data = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		// Multi-dimentional array
		if (is_array($data) and isset($data[0]))
		{
			$headings = array_keys($data[0]);
		}

		// Single array
		else
		{
			$headings = array_keys((array) $data);
			$data = array($data);
		}

		$output = implode(',', $headings) . "\n";
		foreach ($data as &$row)
		{
			$output .= '"' . implode('","', (array) $row) . "\"\n";
		}

		return rtrim($output, "\n");
	}

	/**
	 * To JSON conversion
	 *
	 * @param   mixed  $data
	 * @return  string
	 */
	public function to_json($data = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		// To allow exporting ArrayAccess objects like Orm\Model instances they need to be
		// converted to an array first
		$data = (is_array($data) or is_object($data)) ? $this->to_array($data) : $data;
		return json_encode($data);
	}

	/**
	 * To JSONP conversion
	 *
	 * @param mixed $data
	 * @return string
	 */ 
	public function to_jsonp($data = null)
	{
		 $callback = \Input::get_post('callback', null); 
		 is_null($callback) and $callback = 'response'; 

		 return $callback.'('.$this->to_json($data).')';
	}

	/**
	 * Serialize
	 *
	 * @param   mixed  $data
	 * @return  string
	 */
	public function to_serialized($data = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		return serialize($data);
	}

	/**
	 * Return as a string representing the PHP structure
	 *
	 * @param   mixed  $data
	 * @return  string
	 */
	public function to_php($data = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		return var_export($data, true);
	}

	/**
	 * Convert to YAML
	 *
	 * @param   mixed   $data
	 * @return  string
	 */
	public function to_yaml($data = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		if ( ! function_exists('spyc_load'))
		{
			import('spyc/spyc', 'vendor');
		}

		return \Spyc::YAMLDump($data);
	}

	/**
	 * Import XML data
	 *
	 * @param   string  $string
	 * @return  array
	 */
	protected function _from_xml($string)
	{
		return $string ? (array) simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA) : array();
	}

	/**
	 * Import YAML data
	 *
	 * @param   string  $string
	 * @return  array
	 */
	protected function _from_yaml($string)
	{
		if ( ! function_exists('spyc_load'))
		{
			import('spyc/spyc', 'vendor');
		}

		return \Spyc::YAMLLoadString($string);
	}

	/**
	 * Import CSV data
	 *
	 * @param   string  $string
	 * @return  array
	 */
	protected function _from_csv($string)
	{
		$data = array();

		// Splits
		$rows = explode("\n", trim($string));

		// TODO: This means any headers with , will be split, but this is less likley thay a value containing it
		$headings = array_map(function($value) {
				return trim($value, '"');
			}, explode(',', array_shift($rows)));

		$join_row = null;

		foreach ($rows as $row)
		{
			// Check for odd numer of double quotes
			while (substr_count($row, '"') % 2)
			{
				// They have a line start to join onto
				if ($join_row !== null)
				{
					// Lets stick this row onto a new line after the existing row, and see what happens
					$row = $join_row."\n".$row;

					// Did that fix it?
					if (substr_count($row, '"') % 2)
					{
						// Nope, lets try adding the next line
						continue 2;
					}

					else
					{
						// Yep, lets kill the join row.
						$join_row = null;
					}
				}

				// Lets start a new "join line"
				else
				{
					$join_row = $row;

					// Lets bust outta this join, and go to the next row (foreach)
					continue 2;
				}
			}

			// The substr removes " from start and end
			$data_fields = explode('","', trim($row, '"'));

			if (count($data_fields) == count($headings))
			{
				$data[] = array_combine($headings, $data_fields);
			}

		}

		return $data;
	}

	/**
	 * Import JSON data
	 *
	 * @param   string  $string
	 * @return  mixed
	 */
	private function _from_json($string)
	{
		return json_decode(trim($string));
	}

	/**
	 * Import Serialized data
	 *
	 * @param   string  $string
	 * @return  mixed
	 */
	private function _from_serialize($string)
	{
		return unserialize(trim($string));
	}

}

