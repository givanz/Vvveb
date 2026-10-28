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

if ((!defined('GETTEXT') || GETTEXT == true) && function_exists('_')) {
	/**
	 * Translates text using gettext.
	 *
	 * @param string       $text    The text to translate.
	 * @param string|false $plural  The plural form of the text.
	 * @param int|false    $count   The count for plural translation.
	 * @param bool         $nocache Whether to bypass the translation cache.
	 *
	 * @return string The translated text.
	 */
	function __($text, string|false $plural = false, int|false $count = false, bool $nocache = false): string {
		if ($plural) {
			$translation = ngettext($text, $plural, (int)$count);
		} else {
			$translation = gettext($text);
		}

		//list($text, $plural, $count, $translation) = System\Event::trigger(__NAMESPACE__, '__', $text, $plural, $count, $translation);

		return $translation;
	}
} else {
	/**
	 * Translates text using custom PO file parser.
	 *
	 * @param string       $text    The text to translate.
	 * @param string|false $plural  The plural form of the text.
	 * @param int|false    $count   The count for plural translation.
	 * @param bool         $nocache Whether to bypass the translation cache.
	 *
	 * @return string The translated text.
	 */
	function __($text, string|false $plural = false, int|false $count = false, bool $nocache = false): string {
		static $translations;

		if (($translations === null || $nocache) && !isEditor()) {
			$translations = [];
			$lang         = System\Locale::getLanguage();

			$cacheFile = DIR_CACHE . $lang . '-translations.php';

			if (file_exists($cacheFile)) {
				$translations = require $cacheFile;
			} else {
				$folder = DIR_ROOT . 'locale' . DS . $lang . DS . 'LC_MESSAGES' . DS;
				if (is_dir($folder)) {
					require_once DIR_SYSTEM . 'functions' . DS . 'php-mo.php';

					foreach (['vvveb', 'user'] as $domain) {
						$poFile = $folder . $domain . '.po';
						if (file_exists($poFile) && ($t = phpmo_parse_po_file($poFile) ?: [])) {
							$translations = array_merge($translations, $t);
						}
					}

					foreach ($translations as $id => &$msg) {
						$msg = $msg['msgstr'];
					}

					file_put_contents($cacheFile, '<?php return ' . var_export($translations, true) . ';');
				}
			}
		}

		if (isset($translations[$text])) {
			if ($count && $plural) {
				$translation = $translations[$text][1] ?? $translations[$text][0] ?? $text;
			} else {
				$translation = $translations[$text][0] ?? $text;
			}
		} else {
			$translation = $text;
		}

		//list($text, $plural, $count, $translation) = System\Event::trigger(__NAMESPACE__, '__', $text, $plural, $count, $translation);

		return $translation;
	}
}
