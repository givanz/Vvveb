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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\friendlyDate;
use function Vvveb\getTemplateList;
use function Vvveb\slugify;
use Vvveb\Sql\RegionSQL;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Cache;
use Vvveb\System\CacheManager;
use Vvveb\System\Event;
use Vvveb\System\Extensions\Themes;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use Vvveb\System\Media\Image;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;
use Vvveb\System\Sites;
use Vvveb\System\User\Admin;
use Vvveb\System\User\Role;
use Vvveb\System\Validator;
use function Vvveb\url;

class Site extends Base {
	/**
	 * Output a formatted date for preview.
	 *
	 * @return never
	 */
	public function dateFormat(): never {
		$format = $this->request->get['format'] ?? false;

		exit(date($format));
	}

	/**
	 * Generate an invoice format preview string.
	 *
	 * @param string $format The invoice format pattern
	 *
	 * @return string The formatted preview string
	 */
	private function invoiceFormatPreview(string $format): string {
		$data = ['order_id' => 777, 'customer_order_id' => 'OI888', 'user_id' => 1000, 'site_id' => 1];

		return \Vvveb\invoiceFormat($format, $data);
	}

	/**
	 * Output an invoice format preview for AJAX.
	 *
	 * @return never
	 */
	public function invoiceFormat(): never {
		$format = $this->request->get['format'] ?? false;

		if ($format) {
			$format = $this->invoiceFormatPreview($format);
		}

		exit($format);
	}

	/**
	 * Output an order ID format preview for AJAX.
	 *
	 * @return never
	 */
	public function orderIdFormat(): never {
		$format = $this->request->get['format'] ?? false;

		if ($format) {
			$format = $this->invoiceFormatPreview($format);
		}

		exit($format);
	}

