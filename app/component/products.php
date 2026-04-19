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

class Products extends ComponentBase {
	use Product;

	public static $defaultOptions = [
		'start'            => 0,
		'page'             => 1,
		'limit'            => 4,
		'status'           => 1, // 1 = enabled, 0 disabled
		'source'           => 'autocomplete',
		'language_id'      => null,
		'site_id'          => null,
		'type'             => 'product', // filter by custom product type
		'manufacturer_id'  => NULL, // filter by manufacturer
		'vendor_id'        => NULL, // filter by vendor
		'taxonomy_item_id' => NULL, // filter by taxonomy (category/tag)
		'product_image'    => true, // include product images/gallery
		'product_id'       => [], // show only products with id's in the list, array with product id's [1,2,3]
		'search'           => null, // show products that matches the search string, used on search page
		'search_boolean'   => null, // use boolean search
		'like'             => null, // search using like operator, slower and returns only matches that start with the searched string
		'slug'             => null, // show only products with matching slugs, array with product slugs  ['one','two']
		'related'          => null, // show related products for the products specified at product_id, boolean
		'variant'          => null, // [true, false] include variants
		'promotion'        => null, // [true, false] include promotional price
		'points'           => null, // [true, false] include points
		'stock_status'     => null, // [true, false] include stock status info
		'weight_type'      => null, // [true, false] include weight type info
		'length_type'      => null, // [true, false] include length type info
		'rating'           => null, // [true, false] include rating average
		'reviews'          => null, // [true, false] include reviews count
		'author'           => null, // [true, false] include author/admin info
		'image_size'       => 'medium', //options: xlarge, large, medium, thumb - if null site settings is use
		'image_resize'     => null, // options: cs = Crop & resize, c = crop, r = resize, s = stretch
		'variant'          => null, //[true, false] include variants
		'variant_price'    => NULL, // [true, false] include variants min max prices
		'order_by'         => NULL, // default: product_id
		'direction'        => NULL, // ['desc', 'asc'], //'ASC', default: 'DESC'
		'404'              => NULL, // show 404 page if no product returned, used when added to shop/category etc pages
	];

	private $tax;

	private $currency;

	private $currentCurrency;

	public $options = [];

	function cacheKey() {
		if (isset($this->options['search']) || isset($this->options['page']) || isset($this->options['product_id'])) {
			return false;
		}

		return parent::cacheKey();
	}

	function results() {
		$products = new \Vvveb\Sql\ProductSQL();

		$this->options['limit'] = (int) ($this->options['limit'] ?? 4 ?: 4);

		if (($page = $this->options['page'] ?? false) && is_numeric($page)) {
			$this->options['start'] = ($page - 1) * ($this->options['limit']);
		}

		$this->options['start'] = (int) ($this->options['start'] ?? 0 ?: 0);

		if ($this->options['filter'] ?? false) {
			foreach ($this->options['filter'] as $name => $values) {
				if (in_array($name, ['attribute_id', 'option_value_id', 'field_id', 'manufacturer_id', 'vendor_id'])) {
					$this->options[$name] = $values;
				}
			}
		}

		if (isset($this->options['product_id']) &&
			(isset($this->options['related']) || isset($this->options['variant']) || ($this->options['source'] ?? '' == 'autocomplete'))) {
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

		//wildcard search for one word search
		if (isset($this->options['search']) && strpos($this->options['search'], ' ') == false) {
			$this->options['search_boolean'] = true;
		}

		if (isset($this->options['search']) && isset($this->options['search_boolean'])) {
			$this->options['search'] .= '*';
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

		if (! $results['product'] &&
			isset($this->options['page']) &&
			($this->options['page'] > 1) &&
			(isset($this->options['404']) && $this->options['404']) &&
			! $_SERVER['QUERY_STRING']) {
			$results['404'] = true;
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
