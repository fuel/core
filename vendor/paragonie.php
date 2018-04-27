<?php
/**
 *  These file contains a selection of classes from the Paragon IE packages
 *  HaLite and Constant-time, used in Fuel's Crypt soluion.
 *
 *  Copyright (c) 2016 - 2018 Paragon Initiative Enterprises.
 *  Copyright (c) 2014 Steve "Sc00bz" Thomas (steve at tobtu dot com)
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *  SOFTWARE.
 */

namespace ParagonIE\Fuel;

// check if we have sodium available
if ( ! is_callable('sodium_crypto_generichash') or ! is_callable('random_bytes'))
{
	throw new \FuelException('Fuel requires Sodium support in PHP. Either use PHP 7.2+, install the libsodium PECL extension, of the sodium-compat composer package!');
}


/**
 * Class Binary
 *
 * Binary string operators that don't choke on
 * mbstring.func_overload
 *
 * @package ParagonIE\ConstantTime
 */
abstract class Binary
{
	/**
	 * Safe string length
	 *
	 * @ref mbstring.func_overload
	 *
	 * @param string $str
	 * @return int
	 */
	public static function safeStrlen($str)
	{
		if (function_exists('mb_strlen'))
		{
			return (int) mb_strlen($str, '8bit');
		}
		else
		{
			return (int) strlen($str);
		}
	}

	/**
	 * Safe substring
	 *
	 * @ref mbstring.func_overload
	 *
	 * @staticvar boolean $exists
	 * @param string $str
	 * @param int $start
	 * @param int $length
	 *
	 * @return string
	 */
	public static function safeSubstr($str, $start = 0, $length = null)
	{
		if ($length === 0)
		{
			return '';
		}
		if (function_exists('mb_substr'))
		{
			return mb_substr($str, $start, $length, '8bit');
		}
		// Unlike mb_substr(), substr() doesn't accept NULL for length
		if ($length !== null)
		{
			return substr($str, $start, $length);
		}
		else
		{
			return substr($str, $start);
		}
	}

	/**
	 * Evaluate whether or not two strings are equal (in constant-time)
	 *
	 * @param string $left
	 * @param string $right
	 * @return bool
	 * @throws FuelException
	 */
	public static function hashEquals($left, $right)
	{
		if ( ! is_string($left))
		{
			throw new \FuelException('Argument 1 must be a string, ' . gettype($left) . ' given.');
		}
		if ( ! is_string($right))
		{
			throw new \FuelException('Argument 2 must be a string, ' . gettype($right) . ' given.');
		}

		if (is_callable('hash_equals'))
		{
			return hash_equals($left, $right);
		}
		$d = 0;

		$len = self::safeStrlen($left);
		if ($len !== self::safeStrlen($right))
		{
			return false;
		}
		for ($i = 0; $i < $len; ++$i)
		{
			$d |= self::chrToInt($left[$i]) ^ self::chrToInt($right[$i]);
		}

		if ($d !== 0)
		{
			return false;
		}

		return $left === $right;
	}

	/**
	 * Cache-timing-safe variant of ord()
	 *
	 * @internal You should not use this directly from another application
	 *
	 * @param string $chr
	 * @return int
	 * @throws FuelException
	 */
	public static function chrToInt($chr)
	{
		if ( ! is_string($chr))
		{
			throw new \FuelException('Argument 1 must be a string, ' . gettype($chr) . ' given.');
		}
		if (self::safeStrlen($chr) !== 1)
		{
			throw new \FuelException('chrToInt() expects a string that is exactly 1 character long');
		}

		$chunk = unpack('C', $chr);

		return (int) ($chunk[1]);
	}
}

/**
 * Class Base64
 * [A-Z][a-z][0-9]+/
 *
 * @package ParagonIE\ConstantTime
 */
abstract class Base64
{
	/**
	 * Encode into Base64
	 *
	 * Base64 character set "[A-Z][a-z][0-9]+/"
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	public static function encode($src)
	{
		return static::doEncode($src, true);
	}

	/**
	 * Encode into Base64, no = padding
	 *
	 * Base64 character set "[A-Z][a-z][0-9]+/"
	 *
	 * @param string $src
	 *
	 * @return string
	 */
	public static function encodeUnpadded($src)
	{
		return static::doEncode($src, false);
	}

