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
 * @param string       $input
 * @param string|false $output
 */

/**
 * The main .po to .mo function.
 *
 * @param string       $input  The input .po file path.
 * @param string|false $output The output .mo file path, or false to derive from input.
 *
 * @return bool
 */
function phpmo_convert(string $input, string|false $output = false): bool {
	if (!$output) {
		$output = str_replace('.po', '.mo', $input);
	}

	$hash = phpmo_parse_po_file($input);

	if ($hash === false) {
		return false;
	} else {
		phpmo_write_mo_file($hash, $output);

		return true;
	}
}

/**
 * @param mixed $x
 *
 * @return mixed
 */
function phpmo_clean_helper(mixed $x): mixed {
	if (is_array($x)) {
		foreach ($x as $k => $v) {
			$x[$k] = phpmo_clean_helper($v);
		}
	} else {
		if ($x && $x[0] == '"') {
			$x = substr($x, 1, -1);
		}
		$x = str_replace("\"\n\"", '', $x);
		$x = str_replace('$', '\\$', $x);
		$x = str_replace('\\\\', '\\', $x);
		$x = preg_replace('/\+(["\$n])/', '\$1', $x);
	}

	return $x;
}

// Parse gettext .po files.
/** @see http://www.gnu.org/software/gettext/manual/gettext.html#PO-Files */
/**
 * @param string $in
 *
 * @return array<array-key, mixed>|false
 */
function phpmo_parse_po_file(string $in): array|false {
	// read .po file
	$fh = fopen($in, 'r');

	if ($fh === false) {
		// Could not open file resource
		return false;
	}

	// results array
	$hash = [];
	// temporary array
	$temp = [];
	// state
	$state = null;
	$fuzzy = false;

	// iterate over lines
	while (($line = fgets($fh, 65536)) !== false) {
		$line = trim($line);

		if ($line === '') {
			continue;
		}

		$pair = preg_split('/\s/', $line, 2);
		$key  = $pair[0];
		$data = $pair[1] ?? null;

		if ($data === null) {
			continue;
		}

		switch ($key) {
			case '#,': // flag...
				$fuzzy = in_array('fuzzy', preg_split('/,\s*/', $data));

				// no break
			case '#': // translator-comments
			case '#.': // extracted-comments
			case '#:': // reference...
			case '#|': // msgid previous-untranslated-string
				// start a new entry
				if (count($temp) && array_key_exists('msgid', $temp) && array_key_exists('msgstr', $temp)) {
					if (!$fuzzy) {
						$hash[] = $temp;
					}
					$temp  = [];
					$state = null;
					$fuzzy = false;
				}

				break;

			case 'msgctxt':
				// context
			case 'msgid':
				// untranslated-string
				if (count($temp)) {
					$hash[] = $temp;
					$temp   = [];
				}

				// no break
			case 'msgid_plural':
				// untranslated-string-plural
				$state        = $key;
				$temp[$state] = $data;

				break;

			case 'msgstr':
				// translated-string
				$state          = 'msgstr';
				$temp[$state][] = $data;

				break;

			default:
				if (str_contains($key, 'msgstr[')) {
					// translated-string-case-n
					$state          = 'msgstr';
					$temp[$state][] = $data;
				} else {
					// continued lines
					switch ($state) {
						case 'msgctxt':
						case 'msgid':
						case 'msgid_plural':
							$temp[$state] .= "\n" . $line;

							break;

						case 'msgstr':
							$temp[$state][count($temp[$state]) - 1] .= "\n" . $line;

							break;

						default:
							// parse error
							fclose($fh);

							return false;
					}
				}

				break;
		}
	}
	fclose($fh);

	// add final entry
	if ($state == 'msgstr') {
		$hash[] = $temp;
	}

	// Cleanup data, merge multiline entries, reindex hash for ksort
	$temp = $hash;
	$hash = [];

	foreach ($temp as $entry) {
		foreach ($entry as &$v) {
			$v = phpmo_clean_helper($v);

			if ($v === false) {
				// parse error
				return false;
			}
		}
		$hash[$entry['msgid']] = $entry;
	}

	return $hash;
}

/**
 * @param array<array-key, mixed> $hash
 *
 * @return string
 */
function phpmo_po_text(array $hash): string {
	$text = '';

	foreach ($hash as $id => $value) {
		$msgid  = phpmo_clean_helper(addcslashes($value['msgid'] ?? '', '"'));
		$msgstr = phpmo_clean_helper(addcslashes($value['msgstr'][0] ?? '', '"'));
		$text .= "msgid \"$msgid\"\nmsgstr \"$msgstr\"\n\n";
	}

	return $text;
}

// Write a gettext .po file.
/** @see http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files */
/**
 * @param array<array-key, mixed> $hash
 * @param string                  $out
 *
 * @return int|false
 */
function phpmo_write_po_file(array $hash, string $out): int|false {
	$text = phpmo_po_text($hash);

	return file_put_contents($out, $text);
}

// Write a GNU gettext style machine object.
/** @see http://www.gnu.org/software/gettext/manual/gettext.html#MO-Files */
/**
 * @param array<array-key, mixed> $hash
 * @param string                  $out
 *
 * @return int|false
 */
function phpmo_write_mo_file(array $hash, string $out): int|false {
	// sort by msgid
	ksort($hash, SORT_STRING);
	// our mo file data
	$mo = '';
	// header data
	$offsets = [];
	$ids     = '';
	$strings = '';

	foreach ($hash as $index => &$entry) {
		//don't add empty translations
		if (!$entry['msgstr'] || !$entry['msgstr'][0]) {
			unset($hash[$index]);

			continue;
		}

		$id = $entry['msgid'];

		if (isset($entry['msgid_plural'])) {
			$id .= "\x00" . $entry['msgid_plural'];
		}
		// context is merged into id, separated by EOT (\x04)
		if (array_key_exists('msgctxt', $entry)) {
			$id = $entry['msgctxt'] . "\x04" . $id;
		}

		// plural msgstrs are NUL-separated
		$str = implode("\x00", $entry['msgstr']);
		$str = str_replace('\n', "\n", $str); //restore newlines

		// keep track of offsets
		$offsets[] = [
			strlen(
				$ids
			), strlen($id), strlen($strings), strlen($str), ];
		// plural msgids are not stored (?)
		$ids .= $id . "\x00";
		$strings .= $str . "\x00";
	}

	// keys start after the header (7 words) + index tables ($#hash * 4 words)
	$key_start = 7 * 4 + count($hash) * 4 * 4;
	// values start right after the keys
	$value_start = $key_start + strlen($ids);
	// first all key offsets, then all value offsets
	$key_offsets   = [];
	$value_offsets = [];
	// calculate
	foreach ($offsets as $v) {
		[$o1, $l1, $o2, $l2]     = $v;
		$key_offsets[]           = $l1;
		$key_offsets[]           = $o1 + $key_start;
		$value_offsets[]         = $l2;
		$value_offsets[]         = $o2 + $value_start;
	}
	$offsets = array_merge($key_offsets, $value_offsets);

	// write header
	$mo .= pack(
		'Iiiiiii',
		0x950412DE, // magic number
		0, // version
		count($hash), // number of entries in the catalog
		7 * 4, // key index offset
		7 * 4 + count($hash) * 8, // value index offset,
		0, // hashtable size (unused, thus 0)
		$key_start // hashtable offset
	);
	// offsets
	foreach ($offsets as $offset) {
		$mo .= pack('i', $offset);
	}
	// ids
	$mo .= $ids;
	// strings
	$mo .= $strings;

	return file_put_contents($out, $mo);
}
