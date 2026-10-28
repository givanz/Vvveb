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

namespace Vvveb\System\Functions;

class Plural {
	/** @var array<string, string> */
	public static array $plural = [
		'taxonomy'    => 'taxonomies',
		'points'      => 'points',
		'currency'    => 'currencies',
		'country'     => 'countries',
		'resets'      => 'resets',
		'permissions' => 'permissions',
		'status'      => 'statuses',
		'media'       => 'media',
	];

	/** @var array<string, string> */
	public static array $singular = [];

	/**
	 * @param string $tableName
	 *
	 * @return string
	 */
	public static function tablePlural(string $tableName): string {
		if (isset(self :: $plural[$tableName])) {
			return self :: $plural[$tableName];
		}

		return $tableName . 's';
	}

	/**
	 * @param string $tableName
	 *
	 * @return string
	 */
	public static function tableSingular(string $tableName): string {
		if (isset(self :: $singular[$tableName])) {
			return self :: $singular[$tableName];
		}

		return rtrim($tableName, 's');
	}
}

Plural :: $singular = array_flip(Plural :: $plural);