	/**
	 * @param string $src
	 * @param bool $pad   Include = padding?
	 *
	 * @return string
	 */
	protected static function doEncode($src, $pad = true)
	{
		$dest = '';
		$srcLen = Binary::safeStrlen($src);
		// Main loop (no padding):
		for ($i = 0; $i + 3 <= $srcLen; $i += 3)
		{
			/** @var array<int, int> $chunk */
			$chunk = unpack('C*', Binary::safeSubstr($src, $i, 3));
			$b0 = $chunk[1];
			$b1 = $chunk[2];
			$b2 = $chunk[3];

			$dest .=
				static::encode6Bits(               $b0 >> 2       ) .
				static::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63) .
				static::encode6Bits((($b1 << 2) | ($b2 >> 6)) & 63) .
				static::encode6Bits(  $b2                     & 63);
		}
		// The last chunk, which may have padding:
		if ($i < $srcLen)
		{
			/** @var array<int, int> $chunk */
			$chunk = unpack('C*', Binary::safeSubstr($src, $i, $srcLen - $i));
			$b0 = $chunk[1];
			if ($i + 1 < $srcLen)
			{
				$b1 = $chunk[2];
				$dest .=
					static::encode6Bits($b0 >> 2) .
					static::encode6Bits((($b0 << 4) | ($b1 >> 4)) & 63) .
					static::encode6Bits(($b1 << 2) & 63);
				if ($pad)
				{
					$dest .= '=';
				}
			}
			else
			{
				$dest .=
					static::encode6Bits( $b0 >> 2) .
					static::encode6Bits(($b0 << 4) & 63);
				if ($pad)
				{
					$dest .= '==';
				}
			}
		}

		return $dest;
	}

	/**
	 * decode from base64 into binary
	 *
	 * Base64 character set "./[A-Z][a-z][0-9]"
	 *
	 * @param string $src
	 * @param bool $strictPadding
	 *
	 * @return string
	 * @throws \RangeException
	 */
	public static function decode($src, $strictPadding = false)
	{
		// Remove padding
		$srcLen = Binary::safeStrlen($src);
		if ($srcLen === 0)
		{
			return '';
		}

		if ($strictPadding)
		{
			if (($srcLen & 3) === 0)
			{
				if ($src[$srcLen - 1] === '=')
				{
					$srcLen--;
					if ($src[$srcLen - 1] === '=')
					{
						$srcLen--;
					}
				}
			}
			if (($srcLen & 3) === 1)
			{
				throw new \RangeException('Incorrect padding');
			}
			if ($src[$srcLen - 1] === '=')
			{
				throw new \RangeException('Incorrect padding');
			}
		}
		else
		{
			$src = rtrim($src, '=');
			$srcLen = Binary::safeStrlen($src);
		}

		$err = 0;
		$dest = '';
		// Main loop (no padding):
		for ($i = 0; $i + 4 <= $srcLen; $i += 4)
		{
			/** @var array<int, int> $chunk */
			$chunk = unpack('C*', Binary::safeSubstr($src, $i, 4));
			$c0 = static::decode6Bits($chunk[1]);
			$c1 = static::decode6Bits($chunk[2]);
			$c2 = static::decode6Bits($chunk[3]);
			$c3 = static::decode6Bits($chunk[4]);

			$dest .= pack(
				'CCC',
				((($c0 << 2) | ($c1 >> 4)) & 0xff),
				((($c1 << 4) | ($c2 >> 2)) & 0xff),
				((($c2 << 6) |  $c3      ) & 0xff)
			);
			$err |= ($c0 | $c1 | $c2 | $c3) >> 8;
		}

		// The last chunk, which may have padding:
		if ($i < $srcLen)
		{
			/** @var array<int, int> $chunk */
			$chunk = unpack('C*', Binary::safeSubstr($src, $i, $srcLen - $i));
			$c0 = static::decode6Bits($chunk[1]);

			if ($i + 2 < $srcLen)
			{
				$c1 = static::decode6Bits($chunk[2]);
				$c2 = static::decode6Bits($chunk[3]);
				$dest .= pack(
					'CC',
					((($c0 << 2) | ($c1 >> 4)) & 0xff),
					((($c1 << 4) | ($c2 >> 2)) & 0xff)
				);
				$err |= ($c0 | $c1 | $c2) >> 8;
			}
			elseif ($i + 1 < $srcLen)
			{
				$c1 = static::decode6Bits($chunk[2]);
				$dest .= pack(
					'C',
					((($c0 << 2) | ($c1 >> 4)) & 0xff)
				);
				$err |= ($c0 | $c1) >> 8;
			}
			elseif ($i < $srcLen && $strictPadding)
			{
				$err |= 1;
			}
		}
		if ($err !== 0)
		{
			throw new \RangeException('Base64::decode() only expects characters in the correct base64 alphabet');
		}

		return $dest;
	}

	/**
	 * Uses bitwise operators instead of table-lookups to turn 6-bit integers
	 * into 8-bit integers.
	 *
	 * Base64 character set:
	 * [A-Z]      [a-z]      [0-9]      +     /
	 * 0x41-0x5a, 0x61-0x7a, 0x30-0x39, 0x2b, 0x2f
	 *
	 * @param int $src
	 *
	 * @return int
	 */
	protected static function decode6Bits($src)
	{
		$ret = -1;

		// if ($src > 0x40 && $src < 0x5b) $ret += $src - 0x41 + 1; // -64
		$ret += (((0x40 - $src) & ($src - 0x5b)) >> 8) & ($src - 64);

		// if ($src > 0x60 && $src < 0x7b) $ret += $src - 0x61 + 26 + 1; // -70
		$ret += (((0x60 - $src) & ($src - 0x7b)) >> 8) & ($src - 70);

		// if ($src > 0x2f && $src < 0x3a) $ret += $src - 0x30 + 52 + 1; // 5
		$ret += (((0x2f - $src) & ($src - 0x3a)) >> 8) & ($src + 5);

		// if ($src == 0x2b) $ret += 62 + 1;
		$ret += (((0x2a - $src) & ($src - 0x2c)) >> 8) & 63;

		// if ($src == 0x2f) ret += 63 + 1;
		$ret += (((0x2e - $src) & ($src - 0x30)) >> 8) & 64;

		return $ret;
	}

	/**
	 * Uses bitwise operators instead of table-lookups to turn 8-bit integers
	 * into 6-bit integers.
	 *
	 * @param int $src
	 *
	 * @return string
	 */
	protected static function encode6Bits($src)
	{
		$diff = 0x41;

		// if ($src > 25) $diff += 0x61 - 0x41 - 26; // 6
		$diff += ((25 - $src) >> 8) & 6;

		// if ($src > 51) $diff += 0x30 - 0x61 - 26; // -75
		$diff -= ((51 - $src) >> 8) & 75;

		// if ($src > 61) $diff += 0x2b - 0x30 - 10; // -15
		$diff -= ((61 - $src) >> 8) & 15;

		// if ($src > 62) $diff += 0x2f - 0x2b - 1; // 3
		$diff += ((62 - $src) >> 8) & 3;

		return pack('C', $src + $diff);
	}
}

