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

use function Vvveb\__;

return [
	'billing_address.first_name' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	'billing_address.last_name' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	/*
	'billing_address.company' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],*/
	'billing_address.address_1' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	'billing_address.address_2' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	'billing_address.city' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	'billing_address.post_code' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],

		['maxLength'  => 100,
			'message'    => __('%s must not be greater than 100'), ],
	],
	'billing_address.country_id' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],
	],
	'billing_address.region_id' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],
	],
	/*
	'billing_address.phone_number' => [
		['notEmpty'  => '',
			'message'   => '%s must not be empty', ],
	],*/
];
