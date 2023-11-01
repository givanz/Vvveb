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

class Config {
	private $config = [];

	private static $instance;

	public static function getInstance() {
		if (self::$instance) {
			return self::$instance;
		} else {
			return self::$instance = new self();
		}
	}

	private function loadConfig($namespace) {
		//avoid checking file for non existing namespace
		if (isset($this->config[$namespace]) &&
			$this->config[$namespace] === NULL) {
			return;
		}

		$file = DIR_ROOT . "config/$namespace.php";

		if (file_exists($file)) {
			try {
				$this->config[$namespace] = include $file;
			} catch (Exception $e) {
			}
		} else {
			$this->config[$namespace] = NULL;
		}
	}

	private function saveConfig($namespace) {
		$data       = $this->config[$namespace];
		$configFile = DIR_ROOT . "config/$namespace.php";

		if ($error = file_put_contents($configFile, "<?php\n return " . var_export($data, true) . ';', LOCK_EX)) {
			clearstatcache(true, $configFile);

			if (function_exists('opcache_invalidate')) {
				opcache_invalidate($configFile);
			}

			return true;
		}

		return $error;
	}

	public function get($path, $default = null) {
		$p         = strtok($path, '.');
		$namespace = $p;

		//load config for namespace
		if (! isset($this->config[$namespace])) {
			$this->loadConfig($namespace);

			if (! isset($this->config[$namespace])) {
				return null;
			}
		}

		$a = $this->config;

		while ($p !== false) {
			if (! isset($a[$p])) {
				return $default;
			}

			$a = $a[$p];
			$p = strtok('.');
		}

		return $a;
	}

	public function set($path, $value) {
		$p         = strtok($path, '.');
		$namespace = $p;

		if (! $namespace) {
			return false;
		}

		$a = &$this->config;
		//make sure the namespace is loaded to avoid overriding existing data
		if (! isset($this->config[$namespace])) {
			$this->loadConfig($namespace);
		}

		while ($p !== false) {
			if (! isset($a[$p])) {
				$a[$p] = [];
			}

			$prev = &$a;
			$last = $p;

			$a = &$a[$p];
			$p = strtok('.');
		}

		if (empty($a)) {
			$a = $value;
		} else {
			//if value already set go back one level
			if (! is_array($a)) {
				$prev[$last] = $value;
			} else {
				if (is_array($value)) {
					$a = array_merge($a, $value);
				} else {
					$a[$p] = $value;
				}
			}
		}

		return $this->saveConfig($namespace);
	}

	public function unset($path) {
		$p         = strtok($path, '.');
		$namespace = $p;

		if (! $namespace) {
			return false;
		}

		$a = &$this->config;
		//make sure the namespace is loaded to avoid overriding existing data
		if (! isset($this->config[$namespace])) {
			$this->loadConfig($namespace);
		}

		while ($p !== false) {
			if (! isset($a[$p])) {
				$a[$p] = [];
			}

			$prev = &$a;
			$last = $p;

			$a = &$a[$p];
			$p = strtok('.');
		}

		unset($prev[$last]);

		return $this->saveConfig($namespace);
	}
}
