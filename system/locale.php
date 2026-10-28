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

namespace Vvveb\System;

use function Vvveb\rrmdir;
use function Vvveb\session;

class Locale {
	/** @var string|null Current language code. */
	private static ?string $code = null;

	/**
	 * Get all available languages from the database.
	 *
	 * @return array<string, array<string, mixed>> List of language records keyed by slug.
	 */
	public static function availableLanguages(): array {
		static $languages = [];
		if ($languages != []) {
			return $languages;
		}

		$cache     = Cache::getInstance();
		$languages = $cache->cache(APP, 'languages', static function() {
			$languages             = new \Vvveb\Sql\LanguageSQL();
			$result                = $languages->getAll(['status' => 1]);

			if ($result && isset($result['language'])) {
				return $result['language'];
			}

			return [];
		}, 259200);

		return $languages;
	}

	/**
	 * Get all available currencies from the database.
	 *
	 * @return array<string, array<string, mixed>> List of currency records keyed by code.
	 */
	public static function availableCurrencies(): array {
		static $currencies = [];
		if ($currencies != []) {
			return $currencies;
		}

		$cache      = Cache::getInstance();
		$currencies = $cache->cache(APP, 'currency', static function() {
			$currency             = new \Vvveb\Sql\CurrencySQL();
			$result               = $currency->getAll(['status' => 1]);

			if ($result && isset($result['currency'])) {
				return $result['currency'];
			}

			return [];
		}, 259200);

		return $currencies;
	}

	/**
	 * Get list of installed language directories.
	 *
	 * @return list<string> Language folder names.
	 */
	public static function installedLanguages(): array {
		$languages = glob(DIR_ROOT . 'locale/*', GLOB_ONLYDIR);

		foreach ($languages as &$language) {
			$language = basename($language);
		}

		return $languages;
	}

	/**
	 * Parse the user's preferred languages from the Accept-Language header.
	 *
	 * @return list<string> List of preferred language codes.
	 */
	public static function userPreferedLanguages(): array {
		$languages = [];

		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

			foreach ($languages as &$language) {
				$language = \Vvveb\filter('/[-\w]+/', $language);
				$language = str_replace('-', '_', $language);
			}
		}

		return $languages;
	}

	/**
	 * Find the best matching installed language from the user's preferences.
	 *
	 * @return string|false The matched language code, or false if none found.
	 */
	public static function userPreferedLanguage(): string|false {
		$languages = self :: userPreferedLanguages();
		$installed = self :: installedLanguages();

		foreach ($languages as $language) {
			if (in_array($language, $installed, true)) {
				return $language;
			}

			foreach ($installed as $lang) {
				if (str_starts_with($lang, $language)) {
					//return $language;
					return $lang;
				}
			}
		}

		return false;
	}

	/**
	 * Change locale language for gettext.
	 *
	 * @param string $langCode Language code (e.g. 'en_US').
	 * @param string $domain   Gettext domain (e.g. 'vvveb').
	 *
	 * @return void
	 */
	public static function setLanguage(string $langCode = 'en_US', string $domain = 'vvveb'): void {
		//translating theme text will change theme texts and break translation
		if (\Vvveb\isEditor()) {
			return;
		}

		self :: $code = $langCode;

		if (function_exists('bindtextdomain')) {
			bindtextdomain($domain, DIR_ROOT . 'locale');
			textdomain($domain);
			bind_textdomain_codeset($domain, 'UTF-8');

			setlocale(LC_ALL, 'C.UTF-8');

			if (function_exists('putenv')) {
				@\putenv("LC_ALL=$langCode");
				@\putenv("LC_MESSAGES=$langCode");
				@\putenv("LANG=$langCode");
				@\putenv('LANGUAGE=' . $langCode);
			}

			if (defined('LC_MESSAGES')) {
				setlocale(LC_MESSAGES, "$langCode.UTF-8");
				setlocale(LC_CTYPE, "$langCode.UTF-8");
			} else {
				setlocale(5, "$langCode.UTF-8");
				setlocale(6, "$langCode.UTF-8");
				setlocale(LC_ALL, "$langCode.UTF-8");
			}
		}
	}

	/**
	 * Clear compiled language cache files and reset opcache.
	 *
	 * @param string $langCode Language code to clear cache for.
	 * @param string $domain   Gettext domain.
	 *
	 * @return void
	 */
	public static function clearLanguageCache(string $langCode = 'en_US', string $domain = 'vvveb'): void {
		if (function_exists('bindtextdomain')) {
			$locale  = DIR_ROOT . 'locale';
			$nocache = DIR_CACHE . 'nocache';
			$mo      = $locale . DS . $langCode . DS . 'LC_MESSAGES' . DS . "$domain.mo";
			clearstatcache(false, $mo);

			if (function_exists('opcache_reset')) {
				opcache_invalidate($mo, true);
				opcache_reset();
			}

			if (function_exists('symlink') && @symlink($locale, $nocache)) {
				bindtextdomain($domain, $nocache);
				textdomain($domain);
				bind_textdomain_codeset($domain, 'different_codeset');
				bindtextdomain($domain, $locale);
			}

			@unlink($nocache);
			@rrmdir($nocache); //for windows if it copies folder instead of link
		}
	}

	/**
	 * Get the current language code.
	 *
	 * @return string The active language code.
	 */
	public static function getLanguage(): string {
		return self :: $code ?? session('code') ?? 'en_US';
	}

	/**
	 * Set the language code in the session.
	 *
	 * @param string $code Language code to set.
	 *
	 * @return mixed Result of session set.
	 */
	public static function setLanguageCode(string $code): mixed {
		self :: $code = $code;

		return session(['code' => $code]);
	}

	/**
	 * Get the current language ID from the session.
	 *
	 * @return int The language ID, defaults to 1.
	 */
	public static function getLanguageId(): int {
		return session('language_id') ?? 1;
	}

	/**
	 * Get the current currency code from the session.
	 *
	 * @return string The currency code, defaults to 'USD'.
	 */
	public static function getCurrency(): string {
		return session('currency') ?? 'USD';
	}
}
