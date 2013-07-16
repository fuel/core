<?php
/**
 * Cached database result.
 *
 * @package    Fuel/Database
 * @category   Query/Result
 * @author     Kohana Team
 * @copyright  (c) 2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

namespace Fuel\Core;

class Database_Result_Cached extends \Database_Result
{

	/**
	 * @param  array   $result
	 * @param  string  $sql
	 * @param  mixed   $as_object
	 */
	public function __construct(array $result, $sql, $as_object = null)
	{
		parent::__construct($result, $sql, $as_object);

		// Find the number of rows in the result
		$this->_total_rows = count($result);
	}

	public function __destruct()
	{
		// Cached results do not use resources
	}

	/**
	 * @return $this
	 */
	public function cached()
	{
		return $this;
	}

	/**
	 * @param integer $offset
	 *
	 * @return bool
	 */
	public function seek($offset)
	{
		if ( ! $this->offsetExists($offset))
		{
			return false;
		}

		$this->_current_row = $offset;
		return true;
	}

	/**
	 * @return mixed
	 */
	public function current()
	{
		return $this->valid() ? $this->_result[$this->_current_row] : null;
	}

}