	/**
	 * Return regions for a given country as JSON.
	 *
	 * @return void
	 */
	public function regions(): void {
		$country_id = $this->request->get['country_id'] ?? false;
		$regions    = [];

		if ($country_id) {
			$region            = new RegionSQL();
			$options           = $this->global;
			$options['status'] = 1;
			unset($options['limit']);
			$options['country_id'] = $country_id;
			$regions               = $region->getAll($options)['region'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($regions);
	}

	/**
	 * Alias for save().
	 *
	 * @return void
	 */
	public function add(): void {
		$this->save();
	}

	/**
	 * Get template file list for a given theme.
	 *
	 * @param string $theme The theme name
	 *
	 * @return array<int|string, mixed> The template file list
	 */
	private function themeTemplates(string $theme): array {
		$templates = getTemplateList($theme, ['email']);

		$files = ['index.html' => 'Home page'];
		foreach ($templates as $template) {
			if ($template['folder']) {
				$files[$template['folder']][$template['folder'] . '/' . $template['filename']] = $template['title'];
			} else {
				$files[$template['filename']] = $template['title'];
			}
		}

		return $files;
	}

	/**
	 * Return theme templates as JSON for AJAX.
	 *
	 * @return array<int|string, mixed>|null The template files or null if no theme
	 */
	public function getThemeTemplates(): ?array {
		$theme = $this->request->get['theme'];
		if ($theme) {
			$files = $this->themeTemplates($theme);

			$this->response->setType('json');

			return $files;
		}

		return null;
	}

	/**
	 * Save a site (create or update) with settings, routes, and templates.
	 *
	 * @return void
	 */
	public function save(): void {
		$site_id          = $this->request->get['site_id'] ?? false;
		$edit_other_sites = Admin::hasCapability('edit_other_sites');

		if (!$edit_other_sites) {
			$access = Admin::siteAccess();
			if (!$site_id || !in_array($site_id, $access)) {
				$this->notFound('Permission denied!', 403);

				return;
			}
		}

		$siteValidator 		    = new Validator(['site']);
		$settingsValidator	  = new Validator(['site-settings']);

		$view       = $this->view;
		$site 	     = $this->request->post['site'] ?? [];
		$settings   = $this->request->post['settings'] ?? [];
		$routes     = $this->request->post['route'] ?? [];
		$routesType = $this->request->post['routes-type'] ?? [];

		$newRoutes = [];
		foreach ($routes as $route) {
			$r = $route['route'];
			unset($route['route']);
			foreach ($route as $key => &$value) {
				if (!$value) {
					unset($route[$key]);
				}
			}

			$newRoutes[$r] = $route;
		}

		if (($errors = $siteValidator->validate($site)) === true &&
			($errors = $settingsValidator->validate($settings)) === true) {
			$sites = new SiteSQL();

			if (!isset($site['host']) || !$site['host']) {
				$site['host'] = slugify($site['name']) . '.*.*';
			}

			if ($this->request->post['site-type'] == 'subfolder') {
				$site['host'] = '*.*.*';
				if (!$site['path']) {
					$site['path'] = slugify($site['name']);
				}
			}

			if ($this->request->post['site-type'] == 'both') {
				if (!$site['host']) {
					$site['host'] = '*.*.*';
				}

				if (!$site['path']) {
					$site['path'] = slugify($site['name']);
				}
			}

			if ($this->request->post['site-type'] == 'subdomain') {
				$site['path'] = '';
			}

			$site['path'] = slugify($site['path']);
			$site['key']  = strtolower(Sites::siteKey($site['host'])) . ($site['path'] ? '/' . $site['path'] : '');

			foreach ($settings['description'] as &$lang) {
				foreach ($lang as $field => &$value) {
					$value = \Vvveb\sanitizeHTML($value);
				}
			}

			foreach ($this->view->languagesList as $slug => $lng) {
				if ($lng['language_id'] == $settings['language_id']) {
					$settings['language'] = $slug;
					break;
				}
			}

			$currencies = Locale :: availableCurrencies();
			foreach ($currencies as $currency_id => $currency) {
				if ($currency['currency_id'] == $settings['currency_id']) {
					$settings['currency'] = $currency_id;
					break;
				}
			}

			//array_walk_recursive($settings['description'], '\Vvveb\sanitizeHTML');

			if ($site_id) {
				$data['site_id']  = (int)$site_id;
				$site['settings'] = json_encode($settings);
				$data['site']     = $site;
				$site['site_id']  = $data['site_id'];
				unset($data['site']['template']);
				$result           = $sites->edit($data);

				//Sites::saveSite($site);
				unset($site['settings']);

				Sites::setSiteDataById($data['site_id'], null, $site);

				[$site, $settings, $site_id, $data] = Event :: trigger(__CLASS__, __FUNCTION__, $site, $settings, $site_id, $data);

				if ($result >= 0) {
					//CacheManager::delete('site');
					$siteRoutes = DIR_CONFIG . "site-$site_id-routes.php";

					if ($routesType == 'site') {
						file_put_contents($siteRoutes, '<?php return ' . var_export($newRoutes, true) . ';');
					} else {
						if (file_exists($siteRoutes)) {
							unlink($siteRoutes);
						}
					}

					CacheManager::delete();
					$message              = __('Site saved!');
					$view->success['get'] = $message;
					//$this->redirect(['module'=>'settings/sites', 'success'=> $message]);
				} else {
					$view->errors = ($sites->error ?? []) ?: [];
				}
			} else {
				$newHomepage  = $this->request->post['new-homepage'] ?? [];
				if ($newHomepage) {
					foreach ($newHomepage as $languageId) {
						if ($languageId == $settings['language_id']) {
							continue;
						}

						$template    = $site['template'][$languageId] ?: 'index.html';
						$newTemplate = str_replace('.html', '', $template) . '-' . slugify($site['name']);

						if ($languageId) {
							$lang = $languageId;
							foreach ($this->view->languagesList as $slug => $lng) {
								if ($lng['language_id'] == $languageId) {
									$lang = $slug;
									break;
								}
							}

							$newTemplate .= '-' . $lang;
						}

						$newTemplate .= '.html';
						$theme        = $site['theme'];

						$source = DIR_THEMES . $theme . DS . $template;
						$dest   = DIR_THEMES . $theme . DS . $newTemplate;

						if (file_exists($source)) {
							$html = file_get_contents($source);
							if (file_put_contents($dest, $html)) {
								$site['template'][$languageId] = $newTemplate;
							}
						}
					}
				}

				foreach ($site['template'] as $id => $template) {
					if (!$template) {
						unset($site['template'][$id]);
					}
				}

				$data['site']             = $site;
				$data['site']['settings'] = json_encode($settings);
				$return                   = $sites->add($data);
				$site_id                  = $return['site'];
				$site['state']            = 'live';
				$site['site_id']          = $site_id;
				Sites::saveSite($site);

				[$site, $settings, $site_id, $data] = Event :: trigger(__CLASS__, __FUNCTION__, $site, $settings, $site_id, $data);

				if ($site_id) {
					$siteRoutes = DIR_CONFIG . "site-$site_id-routes.php";

					if ($routesType == 'site') {
						file_put_contents($siteRoutes, '<?php return ' . var_export($newRoutes, true) . ';');
					} else {
						if (file_exists($siteRoutes)) {
							unlink($siteRoutes);
						}
					}

					//CacheManager::delete('site');
					CacheManager::delete();
					$message              = __('Site saved!');
					$view->success['get'] = $message;
					$view->errors         = ($sites->error ?? []) ?: [];
					$this->redirect(['module' => 'settings/site', 'success' => $message, 'site_id' => $site_id]);
				} else {
					$view->errors = ($sites->error ?? []) ?: [];
				}
			}
		} else {
			$view->errors = $errors;
		}

		$this->index();
	}

	/**
	 * Display the site edit/create form with all configuration data.
	 *
	 * @return void
	 */
	public function index(): void {
		$themeList = Themes:: getList();

		$site_id            = $this->request->get['site_id'] ?? null;
		$view               = $this->view;
		$site               = [];
		$siteSQL            = new SiteSQL();

		if ($site_id) {
			$view_other_sites = Admin::hasCapability('view_other_sites');

			if (!$view_other_sites) {
				$access = Admin::siteAccess();
				if (!in_array($site_id, $access)) {
					$this->notFound('Permission denied!', 403);

					return;
				}
			}

			$site = $siteSQL->get(['site_id' => $site_id]);
			$data = Sites::getSiteById($site_id);

			if (!$site || !$data) {
				$this->notFound();
			}

			$site = $data + $site;
		}

		$default       = '{"logo":"logo.png","logo-sticky":"logo.png","logo-dark":"logo-white.png","logo-dark-sticky":"logo-white.png","favicon":"favicon.ico","webbanner":"biglogo.png", "country_id":223, "region_id":3655}';
		$setting       = json_decode($site['settings'] ?? $default, true);

		foreach (['favicon', 'logo', 'logo-sticky', 'logo-dark', 'logo-dark-sticky', 'webbanner'] as $img) {
			if (isset($setting[$img])) {
				$setting[$img . '-src'] = Images::image($setting[$img]);
			}
		}

		$data                       = $siteSQL->getData(['language_id' => $this->global['language_id']] + ($setting ?? []) + $this->global);
		$data['complete_status_id'] = $data['processing_status_id'] = ($data['order_status_id'] ?? []);

		$data['timezone'] = [];

		$timestamp = date_create('now');

		$timezones = timezone_identifiers_list();

		foreach ($timezones as $timezone) {
			date_timezone_set($timestamp, timezone_open($timezone));

			$hour = ' (' . date_format($timestamp, 'P') . ')';

			$data['timezone'][$timezone] = $timezone . $hour;
		}

		$admin_path = \Vvveb\adminPath();

		$domain       = Sites::urlSplit();
		//$data['subtract'] = [1 => __('Yes'), 0 => __('No')]; //Subtract stock options
		$date_format = ['F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'];
		$time_format = ['g:i a', 'g:i A', 'H:i'];

		foreach ($date_format as $format) {
			$data['date_format'][$format] = date($format);
		}

		$data['date_format']['human'] = friendlyDate(time() - 360000);

		foreach ($time_format as $format) {
			$data['time_format'][$format] = date($format);
		}

		[$site, $setting, $site_id, $data] = Event :: trigger(__CLASS__, __FUNCTION__, $site, $setting, $site_id, $data);

		if (!defined('CLI')) {
			$view->domain = '';

			if ($domain) {
				$view->domain = ($domain['domain'] ?? '') . ($domain['tld'] ? '.' . $domain['tld'] : '');
			}

			$setting['invoice_format_preview']  = $this->invoiceFormatPreview($setting['invoice_format'] ?? '');
			$setting['order_id_format_preview'] = $this->invoiceFormatPreview($setting['order_id_format'] ?? '');

			$view->set($data);
			//$site['full-url']   = $site ? ('//' . Sites::url($site['host']) . V_SUBDIR_INSTALL  . ($site['path'] ?? '' ? '/' . $site['path'] : '')) : '';
			$site['full-url']     = $site ? ('//' . $site['url']) : '';
			$view->site           = $site + $setting;
			$view->path           = $site['path'] ?? '';
			$view->setting        = $setting;
			$view->resize         = ['cs' => __('Crop & Resize'), 'r' => __('Resize'), 's' => __('Stretch'), 'c' => __('Crop')];
			$view->formats        = Image::formats();
			$view->themeList      = $themeList;
			$view->subdir         = V_SUBDIR_INSTALL ?: '';
			$view->templateList   = getTemplateList(null, ['email']);
			$view->currenciesList = Locale :: availableCurrencies();

			$siteRoutes = DIR_CONFIG . "site-$site_id-routes.php";
			if (file_exists($siteRoutes)) {
				$view->routes 	   = include $siteRoutes;
				$view->routesType = 'site';
			} else {
				$view->routes 	   = include DIR_CONFIG . 'app-routes.php';
				$view->routesType = 'global';
			}

			$view->modules  = Role::controllers('app');

			$cache = Cache::getInstance();

			$view->modules  = $cache->cache(APP, 'app-actions', function() {
				$actions = Role::controllers('app');

				//set home first
				$home = $actions['index'];

				return array_merge($home, $actions);
			}, 259200);

			$payment  = Payment::getInstance();
			$shipping = Shipping::getInstance();

			$checkoutInfo          = [];
			$view->paymentMethods  = $payment->getMethods($checkoutInfo);
			$view->shippingMethods = $shipping->getMethods($checkoutInfo);

			$controllerPath  = $admin_path . 'index.php?module=media/media';
			$view->scanUrl   = "$controllerPath&action=scan";
			$view->uploadUrl = "$controllerPath&action=upload";
			$view->deleteUrl = "$controllerPath&action=delete";
			$view->renameUrl = "$controllerPath&action=rename";
		}
	}
}
