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

class Db {
	public static function getInstance($engine = DB_ENGINE) {
		static $inst = [];

		if (! isset($inst[$engine]) || $inst[$engine] === null) {
			$driverName = "\Vvveb\System\Db\\$engine";

			if (class_exists($driverName)) {
				$inst[$engine] = new $driverName();
			} else {
				throw new \Exception("Error loading db driver '$driverName'!");
			}
		}

		return $inst[$engine];
	}
}
