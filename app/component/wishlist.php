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

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Wishlist extends ComponentBase {
	public static $defaultOptions = [
		'start'            => 0,
		'page'             => 1,
		'limit'            => 4,
		'language_id'      => null,
		'site_id'          => null,
		'user_id'          => NULL,
		'image_size'       => 'medium',
	];

	public $options = [];

	public $cacheExpire = 0; //no cache

	function results() {
		$products = new \Vvveb\Sql\User_wishlistSQL();

		if ($page = $this->options['page']) {
			$this->options['start'] = ($page - 1) * $this->options['limit'];
		}

		$results = $products->getAll($this->options);

		if ($results && isset($results['product'])) {
			$this->products($results['product'], $this->options);
		} else {
			$results['product'] = [];
		}

		$results['limit']  = $this->options['limit'];
		$results['start']  = $this->options['start'];
		$results['search'] = $this->options['search'] ?? '';

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
