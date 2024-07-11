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

use function Vvveb\__;
use function Vvveb\arrayInsertArrayAfter;
use function Vvveb\availableCurrencies;
use function Vvveb\availableLanguages;
use function Vvveb\clearLanguageCache;
use function Vvveb\filter;
use function Vvveb\setLanguage;
use Vvveb\Sql\taxonomySQL;
use Vvveb\System\Core\FrontController;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Functions\Str;
use Vvveb\System\Images;
use Vvveb\System\PageCache;
use Vvveb\System\Session;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;

#[\AllowDynamicProperties]
class Base {
	public $view;

	public $request;

	public $sesssion;

	protected $global;

	protected function setSite($site_id = false) {
		//if no id set default
		if ($site_id) {
			$site  = Sites::getSiteById($site_id);
		}
		
		if (!$site_id || !$site) {
			$site = Sites::getDefault();
		}

		$site_id = $site['id'];
		$this->session->set('site', $site);
		$this->session->set('site_id', $site_id);
		$this->session->set('site_url', $site['url']);
		$this->session->set('site', $site['id']);
		$this->session->set('host', $site['host']);
		$this->session->set('state', $site['state'] ?? 'live');

		return $site_id;
	}

	protected function getTaxonomies() {
		$categories  = new taxonomySQL();
		$taxonomies  = $categories->getAll($this->global);

		return $taxonomies['taxonomy'] ?? [];
	}

	protected function customPost() {
		//custom posts -- add to menu
		$default_custom_posts =
		[
			'post' => [
				'type'    => 'post',
				'plural'  => 'posts',
				'icon'    => 'icon-document-text-outline',
				'comments'=> true,
			],
			'page' => [
				'type'    => 'page',
				'plural'  => 'pages',
				'icon'    => 'icon-document-outline',
				'comments'=> false,
			],
		];

		$custom_posts_types        = \Vvveb\getSetting('post', 'types', []);
		$custom_posts_types       += $default_custom_posts;
		list($custom_posts_types) = Event::trigger(__CLASS__, __FUNCTION__, $custom_posts_types);

		$custom_post_menu = \Vvveb\config('custom-post-menu', []);
		$posts_menu       = [];

		foreach ($custom_posts_types as $type => $settings) {
			if ($type == 'page') {
				continue;
			}
			$posts_menu[$type] = $custom_post_menu;

			$posts_menu[$type]['name']                   =
			$posts_menu[$type]['items']['posts']['name'] =
			__(ucfirst($settings['plural']));

			$posts_menu[$type]['icon']     = $settings['icon'] ?? '';
			$posts_menu[$type]['icon-img'] = (isset($settings['icon-img']) && $settings['icon-img']) ? Images::image($settings['icon-img'], $type) : '';
			$posts_menu[$type]['url'] .= "&type=$type";

			if (isset($settings['comments']) && ! $settings['comments']) {
				unset($posts_menu[$type]['items']['comments']);
			}

			foreach ($posts_menu[$type]['items'] as $item => &$values) {
				if (isset($values['url'])) {
					$values['url'] .= "&type=$type";
				}
			}

			$admin_path         = \Vvveb\adminPath();
			//add taxonomies for post type
			foreach ($this->taxonomies as $taxonomy) {
				if ($taxonomy['post_type'] != $type) {
					continue;
				}
				$key    = $taxonomy['post_type'] . '_' . $taxonomy['type'] . $taxonomy['taxonomy_id'];
				$module = "content/{$taxonomy['type']}";
				$icon   = $taxonomy['type'] == 'tags' ? 'la la-tags' : 'la la-boxes';
				$tax    = [$key => [
					'name' => __($taxonomy['name']),
					//'subtitle' => __('(Flat)'),
					'url'    => "{$admin_path}index.php?module=$module&type={$taxonomy['post_type']}&taxonomy_id={$taxonomy['taxonomy_id']}",
					'module' => $module,
					'action' => 'index',
					'icon'   => $icon,
				]];

				$posts_menu[$type]['items'] = arrayInsertArrayAfter('taxonomy-heading', $posts_menu[$type]['items'], $tax);
			}
		}

		return $posts_menu;
	}

