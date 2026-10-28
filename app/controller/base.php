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

use function Vvveb\filter;
use function Vvveb\siteSettings;
use Vvveb\System\Core\FrontController;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\Response;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Functions\Str;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use Vvveb\System\PageCache;
use Vvveb\System\Session;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class Base {
	/** @var array<string, mixed> */
	protected array $global = [];

	/** @var Request Request instance injected by FrontController DI. */
	public Request $request;

	/** @var Response Response instance injected by FrontController DI. */
	public Response $response;

	/** @var View View instance injected by FrontController DI. */
	public View $view;

	/** @var Session Session instance injected by FrontController DI. */
	public Session $session;

	/**
	 * Set the current language based on request/session/default values.
	 *
	 * @param string|false                             $default_language
	 * @param int|false                                $default_language_id
	 * @param string|false                             $default_locale
	 * @param string|false                             $default_code
	 * @param array<string, array<string, mixed>>|null $languages
	 *
	 * @return void
	 */
	protected function language(string|false $default_language = false, int|false $default_language_id = false, string|false $default_locale = false, string|false $default_code = false, ?array $languages = null): void {
		$languages ??= Locale::availableLanguages();

		$default_rtl   = false;
		$language      = false;
		$language_id   = $default_language_id;
		$locale        = $default_locale;
		$code          = $default_code;
		$rtl           = $default_rtl;

		//language can be set with a post or get request, post also persists it in the session
		//a get request takes precedence over post for the current page
		$postLanguage = null;
		$getLanguage  = null;

		if (($lang = ($this->request->post['language'] ?? false)) && !is_array($lang)) {
			$lang = filter('/[A-Za-z_-]+/', $lang, 10);

			if (isset($languages[$lang])) {
				$postLanguage = $lang;
			}
		}

		if (($lang = ($this->request->get['language'] ?? false)) && !is_array($lang)) {
			$lang = filter('/[A-Za-z_-]+/', $lang, 10);

			if (isset($languages[$lang])) {
				$getLanguage = $lang;
			}
		}

		if ($postLanguage) {
			$lg = $languages[$postLanguage];
			$this->session->set('language', $postLanguage);
			$this->session->set('language_id', $lg['language_id']);
			$this->session->set('locale', $lg['locale']);
			$this->session->set('code', $lg['code']);
			$this->session->set('rtl', $lg['rtl'] ?? false);
		}

		if ($requestLanguage = ($getLanguage ?? $postLanguage)) {
			$lg          = $languages[$requestLanguage];
			$language    = $requestLanguage;
			$language_id = $lg['language_id'];
			$locale      = $lg['locale'];
			$code        = $lg['code'];
			$rtl         = $lg['rtl'] ?? false;
		} else {
			//don't set user session language for default language homepage
			if (!isset($this->request->get['module']) || $this->request->get['module'] !== 'index/index') {
				$language    = $this->session->get('language') ?? $default_language;
				$language_id = $this->session->get('language_id') ?? $default_language_id;
				$locale      = $this->session->get('locale') ?? $default_locale;
				$code        = $this->session->get('code') ?? $default_code;
				$rtl         = $this->session->get('rtl') ?? false;
			}
		}

		//if no language configured then set default language as current language
		if (!$language) {
			$language    = $default_language;
			$language_id = $default_language_id;
			$locale      = $default_locale;
			$code        = $default_code;
			$rtl         = $default_rtl;
		}

		//if no default language configured then set first language as current language
		if (!isset($languages[$language])) {
			$language            = null;
			$default_language    = key($languages);
			$lang                = $languages[$default_language] ?? [];

			if ($lang) {
				$language_id = $default_language_id = $lang['language_id'];
				$locale      = $default_locale      = $lang['locale'];
				$code        = $default_code        = $lang['code'];
				$rtl         = $default_rtl         = $lang['rtl'] ?? false;
			}
		}

		$this->global['language']            = $language;
		$this->global['locale']              = $locale;
		$this->global['code']                = $code;
		$this->global['language_id']         = $language_id;
		$this->global['rtl']                 = $rtl;
		$this->global['default_language']    = $default_language;
		$this->global['default_locale']      = $default_locale;
		$this->global['default_language_id'] = $default_language_id;

		Locale::setLanguage($code);
	}

	/**
	 * Set the current currency based on request/session/default values.
	 *
	 * @param string|false $defaultCurrency
	 * @param int|false    $defaultCurrencyId
	 *
	 * @return void
	 */
	protected function currency(string|false $defaultCurrency = false, int|false $defaultCurrencyId = false): void {
		if (($currency = ($this->request->post['currency'] ?? false)) && !is_array($currency)) {
			$currency   = filter('/[A-Za-z_-]+/', $currency, 50);
			$currencies = Locale::availableCurrencies();

			if (isset($currencies[$currency])) {
				$this->session->set('currency_id', $currencies[$currency]['currency_id']);
				$this->session->set('currency', $currency);

				\Vvveb\System\Cart\Cart::getInstance($this->global)->updateCart();
			}
		}

		$currency    = $this->session->get('currency');
		$currency_id = $this->session->get('currency_id');

		if (!$currency || !$currency_id) {
			$currencies = Locale::availableCurrencies();

			if ($currencies) {
				foreach ($currencies as $code => $c) {
					if (($defaultCurrency && $defaultCurrency === $c['code']) ||
					   ($defaultCurrencyId && $defaultCurrencyId == $c['currency_id'])) {
						$currency    = $c['code'];
						$currency_id = $c['currency_id'];

						break;
					}
				}

				//if no site currency configured set first available currency
				if (!$currency) {
					$c           = reset($currencies);
					$currency    = $c['code'];
					$currency_id = $c['currency_id'];
				}
			}

			$this->session->set('currency', $currency);
			$this->session->set('currency_id', $currency_id);
		}

		$this->global['currency']            = $currency;
		$this->global['currency_id']         = $currency_id;
	}

	/**
	 * Initialize ecommerce tax settings for the given region.
	 *
	 * @param int $countryId
	 * @param int $regionId
	 *
	 * @return void
	 */
	protected function initEcommerce(int $countryId, int $regionId): void {
		$tax = \Vvveb\System\Cart\Tax::getInstance();
		$tax->setRegionRules($countryId, $regionId, 'store', $this->global['user_group_id'] ?? null);
	}

	/**
	 * Get the global template variables.
	 *
	 * @return array<string, mixed>
	 */
	public function getGlobal(): array {
		return $this->global;
	}

	/**
	 * Initialize the controller: sets up session, user data, site settings, language, and currency.
	 *
	 * @return string|null
	 */
	public function init(): ?string {
		if (!$this->session->get('csrf')) {
			$this->session->set('csrf', Str::random());
		}

		$admin = Admin::current();

		//check if theme preview
		$theme = $this->request->get['theme'] ?? false;

		if ($theme) {
			//check if admin user to allow theme preview
			if ($admin) {
				$this->view->setTheme($theme);
			}
		}

		$siteData = Sites::getSiteData(SITE_ID);

		if (isset($siteData['state']) && $siteData['state'] !== 'live') {
			if (!$admin) {
				$template = Sites::getStates()[$siteData['state']]['template'];
				$this->view->template($template);
				$this->response->output();

				return '';
			}
		}

		$site = siteSettings();

		\Vvveb\System\Cart\Tax::getInstance()->pricesIncludeTax = !empty($site['prices_include_tax']);

		[$site] = Event::trigger(__CLASS__, __FUNCTION__, $site);

		$user = User::current();

		if ($user && isset($user['avatar'])) {
			$user['avatar_url'] = Images::image($user['avatar'], 'user');
		}

		if ($admin && isset($admin['avatar'])) {
			$admin['avatar_url'] = Images::image($admin['avatar'], 'admin');
		}

		$site['url'] = $siteData['host'] ?? ('//' . ($_SERVER['HTTP_HOST'] ?? ''));

		$this->global['site_id']       = SITE_ID;
		$this->global['user_id']       = $user['user_id'] ?? false;
		$this->global['user_group_id'] = $user['user_group_id'] ?? 1;
		$this->global['site']          = &$site;
		$this->global['path']          = V_SUBDIR_INSTALL . (V_SUBDIR_INSTALL && SITE_PATH ? '/' : '') . SITE_PATH;
		$this->global['user']          = $user;
		$this->global['admin']         = $admin;
		$this->global['current_year']  = date('Y');

		$languages = Locale::availableLanguages();
		$language  = $languages[$site['language'] ?? 'en'] ?? ['slug' => 'en', 'language_id' => 1, 'locale' => 'en', 'code' => 'en_US'];

		$this->language($language['slug'], $language['language_id'], $language['locale'], $language['code']);
		$this->currency($site['currency'] ?? false, $site['currency_id'] ?? false);

		$language_id = $this->global['language_id'];

		if (isset($site['description'][$language_id])) {
			$site['description'] = $site['description'][$language_id];
		}

		if (isset($site['country_id'])) {
			$this->initEcommerce($site['country_id'], $site['region_id']);
		}

		$view         = $this->view;
		$view->global = $this->global;

		if ($errors = $this->session->get('errors')) {
			if (is_array($errors)) {
				$view->errors = ($view->errors ?? []) + $errors;
			} else {
				$view->errors['session'] = $errors;
			}
			$this->session->delete('errors');
		}

		if ($success = $this->session->get('success')) {
			if (is_array($success)) {
				$view->success = ($view->success ?? []) + $success;
			} else {
				$view->success['session'] = $success;
			}
			$this->session->delete('success');
		}

		if (\Vvveb\isEditor()) {
			$this->view->errors[]  = 'This is a dummy error message!';
			$this->view->success[] = 'This is a dummy success message!';
			$this->view->info[]    = 'This is a dummy info message!';
			if (!isset($this->view->message)) {
				$this->view->message[] = 'This is a dummy message!';
			}
		}

		[$this->global] = Event::trigger(__CLASS__, __FUNCTION__ . ':after', $this->global);

		return null;
	}

	/**
	 * Redirect to the given URL with optional query parameters.
	 *
	 * @param string|array<string, mixed> $url
	 * @param array<string, mixed>        $parameters
	 *
	 * @return never
	 */
	protected function redirect(string|array $url = '/', array $parameters = []): never {
		$redirect = \Vvveb\url($url, $parameters);

		if ($redirect) {
			$url = $redirect;
		}

		if (isset($this->session)) {
			$this->session->close();
		}

		FrontController::closeConnections();
		PageCache::getInstance()->cleanUp();

		header("Location: $url");

		exit();
	}

	/**
	 * Call this method if the action requires login; shows a login form if the user is not logged in.
	 *
	 * @return never
	 */
	protected function requireLogin(): never {
		$this->view->template(DS . 'login.html');
		$this->view->render();
		exit();
	}

	/**
	 * Show a "Not found" page and send a 404 (or other) HTTP status code.
	 *
	 * @param array<string, mixed>|string|false $message
	 * @param int                               $statusCode
	 * @param mixed                             $service
	 *
	 * @return never
	 */
	protected function notFound(string|array|false $message = false, int $statusCode = 404, mixed $service = false): never {
		FrontController::notFound($service, $message, $statusCode);
	}
}
