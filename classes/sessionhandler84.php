<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010-2025 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * PHP 8.4+ compatible native session handler
 *
 * It is in a seperate file, included in session.php, because
 * older PHP versions with abort with a parse error on the
 * typing definitions required in PHP 8.4.
 */
$sessionHandler = new class() implements SessionHandlerInterface {
	public function open(string $path, string $name): bool
	{
		return true;
	}
	public function close(): bool
	{
		return true;
	}
	public function read(string $id): string|false
	{
		// copy all existing session vars into the PHP session store
		$_SESSION = \Session::get();
		$_SESSION['__org__'] = $_SESSION;
		return '';
	}
	public function write(string $id, string $data): bool
	{
		// get the original data
		$org = isset($_SESSION['__org__']) ? $_SESSION['__org__'] : array();
		unset($_SESSION['__org__']);

		// do we need to remove stuff?
		if ($remove = array_diff_key($org, $_SESSION))
		{
			\Session::delete(array_keys($remove));
		}

		// add or update the remainder
		empty($_SESSION) or \Session::set($_SESSION);
		return true;
	}
	public function destroy(string $id): bool
	{
		\Session::destroy();
		return true;
	}
	public function gc(int $max_lifetime): int|false
	{
		return true;
	}
};
session_set_save_handler($sessionHandler);
