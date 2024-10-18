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

namespace Vvveb\System\Meta;

use function Vvveb\model;

class Meta {
	private $setting = [];

	protected $settingSql;

	private static $instance;

	protected $modelName;

	protected $item_id;

	public static function getInstance() {
		if (static::$instance) {
			return static::$instance;
		} else {
			return static::$instance = new static();
		}
	}

	function __construct() {
		$this->settingSql = model($this->modelName);
	}

	public function getMulti($item_id, $namespace, $key = null, $default = null, $language_id = false) {
		if ($key && ! is_array($key)) {
			$key = [$key];
		}

		$result = $this->settingSql->getMulti([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]);

		$return = [];

		if ($result) {
			foreach ($result as &$value) {
				$val = &$value['value'];

				if ($val && is_string($val) && ($val[0] == '{' || $val[0] == '{')) {
					$json = json_decode($val, true);
					$val  =  $json ?: $val;
				}
			}

			$return = $result;
		} else {
			$return = [];

			if (is_array($key)) {
				foreach ($key as $value) {
					$return[$value]  = $default;
				}
			} else {
				return $default;
			}
		}

		return $return;
	}

	public function get($item_id, $namespace, $key = null, $default = null, $language_id = false) {
		if (! $namespace && ! $key) {
			return $default;
		}

		if (is_array($key) || empty($key)) {
			$this->getMulti($item_id, $namespace, $key, $default, $language_id);
		} else {
			if (isset($this->setting[$namespace][$key])) {
				return $this->setting[$namespace][$key];
			}

			$result = $this->settingSql->get([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]) ?? $default;

			if ($result && is_string($result) && ($result[0] == '{' || $result[0] == '[')) {
				$json    = json_decode($result, true);
				$result  =  $json ?: $result;
			}

			return $result;
		}
	}

	public function set($item_id, $namespace, $key, $value, $language_id = false) {
		if (! $namespace || ! $key) {
			return;
		}

		$this->setting[$namespace][$key] = $value;

		if (is_array($value)) {
			$value = json_encode($value);
		}

		return $this->settingSql->set([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'value' => $value, 'language_id' => $language_id]);
	}

	public function delete($item_id, $namespace, $key, $language_id = false) {
		if (! $namespace || ! $key) {
			return;
		}

		unset($this->setting[$namespace][$key]);

		return $this->settingSql->delete([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]);
	}

	public function setMulti($item_id, $meta) {
		$result = false;

		foreach ($meta as &$item) {
			if (is_array($item['value'])) {
				$item['value'] = json_encode($item['value']);
			}
		}

		$result = $this->settingSql->setMulti([$this->item_id => $item_id, 'meta' => $meta]);

		if ($meta) {
			/*
			foreach ($meta as $key => $value) {
				if (is_array($value)) {
					$value = json_encode($value);
				}
				$this->setting[$namespace][$key] = $value;
				$result = $this->settingSql->set([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'value' => $value, 'site_id' => $site_id, 'language_id' => $language_id]);
			}*/
		}

		return $result;
	}
}
