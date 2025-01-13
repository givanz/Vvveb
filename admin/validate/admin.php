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

return [
	'username' => [
		'notEmpty' => ''/*,
			 'message' => '%s must not be empty'*/,

		'maxLength' => 60 /*,
			 'message' => __('%s must not be greater than %d')*/,
	],
	'display_name' => [
		'notEmpty' => ''/*,
			 'message' => '%s must not be empty'*/,

		'maxLength' => 250 /*,
			 'message' => __('%s must not be greater than %d')*/,
	],
	'first_name' => [
		'notEmpty'  => '',
		'maxLength' => 32,
	],
	'email' => [
		'email' => '',
	],
	/*	
	'captcha' => [
			['captcha' => ''],
	],	*/
];
