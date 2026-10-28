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
 * Convert dot notation to array key notation.
 *
 * Converts var.key1.key2 to var['key1']['key2'].
 *
 * @param string $key Dot-notation key string
 *
 * @return string Array key notation string
 */
function dotToArrayKey(string $key): string {
	//var.key1.key2 > var['key1']['key2']
	//var.key1 > var['key1']

	return preg_replace_callback('/\.([-_\w]+)/', fn ($matches) => "['" . str_replace("'", "\'", $matches[1]) . "']", $key);
}

/**
 * Convert form name bracket notation to array key notation.
 *
 * Converts name[key] to ['name']['key'].
 *
 * @param string $key Form name bracket notation string
 *
 * @return string Array key notation string
 */
function postNameToArrayKey(string $key): string {
	$key = str_replace(['][', '[', ']'], ['.', '.', ''], $key);

	return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
}

/**
 * Prefix all array keys with a given prefix.
 *
 * @param string                  $prefix Prefix to add to each key
 * @param array<array-key, mixed> &$array Array to modify by reference
 *
 * @return array<array-key, mixed> Modified array with prefixed keys
 */
function prefixArrayKeys(string $prefix, array &$array): array {
	foreach ($array as $k => $v) {
		$array["$prefix$k"] = $v;
		unset($array[$k]);
	}

	return $array;
}

/**
 * Converts slug/text to human readable format.
 *
 * @param string $text The text to convert.
 *
 * @return string The human readable text.
 */
function humanReadable(string $text): string {
	return ucfirst(str_replace(['_', '-', '/', '[', ']', '.'], [' ', ' ', ' - ', ' ', ' ', ' '], trim($text, ' /\-_')));
}

/**
 * Cleans text for URL usage without transliteration.
 *
 * @param string $text    The text to clean.
 * @param string $divider The divider character to use.
 *
 * @return string The cleaned URL string.
 */
function cleanUrl(string $text, string $divider = '-'): string {
	// replace non letter or digits by divider
	$text = preg_replace('/[^\pL\d]+/u', $divider, $text) ?? $text;

	// remove unwanted characters
	$text = preg_replace('/[^\\-\w]+/', '', $text) ?? $text;

	return trim($text, $divider);
}

/**
 * Creates URL-safe slug from text with transliteration.
 *
 * @param string $text    The text to slugify.
 * @param string $divider The divider character to use.
 *
 * @return string The URL-safe slug.
 */
function slugify(string $text, string $divider = '-'): string {
	if (!$text) {
		return $text;
	}
	// replace non letter or digits by divider
	$text = preg_replace('/[^\pL\d]+/u', $divider, $text) ?? $text;

	// transliterate
	if (function_exists('iconv')) {
		$transliterated = @iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		if ($transliterated !== false) {
			$text = $transliterated;
		}
	}

	// remove unwanted characters
	$text = preg_replace('/[^-\w]+/', '', $text) ?? $text;

	// trim
	$text = trim($text, $divider);

	// remove duplicate divider
	$text = preg_replace('/-+/', $divider, $text) ?? $text;

	// lowercase
	return strtolower($text);
}

/**
 * Converts a dash-separated string to CamelCase.
 *
 * @param string $string The string to convert.
 * @param string $dash   The dash character used as separator.
 *
 * @return string The CamelCase string.
 */
function dashesToCamelCase(string $string, string $dash = '-'): string {
	return str_replace($dash, '', ucwords($string, $dash));
}

/**
 * Converts CamelCase string to dash/underscore-separated lowercase.
 *
 * @param string $string The CamelCase string to convert.
 * @param string $us     The separator character (default '-').
 *
 * @return string The converted string.
 */
function camelToUnderscore(string $string, string $us = '-'): string {
	return strtolower(preg_replace(
		'/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/',
		$us,
		$string
	));
}

/**
 * Recursively converts underscore-separated array keys to camelCase.
 *
 * @param array<array-key, mixed> $array The array whose keys to convert.
 *
 * @return array<array-key, mixed> The array with converted keys.
 */
function arrayKeysToCamelCase(array &$array): array {
	foreach ($array as $key => &$value) {
		if (is_array($value)) {
			$value = arrayKeysToCamelCase($value);
		}

		$newKey = lcfirst(dashesToCamelCase($key, '_'));

		if ($newKey != $key) {
			$array[$newKey] = $value;
			unset($array[$key]);
		}
	}

	return $array;
}

