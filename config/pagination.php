<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2018 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  [!] NOTICE
 * -----------------------------------------------------------------------------
 *
 *  If you need to make modifications to the default configuration, copy
 *  this file to your app/config folder, and make them in there.
 *
 *  This will allow you to upgrade FuelPHP without losing your custom config.
 *
 */

return array(
	/**
	 * -------------------------------------------------------------------------
	 *  Active pagination template
	 * -------------------------------------------------------------------------
	 *
	 *  The template to be used on pagination.
	 *
	 *  You can use other template provided or create your own template. If you
	 *  want to create your own template, you MUST follow template settings
	 *  as described in:
	 *
	 *  https://fuelphp.com/docs/classes/pagination.html#/templating
	 *
	 */

	'active' => 'default',

	/**
	 * -------------------------------------------------------------------------
	 *  Default pagination template
	 * -------------------------------------------------------------------------
	 *
	 *  This template is compatible with FuelPHP version 1.4 or lower.
	 *
	 *  This template provide basic HTML layout. You may need to add styling on
	 *  your CSS.
	 *
	 */

	'default' => array(
		'wrapper'                => "<div class=\"pagination\">\n\t{pagination}\n</div>\n",

		'first'                  => "<span class=\"first\">\n\t{link}\n</span>\n",
		'first-marker'           => "&laquo;&laquo;",
		'first-link'             => "\t\t<a href=\"{uri}\">{page}</a>\n",

		'first-inactive'         => "",
		'first-inactive-link'    => "",

		'previous'               => "<span class=\"previous\">\n\t{link}\n</span>\n",
		'previous-marker'        => "&laquo;",
		'previous-link'          => "\t\t<a href=\"{uri}\" rel=\"prev\">{page}</a>\n",

		'previous-inactive'      => "<span class=\"previous-inactive\">\n\t{link}\n</span>\n",
		'previous-inactive-link' => "\t\t<a href=\"#\" rel=\"prev\">{page}</a>\n",

		'regular'                => "<span>\n\t{link}\n</span>\n",
		'regular-link'           => "\t\t<a href=\"{uri}\">{page}</a>\n",

		'active'                 => "<span class=\"active\">\n\t{link}\n</span>\n",
		'active-link'            => "\t\t<a href=\"#\">{page}</a>\n",

		'next'                   => "<span class=\"next\">\n\t{link}\n</span>\n",
		'next-marker'            => "&raquo;",
		'next-link'              => "\t\t<a href=\"{uri}\" rel=\"next\">{page}</a>\n",

		'next-inactive'          => "<span class=\"next-inactive\">\n\t{link}\n</span>\n",
		'next-inactive-link'     => "\t\t<a href=\"#\" rel=\"next\">{page}</a>\n",

		'last'                   => "<span class=\"last\">\n\t{link}\n</span>\n",
		'last-marker'            => "&raquo;&raquo;",
		'last-link'              => "\t\t<a href=\"{uri}\">{page}</a>\n",

		'last-inactive'          => "",
		'last-inactive-link'     => "",
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Twitter Bootstrap 3.3.7 pagination template
	 * -------------------------------------------------------------------------
	 *
	 *  This template use Pagination layout from Bootstrap 3.3.7
	 *
	 *  Visit https://getbootstrap.com/docs/3.3/components/#pagination
	 *  for more information
	 *
	 */

	'bootstrap3' => array(
		'wrapper'                => "<nav aria-label=\"Page navigation\">\n\t<ul class=\"pagination\">\n\t{pagination}\n\t</ul>\n\t</nav>\n",

		'first'                  => "\n\t\t<li>{link}</li>",
		'first-marker'           => "<span aria-hidden=\"true\">&laquo;&laquo;</span>",
		'first-link'             => "<a href=\"{uri}\">{page}</a>",

		'first-inactive'         => "",
		'first-inactive-link'    => "",

		'previous'               => "\n\t\t<li>{link}</li>",
		'previous-marker'        => "<span aria-hidden=\"true\">&laquo;</span>",
		'previous-link'          => "<a href=\"{uri}\" aria-label=\"Previous\">{page}</a>",

		'previous-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'previous-inactive-link' => "<a href=\"#\" aria-label=\"Previous\">{page}</a>",

		'regular'                => "\n\t\t<li>{link}</li>",
		'regular-link'           => "<a href=\"{uri}\">{page}</a>",

		'active'                 => "\n\t\t<li class=\"active\">{link}</li>",
		'active-link'            => "<a href=\"#\">{page} <span class=\"sr-only\">(current)</span></a>",

		'next'                   => "\n\t\t<li>{link}</li>",
		'next-marker'            => "<span aria-hidden=\"true\">&raquo;</span>",
		'next-link'              => "<a href=\"{uri}\" aria-label=\"Next\">{page}</a>",

		'next-inactive'          => "\n\t\t<li class=\"disabled\">{link}</li>",
		'next-inactive-link'     => "<a href=\"#\" aria-label=\"Next\">{page}</a>",

		'last'                   => "\n\t\t<li>{link}</li>",
		'last-marker'            => "<span aria-hidden=\"true\">&raquo;&raquo;</span>",
		'last-link'              => "<a href=\"{uri}\">{page}</a>",

		'last-inactive'          => "",
		'last-inactive-link'     => "",
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Twitter Bootstrap 2.3.2 pagination template
	 * -------------------------------------------------------------------------
	 *
	 *  This template use Pagination layout from Bootstrap 2.3.2
	 *
	 *  Visit https://getbootstrap.com/2.3.2/components.html#pagination
	 *  for more information
	 *
	 */

	'bootstrap2' => array(
		'wrapper'                => "<div class=\"pagination\">\n\t<ul>{pagination}\n\t</ul>\n</div>\n",

		'first'                  => "\n\t\t<li>{link}</li>",
		'first-marker'           => "&laquo;&laquo;",
		'first-link'             => "<a href=\"{uri}\">{page}</a>",

		'first-inactive'         => "",
		'first-inactive-link'    => "",

		'previous'               => "\n\t\t<li>{link}</li>",
		'previous-marker'        => "&laquo;",
		'previous-link'          => "<a href=\"{uri}\" rel=\"prev\">{page}</a>",

		'previous-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'previous-inactive-link' => "<a href=\"#\" rel=\"prev\">{page}</a>",

		'regular'                => "\n\t\t<li>{link}</li>",
		'regular-link'           => "<a href=\"{uri}\">{page}</a>",

		'active'                 => "\n\t\t<li class=\"active\">{link}</li>",
		'active-link'            => "<a href=\"#\">{page}</a>",

		'next'                   => "\n\t\t<li>{link}</li>",
		'next-marker'            => "&raquo;",
		'next-link'              => "<a href=\"{uri}\" rel=\"next\">{page}</a>",

		'next-inactive'          => "\n\t\t<li class=\"disabled\">{link}</li>",
		'next-inactive-link'     => "<a href=\"#\" rel=\"next\">{page}</a>",

		'last'                   => "\n\t\t<li>{link}</li>",
		'last-marker'            => "&raquo;&raquo;",
		'last-link'              => "<a href=\"{uri}\">{page}</a>",

		'last-inactive'          => "",
		'last-inactive-link'     => "",
	),
);
