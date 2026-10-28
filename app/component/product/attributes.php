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

namespace Vvveb\Component\Product;

use Vvveb\Sql\AttributeSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Attributes extends ComponentBase {
	public static array $defaultOptions = [
		'start'       => 0,
		'limit'       => 100,
		'site_id'     => null,
		'language_id' => null,
		'product_id'  => 'url',
		'search'      => null,
		'image_size'  => 'thumb',
	];

	public int $cacheExpire = 0;

	/**
	 * Get product attributes with product_id filter normalization.
	 *
	 * @return array<string, mixed> Attributes data with attribute list.
	 */
	public function results(): array {
		$category   = new AttributeSQL();

		if (isset($this->options['product_id'])) {
			if (!is_array($this->options['product_id'])) {
				$this->options['product_id'] = [$this->options['product_id']];
			}
		}

		$results    = $category->getAll($this->options);
		$attributes = [];
		$group      = false;

		$results['attribute'] ??= [];

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
