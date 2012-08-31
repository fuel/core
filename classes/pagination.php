<?php
/**
 * Part of the Fuel framework.
 *
 * @package		Fuel
 * @version		1.0
 * @author		Dan Horrigan <http://dhorrigan.com>
 * @license		MIT License
 * @copyright	2010 - 2011 Fuel Development Team
 */

namespace Fuel\Core;



class Pagination
{

	/**
	 * @var	integer	The current page
	 */
	public static $current_page = null;

	/**
	 * @var	integer	The offset that the current page starts at
	 */
	public static $offset = 0;

	/**
	 * @var	integer	The number of items per page
	 */
	public static $per_page = 10;

	/**
	 * @var	integer	The number of total pages
	 */
	public static $total_pages = 0;

	/**
	 * @var array The HTML for the display
	 */
	public static $template = array(
		'wrapper_start'           => '<div class="pagination"> ',
		'wrapper_end'             => ' </div>',
		'page_start'              => '<span class="page-links"> ',
		'page_end'                => ' </span>',
		'previous_start'          => '<span class="previous"> ',
		'previous_end'            => ' </span>',
		'previous_inactive_start' => ' <span class="previous-inactive">',
		'previous_inactive_end'   => ' </span>',
		'previous_inactive_attrs' => array(),
		'previous_mark'           => '&laquo; ',
		'previous_attrs'          => array(),
		'next_start'              => '<span class="next"> ',
		'next_end'                => ' </span>',
		'next_inactive_start'     => ' <span class="next-inactive">',
		'next_inactive_end'       => ' </span>',
		'next_inactive_attrs'     => array(),
		'next_mark'               => ' &raquo;',
		'next_attrs'              => array(),
		'active_start'            => '<span class="active"> ',
		'active_end'              => ' </span>',
		'active_attrs'            => array(),
		'regular_start'           => '',
		'regular_end'             => '',
		'regular_attrs'           => array(),
	);

	/**
	 * @var	integer	The total number of items
	 */
	protected static $total_items = 0;

	/**
	 * @var	integer	The total number of links to show
	 */
	protected static $num_links = 5;

	/**
	 * @var	integer	The URI segment containg page number
	 */
	protected static $uri_segment = 3;

	/**
	 * @var	mixed	The pagination URL
	 */
	protected static $pagination_url;

	/**
	 * Init
	 *
	 * Loads in the config and sets the variables
	 *
	 * @access	public
	 * @return	void
	 */
	public static function _init()
	{
		\Config::load('pagination', true);
		$config = \Config::get('pagination', array());

		static::set_config($config);
	}

	// --------------------------------------------------------------------

	/**
	 * Set Config
	 *
	 * Sets the configuration for pagination
	 *
	 * @access public
	 * @param array   $config The configuration array
	 * @return void
	 */
	public static function set_config(array $config)
	{

		foreach ($config as $key => $value)
		{
			if ($key == 'template')
			{
				static::$template = array_merge(static::$template, $config['template']);
				continue;
			}

			static::${$key} = $value;
		}

		static::initialize();
	}

	// --------------------------------------------------------------------

	/**
	 * Prepares vars for creating links
	 *
	 * @access public
	 * @return array    The pagination variables
	 */
	protected static function initialize()
	{
		static::$total_pages = ceil(static::$total_items / static::$per_page) ?: 1;

		static::$current_page = (static::$total_items > 0 && static::$current_page > 1) ? static::$current_page : (int) \URI::segment(static::$uri_segment);

		if (static::$current_page > static::$total_pages)
		{
			static::$current_page = static::$total_pages;
		}
		elseif (static::$current_page < 1)
		{
			static::$current_page = 1;
		}

		// The current page must be zero based so that the offset for page 1 is 0.
		static::$offset = (static::$current_page - 1) * static::$per_page;
	}

	// --------------------------------------------------------------------

	/**
	 * Creates the pagination links
	 *
	 * @access public
	 * @return mixed    The pagination links
	 */
	public static function create_links()
	{
		if (static::$total_pages == 1)
		{
			return '';
		}

		\Lang::load('pagination', true);

		$pagination  = static::$template['wrapper_start'];
		$pagination .= static::prev_link(\Lang::get('pagination.previous'));
		$pagination .= static::page_links();
		$pagination .= static::next_link(\Lang::get('pagination.next'));
		$pagination .= static::$template['wrapper_end'];

		return $pagination;
	}

	// --------------------------------------------------------------------

	/**
	 * Pagination Page Number links
	 *
	 * @access public
	 * @return mixed    Markup for page number links
	 */
	public static function page_links()
	{
		if (static::$total_pages == 1)
		{
			return '';
		}

		$pagination = '';

		// Let's get the starting page number, this is determined using num_links
		$start = ((static::$current_page - static::$num_links) > 0) ? static::$current_page - (static::$num_links - 1) : 1;

		// Let's get the ending page number
		$end   = ((static::$current_page + static::$num_links) < static::$total_pages) ? static::$current_page + static::$num_links : static::$total_pages;

		for($i = $start; $i <= $end; $i++)
		{
			if (static::$current_page == $i)
			{
				$pagination .= static::$template['active_start'].\Html::anchor('#', $i, static::$template['active_attrs']).static::$template['active_end'];
			}
			else
			{
				$url = ($i == 1) ? '' : '/'.$i;
				$pagination .= static::$template['regular_start'].\Html::anchor(rtrim(static::$pagination_url, '/').$url, $i, static::$template['regular_attrs']).static::$template['regular_end'];
			}
		}

		return static::$template['page_start'].$pagination.static::$template['page_end'];
	}

	// --------------------------------------------------------------------

	/**
	 * Pagination "Next" link
	 *
	 * @access public
	 * @param string $value The text displayed in link
	 * @return mixed    The next link
	 */
	public static function next_link($value)
	{
		if (static::$total_pages == 1)
		{
			return '';
		}

		if (static::$current_page == static::$total_pages)
		{
			return static::$template['next_inactive_start'].\Html::anchor('#', $value.static::$template['next_mark'], static::$template['next_inactive_attrs']).static::$template['next_inactive_end'];
		}
		else
		{
			$next_page = static::$current_page + 1;
			return static::$template['next_start'].\Html::anchor(rtrim(static::$pagination_url, '/').'/'.$next_page, $value.static::$template['next_mark'], static::$template['next_attrs']).static::$template['next_end'];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Pagination "Previous" link
	 *
	 * @access public
	 * @param string $value The text displayed in link
	 * @return mixed    The previous link
	 */
	public static function prev_link($value)
	{
		if (static::$total_pages == 1)
		{
			return '';
		}

		if (static::$current_page == 1)
		{
			return static::$template['previous_inactive_start'].\Html::anchor('#', static::$template['previous_mark'].$value, static::$template['previous_inactive_attrs']).static::$template['previous_inactive_end'];
		}
		else
		{
			$previous_page = static::$current_page - 1;
			$previous_page = ($previous_page == 1) ? '' : '/'.$previous_page;
			return static::$template['previous_start'].\Html::anchor(rtrim(static::$pagination_url, '/').$previous_page, static::$template['previous_mark'].$value, static::$template['previous_attrs']).static::$template['previous_end'];
		}
	}
}


