<?php

/**
 * Vvveb
 *
 * Copyright (C) 2022  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Vvveb\System\Functions;

class Str {
	/**
	 * @param string $filename
	 *
	 * @return string
	 */
	public static function sanitizeFilename(string $filename): string {
		return preg_replace('([^\w\s\d\.\-_~,;:\[\]\(\)]|[\.]{2,})', '', $filename);
	}

	/**
	 * @param string $filename
	 *
	 * @return string
	 */
	public static function sanitizePath(string $filename): string {
		return preg_replace('([^\/\w\s\d\.\-_~,;:\[\]\(\)\\]|[\.]{2,})', '', $filename);
	}

	/**
	 * @param string $pattern
	 * @param string $subject
	 *
	 * @return string|array<array-key, mixed>|null
	 */
	public static function match(string $pattern, string $subject): string|array|null {
		if (preg_match($pattern, $subject, $matches)) {
			if (count($matches) > 2) {
				return array_slice($matches, 1);
			}

			return $matches[1] ?? $matches[0];
		}

		return null;
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function random(int $length = 16): string {
		$string = '';

		while (($len = strlen($string)) < $length) {
			$size = $length - $len;

			$bytes = random_bytes($size);

			$string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
		}

		return $string;
	}
}
