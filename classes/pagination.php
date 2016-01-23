<?php
/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.8
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2016 Fuel Development Team
 * @link       http://fuelphp.com
 */

namespace Fuel\Core;

class Pagination
{
	/**
	 * @var	array	Pagination instances
	 */
	protected static $_instances = array();

	/**
	 * @var	array	Pagination default instance
	 */
	protected static $_instance = null;

	/**
	 * Init
	 *
	 * Loads in the config and sets the variables
	 *
	 * @return	void
	 */
	public static function _init()
	{
		\Config::load('pagination', true);
		\Lang::load('pagination', true);
	}

	/**
	 * Static access to the default instance
	 *
	 * @param 	string	$name
	 * @param 	array	$arguments
	 * @return	mixed
	 * @throws	\BadMethodCallException if the request method does not exist
	 */
	public static function __callStatic($name, $arguments)
	{
		// old pre-1.4 mapping to new instance methods
		static $mapping = array(
			'get'          => '__get',
			'set'          => '__set',
			'set_config'   => '__set',
			'create_links' => 'render',
			'page_links'   => 'pages_render',
			'prev_link'    => 'previous',
			'next_link'    => 'next',
		);

		array_key_exists($name, $mapping) and $name = $mapping[$name];

		// call the method on the default instance
		if ($instance = static::instance() and method_exists($instance, $name))
		{
			return call_fuel_func_array(array($instance, $name), $arguments);
		}

		throw new \BadMethodCallException('The pagination class doesn\'t have a method called "'.$name.'"');
	}

	/**
	 * forge a new pagination instance
	 *
	 * @param	string $name
	 * @param	array $config
	 * @return	\Pagination	a new pagination instance
	 */
	public static function forge($name = 'default', $config = array())
	{
		if ($exists = static::instance($name))
		{
			\Errorhandler::notice('Pagination with this name exists already, cannot be overwritten.');
			return $exists;
		}

		static::$_instances[$name] = new static($config);

		if ($name == 'default')
		{
			static::$_instance = static::$_instances[$name];
		}

		return static::$_instances[$name];
	}

	/**
	 * retrieve an existing pagination instance
	 *
	 * @param	string $name
	 * @return	\Pagination	a existing pagination instance
	 */
	public static function instance($name = null)
	{
		if ($name !== null)
		{
			if ( ! array_key_exists($name, static::$_instances))
			{
				return false;
			}

			return static::$_instances[$name];
		}

		if (static::$_instance === null)
		{
			static::$_instance = static::forge();
		}

		return static::$_instance;
	}

	// --------------------------------------------------------------------

	/**
	 * instance configuration values
	 */
	protected $config = array(
		'current_page'            => null,
		'offset'                  => 0,
		'per_page'                => 10,
		'total_pages'             => 0,
		'total_items'             => 0,
		'num_links'               => 5,
		'uri_segment'             => 3,
		'show_first'              => false,
		'show_last'               => false,
		'pagination_url'          => null,
		'link_offset'             => 0.5,
	);

	/**
	 * instance template values
	 */
	protected $template = array(
		'wrapper'                 => "<div class=\"pagination\">\n\t{pagination}\n</div>\n",
		'first'                   => "<span class=\"first\">\n\t{link}\n</span>\n",
		'first-marker'            => "&laquo;&laquo;",
		'first-link'              => "\t\t<a href=\"{uri}\">{page}</a>\n",
		'first-inactive'          => "",
		'first-inactive-link'     => "",
		'previous'                => "<span class=\"previous\">\n\t{link}\n</span>\n",
		'previous-marker'         => "&laquo;",
		'previous-link'           => "\t\t<a href=\"{uri}\">{page}</a>\n",
		'previous-inactive'       => "<span class=\"previous-inactive\">\n\t{link}\n</span>\n",
		'previous-inactive-link'  => "\t\t<a href=\"#\">{page}</a>\n",
		'regular'                 => "<span>\n\t{link}\n</span>\n",
		'regular-link'            => "\t\t<a href=\"{uri}\">{page}</a>\n",
		'active'                  => "<span class=\"active\">\n\t{link}\n</span>\n",
		'active-link'             => "\t\t<a href=\"#\">{page}</a>\n",
		'next'                    => "<span class=\"next\">\n\t{link}\n</span>\n",
		'next-marker'             => "&raquo;",
		'next-link'               => "\t\t<a href=\"{uri}\">{page}</a>\n",
		'next-inactive'           => "<span class=\"next-inactive\">\n\t{link}\n</span>\n",
		'next-inactive-link'      => "\t\t<a href=\"#\">{page}</a>\n",
		'last'                    => "<span class=\"next\">\n\t{link}\n</span>\n",
		'last-marker'             => "&raquo;&raquo;",
		'last-link'               => "\t\t<a href=\"{uri}\">{page}</a>\n",
		'last-inactive'           => "",
		'last-inactive-link'      => "",
	);

