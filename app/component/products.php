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
use Vvveb\System\Traits\Product;
use function Vvveb\url;

class Products extends ComponentBase {
	use Product;

	public static $defaultOptions = [
		'start'            => 0,
		'page'             => 1,
		'limit'            => 4,
		'status'           => 1,
		'source'           => 'autocomplete',
		'language_id'      => null,
		'site_id'          => null,
		'type'             => 'product',
		'parent'           => null,
		'manufacturer_id'  => NULL,
		'vendor_id'        => NULL,
		'taxonomy_item_id' => NULL,
		'product_image'    => true,
		'product_id'       => [],
		'search'           => null,
		'search_boolean'   => true,
		'like'             => null,
		'slug'             => null,
		'related'          => null,
		'variant'          => null, //[true, false] include variants
		'promotion'        => null, //[true, false] include promotional price
		'points'           => null, //[true, false] include points
		'stock_status'     => null, //[true, false] include stock status info
		'weight_type'      => null, //[true, false] include weight type info
		'length_type'      => null, //[true, false] include length type info
		'rating'           => null, //[true, false] include rating average
		'reviews'          => null, //[true, false] include reviews count
		'author'           => null, //[true, false] include author/admin info
		'image_size'       => 'medium',
		'filter'           => null, //[true, false] include variants
		'order_by'         => NULL,
		'variant_price'    => NULL,
		'direction'        => ['desc', 'asc'], //'url','asc', 'desc'
	];

	private $tax;

	private $currency;

	private $currentCurrency;

	public $options = [];

	function results() {
		$products = new \Vvveb\Sql\ProductSQL();

		if (($page = $this->options['page']) && is_numeric($page)) {
			$this->options['start'] = ($page - 1) * ((int) ($this->options['limit'] ?? 4));
		}

		if ($this->options['filter']) {
			foreach ($this->options['filter'] as $name => $values) {
				if ($name == 'manufacturer_id' || $name == 'vendor_id') {
					$this->options[$name] = $values;
				}
			}
		}

		if (isset($this->options['product_id']) &&
			($this->options['related'] || $this->options['variant'] || $this->options['source'] == 'autocomplete')) {
			if (! is_array($this->options['product_id'])) {
				$this->options['product_id'] = [$this->options['product_id'] => 1];
			}
			$this->options['product_id'] = array_keys($this->options['product_id']);
		} else {
			$this->options['product_id'] = [];
		}

		if (isset($this->options['order_by']) &&
				! in_array($this->options['order_by'], ['product_id', 'price', 'created_at', 'updated_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				! in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		//if only one slug is provided then add it to array
		if (isset($this->options['slug']) && ! is_array($this->options['slug'])) {
			$this->options['slug'] = [$this->options['slug']];
		}

		//if only one manufacturer_id is provided then add it to array
		if (isset($this->options['manufacturer_id']) && ! is_array($this->options['manufacturer_id'])) {
			$this->options['manufacturer_id'] = [$this->options['manufacturer_id']];
		}

		//if only one vendor_id is provided then add it to array
		if (isset($this->options['vendor_id']) && ! is_array($this->options['vendor_id'])) {
			$this->options['vendor_id'] = [$this->options['vendor_id']];
		}

		//if only one taxonomy_item_id is provided then add it to array
		if (isset($this->options['taxonomy_item_id']) && ! is_array($this->options['taxonomy_item_id'])) {
			$this->options['taxonomy_item_id'] = [$this->options['taxonomy_item_id']];
		}

		if ($this->options['search'] && $this->options['search_boolean']) {
			$this->options['search'] .= '*';
		}

		$results = $products->getAll($this->options) + $this->options;

		if ($results && isset($results['product'])) {
			$this->products($results['product'], $this->options);
		} else {
			$results['product'] = [];
		}

		$results['limit']  = $this->options['limit'];
		$results['start']  = $this->options['start'];
		$results['search'] = $this->options['search'];

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
