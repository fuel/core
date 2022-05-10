<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Arraylike implements \ArrayAccess, \IteratorAggregate
{
	private $items;

	public function __construct($items)
	{
		$this->items = $items;
	}

	#[\ReturnTypeWillChange]
	public function offsetExists(/*mixed */$offset)/*: bool*/
	{
		return isset($this->items[$offset]);
	}

	#[\ReturnTypeWillChange]
	public function offsetGet(/*mixed */$offset)/*: mixed*/
	{
		return $this->items[$offset];
	}

	#[\ReturnTypeWillChange]
	public function offsetSet(/*mixed */$offset, /*mixed */$value)/*: void*/
	{
		$this->items[$offset] = $value;
	}

	#[\ReturnTypeWillChange]
	final public function offsetUnset(/*mixed */$offset)/*: void*/
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
