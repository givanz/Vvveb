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
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\regionSQL;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\CacheManager;
use Vvveb\System\Extensions\Themes;
use Vvveb\System\Images;
use Vvveb\System\Sites;
use Vvveb\System\Validator;

class Site extends Base {
	private function data() {
		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country	              = $countryModel->getAll($options);
		$this->view->countries = $country['country'] ?? [];

		//set Regions for default store country
		/*
		$region  = new RegionSQL();
		$regions	 = $countryModel->getAll($options);

		$options['country_id'] = $country_id;
		$this->view->regions = $regions['region'] ?? [];
		*/
		$this->view->regionsUrl   = url(['module' => 'checkout/checkout', 'action' => 'regions']);
	}

	function dateFormat() {
		$format = $this->request->get['format'] ?? false;

		die(date($format));
	}

	function regions() {
		$country_id   = $this->request->get['country_id'] ?? false;
		$regions      = [];

		if ($country_id) {
			$region              = new RegionSQL();
			$options             = $this->global;
			$options['status']   = 1;
			unset($options['limit']);
			$options['country_id']   = $country_id;
			$regions	                = $region->getAll($options)['region'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($regions);
	}

	function save() {
		$siteValidator 		    = new Validator(['site']);
		$settingsValidator	  = new Validator(['site-settings']);

		$view      = $this->view;
		$site 	    = $this->request->post['site'] ?? [];
		$settings  = $this->request->post['settings'] ?? [];

		if (($errors = $siteValidator->validate($site)) === true &&
			($errors = $settingsValidator->validate($settings)) === true) {
			$sites = new SiteSQL();

			if (! isset($site['host']) || ! $site['host']) {
				$site['host'] = strtolower($site['name']) . '.*';
			}

			$site['key'] = strtolower(Sites::siteKey($site['host']));

			if (isset($this->request->get['site_id'])) {
				$data['site_id']  = (int)$this->request->get['site_id'];
				$site['settings'] = json_encode($settings);
				$data['site']     = $site;
				$site['id']       = $data['site_id'];
				$result           = $sites->edit($data);

				//Sites::saveSite($site);
				unset($site['settings']);

				Sites::setSiteDataById($data['site_id'], null, $site);

				if ($result >= 0) {
					//CacheManager::delete('site');
					CacheManager::delete();
					$message             = __('Site saved!');
					$this->view->success = [$message];
				//$this->redirect(['module'=>'settings/sites', 'success'=> $message]);
				} else {
					$this->view->errors = [$sites->error];
				}
			} else {
				$data['site']             = $site;
				$data['site']['settings'] = json_encode($settings);
				$return                   = $sites->add($data);
				$id                       = $return['site'];
				$site['state']            = 'live';
				$site['id']	              = $id;
				Sites::saveSite($site);

				if (! $id) {
					$view->errors = [$sites->error];
				} else {
					//CacheManager::delete('site');
					CacheManager::delete();
					$message       = __('Site saved!');
					$view->success = [$message];
					//$this->redirect(['module'=>'settings/sites', 'success'=> $message]);
				}
			}
		} else {
			$view->errors = $errors;
		}

		$this->index();
	}

	function index() {
		$themeList = Themes:: getList();

		$site_id                   = $this->request->get['site_id'] ?? null;
		$view                      = $this->view;
		$view->themeList           = $themeList;
		$view->templateList        = \Vvveb\getTemplateList(false, ['email']);
		$site                      = [];
		$siteSql                   = new SiteSQL();

		if ($site_id) {
			$site                = $siteSql->get(['site_id' => $site_id]);
		}

		$default	      = '{"logo":"logo.png","logo-sticky":"logo.png","logo-dark":"logo-white.png","logo-dark-sticky":"logo-white.png","favicon":"favicon.ico", "country_id":223, "region_id":3655}';
		$view->setting	= json_decode($site['settings'] ?? $default, true);

		foreach (['favicon', 'logo', 'logo-sticky', 'logo-dark', 'logo-dark-sticky'] as $img) {
			if (isset($view->setting[$img])) {
				$view->setting[$img . '-src'] = Images::image($view->setting[$img]);
			}
		}

		$data             = $siteSql->getData(($view->setting ?? []) + $this->global);
		$data['complete_status_id'] = $data['processing_status_id'] = $data['order_status_id'];

		$data['timezone'] = [];

		$timestamp = date_create('now');

		$timezones = timezone_identifiers_list();

		foreach ($timezones as $timezone) {
			date_timezone_set($timestamp, timezone_open($timezone));

			$hour = ' (' . date_format($timestamp, 'P') . ')';

			$data['timezone'][$timezone] = $timezone . $hour;
		}

		$admin_path          = \Vvveb\adminPath();
		$view->site	         = $site + $view->setting;

		$domain       = Sites::urlSplit();
		$view->domain = '';

		if ($domain) {
			$view->domain = ($domain['domain'] ?? '') . '.' . ($domain['tld'] ?? '');
		}

		//$data['subtract'] = [1 => __('Yes'), 0 => __('No')]; //Subtract stock options
		$date_format = ['F j, Y', 'Y-m-d', 'm/d/Y', 'd/m/Y'];
		$time_format = ['g:i a', 'g:i A', 'H:i'];

		foreach ($date_format as $format) {
			$data['date_format'][$format] = date($format);
		}

		foreach ($time_format as $format) {
			$data['time_format'][$format] = date($format);
		}

		$view->set($data);
		$view->resize = ['s' => __('Stretch'), 'c' => __('Crop')];

		$controllerPath        = $admin_path . 'index.php?module=media/media';
		$view->scanUrl         = "$controllerPath&action=scan";
		$view->uploadUrl       = "$controllerPath&action=upload";
	}
}