/**
 * Recursively converts camelCase array keys to dash-separated lowercase.
 *
 * @param array<array-key, mixed> &$array The array to convert keys for.
 * @param string                  $us     The separator character (default '-').
 *
 * @return array<array-key, mixed> The array with converted keys.
 */
function arrayKeysToUnderscore(array &$array, string $us = '-'): array {
	foreach ($array as $key => &$value) {
		if (is_array($value)) {
			$value = arrayKeysToUnderscore($value, $us);
		}

		$newKey = camelToUnderscore($key, $us);

		if ($newKey != $key) {
			$array[$newKey] = $value;
			unset($array[$key]);
		}
	}

	return $array;
}

/**
 * Removes extra spaces from a string.
 *
 * @param string $string The string to process.
 *
 * @return string The string with extra spaces removed.
 */
function stripExtraSpaces(string $string): string {
	foreach (['\t', '\n', '\r', ' '] as $space) {
		$string = preg_replace('/(' . $space . ')' . $space . '+/', '\1', $string);
	}

	return $string;
}

/**
 * Truncates text to specified number of characters.
 *
 * @param string $text  The text to truncate.
 * @param int    $limit The maximum number of characters.
 *
 * @return string The truncated text.
 */
function truncateWords(string $text, int $limit): string {
	$parts      = preg_split('/(\s+)/ms', substr($text, 0, $limit * 2), 0, PREG_SPLIT_DELIM_CAPTURE);
	$partsCount = count($parts);

	$length   = 0;
	$lastPart = 0;
	for (; $lastPart < $partsCount; $lastPart++) {
		$partlen = strlen($parts[$lastPart]);
		$length += $partlen;
		if ($length >= $limit) {
			//don't include less than two letter last word or spaces
			$partlen = $lastPart > 0 ? strlen($parts[$lastPart - 1]) : 0;
			while (($partlen < 3) && $lastPart > 3) {
				$lastPart--;
				$partlen = strlen($parts[$lastPart - 1]);
			}
			break;
		}
	}

	return implode('', array_slice($parts, 0, $lastPart));
}

/**
 * Sanitize text by encoding special characters.
 *
 * @param string $data Text to sanitize
 *
 * @return string Sanitized text
 */
function filterText(string $data): string {
	//no urldecode after sanitizing - it would re-introduce percent-encoded
	//special chars (eg %3Cscript%3E -> <script>) defeating the filter
	return filter_var($data, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_ENCODE_HIGH | FILTER_FLAG_ENCODE_LOW);
}

/**
 * Regex filter to allow only matched content.
 *
 * @param string $regex        Regular expression pattern
 * @param string $input        Input string to filter
 * @param int    $maxInputSize Maximum input size to test
 *
 * @return string|false Matched content, or false if no match
 */
function filter(string $regex, string $input, int $maxInputSize = 100): string|false {
	$matches = [];

	if (preg_match($regex, substr($input, 0, $maxInputSize), $matches)) {
		return $matches[0];
	} else {
		return false;
	}
}

/**
 * Shortcut for preg_match to return the matched parameter directly.
 *
 * @param string $regex
 * @param string $input
 * @param int    $level [false]
 *
 * @return mixed
 */
function pregMatch(string $regex, string $input, int|false $level = false): mixed {
	$matches = [];

	//if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL | PREG_PATTERN_ORDER)) {
	if (preg_match($regex, $input, $matches, PREG_UNMATCHED_AS_NULL)) {
		if ($level !== false) {
			return $matches[$level];
		}

		return $matches;
	} else {
		return false;
	}
}

/**
 * Shortcut for preg_match_all to return the matched parameters directly.
 *
 * @param string $regex
 * @param string $input
 * @param int    $level [false]
 *
 * @return mixed
 */
function pregMatchAll(string $regex, string $input, int|false $level = false): mixed {
	$matches = [];

	if (preg_match_all($regex, $input, $matches, PREG_UNMATCHED_AS_NULL | PREG_PATTERN_ORDER)) {
		if ($level !== false) {
			return $matches[$level];
		}

		return $matches;
	} else {
		return false;
	}
}
