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

namespace Vvveb;

/**
 * Validates that input is in the allowed values list.
 *
 * @param mixed                   $input         The input value to validate.
 * @param array<array-key, mixed> $allowedValues The list of allowed values.
 *
 * @return mixed The input value if valid, null otherwise.
 */
function arrayAllowValues(mixed $input, array $allowedValues): mixed {
	if (!in_array($input, $allowedValues)) {
		return null;
	} else {
		return $input;
	}
}

/**
 * Get values from multidimensional arrays based on path.
 * For example for array ['item' => ['desc' => ['name' => 'test']]] the path "item.desc.name" will return "test".
 *
 * @param array<array-key, mixed> $a       The array to search.
 * @param string                  $path    The dot-separated path to the value.
 * @param mixed                   $default The default value if path is not found.
 * @param string                  $token   The token used to separate path segments.
 *
 * @return mixed The value at the given path or the default value.
 */
function arrayPath(array $a, string $path, mixed $default = null, string $token = '.'): mixed {
	$p = strtok($path, $token);

	while ($p !== false) {
		if (!isset($a[$p])) {
			return $default;
		}

		$a = $a[$p];
		$p = strtok($token);
	}

	return $a;
}

/**
 * Inserts a new key/value before the key in the array.
 *
 * @param string                  $key       The key to insert before.
 * @param array<array-key, mixed> $array     An array to insert in to.
 * @param string                  $new_key   The key to insert.
 * @param mixed                   $new_value An value to insert.
 *
 * @return array<array-key, mixed> The new array if the key exists, otherwise the unchanged array.
 *
 * @see arrayInsertAfter()
 */
function arrayInsertBefore(string $key, array $array, string $new_key, mixed $new_value): array {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			if ($k === $key) {
				$new[$new_key] = $new_value;
			}
			$new[$k] = $value;
		}

		return $new;
	}

	return $array;
}

/**
 * Inserts a new array before the key in the array.
 *
 * @param string                  $key       The key to insert before.
 * @param array<array-key, mixed> $array     An array to insert in to.
 * @param array<array-key, mixed> $new_array An array to insert.
 *
 * @return array<array-key, mixed> The new array if the key exists, otherwise the unchanged array.
 *
 * @see arrayInsertAfter()
 */
function arrayInsertArrayBefore(string $key, array $array, array $new_array): array {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			if ($k === $key) {
				$new += $new_array;
			}
			$new[$k] = $value;
		}

		return $new;
	}

	return $array;
}

/**
 * Inserts a new key/value after the key in the array.
 *
 * @param string                  $key       The key to insert after.
 * @param array<array-key, mixed> $array     An array to insert in to.
 * @param string                  $new_key   The key to insert.
 * @param mixed                   $new_value An value to insert.
 *
 * @return array<array-key, mixed> The new array if the key exists, otherwise the unchanged array.
 *
 * @see arrayInsertBefore()
 */
function arrayInsertAfter(string $key, array $array, string $new_key, mixed $new_value): array {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			$new[$k] = $value;

			if ($k === $key) {
				$new[$new_key] = $new_value;
			}
		}

		return $new;
	}

	return $array;
}

/**
 * Inserts a new array after the key in the array.
 *
 * @param string                  $key       The key to insert after.
 * @param array<array-key, mixed> $array     An array to insert in to.
 * @param array<array-key, mixed> $new_array An array to insert.
 *
 * @return array<array-key, mixed> The new array if the key exists, otherwise the unchanged array.
 *
 * @see arrayInsertBefore()
 */
function arrayInsertArrayAfter(string $key, array $array, array $new_array): array {
	if (array_key_exists($key, $array)) {
		$new = [];

		foreach ($array as $k => $value) {
			$new[$k] = $value;

			if ($k === $key) {
				$new += $new_array;
			}
		}

		return $new;
	}

	return $array;
}
