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

/**
 * Manages image retrival and saving.
 *
 * @package Vvveb
 * @subpackage System
 * @since 0.0.1
 */

namespace Vvveb\System;

use function Vvveb\rrmdir;
use function Vvveb\session;

class Locale {
	static private $code;

	static function availableLanguages() {
		static $languages = [];
		if ($languages != []) {
			return $languages;
		}

		$cache     = Cache::getInstance();
		$languages = $cache->cache(APP,'languages',static function () {
			$languages             = new \Vvveb\Sql\LanguageSQL();
			$result = $languages->getAll(['status' => 1]);

			if ($result && isset($result['language'])) {
				return $result['language'];
			}

			return [];
		}, 259200);

		return $languages;
	}

	static function availableCurrencies() {
		static $currencies = [];
		if ($currencies != []) {
			return $currencies;
		}

		$cache     = Cache::getInstance();
		$currencies = $cache->cache(APP,'currency',static function () {
			$currency             = new \Vvveb\Sql\CurrencySQL();
			$result = $currency->getAll(['status' => 1]);

			if ($result && isset($result['currency'])) {
				return $result['currency'];
			}

			return [];
		}, 259200);

		return $currencies;
	}

	static function installedLanguages() {
		$languages = glob(DIR_ROOT . 'locale/*', GLOB_ONLYDIR);

		foreach ($languages as &$language) {
			$language = basename($language);
		}

		return $languages;
	}

	static function userPreferedLanguages() {
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

			foreach ($languages as &$language) {
				$language = \Vvveb\filter('/[-\w]+/', $language);
				$language = str_replace('-', '_', $language);
			}
		}

		return $languages ?? [];
	}

	static function userPreferedLanguage() {
		$languages = self :: userPreferedLanguages();
		$installed = self :: installedLanguages();

		foreach ($languages as $language) {
			if (isset($installed[$language])) {
				return $language;
			}

			foreach ($installed as $lang) {
				if (strpos($lang, $language) === 0) {
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
	 * @param string $langCode ['en_US'] 
	 * @param string $domain ['vvveb'] 
	 *
	 * @return mixed 
	 */
	static function setLanguage($langCode = 'en_US', $domain = 'vvveb') {
		//global $vvvebTranslationDomains;
		//setlocale(LC_TIME, "");
		//\putenv('LOCPATH=' . DIR_ROOT. "locale");

		//translating theme text will change theme texts and break translation
		if (\Vvveb\isEditor()) {
			return;
		}

		self :: $code = $langCode;

		if (function_exists('bindtextdomain')) {
			/*
			foreach ($vvvebTranslationDomains as $tdomain) {
				bindtextdomain($tdomain, DIR_ROOT . 'locale');
			}
			*/
			bindtextdomain($domain, DIR_ROOT . 'locale');
			textdomain($domain);
			bind_textdomain_codeset($domain, 'UTF-8');

			setlocale(LC_ALL,'C.UTF-8');

			if (function_exists('putenv')) {
				@\putenv("LC_ALL=$langCode");
				@\putenv("LC_MESSAGES=$langCode");
				@\putenv("LANG=$langCode");
				@\putenv('LANGUAGE=' . $langCode);
			}

			if (defined('LC_MESSAGES')) {
				setlocale(LC_MESSAGES, "$langCode.UTF-8");
				setlocale(LC_CTYPE,"$langCode.UTF-8");
			} else {
				setlocale(5, "$langCode.UTF-8");
				setlocale(6,"$langCode.UTF-8");
				setlocale(LC_ALL, "$langCode.UTF-8");
			}
		}
	}

	static function clearLanguageCache($langCode = 'en_US', $domain = 'vvveb') {
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

	static function getLanguage() {
		return self :: $code ?? session('code') ?? 'en_US';
	}

	static function setLanguageCode($code) {
		self :: $code = $code;
		return session(['code' => $code]);
	}

	static function getLanguageId() {
		return session('language_id') ?? 1;
	}

	static function getCurrency() {
		return session('currency') ?? 'USD';
	}
}
