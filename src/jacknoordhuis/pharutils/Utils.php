<?php

declare(strict_types=1);

namespace jacknoordhuis\pharutils;

class Utils {

	/**
	 * @param array       $strings
	 * @param string|null $delim
	 *
	 * @return array
	 */
	public static function preg_quote_array(array $strings, string $delim = null) : array {
		return array_map(function(string $str) use ($delim) : string{ return preg_quote($str, $delim); }, $strings);
	}

}