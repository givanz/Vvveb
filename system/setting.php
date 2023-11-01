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

use Vvveb\Sql\SettingSQL as SettingSQL;

class Setting {
	private $setting = [];

	private $settingSql;

	private static $instance;

	public static function getInstance() {
		if (self::$instance) {
			return self::$instance;
		} else {
			return self::$instance = new self();
		}
	}

	function __construct() {
		$this->settingSql = new SettingSQL();
	}

	public function get($namespace, $key, $default = null, $site_id = SITE_ID) {
		if (! $namespace && ! $key) {
			return $default;
		}

		if (is_array($key) || empty($key)) {
			$result = $this->settingSql->getSettings(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]);

			if ($result) {
				$return = [];

				foreach ($result as $value) {
					$return[$value['key']] = $value['value'];
				}

				return $return;
			} else {
				$return = [];

				if (is_array($key)) {
					foreach ($key as $value) {
						$return[$value]  = $default;
					}
				} else {
					return $default;
				}

				return $return;
			}
		} else {
			if (isset($this->setting[$namespace][$key])) {
				return $this->setting[$namespace][$key];
			}

			return $this->settingSql->getSetting(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]) ?? $default;
		}
	}

	public function set($namespace, $key, $value, $site_id = SITE_ID) {
		if (! $namespace || ! $key) {
			return $default;
		}

		$this->setting[$namespace][$key] = $value;

		return $this->settingSql->setSetting(['namespace' => $namespace, 'key' => $key, 'value' => $key, 'site_id' => $site_id]);
	}

	public function delete($namespace, $key, $value, $site_id = SITE_ID) {
		if (! $namespace || ! $key) {
			return $default;
		}

		unset($this->setting[$namespace][$key]);

		return $this->settingSql->delete(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]);
	}

	public function multiSet($namespace, $settings, $site_id = SITE_ID) {
		if (! $site_id) {
			$site_id = 0;
		}

		$result = false;

		if ($settings) {
			foreach ($settings as $key => $value) {
				$this->setting[$namespace][$key] = $value;
				$result              = $this->settingSql->setSetting(['namespace' => $namespace, 'key' => $key, 'value' => $value, 'site_id' => $site_id]);
			}
		}

		return $result;
	}
}