/**
 * Class Base64UrlSafe
 * [A-Z][a-z][0-9]\-_
 *
 * @package ParagonIE\ConstantTime
 */
abstract class Base64UrlSafe extends Base64
{

	/**
	 * Uses bitwise operators instead of table-lookups to turn 6-bit integers
	 * into 8-bit integers.
	 *
	 * Base64 character set:
	 * [A-Z]      [a-z]      [0-9]      -     _
	 * 0x41-0x5a, 0x61-0x7a, 0x30-0x39, 0x2d, 0x5f
	 *
	 * @param int $src
	 * @return int
	 */
	protected static function decode6Bits($src)
	{
		$ret = -1;

		// if ($src > 0x40 && $src < 0x5b) $ret += $src - 0x41 + 1; // -64
		$ret += (((0x40 - $src) & ($src - 0x5b)) >> 8) & ($src - 64);

		// if ($src > 0x60 && $src < 0x7b) $ret += $src - 0x61 + 26 + 1; // -70
		$ret += (((0x60 - $src) & ($src - 0x7b)) >> 8) & ($src - 70);

		// if ($src > 0x2f && $src < 0x3a) $ret += $src - 0x30 + 52 + 1; // 5
		$ret += (((0x2f - $src) & ($src - 0x3a)) >> 8) & ($src + 5);

		// if ($src == 0x2c) $ret += 62 + 1;
		$ret += (((0x2c - $src) & ($src - 0x2e)) >> 8) & 63;

		// if ($src == 0x5f) ret += 63 + 1;
		$ret += (((0x5e - $src) & ($src - 0x60)) >> 8) & 64;

		return $ret;
	}

	/**
	 * Uses bitwise operators instead of table-lookups to turn 8-bit integers
	 * into 6-bit integers.
	 *
	 * @param int $src
	 * @return string
	 */
	protected static function encode6Bits($src)
	{
		$diff = 0x41;

		// if ($src > 25) $diff += 0x61 - 0x41 - 26; // 6
		$diff += ((25 - $src) >> 8) & 6;

		// if ($src > 51) $diff += 0x30 - 0x61 - 26; // -75
		$diff -= ((51 - $src) >> 8) & 75;

		// if ($src > 61) $diff += 0x2d - 0x30 - 10; // -13
		$diff -= ((61 - $src) >> 8) & 13;

		// if ($src > 62) $diff += 0x5f - 0x2b - 1; // 3
		$diff += ((62 - $src) >> 8) & 49;

		return pack('C', $src + $diff);
	}
}
