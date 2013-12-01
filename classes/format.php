<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.7
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2013 Fuel Development Team
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
	 * @param   mixed  general date to be converted
	 * @param   string  data format the file was provided in
	 * @return  Format
	 */
	public static function forge($data = null, $from_type = null)
	{
		return new static($data, $from_type);
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
	 */
	public function __construct($data = null, $from_type = null)
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
				$data = call_user_func(array($this, '_from_' . $from_type), $data);
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
	 * @param   null|bool    whether to use CDATA in nodes
	 * @return  string
	 */
	public function to_xml($data = null, $structure = null, $basenode = null, $use_cdata = null)
	{
		if ($data == null)
		{
			$data = $this->_data;
		}

		is_null($basenode) and $basenode = \Config::get('format.xml.basenode', 'xml');
		is_null($use_cdata) and $use_cdata = \Config::get('format.xml.use_cdata', false);

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
					$this->to_xml($value, $node, $key, $use_cdata);
				}
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
	 * @return  string
	 */
	public function to_csv($data = null, $delimiter = null)
	{
		// csv format settings
		$newline = \Config::get('format.csv.export.newline', \Config::get('format.csv.newline', "\n"));
		$delimiter or $delimiter = \Config::get('format.csv.export.delimiter', \Config::get('format.csv.delimiter', ','));
		$enclosure = \Config::get('format.csv.export.enclosure', \Config::get('format.csv.enclosure', '"'));
		$escape = \Config::get('format.csv.export.escape', \Config::get('format.csv.escape', '"'));

		// escape function
		$escaper = function($items) use($enclosure, $escape) {
			return array_map(function($item) use($enclosure, $escape){
				return str_replace($enclosure, $escape.$enclosure, $item);
			}, $items);
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

		$output = $enclosure.implode($enclosure.$delimiter.$enclosure, $escaper($headings)).$enclosure.$newline;

		foreach ($data as $row)
		{
			$output .= $enclosure.implode($enclosure.$delimiter.$enclosure, $escaper((array) $row)).$enclosure.$newline;
		}

		return rtrim($output, $newline);
	}

	/**
	 * To JSON conversion
	 *
	 * @param   mixed  $data
	 * @param   bool   wether to make the json pretty
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
		return $pretty ? static::pretty_json($data) : json_encode($data, \Config::get('format.json.encode.option', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
	}

	/**
	 * To JSONP conversion
	 *
	 * @param   mixed   $data
	 * @param   bool    $pretty    wether to make the json pretty
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
		$arr = array();

		// Convert all objects SimpleXMLElement to array recursively
		foreach ((array)$_arr as $key => $val)
		{
			$this->ignore_namespaces or $key = \Arr::get($escape_keys, $key, $key);
			$arr[$key] = (is_array($val) or is_object($val)) ? $this->_from_xml($val, true) : $val;
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
	 * @return  array
	 */
	protected function _from_csv($string)
	{
		$data = array();

		$rows = preg_split('/(?<='.preg_quote(\Config::get('format.csv.import.enclosure', \Config::get('format.csv.enclosure', '"'))).')'.\Config::get('format.csv.regex_newline', '\n').'/', trim($string));

		// csv config
		$delimiter = \Config::get('format.csv.import.delimiter', \Config::get('format.csv.delimiter', ','));
		$enclosure = \Config::get('format.csv.import.enclosure', \Config::get('format.csv.enclosure', '"'));
		$escape = \Config::get('format.csv.import.escape', \Config::get('format.csv.escape', '"'));

		// Get the headings
		$headings = str_replace($escape.$enclosure, $enclosure, str_getcsv(array_shift($rows), $delimiter, $enclosure, $escape));

		foreach ($rows as $row)
		{
			$data_fields = str_replace($escape.$enclosure, $enclosure, str_getcsv($row, $delimiter, $enclosure, $escape));

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

	/**
	 * Makes json pretty the json output.
	 * Barrowed from http://www.php.net/manual/en/function.json-encode.php#80339
	 *
	 * @param   string  $json  json encoded array
	 * @return  string|false  pretty json output or false when the input was not valid
	 */
	protected static function pretty_json($data)
	{
		$json = json_encode($data, \Config::get('format.json.encode.option', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));

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
