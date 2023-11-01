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

namespace Vvveb\Controller;

use \Vvveb\System\Core\FrontController;
use \Vvveb\System\Functions\Str;
use function Vvveb\availableCurrencies;
use function Vvveb\availableLanguages;
use function Vvveb\clearLanguageCache;
use function Vvveb\filter;
use function Vvveb\setLanguage;
use function Vvveb\siteSettings;
use Vvveb\System\Core\View;
use Vvveb\System\User\Admin;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class Base {
	protected function language($defaultLanguage, $defaultLanguageId) {
		$languages = availableLanguages();

		if (($language = ($this->request->post['language'] ?? false)) && ! is_array($language)) {
			$language  = filter('/[A-Za-z_-]+/', $language, 50);
			$this->session->set('language', $language);
			$this->session->set('language_id', $languages[$language]['language_id'] ?? $defaultLanguageId);
			clearLanguageCache($language);
		}

		$default_language    = $this->session->get('default_language') ?? $default_language = $defaultLanguage;
		$default_language_id = $this->session->get('default_language_id') ?? $default_language_id = $defaultLanguageId;
		$language            = $this->session->get('language') ?? $language = $default_language;
		$language_id         = $this->session->get('language_id') ?? $language_id = $defaultLanguageId;

		if (! $default_language) {
			foreach ($languages as $code => $lang) {
				if ($lang['default']) {
					$default_language    = $code;
					$default_language_id = $lang['language_id'];

					break;
				}
			}
			//no default language? set english as default
			if (! $default_language) {
				$default_language    = 'en_US';
				$default_language_id = 1;
			}

			$this->session->set('default_language', $default_language);
			$this->session->set('default_language_id', $default_language_id);
		}

		//if no language configured then set default language as current language
		if (! $language) {
			$language    = $default_language;
			$language_id = $default_language_id;
			$this->session->set('language', $language);
			$this->session->set('language_id', $language_id);
		}

		//if no default language configured then set first language as current language
		if (! isset($languages[$language])) {
			$language = key($languages);
			$this->session->set('language', $language);
			$this->session->set('language_id', $languages[$language]['language_id'] ?? $defaultLanguageId);
		}

		$language    = $this->session->get('language') ?? 'en_US';
		$language_id = $this->session->get('language_id') ?? $defaultLanguageId;

		$this->global['language']            = $language;
		$this->global['language_id']         = $language_id;
		$this->global['default_language']    = $default_language;
		$this->global['default_language_id'] = $default_language_id;

		setLanguage($language);
	}

	protected function currency($defaultCurrency, $defaultCurrencyId) {
		if (($currency = ($this->request->post['currency'] ?? false)) && ! is_array($currency)) {
			$currency   = filter('/[A-Za-z_-]+/', $currency, 50);
			$currencies = availableCurrencies();

			if (isset($currencies[$currency])) {
				$this->session->set('currency_id', $currencies[$currency]['currency_id']);
				$this->session->set('currency', $currency);

				\Vvveb\System\Cart\Cart::getInstance($this->global)->updateCart();
			}
		}

		$currency    = $this->session->get('currency');
		$currency_id = $this->session->get('currency_id');

		if (! $currency) {
			$this->session->set('currency', $currency = $defaultCurrency);
			$this->session->set('currency_id', $currency_id = $defaultCurrencyId);
			/*
			//if no site currency configured set first available currency
			$currencies = availableCurrencies();
			$currency = $this->session->get('currency')
			$currency_id = $this->session->get('currency_id');
			*/
		}

		$this->global['currency']            = $currency;
		$this->global['currency_id']         = $currency_id;
	}

	protected function initEcommerce($countryId, $regionId) {
		$tax = \Vvveb\System\Cart\Tax::getInstance();
		$tax->setRegionRules($countryId, $regionId, 'store');
	}

	function init() {
		if (! $this->session->get('csrf')) {
			$this->session->set('csrf', Str::random());
		}

		//check if theme preview
		$theme = $this->request->get['theme'] ?? false;

		if ($theme) {
			//check if admin user to allow theme preview
			$admin = Admin::current();

			if ($admin) {
				$this->view->setTheme($theme);
			}
		}

		$site = siteSettings();
		$user = User::current();

		$this->global['site_id'] = SITE_ID ?? 1;
		$this->global['user_id'] = $user['user_id'] ?? false;
		$this->global['site']    = $site;
		$this->global['user']    = $user ?? [];

		$this->language($site['language'] ?? 'en_US', $site['language_id'] ?? 1);
		$this->currency($site['currency'] ?? 'USD', $site['currency_id'] ?? 1);

		$this->view->global = $this->global;

		if (isset($site['country_id'])) {
			$this->initEcommerce($site['country_id'], $site['region_id']);
		}

		$view = View :: getInstance();

		if ($errors = $this->session->get('errors')) {
			$view->errors[] = $errors;
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			$view->success[] = $success;
			$this->session->delete('success');
		}

		if (\Vvveb\isEditor()) {
			$this->view->errors[]  = 'This is a dummy error message!';
			$this->view->success[] = 'This is a dummy success message!';
			$this->view->info[]    = 'This is a dummy info message!';
			$this->view->message[] = 'This is a dummy message!';
		}
	}

	protected function redirect($url = '/', $parameters = []) {
		$redirect = \Vvveb\url($url, $parameters);

		if ($redirect) {
			$url = $redirect;
		}

		if ($this->session) {
			$this->session->close();
		}

		FrontController::closeConnections();

		die(header("Location: $url"));
	}

	/**
	 * Call this method if the action requires login, if the user is not logged in, a login form will be shown.
	 *
	 */
	protected function requireLogin() {
		$view = view :: getInstance();
		$view :: template('/login.html');

		die(view :: getInstance()->render());
	}

	/**
	 * Call this function if the requeste information was not found, for example if the specifed news, image, profile etc is not found then call this function.
	 * It shows a "Not found" page and it also send 404 http status code, this is usefull for search engines etc.
	 *
	 * @param unknown_type $code
	 * @param mixed $service
	 * @param mixed $statusCode
	 * @param null|mixed $message
	 */
	protected function notFound($service = false, $message = null, $statusCode = 404) {
		return FrontController::notFound($service, $message, $statusCode);
	}
}
