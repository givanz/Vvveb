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

trait EventTrait {
	static function trigger($method, $params = NULL) {
		if ($params === NULL) {
			return $params = \Vvveb\System\Event::trigger(__CLASS__, $method);
		} else {
			return $params = \Vvveb\System\Event::trigger(__CLASS__, $method, $params);
		}
	}

	public static function on($namespace, $name, $id, $callback, $priority = 0) {
		return $params = \Vvveb\System\Event::on($namespace, $name, __CLASS__ . '::' . $id, $callback, $priority);
	}
}

class Event {
	private static $events = [];

	private function __construct() {
	}

	private function __clone() {
	}

	public static function on($namespace, $name, $id, $callback, $priority = 1000) {
		if (empty(self::$events[$namespace][$name][$priority])) {
			self::$events[$namespace][$name][$priority] = [];
		}

		self::$events[$namespace][$name][$priority][$id] = $callback;
		ksort(self::$events[$namespace][$name], SORT_NUMERIC);
	}

	public static function trigger($namespace, $name) {
		$hasParams = false;
		$params    = func_get_args();
		$params    = array_slice($params, 2);

		if (empty(self::$events[$namespace][$name])) {
			return $params;
		}

		//$params can be changed by events and be empty, set flag so that we know to return it
		if (isset($params[0])) {
			$hasParams = true;
		}

		foreach (self::$events[$namespace][$name] as $priority => $events) {
			foreach ($events as $id => $event) {
				if (is_callable($event)) {
					if ($hasParams) {
						$ret = call_user_func_array($event, $params);
						//if event does not filter parameters or someone forgot to return them keep previous parameters
						if ($ret !== null) {
							$params = $ret;
						}
					} else {
						call_user_func($event);
					}
				}
			}
		}

		if ($hasParams) {
			return $params;
		}
	}

	public static function off($namespace, $name = false, $eventId = false) {
		if ($eventId && ! empty(self::$events[$namespace][$name])) {
			foreach (self::$events[$namespace][$name] as $priority => &$events) {
				foreach ($events as $id => $event) {
					if ($id == $eventId) {
						unset($events[$id]);

						break 2;

						return true;
					}
				}
			}
		} else {
			if ($name && ! empty(self::$events[$namespace][$name])) {
				unset(self::$events[$namespace][$name]);

				return true;
			} else {
				if ($namespace && ! empty(self::$events[$namespace])) {
					unset(self::$events[$namespace]);

					return true;
				}
			}
		}

		return false;
	}

	public static function getEvents($namespace = false, $name = false) {
		if ($name) {
			return self::$events[$namespace][$name] ?? [];
		} else {
			if ($namespace) {
				return self::$events[$namespace] ?? [];
			}
		}

		return self::$events;
	}
}
