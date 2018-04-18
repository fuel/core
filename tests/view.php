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

class Arraylike implements \ArrayAccess, \IteratorAggregate
{
	private $items;

	public function __construct($items)
	{
		$this->items = $items;
	}

	public function offsetExists($offset)
	{
		return isset($this->items[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->items[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->items[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}

	public function getIterator()
	{
		return new \ArrayIterator($this->items);
	}
}

/**
 * View class tests
 *
 * @group Core
 * @group View
 */
class Test_View extends TestCase
{
	public function test_unsanitize()
	{
		$ds = DIRECTORY_SEPARATOR;
		$child = new View(implode($ds, [__DIR__, 'view', 'child.txt']), [
			'items' => new Arraylike(['name' => 'test']),
		]);
		$parent = new \View(implode($ds, [__DIR__, 'view', 'parent.txt']), [
			'child1' => $child,
			'child2' => $child,
		]);

		$result = $parent->render();
		$views = explode("\n", $result);
		$this->assertSame('<p>test</p>', trim($views[0]));
		$this->assertSame('<p>test</p>', trim($views[1]));
	}
}
