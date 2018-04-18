<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.1
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
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
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://docs.fuelphp.com/classes/format.html
 */
class Format
{
	/**
	 * Returns an instance of the Format object.
	 *
	 *     echo Format::forge(array('foo' => 'bar'))->to_xml();
	 *
	 * @param   mixed   $data       general date to be converted
	 * @param   string  $from_type  data format the file was provided in
	 * @param   mixed   $param      additional parameter that can be passed on to a 'from' method
	 * @return  Format
	 */
	public static function forge($data = null, $from_type = null, $param = null)
	{
		return new static($data, $from_type, $param);
	}

	/**
	 * @var  array|mixed  input to convert
	 */
	protected $_data = array();

	/**
	 * @var  bool 	whether to ignore namespaces when parsing xml
	 */
	protected $ignore_namespaces = true;

	/**
	 * Do not use this directly, call forge()
	 *
	 * @param   mixed   $data       general date to be converted
	 * @param   string  $from_type  data format the file was provided in
	 * @param   mixed   $param      additional parameter that can be passed on to a 'from' method
	 * @throws \FuelException
	 */
	public function __construct($data = null, $from_type = null, $param = null)
	{
		// If the provided data is already formatted we should probably convert it to an array
		if ($from_type !== null)
		{

			if ($from_type == 'xml:ns')
			{
				$this->ignore_namespaces = false;
				$from_type = 'xml';
			}

			if (method_exists($this, '_from_' . $from_type))
			{
				$data = call_user_func_array(array($this, '_from_' . $from_type), array($data, $param));
			}

			else
			{
				throw new \FuelException('Format class does not support conversion from "' . $from_type . '".');
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

		if (empty($data))
		{
			return array();
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
	 * @param   null|bool    $use_cdata             whether to use CDATA in nodes
	 * @param   mixed        $bool_representation  if true, element values are true/false. if 1, 1/0.
	 * @return  string
	 */
	public function to_xml($data = null, $structure = null, $basenode = null, $use_cdata = null, $bool_representation = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		is_null($basenode) and $basenode = \Config::get('format.xml.basenode', 'xml');
		is_null($use_cdata) and $use_cdata = \Config::get('format.xml.use_cdata', false);
		is_null($bool_representation) and $bool_representation = \Config::get('format.xml.bool_representation', null);

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
		if ( ! is_array($data) and ! is_object($data))
		{
			$data = (array) $data;
		}

		foreach ($data as $key => $value)
		{
			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

			// no numeric keys in our xml please!
			if (is_numeric($key))
			{
				// make string key...
				$key = (\Inflector::singularize($basenode) != $basenode) ? \Inflector::singularize($basenode) : 'item';
			}

			// if there is another array found recrusively call this function
			if (is_array($value) or is_object($value))
			{
				$node = $structure->addChild($key);

				// recursive call if value is not empty
				if( ! empty($value))
				{
					$this->to_xml($value, $node, $key, $use_cdata, $bool_representation);
				}
			}
			elseif ($bool_representation and is_bool($value))
			{
				if ($bool_representation === true)
				{
					$bool = $value ? 'true' : 'false';
				}
				else
				{
					$bool = $value ? '1' : '0';
				}
				$structure->addChild($key, $bool);
			}
			else
			{
				// add single node.
				$encoded = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, "UTF-8");

				if ($use_cdata and ($encoded !== (string) $value))
				{
					$dom = dom_import_simplexml($structure->addChild($key));
					$owner = $dom->ownerDocument;
					$dom->appendChild($owner->createCDATASection($value));
				}
				else
				{
					$structure->addChild($key, $encoded);
				}
			}
		}

		// pass back as string. or simple xml object if you want!
		return $structure->asXML();
	}

	/**
	 * To CSV conversion
	 *
	 * @param   mixed   $data
	 * @param   mixed   $delimiter
	 * @param   mixed   $enclose_numbers
	 * @param   array   $headings         Custom headings to use
	 * @return  string
	 */
	public function to_csv($data = null, $delimiter = null, $enclose_numbers = null, array $headings = array())
	{
		// csv format settings
		$newline = \Config::get('format.csv.newline', \Config::get('format.csv.export.newline', "\n"));
		$delimiter or $delimiter = \Config::get('format.csv.delimiter', \Config::get('format.csv.export.delimiter', ','));
		$enclosure = \Config::get('format.csv.enclosure', \Config::get('format.csv.export.enclosure', '"'));
		$escape = \Config::get('format.csv.escape', \Config::get('format.csv.export.escape', '\\'));
		is_null($enclose_numbers) and $enclose_numbers = \Config::get('format.csv.enclose_numbers', true);

		// escape, delimit and enclose function
		$escaper = function($items, $enclose_numbers) use($enclosure, $escape, $delimiter) {
			return 	implode($delimiter, array_map(function($item) use($enclosure, $escape, $delimiter, $enclose_numbers) {
				if ( ! is_numeric($item) or $enclose_numbers)
				{
					$item = $enclosure.str_replace($enclosure, $escape.$enclosure, $item).$enclosure;
				}
				return $item;
			}, $items));
		};

		if ($data === null)
		{
			$data = $this->_data;
		}

		if (is_object($data) and ! $data instanceof \Iterator)
		{
			$data = $this->to_array($data);
		}

		// Multi-dimensional array
		if (empty($headings))
		{
			if (is_array($data) and \Arr::is_multi($data))
			{
				$data = array_values($data);

				if (\Arr::is_assoc($data[0]))
				{
					$headings = array_keys($data[0]);
				}
				else
				{
					$headings = array_shift($data);
				}
			}
			// Single array
			else
			{
				$headings = array_keys((array) $data);
				$data = array($data);
			}
		}

		$output = $escaper($headings, true).$newline;

		foreach ($data as $row)
		{
			$output .= $escaper($row, $enclose_numbers).$newline;
		}

		return rtrim($output, $newline);
	}

	/**
	 * To JSON conversion
	 *
	 * @param   mixed  $data
	 * @param   bool   $pretty  whether to make the json pretty
	 * @return  string
	 */
	public function to_json($data = null, $pretty = false)
	{
		if ($data === null)
		{
			$data = $this->_data;
		}

		// To allow exporting ArrayAccess objects like Orm\Model instances they need to be
		// converted to an array first
		$data = (is_array($data) or is_object($data)) ? $this->to_array($data) : $data;
		return $pretty ? static::pretty_json($data) : json_encode($data, \Config::get('format.json.encode.options', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
	}

	/**
	 * To JSONP conversion
	 *
	 * @param   mixed   $data
	 * @param   bool    $pretty    whether to make the json pretty
	 * @param   string  $callback  JSONP callback
	 * @return  string  formatted JSONP
	 */
	public function to_jsonp($data = null, $pretty = false, $callback = null)
	{
		$callback or $callback = \Input::param('callback');
		is_null($callback) and $callback = 'response';

		return $callback.'('.$this->to_json($data, $pretty).')';
	}

	/**
	 * Serialize
	 *
	 * @param   mixed  $data
	 * @return  string
	 */
	public function to_serialized($data = null)
	{
		if ($data === null)
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
		if ($data === null)
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
	 * @param   bool    $recursive
	 * @return  array
	 */
	protected function _from_xml($string, $recursive = false)
	{
		// If it forged with 'xml:ns'
		if ( ! $this->ignore_namespaces)
		{
			static $escape_keys = array();
			$recursive or $escape_keys = array('_xmlns' => 'xmlns');

			if ( ! $recursive and strpos($string, 'xmlns') !== false and preg_match_all('/(\<.+?\>)/s', $string, $matches))
			{
				foreach ($matches[1] as $tag)
				{
					$escaped_tag = $tag;

					strpos($tag, 'xmlns=') !== false and $escaped_tag = str_replace('xmlns=', '_xmlns=', $tag);

					if (preg_match_all('/[\s\<\/]([^\/\s\'"]*?:\S*?)[=\/\>\s]/s', $escaped_tag, $xmlns))
					{
						foreach ($xmlns[1] as $ns)
						{
							$escaped = \Arr::search($escape_keys, $ns);
							$escaped or $escape_keys[$escaped = str_replace(':', '_', $ns)] = $ns;
							$string = str_replace($tag, $escaped_tag = str_replace($ns, $escaped, $escaped_tag), $string);
							$tag = $escaped_tag;
						}
					}
				}
			}
		}

		$_arr = is_string($string) ? simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA) : $string;

		// Convert all objects SimpleXMLElement to array recursively
		$arr = array();
		foreach ((array) $_arr as $key => $val)
		{
			$this->ignore_namespaces or $key = \Arr::get($escape_keys, $key, $key);
			if ( ! $val instanceOf \SimpleXMLElement or $val->count() or $val->attributes())
			{
				$arr[$key] = (is_array($val) or is_object($val)) ? $this->_from_xml($val, true) : $val;
			}
			else
			{
				$arr[$val->getName()] = null;
			}
		}

		return $arr;
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
	 * @param   bool    $no_headings
	 * @return  array
	 */
	protected function _from_csv($string, $no_headings = false)
	{
		$data = array();

		// csv config
		$newline = \Config::get('format.csv.regex_newline', "\n");
		$delimiter = \Config::get('format.csv.delimiter', \Config::get('format.csv.import.delimiter', ','));
		$escape = \Config::get('format.csv.escape', \Config::get('format.csv.import.escape', '"'));
		// have to do this in two steps, empty string is a valid value for enclosure!
		$enclosure = \Config::get('format.csv.enclosure', \Config::get('format.csv.import.enclosure', null));
		$enclosure === null and $enclosure = '"';

		if (empty($enclosure))
		{
			$rows = preg_split('/(['.$newline.'])/m', trim($string), -1, PREG_SPLIT_NO_EMPTY);
		}
		else
		{
			$rows = preg_split('/(?<=[0-9'.preg_quote($enclosure).'])'.$newline.'/', trim($string));
		}

		// Get the headings
		if ($no_headings !== false)
		{
			$headings = str_replace($escape.$enclosure, $enclosure, str_getcsv(array_shift($rows), $delimiter, $enclosure, $escape));
			$headcount = count($headings);
		}

		// Process the rows
		$incomplete = '';
		foreach ($rows as $row)
		{
			// process the row
			$data_fields = str_replace($escape.$enclosure, $enclosure, str_getcsv($incomplete.($incomplete ? $newline : '').$row, $delimiter, $enclosure, $escape));

			// if we didn't have headers, the first row determines the number of fields
			if ( ! isset($headcount))
			{
				$headcount = count($data_fields);
			}

			// finish the row if the have the correct field count, otherwise add the data to the next row
			if (count($data_fields) == $headcount)
			{
				$data[] = $no_headings === false ? $data_fields : array_combine($headings, $data_fields);
				$incomplete = '';
			}
			else
			{
				$incomplete = $incomplete . $row;
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

	/**
	 * Makes json pretty the json output.
	 * Borrowed from http://www.php.net/manual/en/function.json-encode.php#80339
	 *
	 * @param   string  $data  json encoded array
	 * @return  string|false  pretty json output or false when the input was not valid
	 */
	protected static function pretty_json($data)
	{
		$json = json_encode($data, \Config::get('format.json.encode.options', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));

		if ( ! $json)
		{
			return false;
		}

		$tab = "\t";
		$newline = "\n";
		$new_json = "";
		$indent_level = 0;
		$in_string = false;
		$len = strlen($json);

		for ($c = 0; $c < $len; $c++)
		{
			$char = $json[$c];
			switch($char)
			{
				case '{':
				case '[':
					if ( ! $in_string)
					{
						$new_json .= $char.$newline.str_repeat($tab, $indent_level+1);
						$indent_level++;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if ( ! $in_string)
					{
						$indent_level--;
						$new_json .= $newline.str_repeat($tab, $indent_level).$char;
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ',':
					if ( ! $in_string)
					{
						$new_json .= ','.$newline.str_repeat($tab, $indent_level);
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case ':':
					if ( ! $in_string)
					{
						$new_json .= ': ';
					}
					else
					{
						$new_json .= $char;
					}
					break;
				case '"':
					if ($c > 0 and $json[$c-1] !== '\\')
					{
						$in_string = ! $in_string;
					}
				default:
					$new_json .= $char;
					break;
			}
		}

		return $new_json;
	}

	/**
	 * Loads Format config.
	 */
	public static function _init()
	{
		\Config::load('format', true);
	}
}
