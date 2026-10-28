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

class Event {
	/** @var array<string, array<string, array<int, array<string, callable>>>> */
	private static array $events = [];

	private function __construct() {}

	private function __clone() {}

	/**
	 * @param string   $namespace
	 * @param string   $name
	 * @param string   $id
	 * @param callable $callback
	 * @param int      $priority
	 *
	 * @return void
	 */
	public static function on(string $namespace, string $name, string $id, callable $callback, int $priority = 1000): void {
		if (empty(self::$events[$namespace][$name][$priority])) {
			self::$events[$namespace][$name][$priority] = [];
		}

		self::$events[$namespace][$name][$priority][$id] = $callback;
		ksort(self::$events[$namespace][$name], SORT_NUMERIC);
	}

	/**
	 * Trigger all callbacks registered for the given namespace and name.
	 * Additional arguments are passed to each callback.
	 *
	 * @param string $namespace
	 * @param string $name
	 * @param mixed  ...$args
	 *
	 * @return mixed
	 */
	public static function trigger(string $namespace, string $name, mixed ...$args): mixed {
		$hasParams = false;
		$params    = $args;

		if (empty(self::$events[$namespace][$name])) {
			return $params;
		}

		//$params can be changed by events and be empty, set flag so that we know to return it
		if ($params) {
			$hasParams = true;
		}

		foreach (self::$events[$namespace][$name] as $priority => $events) {
			foreach ($events as $id => $event) {
				if ($hasParams) {
					$ret = $event(...$params);
					//if event does not filter parameters or someone forgot to return them keep previous parameters
					if ($ret !== null) {
						$params = $ret;
					}
				} else {
					$event();
				}
			}
		}

		if ($hasParams) {
			return $params;
		}

		return null;
	}

	/**
	 * @param string       $namespace
	 * @param string|false $name
	 * @param string|false $eventId
	 *
	 * @return bool
	 */
	public static function off(string $namespace, string|false $name = false, string|false $eventId = false): bool {
		if ($eventId && $name && !empty(self::$events[$namespace][$name])) {
			foreach (self::$events[$namespace][$name] as $priority => &$events) {
				foreach ($events as $id => $event) {
					if ($id == $eventId) {
						unset($events[$id]);

						return true;
					}
				}
			}
		} elseif ($eventId && !empty(self::$events[$namespace])) {
			//no name given, remove the event id from all names
			$removed = false;

			foreach (self::$events[$namespace] as &$priorities) {
				foreach ($priorities as &$events) {
					if (isset($events[$eventId])) {
						unset($events[$eventId]);
						$removed = true;
					}
				}
			}

			return $removed;
		} elseif ($name && !empty(self::$events[$namespace][$name])) {
			unset(self::$events[$namespace][$name]);

			return true;
		} elseif ($namespace && !empty(self::$events[$namespace])) {
			unset(self::$events[$namespace]);

			return true;
		}

		return false;
	}

	/**
	 * @param string|false $namespace
	 * @param string|false $name
	 *
	 * @return array<array-key, mixed>
	 */
	public static function getEvents(string|false $namespace = false, string|false $name = false): array {
		if ($name) {
			return self::$events[$namespace][$name] ?? [];
		}

		if ($namespace) {
			return self::$events[$namespace] ?? [];
		}

		return self::$events;
	}
}
