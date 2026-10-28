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

use Vvveb\Sql\SettingSQL;

class Setting {
	/** @var array<string, mixed> */
	private array $setting = [];

	/** @var SettingSQL */
	private SettingSQL $settingSql;

	/** @var ?self */
	private static ?self $instance = null;

	/** @return self */
	public static function getInstance(): self {
		if (self::$instance) {
			return self::$instance;
		} else {
			return self::$instance = new self();
		}
	}

	/**
	 * @return void
	 */
	public function __construct() {
		$this->settingSql = new SettingSQL();
	}

	/**
	 * Decrypt a stored value (or nested settings array) for runtime use.
	 *
	 * Key-agnostic: any `enc:v1:` envelope is decrypted, legacy plaintext
	 * passes through, failures keep the stored value so re-save can recover.
	 *
	 * @param mixed $value Stored value.
	 *
	 * @return mixed Decrypted value.
	 */
	private function decryptStored(mixed $value): mixed {
		if (function_exists('\Vvveb\decryptSettings')) {
			return \Vvveb\decryptSettings($value);
		}

		if (is_array($value)) {
			foreach ($value as &$item) {
				$item = $this->decryptStored($item);
			}
			unset($item);

			return $value;
		}

		if (is_string($value) && function_exists('\Vvveb\isEncryptedSecret') && function_exists('\Vvveb\decryptSecret') && \Vvveb\isEncryptedSecret($value)) {
			$plain = \Vvveb\decryptSecret($value);

			return $plain === false ? $value : $plain;
		}

		return $value;
	}

	/**
	 * Encrypt a value for database storage.
	 *
	 * Sensitive keys (`key|secret|password|signature|token|private|username|
	 * login`) are encrypted with the install-generated `app.key`; everything
	 * else and already-encrypted values pass through. Failures fall back to
	 * plaintext so a missing key never loses data.
	 *
	 * @param string|int $key   Setting key (drives the sensitivity check).
	 * @param mixed      $value Plaintext value.
	 *
	 * @return mixed Encrypted value ready for storage.
	 */
	private function encryptForStorage(string|int $key, mixed $value): mixed {
		if (is_array($value)) {
			if (function_exists('\Vvveb\encryptSettings')) {
				return \Vvveb\encryptSettings($value);
			}

			return $value;
		}

		if (!is_string($value) || $value === '') {
			return $value;
		}

		if (function_exists('\Vvveb\isEncryptedSecret') && \Vvveb\isEncryptedSecret($value)) {
			return $value;
		}

		$sensitive = is_string($key) && function_exists('\Vvveb\isSensitiveSettingKey') ? \Vvveb\isSensitiveSettingKey($key) : false;

		if ($sensitive && function_exists('\Vvveb\encryptSecret')) {
			$encrypted = \Vvveb\encryptSecret($value);

			if (is_string($encrypted) && $encrypted !== '') {
				return $encrypted;
			}
		}

		return $value;
	}

	/**
	 * @param string                              $namespace
	 * @param string|array<array-key, mixed>|null $key
	 * @param mixed                               $default
	 * @param int                                 $site_id
	 *
	 * @return mixed
	 */
	public function get(string $namespace, string|array|null $key, mixed $default = null, int $site_id = SITE_ID): mixed {
		if (!$namespace && !$key) {
			return $default;
		}

		if (is_array($key) || empty($key)) {
			$result = $this->settingSql->getMulti(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]);

			if ($result) {
				$return = [];

				foreach ($result as $value) {
					$val = $value['value'];

					if ($val && is_string($val) && ($val[0] == '{' || $val[0] == '[')) {
						$json = json_decode($val, true);
						$val  =  $json ?: $val;
					}
					$return[$value['key']] = $this->decryptStored($val);
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

			$result = $this->settingSql->get(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]) ?? $default;

			if ($result && is_string($result) && ($result[0] == '{' || $result[0] == '[')) {
				$json    = json_decode($result, true);
				$result  =  $json ?? $result;
			}

			return $this->decryptStored($result);
		}
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @param mixed  $value
	 * @param int    $site_id
	 *
	 * @return mixed
	 */
	public function set(string $namespace, string $key, mixed $value, int $site_id = SITE_ID): mixed {
		if (!$namespace || !$key) {
			return null;
		}

		$this->setting[$namespace][$key] = $this->decryptStored($value);

		$storeValue = $this->encryptForStorage($key, $value);

		if (is_array($storeValue)) {
			$storeValue = json_encode($storeValue);
		}

		return $this->settingSql->set(['namespace' => $namespace, 'key' => $key, 'value' => $storeValue, 'site_id' => $site_id]);
	}

	/**
	 * @param string $namespace
	 * @param string $key
	 * @param int    $site_id
	 *
	 * @return mixed
	 */
	public function delete(string $namespace, ?string $key = null, int $site_id = SITE_ID): mixed {
		if (!$namespace) {
			return null;
		}

		unset($this->setting[$namespace][$key]);

		return $this->settingSql->delete(['namespace' => $namespace, 'key' => $key, 'site_id' => $site_id]);
	}

	/**
	 * @param string               $namespace
	 * @param array<string, mixed> $settings
	 * @param int                  $site_id
	 *
	 * @return mixed
	 */
	public function setMulti(string $namespace, array $settings, int $site_id = SITE_ID): mixed {
		if (!$site_id) {
			$site_id = 0;
		}

		$result = false;

		if (function_exists('\Vvveb\encryptSettings')) {
			$settings = \Vvveb\encryptSettings($settings);
		} else {
			foreach ($settings as $storeKey => &$item) {
				$item = $this->encryptForStorage($storeKey, $item);
			}
			unset($item);
		}

		foreach ($settings as &$item) {
			if (is_array($item)) {
				$item = json_encode($item);
			}
		}

		$result = $this->settingSql->setMulti(['site_id' => $site_id, 'namespace' => $namespace, 'settings' => $settings]);

		if ($settings) {
			/*
			foreach ($settings as $key => $value) {
				if (is_array($value)) {
					$value = json_encode($value);
				}
				$this->setting[$namespace][$key] = $value;
				$result                          = $this->settingSql->set(['namespace' => $namespace, 'key' => $key, 'value' => $value, 'site_id' => $site_id]);
			}
			 */
		}

		return $result;
	}
}
