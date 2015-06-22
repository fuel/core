<?php
/**
 * SQL Server database result.
 *
 * @package    Fuel/Database
 * @category   Query/Result
 * @author     Takeshi Sakurai <sakurai@pnop.co.jp>
 * @copyright  (c) 2015 Takeshi Sakurai
 * @license    http://kohanaphp.com/license
 */

namespace Fuel\Core;

class Database_SQLSRV_Result extends \Database_Result
{
	protected $_internal_row = 0;

	public function __construct($result, $sql, $as_object)
	{
		parent::__construct($result, $sql, $as_object);

		// Find the number of rows in the result
		$this->_total_rows = sqlsrv_num_rows($result);
	}

	public function __destruct()
	{
		if (is_resource($this->_result))
		{
			sqlsrv_free_stmt($this->_result);
		}
	}

	public function seek($offset)
	{
		if ($this->offsetExists($offset) and sqlsrv_fetch($this->_result, SQLSRV_SCROLL_ABSOLUTE, $offset))
		{
			// Set the current row to the offset
			$this->_current_row = $this->_internal_row = $offset;

			return true;
		}
		else
		{
			return false;
		}
	}

	public function current()
	{
		if ($this->_current_row !== $this->_internal_row and ! $this->seek($this->_current_row))
		{
			return false;
		}

		// Increment internal row for optimization assuming rows are fetched in order
		$this->_internal_row++;

		if ($this->_as_object === true)
		{
			// Return an stdClass
			return sqlsrv_fetch_object($this->_result);
		}
		elseif (is_string($this->_as_object))
		{
			// Return an object of given class name
			return sqlsrv_fetch_object($this->_result, $this->_as_object);
		}
		else
		{
			// Return an array of the row
			return sqlsrv_fetch_array($this->_result);
		}
	}
}
