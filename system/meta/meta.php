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

/** @phpstan-consistent-constructor */
class Meta {
	/** @var array<array-key, mixed> */
	private array $setting = [];

	/** @var mixed */
	protected mixed $settingSql;

	/** @var array<class-string, static> Per-class instances so subclasses don't collide */
	private static array $instances = [];

	/** @var string */
	protected string $modelName;

	/** @var string */
	protected string $item_id;

	/**
	 * Get singleton instance of the Meta class.
	 *
	 * @return static
	 */
	public static function getInstance(): static {
		//per-class storage: PostMeta::getInstance() and ProductMeta::getInstance()
		//must return distinct instances even though they share this base class
		$class = static::class;

		return self::$instances[$class] ??= new static();
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settingSql = model($this->modelName);
	}

	/**
	 * Decode a JSON-looking meta value ({...} or [...]) to PHP, if possible.
	 *
	 * Shared by get()/getMulti() and the setMulti() runtime-cache sync so
	 * all three interpret stored values identically. Non-strings, short
	 * strings and invalid JSON pass through unchanged.
	 *
	 * @param mixed $value Raw stored value.
	 *
	 * @return mixed Decoded value or the original.
	 */
	private function decodeValue(mixed $value): mixed {
		if (is_string($value) && strlen($value) > 1 && ($value[0] === '{' || $value[0] === '[')) {
			$json = json_decode($value, true);

			if ($json !== null) {
				return $json;
			}
		}

		return $value;
	}

	/**
	 * Get multiple meta values.
	 *
	 * @param int|string                          $item_id
	 * @param string                              $namespace
	 * @param string|array<array-key, mixed>|null $key
	 * @param mixed                               $default
	 * @param int|false                           $language_id
	 *
	 * @return array<array-key, mixed>
	 */
	public function getMulti(int|string $item_id, string $namespace, string|array|null $key = null, mixed $default = [], int|false $language_id = false): array {
		if ($key && !is_array($key)) {
			$key = [$key];
		}

		$result = $this->settingSql->getMulti([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]);

		$return = [];

		if ($result) {
			foreach ($result as &$value) {
				$value['value'] = $this->decodeValue($value['value'] ?? null);
			}
			unset($value);

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

	/**
	 * Get a single meta value.
	 *
	 * @param int|string                          $item_id
	 * @param string                              $namespace
	 * @param string|array<array-key, mixed>|null $key
	 * @param mixed                               $default
	 * @param int|false                           $language_id
	 *
	 * @return mixed
	 */
	public function get(int|string $item_id, string $namespace, string|array|null $key = null, mixed $default = null, int|false $language_id = false): mixed {
		if (!$namespace && !$key) {
			return $default;
		}

		if (is_array($key) || empty($key)) {
			return $this->getMulti($item_id, $namespace, $key, $default, $language_id);
		}

		if (isset($this->setting[$namespace][$key])) {
			return $this->setting[$namespace][$key];
		}

		$result = $this->settingSql->get([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]) ?? $default;

		return $this->decodeValue($result);
	}

	/**
	 * Set a single meta value.
	 *
	 * @param int|string $item_id
	 * @param string     $namespace
	 * @param string     $key
	 * @param mixed      $value
	 * @param int|false  $language_id
	 *
	 * @return mixed
	 */
	public function set(int|string $item_id, string $namespace, string $key, mixed $value, int|false $language_id = false): mixed {
		if (!$namespace || !$key) {
			return null;
		}

		$this->setting[$namespace][$key] = $value;

		if (is_array($value)) {
			$value = json_encode($value);
		}

		return $this->settingSql->set([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'value' => $value, 'language_id' => $language_id]);
	}

	/**
	 * Delete a single meta value.
	 *
	 * @param int|string                          $item_id
	 * @param string                              $namespace
	 * @param array<array-key, mixed>|string|null $key
	 * @param int|false                           $language_id
	 *
	 * @return mixed
	 */
	public function delete(int|string $item_id, string $namespace, array|string|null $key = null, int|false $language_id = false): mixed {
		if (!$namespace || !$key) {
			return null;
		}

		if (is_array($key)) {
			foreach ($key as $k) {
				unset($this->setting[$namespace][$k]);
			}
		} else {
			unset($this->setting[$namespace][$key]);
		}

		return $this->settingSql->delete([$this->item_id => $item_id, 'namespace' => $namespace, 'key' => $key, 'language_id' => $language_id]);
	}

	/**
	 * Set multiple meta values.
	 *
	 * @param int|string              $item_id
	 * @param array<array-key, mixed> $meta        List of meta rows, each with at least
	 *                                             a `key` and a `value`. Rows may carry their
	 *                                             own `namespace`, otherwise $namespace applies.
	 * @param string                  $namespace   Namespace applied to rows that do not
	 *                                             define their own (e.g. post/product meta).
	 * @param int|false               $language_id Language applied when the meta table is
	 *                                             language-aware, false otherwise.
	 *
	 * @return mixed
	 */
	public function setMulti(int|string $item_id, array $meta, string $namespace = '', int|false $language_id = false): mixed {
		foreach ($meta as &$item) {
			if (isset($item['value']) && is_array($item['value'])) {
				$item['value'] = json_encode($item['value']);
			}
		}
		unset($item);

		$result = $this->settingSql->setMulti([$this->item_id => $item_id, 'language_id' => $language_id, 'namespace' => $namespace, 'meta' => $meta]);

		//keep the runtime cache consistent for values set through this call
		foreach ($meta as $item) {
			if (isset($item['key'])) {
				$ns = $item['namespace'] ?? $namespace;

				if ($ns !== '') {
					$this->setting[$ns][$item['key']] = $this->decodeValue($item['value'] ?? '');
				}
			}
		}

		return $result;
	}
}
