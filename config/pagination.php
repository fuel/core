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
	 *  Active Template
	 * -------------------------------------------------------------------------
	 *
	 *  The template to be used on pagination.
	 *
	 *  You can use other template provided or create your own template. If you
	 *  want to create your own template, you MUST follow template settings
	 *  as described in:
	 *
	 *      https://fuelphp.com/docs/classes/pagination.html#/templating
	 *
	 */

	'active' => 'default',

	/**
	 * -------------------------------------------------------------------------
	 *  Default Template
	 * -------------------------------------------------------------------------
	 *
	 *  This template is compatible with FuelPHP version 1.4 or lower.
	 *
	 *  This template provide basic HTML layout. You may need to add styling on
	 *  your CSS.
	 *
	 */

	'default' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Wrapper
		 * ---------------------------------------------------------------------
		 */

		'wrapper' => "<div class=\"pagination\">\n\t{pagination}\n</div>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page
		 * ---------------------------------------------------------------------
		 */

		'first'        => "<span class=\"first\">\n\t{link}\n</span>\n",
		'first-marker' => "&laquo;&laquo;",
		'first-link'   => "\t\t<a href=\"{uri}\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'first-inactive'      => "",
		'first-inactive-link' => "",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page
		 * ---------------------------------------------------------------------
		 */

		'previous'        => "<span class=\"previous\">\n\t{link}\n</span>\n",
		'previous-marker' => "&laquo;",
		'previous-link'   => "\t\t<a href=\"{uri}\" rel=\"prev\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'previous-inactive'      => "<span class=\"previous-inactive\">\n\t{link}\n</span>\n",
		'previous-inactive-link' => "\t\t<a href=\"#\" rel=\"prev\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Pages
		 * ---------------------------------------------------------------------
		 */

		'regular'      => "<span>\n\t{link}\n</span>\n",
		'regular-link' => "\t\t<a href=\"{uri}\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Current Page
		 * ---------------------------------------------------------------------
		 */

		'active'      => "<span class=\"active\">\n\t{link}\n</span>\n",
		'active-link' => "\t\t<a href=\"#\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page
		 * ---------------------------------------------------------------------
		 */

		'next'        => "<span class=\"next\">\n\t{link}\n</span>\n",
		'next-marker' => "&raquo;",
		'next-link'   => "\t\t<a href=\"{uri}\" rel=\"next\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'next-inactive'      => "<span class=\"next-inactive\">\n\t{link}\n</span>\n",
		'next-inactive-link' => "\t\t<a href=\"#\" rel=\"next\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page
		 * ---------------------------------------------------------------------
		 */

		'last'        => "<span class=\"last\">\n\t{link}\n</span>\n",
		'last-marker' => "&raquo;&raquo;",
		'last-link'   => "\t\t<a href=\"{uri}\">{page}</a>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'last-inactive'      => "",
		'last-inactive-link' => "",
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Twitter Bootstrap 3.3.7 Template
	 * -------------------------------------------------------------------------
	 *
	 *  This template uses Pagination layout from Bootstrap 3.3.7.
	 *
	 *  For more information, visit the official documentation:
	 *
	 *      https://getbootstrap.com/docs/3.3/components/#pagination
	 *
	 */

	'bootstrap3' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Wrapper
		 * ---------------------------------------------------------------------
		 */

		'wrapper' => "<nav aria-label=\"Page navigation\">\n\t<ul class=\"pagination\">\n\t{pagination}\n\t</ul>\n\t</nav>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page
		 * ---------------------------------------------------------------------
		 */

		'first'        => "\n\t\t<li>{link}</li>",
		'first-marker' => "<span aria-hidden=\"true\">&laquo;&laquo;</span>",
		'first-link'   => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'first-inactive'      => "",
		'first-inactive-link' => "",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page
		 * ---------------------------------------------------------------------
		 */

		'previous'        => "\n\t\t<li>{link}</li>",
		'previous-marker' => "<span aria-hidden=\"true\">&laquo;</span>",
		'previous-link'   => "<a href=\"{uri}\" aria-label=\"Previous\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'previous-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'previous-inactive-link' => "<a href=\"#\" aria-label=\"Previous\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Pages
		 * ---------------------------------------------------------------------
		 */

		'regular'      => "\n\t\t<li>{link}</li>",
		'regular-link' => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Current Page
		 * ---------------------------------------------------------------------
		 */

		'active'      => "\n\t\t<li class=\"active\">{link}</li>",
		'active-link' => "<a href=\"#\">{page} <span class=\"sr-only\">(current)</span></a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page
		 * ---------------------------------------------------------------------
		 */

		'next'        => "\n\t\t<li>{link}</li>",
		'next-marker' => "<span aria-hidden=\"true\">&raquo;</span>",
		'next-link'   => "<a href=\"{uri}\" aria-label=\"Next\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'next-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'next-inactive-link' => "<a href=\"#\" aria-label=\"Next\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page
		 * ---------------------------------------------------------------------
		 */

		'last'        => "\n\t\t<li>{link}</li>",
		'last-marker' => "<span aria-hidden=\"true\">&raquo;&raquo;</span>",
		'last-link'   => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'last-inactive'      => "",
		'last-inactive-link' => "",
	),

	/**
	 * -------------------------------------------------------------------------
	 *  Twitter Bootstrap 2.3.2 pagination template
	 * -------------------------------------------------------------------------
	 *
	 *  This template uses Pagination layout from Bootstrap 2.3.2
	 *
	 *  For more information, visit the official documentation:
	 *
	 *      https://getbootstrap.com/2.3.2/components.html#pagination
	 *
	 */

	'bootstrap2' => array(
		/**
		 * ---------------------------------------------------------------------
		 *  Wrapper
		 * ---------------------------------------------------------------------
		 */

		'wrapper' => "<div class=\"pagination\">\n\t<ul>{pagination}\n\t</ul>\n</div>\n",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page
		 * ---------------------------------------------------------------------
		 */

		'first'        => "\n\t\t<li>{link}</li>",
		'first-marker' => "&laquo;&laquo;",
		'first-link'   => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  First Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'first-inactive'      => "",
		'first-inactive-link' => "",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page
		 * ---------------------------------------------------------------------
		 */

		'previous'        => "\n\t\t<li>{link}</li>",
		'previous-marker' => "&laquo;",
		'previous-link'   => "<a href=\"{uri}\" rel=\"prev\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Previous Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'previous-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'previous-inactive-link' => "<a href=\"#\" rel=\"prev\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Pages
		 * ---------------------------------------------------------------------
		 */

		'regular'      => "\n\t\t<li>{link}</li>",
		'regular-link' => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Current Page
		 * ---------------------------------------------------------------------
		 */

		'active'      => "\n\t\t<li class=\"active\">{link}</li>",
		'active-link' => "<a href=\"#\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page
		 * ---------------------------------------------------------------------
		 */

		'next'        => "\n\t\t<li>{link}</li>",
		'next-marker' => "&raquo;",
		'next-link'   => "<a href=\"{uri}\" rel=\"next\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Next Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'next-inactive'      => "\n\t\t<li class=\"disabled\">{link}</li>",
		'next-inactive-link' => "<a href=\"#\" rel=\"next\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page
		 * ---------------------------------------------------------------------
		 */

		'last'        => "\n\t\t<li>{link}</li>",
		'last-marker' => "&raquo;&raquo;",
		'last-link'   => "<a href=\"{uri}\">{page}</a>",

		/**
		 * ---------------------------------------------------------------------
		 *  Last Page - Inactive/Disabled State
		 * ---------------------------------------------------------------------
		 */

		'last-inactive'      => "",
		'last-inactive-link' => "",
	),
);