	protected function customProduct() {
		//custom products -- add to menu
		$default_custom_products =
		[
			'product' => [
				'type'   => 'product',
				'plural' => 'products',
				'icon'   => 'icon-cube-outline',
			],
		];

		$custom_products_types       = \Vvveb\getSetting('product', 'types', []);
		$custom_products_types      += $default_custom_products;
		list($custom_products_types) = Event::trigger(__CLASS__, __FUNCTION__, $custom_products_types);

		$custom_product_menu = \Vvveb\config('custom-product-menu', []);
		$products_menu       = [];

		foreach ($custom_products_types as $type => $settings) {
			if ($type == 'page') {
				continue;
			}
			$products_menu[$type] = $custom_product_menu;

			$products_menu[$type]['name']                      =
			$products_menu[$type]['items']['products']['name'] =
			__(ucfirst($settings['plural']));

			$products_menu[$type]['icon']     = $settings['icon'] ?? '';
			$products_menu[$type]['icon-img'] = (isset($settings['icon-img']) && $settings['icon-img']) ? Images::image($settings['icon-img'], $type) : '';
			$products_menu[$type]['url'] .= "&type=$type";

			foreach ($products_menu[$type]['items'] as $item => &$values) {
				if (isset($values['url'])) {
					$values['url'] .= "&type=$type";
				}
			}

			$admin_path         = \Vvveb\adminPath();
			//add taxonomies for post type
			foreach ($this->taxonomies as $taxonomy) {
				if ($taxonomy['post_type'] != $type) {
					continue;
				}
				$key = $taxonomy['post_type'] . '_' . $taxonomy['type'] . $taxonomy['taxonomy_id'];

				$module = "content/{$taxonomy['type']}";
				$icon   = $taxonomy['type'] == 'tags' ? 'la la-tags' : 'la la-boxes';
				$tax    = [$key => [
					'name' => __($taxonomy['name']),
					//'subtitle' => __('(Flat)'),
					'url'    => "{$admin_path}index.php?module=$module&type={$taxonomy['post_type']}&taxonomy_id={$taxonomy['taxonomy_id']}",
					'module' => $module,
					'action' => 'index',
					'icon'   => $icon,
				]];

				$products_menu[$type]['items'] = arrayInsertArrayAfter('taxonomy-heading', $products_menu[$type]['items'], $tax);
			}
		}

		return $products_menu;
	}

	/*
	 * Permission check for each module/action
	 */
	protected function permissions() {
		$module     = strtolower(FrontController::getModuleName());
		$action     = strtolower(FrontController::getActionName());
		$action     = $action ? '/' . $action : '';
		$permission = $module . $action;

		//if current module/action does not have permission then show permission denied page
		if (! Admin::hasPermission($permission)) {
			$message              = __('Your role does not have permission to access this action!');
			$this->view->errors[] = $message;

			die($this->notFound(true, $message, 403));
		}

		//get current controller methods to check for permission
		$methods = get_class_methods($this);
		//$methods = array_map(fn ($value) => "$module/$value", $methods);
		$methods = array_map(function ($value) use ($module) {return "$module/$value"; }, $methods);

		//check if controller requires additional permission check
		if (isset($this->additionalPermissionCheck)) {
			$methods = array_merge($methods, $this->additionalPermissionCheck);
		}

		$permissions = Admin::hasPermission($methods);

		//set a permission array only with action keys for easier permission check in html
		$this->modulePermissions = $permissions;

		foreach ($permissions as $permission => &$value) {
			$key                     = str_replace("$module/", '', $permission);
			$actionPermissions[$key] = $value;
		}
		$this->actionPermissions = $actionPermissions;
	}

