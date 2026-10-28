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
 * Removes XML version declaration to avoid issues with PHP short open tags.
 *
 * @param string|null $xmlString The XML string to process
 *
 * @return string The XML string without the version declaration
 */
function xmlStripVersionDeclr(?string $xmlString): string {
	$xmlString = preg_replace('/<\?xml\s+version[^\?]+\?>\s*/', '', $xmlString ?? '') ?? '';

	//xml stylesheet
	return str_replace('<?xml-', '<?php echo \'<?xml-\';?>', $xmlString);
}

/**
 * Converts a PHP array to XML string using SimpleXMLElement.
 *
 * Supports attribute encoding via @@ prefix and type preservation markers (--array, --boolean, --int, --null, --object).
 *
 * @param array<string,mixed>     $array The array to convert
 * @param \SimpleXMLElement|false $xml   Existing XML element to append to, or false to create new
 *
 * @return string The XML string representation
 */
function array2xml(array $array, \SimpleXMLElement|false $xml = false): string {
	if ($xml === false) {
		$xml = new \SimpleXMLElement('<root/>');
	}

	foreach ($array as $key => $value) {
		$attributes  = [];
		$processAttr = function(&$key) use (&$attributes): void {
			$i     = 0;
			$start = 0;

			if ($key) {
				while (($start = strpos($key, '@@', $start))) {
					$i++;
					$start += 2;
					$end  = (int)strpos($key, ' ', $start);
					$name = substr($key, $start, $end ? $end - $start : null);

					if ($end == 0) {
						$start += strlen($name);
					} else {
						$start = (int)$end;
					}

					if ($name) {
						$name                 = explode('=', $name);
						$attributes[$name[0]] = trim($name[1] ?? '', '\'"');
					}
				}

				$key = strstr($key, ' @@', true) ?: $key;
			}
		};

		$processAttr($key);

		if (is_array($value) || is_object($value)) {
			$node = $xml->addChild($key);

			if ($attributes) {
				foreach ($attributes as $key => $val) {
					$node->addAttribute($key, htmlspecialchars($val));
				}
			}
			array2xml($value, $node);
		} else {
			if (substr_compare($key, '--xmlattr', -9, 9) === 0) {
				$key = substr($key, 0, -9);
				$xml->addAttribute($key, htmlspecialchars($value));
			} else {
				$processAttr($value);

				if ($value && is_string($value)) {
					$value = htmlspecialchars($value);
				}
				$node = $xml->addChild($key, $value);

				if ($attributes) {
					foreach ($attributes as $key => $val) {
						$node->addAttribute($key, $val);
					}
				}
			}
		}
	}

	$xmlString = $xml->asXML();

	return xmlStripVersionDeclr($xmlString);
}

/**
 * Removes single-line and multi-line comments from a JSON string.
 *
 * @param string $json The JSON string with comments
 *
 * @return string The JSON string without comments
 */
function removeJsonComments(string $json): string {
	return preg_replace('@^\s*//.*([\r\n]|\s)*|^\s*/\*([\r\n]|.)*?\*/([\r\n]|\s)*@m', '', $json) ?? $json;
}

/**
 * Encodes invalid XML name characters to numeric entity format (_xNNN_).
 *
 * @param string $name The XML element or attribute name to encode
 *
 * @return string The encoded name with invalid characters replaced
 */
function encodeXmlName(string $name): string {
	$len     = strlen($name);
	$newName = '';

	for ($i = 0; $i < $len; $i++) {
		$char = $name[$i];
		$code = ord($char);

		// = @ _ - . A-Z a-z 0-9
		if (($code >= 48 && $code <= 57) || ($code >= 65 && $code <= 90) || ($code >= 97 && $code <= 122) || $code === 32 || $code === 34 || $code === 45 || $code === 46 || $code === 61 || $code === 64 || $code === 95) {
			$char = '_x' . sprintf('%03d', $code) . '_';
		}

		$newName .= $char;
	}

	return $newName;
}

