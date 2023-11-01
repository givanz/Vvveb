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

namespace Vvveb\Component;

class Questions extends Comments {
	protected $type = 'question';

	protected $route = 'product/product/index';
	
	protected $model = 'product_question';

	public static $defaultOptions = [
		'product_id'    => 'url',
		'slug'          => 'url',
		'post_title'    => null, //include post title (for recent reviews etc)
		'status'        => 1, //approved reviews
		'start'         => 0,
		'limit'         => 10,
		'order'         => 'asc', //desc
	];
}