	protected function getPermissionsFromUrl(&$array, &$permissions) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				if (isset($v['url'])) {
					if (isset($v['module'])) {
						$permissions[$v['url']] = ($v['module'] ?? '') . (isset($v['action']) ? '/' . $v['action'] : '');
					} else {
						$permissions[$v['url']] = \Vvveb\pregMatch('/module=([^&$]+)/', $v['url'], 1);
					}
				}
				$this->getPermissionsFromUrl($v, $permissions);
			}
		}
	}

	protected function setPermissionsFromUrl(&$array, &$permissions) {
		foreach ($array as $k => &$v) {
			if (is_array($v)) {
				if (isset($v['url'])) {
					$url = $v['url'];

					if (isset($permissions[$url])) {
						$v['permission'] = $permissions[$url];
					}
				}
				$this->setPermissionsFromUrl($v, $permissions);
			}
		}
	}

	protected function language($defaultLanguage = false, $defaultLanguageId = false) {
		$languages = availableLanguages();

		$default_language    = $this->session->get('default_language');
		$default_language_id = $this->session->get('default_language_id');
		$language            = $this->session->get('language');
		$language_id         = $this->session->get('language_id');
		$site_language       = false;

		if (($lang = ($this->request->post['language'] ?? false)) && ! is_array($lang)) {
			$language  = filter('/[A-Za-z_-]+/', $lang, 50);
			$this->session->set('language', $language);
			$this->session->set('language_id', $languages[$language]['language_id']);
			$default_language = false; //recheck default language
			clearLanguageCache($language);
		}

		if (! $default_language) {
			foreach ($languages as $code => $lang) {
				//set site language
				if ($defaultLanguageId && ($defaultLanguageId == $lang['language_id'])) {
					$site_language    = $code;
					$site_language_id = $lang['language_id'];
				}
				//set global default language
				if ($lang['default']) {
					$default_language    = $code;
					$default_language_id = $lang['language_id'];

					break;
				}
			}

			if ($site_language) {
				$default_language    = $site_language;
				$default_language_id = $site_language_id;
			}

			//no valid default site or global language? set english as default
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

		$language    = $request->request['language'] ?? $this->session->get('language') ?? 'en_US';
		$language_id = $request->request['language_id'] ?? $this->session->get('language_id') ?? $defaultLanguageId;

		$this->global['language']            = $language;
		$this->global['language_id']         = $language_id;
		$this->global['default_language']    = $default_language;
		$this->global['default_language_id'] = $default_language_id;

		setLanguage($language);

		if (! defined('CLI')) {
			$view                = $this->view;
			$view->languagesList = $languages;
		}
	}

	protected function currency($defaultCurrency = false, $defaultCurrencyId = false) {
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

		if (! $currency || ! $currency_id) {
			$currencies = availableCurrencies();

			if ($currencies) {
				foreach ($currencies as $code => $c) {
					if ($defaultCurrency && ($defaultCurrency == $c['code']) ||
					   ($defaultCurrencyId && ($defaultCurrencyId == $c['currency_id']))) {
						$currency    = $c['code'];
						$currency_id = $c['currency_id'];

						break;
					}
				}

				//if no site currency configured set first available currency
				if (! $currency) {
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

	function init() {
		//$this->session->delete('csrf');
		//$this->session = Session::getInstance();
		//$this->request = Request::getInstance();
		$view = View :: getInstance();
		$view->removeVattrs(false);

		if (isset($this->request->get['errors']) && $this->request->get['errors']) {
			$view->errors['get'] = htmlentities($this->request->get['errors']);
		}

		if (isset($this->request->get['success']) && $this->request->get['success']) {
			$view->success['get'] = htmlentities($this->request->get['success']);
		}

		//prevent admin loading in iframe
		$this->response->addHeader('X-Frame-Options', 'SAMEORIGIN');
		$this->response->addHeader('X-Content-Type-Options', 'nosniff');

		$admin = Admin::current();

		if (! $admin) {
			return $this->requireLogin();
		}

		if (! $this->session->get('csrf')) {
			$this->session->set('csrf', Str::random());
		}

		if (($site_id = ($this->request->post['site'] ?? false)) && is_numeric($site_id)) {
			$this->setSite($site_id);
		}

		$site_id = $this->session->get('site_id');

		if (! $site_id) {
			$site_id = $this->setSite();
		}

		$this->language();
		$this->currency();

		//change site status (live, under maintenance etc)
		if ($state = ($this->request->post['state'] ?? false)) {
			if (Admin::hasPermission('settings/site/save')) {
				if (Sites::setSiteDataById($site_id, 'state', $state)) {
					$this->session->set('state', $state);
					PageCache::getInstance()->purge();
				}
			} else {
				$message              = __('Your role does not have permission to access this action!');
				$this->view->errors[] = $message;
			}
		}

		$page        = $this->request->get['page'] ?? 1;
		$limit       = $this->request->get['limit'] ?? 10;

		$this->global['site_id']  = $site_id;
		$this->global['host']     = $this->session->get('host');
		$this->global['site_url'] = $this->session->get('site_url');
		$this->global['admin_id'] = $admin['admin_id'];
		$this->global['state']    = $state;
		$this->global['page']     = $page;
		$this->global['start']    = ($page - 1) * $limit;
		$this->global['limit']    = $limit;

		//Check permissions
		$className = get_class($this);

		if ($className != 'Vvveb\Controller\Error403') {
			$this->permissions();
		}

		//load plugins for active site if safe mode is not selected
		if (! isset($admin['safemode']) || ! $admin['safemode']) {
			Plugins :: loadPlugins($site_id);
		}

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

		$menu             = \Vvveb\config('admin-menu', []);

		//don't initialize menu items for CLI
		if (defined('CLI')) {
			return;
		}

		//send to view for button visibillity check
		$this->view->actionPermissions = $this->actionPermissions ?? [];
		$this->view->modulePermissions = $this->modulePermissions ?? [];

		//custom posts -- add to menu
		$this->taxonomies = $this->getTaxonomies();
		$posts_menu       = $this->customPost();
		$menu             = arrayInsertArrayAfter('edit', $menu, $posts_menu);

		//products - add to menu
		$products_menu = $this->customProduct();
		$menu          = arrayInsertArrayAfter('sales', $menu, $products_menu);

		list($menu)       = Event::trigger(__CLASS__, __FUNCTION__ . '-menu', $menu);

		$urls = [];
		$this->getPermissionsFromUrl($menu, $urls);
		$permissions = Admin::hasPermission($urls);
		//$urls        = array_map(fn ($value) => $value ? ($permissions[$value] ?? false) : false, $urls);
		$urls        = array_map(function ($value) use ($permissions) { return $value ? ($permissions[$value] ?? false) : false; }, $urls);
		$this->setPermissionsFromUrl($menu, $urls);

		$view->menu       = $menu;

		$adminPath        = \Vvveb\adminPath();
		$view->adminPath  = $adminPath;
		$view->mediaPath  = PUBLIC_PATH . 'media';
		$view->publicPath = PUBLIC_PATH . 'media';
	}

	protected function redirect($url = '/', $parameters = [], $stop = true) {
		$redirect = \Vvveb\url($url, $parameters);

		if ($redirect) {
			$url = $redirect;
		}

		header("Location: $url");

		if ($stop) {
			$this->session->close();
			FrontController::closeConnections();
			PageCache::getInstance()->cleanUp();

			die();
		}
	}

	/**
	 * Call this method if the action requires login, if the user is not logged in, a login form will be shown.
	 *
	 */
	protected function requireLogin() {
		//return \Vvveb\System\Core\FrontController::redirect('user/login');
		//$view = view :: getInstance();
		$admin_path            = \Vvveb\adminPath();
		$this->view->redir     = $_SERVER['REQUEST_URI'] ?? '';
		$this->view->adminPath = $admin_path;
		$this->view->action    = "{$admin_path}index.php?module=user/login";
		$this->view->template('user/login.html');

		die($this->view->render());
	}

	/**
	 * Shows a "Not found", "Internal server error" or "Permission denied" page.
	 *
	 * @param unknown_type $code
	 * @param mixed $statusCode
	 * @param mixed $service
	 * @param mixed $message
	 */
	protected function notFound($service = false, $message = false, $statusCode = 404) {
		return FrontController::notFound($service, $message, $statusCode);
	}

	/**
	 * Generates the documentation link for current page.
	 *
	 * @param unknown_type $code
	 * @param null|mixed $module
	 * @param null|mixed $action
	 */
	protected function getDocUrlForPage($module = null, $action = null) {
		$module = $module ?? $this->request->get['module'] ?? '';
		$action = $action ?? $this->request->get['origaction'] ?? '';
		$type   = $type ?? $this->request->get['type'] ?? '';
		$action = $action ? '/' . $action : '';
		$url    = 'https://docs.vvveb.com/';

		if ($type == 'post' || $type == 'page'/* || $type == 'product'*/) {
			$type   = $type ? '/' . $type : '';
		} else {
			$type = '';
		}

		$documentionList             = include DIR_SYSTEM . 'data/documentation-map.php';

		if (isset($documentionList[$module . $action . $type])) {
			$url .= $documentionList[$module . $action . $type];
		} else {
			$url .= str_replace('/', '-', $module . $action . $type);
		}

		return $url;
	}

	function goToHelp() {
		$url = $this->getDocUrlForPage();

		return header("Location: $url");

		die($url);
	}
}