	/**
	 * raw pagination results
	 */
	protected $raw_results = array();

	/**
	 * @param	array $config
	 */
	public function __construct($config = array())
	{
		// make sure config is an array
		is_array($config) or $config = array('name' => $config);

		// and we have a template name
		array_key_exists('name', $config) or $config['name'] = \Config::get('pagination.active', 'default');

		// merge the config passed with the defined configuration
		$config = array_merge(\Config::get('pagination.'.$config['name'], array()), $config);

		// don't need the template name anymore
		unset($config['name']);

		// update the instance default config with the data passed
		foreach ($config as $key => $value)
		{
			$this->__set($key, $value);
		}
	}

	/**
	 * configuration value getter
	 * @param	$name
	 * @return	mixed
	 */
	public function __get($name)
	{
		// use the calculated page if no current_page is passed
		if ($name === 'current_page' and $this->config[$name] === null)
		{
			$name = 'calculated_page';
		}

		if (array_key_exists($name, $this->config))
		{
			return $this->config[$name];
		}
		elseif (array_key_exists($name, $this->template))
		{
			return $this->template[$name];
		}
		else
		{
			return null;
		}
	}

	/**
	 * configuration value setter
	 *
	 * @param	$name
	 * @param	mixed $value
	 */
	public function __set($name, $value = null)
	{
		if (is_array($name))
		{
			foreach($name as $key => $value)
			{
				$this->__set($key, $value);
			}
		}
		else
		{
			$value = $this->_validate($name, $value);

			if (array_key_exists($name, $this->config))
			{
				$this->config[$name] = $value;
			}
			elseif (array_key_exists($name, $this->template))
			{
				$this->template[$name] = $value;
			}
		}

		// update the page counters
		$this->_recalculate();
	}

	/**
	 * Render the pagination when the object is cast to string
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Creates the pagination markup
	 *
	 * @param	mixed $raw
	 * @return	mixed	HTML Markup for page number links, or an array of raw pagination data
	 */
	public function render($raw = false)
	{
		// no links if we only have one page
		if ($this->config['total_pages'] == 1)
		{
			return $raw ? array() : '';
		}

		$this->raw_results = array();

		$html = str_replace(
			'{pagination}',
			$this->first().$this->previous().$this->pages_render().$this->next().$this->last(),
			$this->template['wrapper']
		);

		return $raw ? $this->raw_results : $html;
	}

