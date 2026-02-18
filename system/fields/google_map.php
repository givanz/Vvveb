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

namespace Vvveb\System\Fields;

class Google_map extends Field {
	const valueType = ['address', 'lat', 'long', 'zoom'];

	protected $settings = [
		'lat' => [
			'label'        => 'Lat',
			'type'         => 'number',
			'name'         => 'lat',
		],
		'long' => [
			'label'        => 'Long',
			'type'         => 'number',
			'name'         => 'long',
		],
		'zoom' => [
			'label'        => 'Zoom',
			'type'         => 'number',
			'name'         => 'zoom',
			'value'        => 1,
		],
		'address' => [
			'label'        => 'Address',
			'instructions' => 'Default address for new post',
			'type'         => 'text',
			'name'         => 'address',
		],
		'default' => null,
	];

	protected $validation = [
	];

	protected $presentation = [
	];
}
