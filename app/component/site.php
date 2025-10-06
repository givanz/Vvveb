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

namespace Vvveb\Component;

use function Vvveb\siteSettings;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Site extends ComponentBase {
	public static $defaultOptions = [
		'site_id'     => null,
		'language_id' => null,
	];

	protected $options = [];

	public $cacheExpire = 0; //seconds

	function cacheKey() {
		//disable caching
		return false;
	}

	function results() {
		$results = siteSettings($this->options['site_id'], $this->options['language_id']);

		if (! $results) {
			$results    = ['logo'=>'logo.png', 'logo-sticky' => 'logo.png', 'logo-dark' => 'logo-white.png', 'logo-dark-sticky' => 'logo-white.png', 'favicon' => 'favicon.ico'];
			$publicPath = \Vvveb\publicUrlPath() . 'media';

			foreach ($results as $key => &$value) {
				$value = "$publicPath/$value";
			}
		}

		$urlOptions = [];

		if ($this->options['default_language'] != $this->options['language']) {
			$urlOptions = ['language' => $this->options['language']];
		} else {
		}

		$results['url']      = (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . '/'; //url('index/index');
		$results['full-url'] = SITE_URL; //url('index/index', $urlOptions + ['host' => '*.*.*']);

		$results['site_id'] = $this->options['site_id'];
		list($results)      = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'site') {
		$sites      = new SiteSQL();
		$result     = [];
		$site       = $sites->get(['site_id' => $id]);

		if ($site) {
			$settings = json_decode($site['settings'], true);

			$publicPath = \Vvveb\publicUrlPath() . 'media/';

			foreach ($fields as $field) {
				$name  = $field['name'];
				$value = $field['value'];

				if ($name == 'favicon' || $name == 'webbanner' || strpos($name, 'logo') !== false) {
					$value = str_replace($publicPath,'', $value);
				}
				$settings[$name] = $value;
			}

			$site             = [];
			$data['site_id']  = $id;
			$site['settings'] = json_encode($settings);
			$data['site']     = $site;
			$result           = $sites->edit($data);
		}
	}
}
