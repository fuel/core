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

// ------------------------------------------------------------------------

/**
* Html Class
 *
 * @package		Fuel
 * @subpackage	Core
 * @category	Core
 * @author		Alfredo Rivera
 * @link		http://docs.fuelphp.com/classes/html.html
 */
class Html
{
	public static $doctypes = null;
	public static $html5 = true;

	/**
	 * Creates an html link
	 *
	 * @param	string	$href	the url
	 * @param	string	$text	the text value
	 * @param	array	$attr	the attributes array
	 * @param	bool	$secure	true to force https, false to force http
	 * @return	string	the html link
	 */
	public static function anchor($href, $text = null, $attr = array(), $secure = null)
	{
		if ( ! preg_match('#^(\w+://|javascript:|\#)# i', $href))
		{
			$urlparts = explode('?', $href, 2);
			$href = \Uri::create($urlparts[0], array(), isset($urlparts[1]) ? $urlparts[1] : array(), $secure);
		}
		elseif ( ! preg_match('#^(javascript:|\#)# i', $href) and is_bool($secure))
		{
			$href = http_build_url($href, array('scheme' => $secure ? 'https' : 'http'));

			// Trim the trailing slash
			$href = rtrim($href, '/');
		}

		// Create and display a URL hyperlink
		is_null($text) and $text = $href;

		$attr['href'] = $href;

		return html_tag('a', $attr, $text);
	}

	/**
	 * Creates an html image tag
	 *
	 * Sets the alt attribute to filename of it is not supplied.
	 *
	 * @param	string	$src	the source
	 * @param	array	$attr	the attributes array
	 * @return	string	the image tag
	 */
	public static function img($src, $attr = array())
	{
		if ( ! preg_match('#^(\w+://)# i', $src))
		{
			$src = \Uri::base(false).$src;
		}
		$attr['src'] = $src;
		$attr['alt'] = (isset($attr['alt'])) ? $attr['alt'] : pathinfo($src, PATHINFO_FILENAME);
		return html_tag('img', $attr);
	}

	/**
	 * Adds the given schema to the given URL if it is not already there.
	 *
	 * @param	string	$url	the url
	 * @param	string	$schema	the schema
	 * @return	string	url with schema
	 */
	public static function prep_url($url, $schema = 'http')
	{
		if ( ! preg_match('#^(\w+://|javascript:)# i', $url))
		{
			$url = $schema.'://'.$url;
		}

		return $url;
	}

	/**
	 * Creates a mailto link.
	 *
	 * @param	string	$email		The email address
	 * @param	string	$text		The text value
	 * @param	string	$subject	The subject
	 * @param	array	$attr		attributes for the tag
	 * @return	string	The mailto link
	 */
	public static function mail_to($email, $text = null, $subject = null, $attr = array())
	{
		$text or $text = $email;

		$subject and $subject = '?subject='.$subject;

		return html_tag('a', array(
			'href' => 'mailto:'.$email.$subject,
		) + $attr, $text);
	}

	/**
	 * Creates a mailto link with Javascript to prevent bots from picking up the
	 * email address.
	 *
	 * @param	string	$email		the email address
	 * @param	string	$text		the text value
	 * @param	string	$subject	the subject
	 * @param	array	$attr		attributes for the tag
	 * @return	string	the javascript code containing email
	 */
	public static function mail_to_safe($email, $text = null, $subject = null, $attr = array())
	{
		$text or $text = str_replace('@', '[at]', $email);

		$email = explode("@", $email);

		$subject and $subject = '?subject='.$subject;

		$attr = array_to_attr($attr);
		$attr = ($attr == '' ? '' : ' ').$attr;

		$output = '<script type="text/javascript">';
		$output .= '(function() {';
		$output .= 'var user = "'.$email[0].'";';
		$output .= 'var at = "@";';
		$output .= 'var server = "'.$email[1].'";';
		$output .= "document.write('<a href=\"' + 'mail' + 'to:' + user + at + server + '$subject\"$attr>$text</a>');";
		$output .= '})();';
		$output .= '</script>';
		return $output;
	}

	/**
	 * Generates a html meta tag
	 *
	 * @param	string|array	$name		multiple inputs or name/http-equiv value
	 * @param	string			$content	content value
	 * @param	string			$type		name or http-equiv
	 * @return	string
	 */
	public static function meta($name = '', $content = '', $type = 'name')
	{
		if( ! is_array($name))
		{
			$result = html_tag('meta', array($type => $name, 'content' => $content));
		}
		elseif(is_array($name))
		{
			$result = "";
			foreach($name as $array)
			{
				$meta = $array;
				$result .= "\n" . html_tag('meta', $meta);
			}
		}
		return $result;
	}

	/**
	 * Generates a html doctype tag
	 *
	 * @param	string	$type	doctype declaration key from doctypes config
	 * @return	string
	 */
	public static function doctype($type = 'xhtml1-trans')
	{
		if(static::$doctypes === null)
		{
			\Config::load('doctypes', true);
			static::$doctypes = \Config::get('doctypes', array());
		}

		if(is_array(static::$doctypes) and isset(static::$doctypes[$type]))
		{
			if($type == "html5")
			{
				static::$html5 = true;
			}
			return static::$doctypes[$type];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Generates a html5 audio tag
	 * It is required that you set html5 as the doctype to use this method
	 *
	 * @param	string|array	$src	one or multiple audio sources
	 * @param	array			$attr	tag attributes
	 * @return	string
	 */
	public static function audio($src = '', $attr = false)
	{
		if(static::$html5)
		{
			if(is_array($src))
			{
				$source = '';
				foreach($src as $item)
				{
					$source .= html_tag('source', array('src' => $item));
				}
			}
			else
			{
				$source = html_tag('source', array('src' => $src));
			}
			return html_tag('audio', $attr, $source);
		}
	}

	/**
	 * Generates a html un-ordered list tag
	 *
	 * @param	array			$list	list items, may be nested
	 * @param	array|string	$attr	outer list attributes
	 * @return	string
	 */
	public static function ul(array $list = array(), $attr = false)
	{
		return static::build_list('ul', $list, $attr);
	}

	/**
	 * Generates a html ordered list tag
	 *
	 * @param	array			$list	list items, may be nested
	 * @param	array|string	$attr	outer list attributes
	 * @return	string
	 */
	public static function ol(array $list = array(), $attr = false)
	{
		return static::build_list('ol', $list, $attr);
	}

	/**
	 * Generates the html for the list methods
	 *
	 * @param	string	$type	list type (ol or ul)
	 * @param	array	$list	list items, may be nested
	 * @param	array	$attr	tag attributes
	 * @param	string	$indent	indentation
	 * @return	string
	 */
	protected static function build_list($type = 'ul', array $list = array(), $attr = false, $indent = '')
	{
		if ( ! is_array($list))
		{
			$result = false;
		}

		$out = '';
		foreach ($list as $key => $val)
		{
			if ( ! is_array($val))
			{
				$out .= $indent."\t".html_tag('li', array(), $val).PHP_EOL;
			}
			else
			{
				$out .= $indent."\t".html_tag('li', array(), $key.PHP_EOL.static::build_list($type, $val, '', $indent."\t\t").$indent."\t").PHP_EOL;
			}
		}
		$result = $indent.html_tag($type, $attr, PHP_EOL.$out.$indent).PHP_EOL;
		return $result;
	}
}
