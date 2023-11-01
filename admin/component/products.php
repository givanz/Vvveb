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
use Vvveb\System\Images;

class Products extends ComponentBase {
	public static $defaultOptions = [
		'start'                 => 0,
		'limit'                 => 4,
		'language_id'           => 1,
		'site_id'               => 1,
		'manufacturer_id'       => NULL,
		'vendor_id'       		    => NULL,
		'related'       		      => NULL,
		'order'                 => ['url', 'price asc'],
		//'taxonomy_item_id'    => null,
		'include_image_gallery' => true,
	];

	public $options = [];

	function __construct($class = __CLASS__) {
		return parent::__construct($class);
	}

	function results() {
		$products = new \Vvveb\Sql\ProductSQL();

		$results = $products->getAll($this->options);

		foreach ($results['products'] as $id => &$product) {
			if (isset($product['images'])) {
				$product['images'] = json_decode($product['images'], true);

				foreach ($product['images'] as &$image) {
					$image['image'] = Images::image($image['image'], 'product');
				}
			}

			if (isset($product['image']) && $product['image']) {
				$product['image'] =Images::image($product['image'], 'product');
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
