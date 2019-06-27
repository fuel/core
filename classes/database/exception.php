<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.8.2
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @copyright  2008 - 2009 Kohana Team
 * @link       https://fuelphp.com
 */

namespace Fuel\Core;

class Database_Exception extends \FuelException
{
	/**
	 * @var mixed  The exception code
	 *
	 * Redefine the exception code property, as PDO uses alphanumeric codes
	 */
	protected $code;

	/**
	 * @var mixed  The original databaase error code
	 *
	 * We also store the original error code of the underlying driver
	 */
	protected $dbcode;

	/**
	 * Overload the constructor to allow an additional error code to be passed
	 */
	public function __construct ($message, $code = 0, $previous = null, $dbcode = 0)
	{
		// call the parent without a code, the interface is defined as numeric
		parent::__construct($message, 0, $previous);

		// so the codes need to be stored seperately
		$this->dbcode = $dbcode;
		$this->code = $code;
	}

	/**
	 * Return the original database error code if given
	 */
	final public function getDbCode()
	{
		return $this->dbcode;
	}
}