	/**
	 * generate the HTML for the page links only
	 *
	 * @return	string	Markup for the pagination block
	 */
	public function pages_render()
	{
		// no links if we only have one page
		if ($this->config['total_pages'] == 1)
		{
			return '';
		}

		$html = '';

		// calculate start- and end page numbers
		$start = $this->config['calculated_page'] - floor($this->config['num_links'] * $this->config['link_offset']);
		$end = $this->config['calculated_page'] + floor($this->config['num_links'] * ( 1 - $this->config['link_offset']));

		// adjust for the first few pages
		if ($start < 1)
		{
			$end -= $start - 1;
			$start = 1;
		}

		// make sure we don't overshoot the current page due to rounding issues
		if ($end < $this->config['calculated_page'])
		{
			$start++;
			$end++;
		}

		// make sure we don't overshoot the total
		if ($end > $this->config['total_pages'])
		{
			$start = max(1, $start - $end + $this->config['total_pages']);
			$end = $this->config['total_pages'];
		}

		for($i = intval($start); $i <= intval($end); $i++)
		{
			if ($this->config['calculated_page'] == $i)
			{
				$html .= str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array('#', $i), $this->template['active-link']),
				    $this->template['active']
				);
				$this->raw_results[] = array('uri' => '#', 'title' => $i, 'type' => 'active');
			}
			else
			{
				$html .= str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array($this->_make_link($i), $i), $this->template['regular-link']),
				    $this->template['regular']
				);
				$this->raw_results[] = array('uri' => $this->_make_link($i), 'title' => $i, 'type' => 'regular');
			}
		}

		return $html;
	}

	/**
	 * Pagination "First" link
	 *
	 * @param	string	$marker optional text to display in the link
	 * @return	string	Markup for the 'first' page number link
	 */
	public function first($marker = null)
	{
		$html = '';

		$marker === null and $marker = $this->template['first-marker'];

		if ($this->config['show_first'])
		{
			if ($this->config['total_pages'] > 1 and $this->config['calculated_page'] > 1)
			{
				$html = str_replace(
					'{link}',
					str_replace(array('{uri}', '{page}'), array($this->_make_link(1), $marker), $this->template['first-link']),
					$this->template['first']
				);
				$this->raw_results['first'] = array('uri' => $this->_make_link(1), 'title' => $marker, 'type' => 'first');
			}
			else
			{
				$html = str_replace(
					'{link}',
					str_replace(array('{uri}', '{page}'), array('#', $marker), $this->template['first-inactive-link']),
					$this->template['first-inactive']
				);
				$this->raw_results['first'] = array('uri' => '#', 'title' => $marker, 'type' => 'first-inactive');
			}
		}

		return $html;
	}

	/**
	 * Pagination "Previous" link
	 *
	 * @param	string $marker	optional text to display in the link
	 * @return	string	Markup for the 'previous' page number link
	 */
	public function previous($marker = null)
	{
		$html = '';

		$marker === null and $marker = $this->template['previous-marker'];

		if ($this->config['total_pages'] > 1)
		{
			if ($this->config['calculated_page'] == 1)
			{
				$html = str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array('#', $marker), $this->template['previous-inactive-link']),
				    $this->template['previous-inactive']
				);
				$this->raw_results['previous'] = array('uri' => '#', 'title' => $marker, 'type' => 'previous-inactive');
			}
			else
			{
				$previous_page = $this->config['calculated_page'] - 1;
				$previous_page = ($previous_page == 1) ? '' : $previous_page;

				$html = str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array($this->_make_link($previous_page), $marker), $this->template['previous-link']),
				    $this->template['previous']
				);
				$this->raw_results['previous'] = array('uri' => $this->_make_link($previous_page), 'title' => $marker, 'type' => 'previous');
			}
		}

		return $html;
	}

	/**
	 * Pagination "Next" link
	 *
	 * @param	string	$marker optional text to display in the link
	 * @return	string	Markup for the 'next' page number link
	 */
	public function next($marker = null)
	{
		$html = '';

		$marker === null and $marker = $this->template['next-marker'];

		if ($this->config['total_pages'] > 1)
		{
			if ($this->config['calculated_page'] == $this->config['total_pages'])
			{
				$html = str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array('#', $marker), $this->template['next-inactive-link']),
				    $this->template['next-inactive']
				);
				$this->raw_results['next'] = array('uri' => '#', 'title' => $marker, 'type' => 'next-inactive');
			}
			else
			{
				$next_page = $this->config['calculated_page'] + 1;

				$html = str_replace(
				    '{link}',
				    str_replace(array('{uri}', '{page}'), array($this->_make_link($next_page), $marker), $this->template['next-link']),
				    $this->template['next']
				);
				$this->raw_results['next'] = array('uri' => $this->_make_link($next_page), 'title' => $marker, 'type' => 'next');
			}
		}

		return $html;
	}

	/**
	 * Pagination "Last" link
	 *
	 * @param	string $marker optional text to display in the link
	 * @return	string	Markup for the 'last' page number link
	 */
	public function last($marker = null)
	{
		$html = '';

		$marker === null and $marker = $this->template['last-marker'];

		if ($this->config['show_last'])
		{
			if ($this->config['total_pages'] > 1 and $this->config['calculated_page'] != $this->config['total_pages'])
			{
				$html = str_replace(
					'{link}',
					str_replace(array('{uri}', '{page}'), array($this->_make_link($this->config['total_pages']), $marker), $this->template['last-link']),
					$this->template['last']
				);
				$this->raw_results['last'] = array('uri' => $this->_make_link($this->config['total_pages']), 'title' => $marker, 'type' => 'last');
			}
			else
			{
				$html = str_replace(
					'{link}',
					str_replace(array('{uri}', '{page}'), array('#', $marker), $this->template['last-inactive-link']),
					$this->template['last-inactive']
				);
				$this->raw_results['last'] = array('uri' => '#', 'title' => $marker, 'type' => 'last-inactive');
			}
		}

		return $html;
	}

	/**
	 * Prepares vars for creating links
	 */
	protected function _recalculate()
	{
		// get the current page number, either from the one set, or from the URI or the query string
		if ($this->config['current_page'])
		{
				$this->config['calculated_page'] = $this->config['current_page'];
		}
		else
		{
			if (is_string($this->config['uri_segment']))
			{
				$this->config['calculated_page'] = \Input::get($this->config['uri_segment'], 1);
			}
			else
			{
				$this->config['calculated_page'] = (int) \Request::main()->uri->get_segment($this->config['uri_segment'], 1);
			}
		}

		// do we have the total number of items?
		if ($this->config['total_items'] > 0)
		{
			// calculate the number of pages
			$this->config['total_pages'] = (int) ceil($this->config['total_items'] / $this->config['per_page']) ?: 1;

			// make sure the current page is within bounds
			if ($this->config['calculated_page'] > $this->config['total_pages'])
			{
				$this->config['calculated_page'] = $this->config['total_pages'];
			}
			elseif ($this->config['calculated_page'] < 1)
			{
				$this->config['calculated_page'] = 1;
			}
		}

		// the current page must be zero based so that the offset for page 1 is 0.
		$this->config['offset'] = ($this->config['calculated_page'] - 1) * $this->config['per_page'];
	}

	/**
	 * Generate a pagination link
	 */
	protected function _make_link($page)
	{
		// make sure we have a valid page number
		empty($page) and $page = 1;

		// construct a pagination url if we don't have one
		if (is_null($this->config['pagination_url']))
		{
			// start with the main uri
			$this->config['pagination_url'] = \Uri::main();
			\Input::get() and $this->config['pagination_url'] .= '?'.http_build_query(\Input::get());
		}

		// was a placeholder defined in the url?
		if (strpos($this->config['pagination_url'], '{page}') === false)
		{
			// break the url in bits so we can insert it
			$url = parse_url($this->config['pagination_url']);

			// parse the query string
			if (isset($url['query']))
			{
				parse_str($url['query'], $url['query']);
			}
			else
			{
				$url['query'] = array();
			}

			// make sure we don't destroy any fragments
			if (isset($url['fragment']))
			{
				$url['fragment'] = '#'.$url['fragment'];
			}

			// do we have a segment offset due to the base_url containing segments?
			$seg_offset = parse_url(rtrim(\Uri::base(), '/'));
			$seg_offset = empty($seg_offset['path']) ? 0 : count(explode('/', trim($seg_offset['path'], '/')));

			// is the page number a URI segment?
			if (is_numeric($this->config['uri_segment']))
			{
				// get the URL segments
				$segs = isset($url['path']) ? explode('/', trim($url['path'], '/')) : array();

				// do we have enough segments to insert? we can't fill in any blanks...
				if (count($segs) < $this->config['uri_segment'] - 1)
				{
					throw new \RuntimeException("Not enough segments in the URI, impossible to insert the page number");
				}

				// replace the selected segment with the page placeholder
				$segs[$this->config['uri_segment'] - 1 + $seg_offset] = '{page}';
				$url['path'] = '/'.implode('/', $segs);
			}
			else
			{
				// add our placeholder
				$url['query'][$this->config['uri_segment']] = '{page}';
			}

			// re-assemble the url
			$query = empty($url['query']) ? '' : '?'.preg_replace('/%7Bpage%7D/', '{page}', http_build_query($url['query'], '', '&amp;'));
			unset($url['query']);
			empty($url['scheme']) or $url['scheme'] .= '://';
			empty($url['port']) or $url['host'] .= ':';
			$this->config['pagination_url'] = implode($url).$query;
		}

		// return the page link
		return str_replace('{page}', $page, $this->config['pagination_url']);
	}

	/**
	 * Validate the input configuration
	 *
	 * @param	$name
	 * @param	$value
	 * @return	int|mixed
	 */
	protected function _validate($name, $value)
	{
 		switch ($name)
		{
			case 'offset':
			case 'total_items':
				// make sure it's an integer
				if ($value != intval($value))
				{
					$value = 0;
				}
				// and that it's within bounds
				$value = max(0, $value);
			break;

			// integer or string
			case 'uri_segment':
				if (is_numeric($value))
				{
					// make sure it's an integer
					if ($value != intval($value))
					{
						$value = 1;
					}
					// and that it's within bounds
					$value = max(1, $value);
				}
			break;

			// validate integer values
			case 'current_page':
			case 'per_page':
			case 'limit':
			case 'total_pages':
			case 'num_links':
				// make sure it's an integer
				if ($value != intval($value))
				{
					$value = 1;
				}
				// and that it's within bounds
				$value = max(1, $value);
			break;

			// validate booleans
			case 'show_first':
			case 'show_last':
				if ( ! is_bool($value))
				{
					$value = (bool) $value;
				}
			break;

			// validate the link offset, and adjust if needed
			case 'link_offset':
				// make sure we have a fraction between 0 and 1
				if ($value > 1)
				{
					$value = $value / 100;
				}

				// and that it's within bounds
				$value = max(0.01, min($value, 0.99));
			break;
		}

		return $value;
	}
}