/**
 * Decodes encoded XML name characters back to their original form.
 *
 * @param string $name The encoded XML name to decode
 *
 * @return string The decoded name with _xNNN_ entities restored to characters
 */
function decodeXmlName(string $name): string {
	return preg_replace_callback('/_x(\d+)_/', fn ($matches) => chr((int)ltrim($matches[1], '0')), $name);
}

/**
 * Prepares an array for XML/JSON conversion by encoding keys and preserving PHP types.
 *
 * Encodes attribute markers (@, @@), preserves empty arrays/objects, booleans, integers, and nulls
 * using type suffix markers (--array, --boolean, --int, --null, --object).
 *
 * @param array<string,mixed> $array The array to prepare
 *
 * @return array<string,mixed> The prepared array with encoded keys
 */
function prepareJson(array $array): array {
	$helper = [];

	foreach ($array as $key => $value) {
		$key = (string)$key;

		if (is_numeric($key)) {
			$key = 'n--' . $key;
		}

		if ($key !== '' && $key[0] == '@') {
			if (isset($key[1]) && $key[1] == '@') {
				$key = substr($key, 2) . '--xmlattr';
			} else {
				$key = substr($key, 1) . '--attr';
			}
		}

		//keep empty arrays as array type
		if (is_array($value) && [] === $value) {
			$key .= '--array';
		}

		//keep boolean type
		if (is_bool($value)) {
			$key .= '--boolean';
		}

		//keep int type
		if (is_int($value)) {
			$key .= '--int';
		}

		//keep null type
		if ($value === null) {
			$key .= '--null';
		}

		$key = encodeXmlName(trim($key));

		$helper[$key] = is_array($value) ? prepareJson($value) : $value;
	}

	return $helper;
}

/**
 * Reverses prepareJson transformations to restore original array structure.
 *
 * Decodes type suffix markers, restores attribute prefixes, and converts numeric string keys back to integers.
 *
 * @param mixed $array       The prepared array to reconstruct (passed by reference)
 * @param bool  $removeAttrs Whether to remove @attributes keys entirely
 *
 * @return array<string,mixed>|null The reconstructed array<array-key, mixed>, or null if input is not iterable
 */
function reconstructJson(mixed &$array, bool $removeAttrs = false): mixed {
	if (!is_iterable($array)) {
		return null;
	}
	$helper = [];
	$array  = (array)$array;

	foreach ($array as $key => $value) {
		if ($removeAttrs && $key == '@attributes') {
			continue;
		}

		$key = decodeXmlName($key);

		if (substr_compare($key, '--array', -7, 7) === 0) {
			$key   = substr($key, 0, -7);
			$value = (array)$value;
		} else {
			if ($value === null) {
				$value = '';
			}
		}

		if (substr_compare($key, '--boolean', -9, 9) === 0) {
			$key   = substr($key, 0, -9);
			$value = (bool)$value;
		}

		if (substr_compare($key, '--null', -6, 6) === 0) {
			$key   = substr($key, 0, -6);
			$value = null;
		}

		if (substr_compare($key, '--int', -5, 5) === 0) {
			$key   = substr($key, 0, -5);
			$value = (int)$value;
		}

		if (substr_compare($key, '--object', -8, 8) === 0) {
			$key   = substr($key, 0, -8);
			$value = (object)$value;
		}

		if (substr_compare($key, 'n--', 0, 3) === 0) {
			$newkey = substr($key, 3);

			if (is_numeric($newkey)) {
				$key = (int)$newkey;
			}
		}

		if (substr_compare($key, '--xmlattr', -9, 9) === 0) {
			$key = '@@' . substr($key, 0, -9);
		}

		if (substr_compare($key, '--attr', -6, 6) === 0) {
			$key = '@' . substr($key, 0, -6);
		}

		$helper[$key] = (is_iterable($value)) ? reconstructJson($value, $removeAttrs) : $value;
	}

	return $helper;
}
