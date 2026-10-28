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

use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Traits\Product;

class Products extends ComponentBase {
	use Product;

	public static array $defaultOptions = [
		'start'            => 0,
		'page'             => 1,
		'limit'            => 4,
		'status'           => 1, // 1 = enabled, 0 disabled
		'source'           => 'autocomplete',
		'language_id'      => null,
		'site_id'          => null,
		'type'             => 'product', // filter by custom product type
		'manufacturer_id'  => null,
		'vendor_id'        => null,
		'taxonomy_item_id' => null,
		'product_image'    => true,
		'product_id'       => [],
		'search'           => null,
		'search_boolean'   => null,
		'like'             => null,
		'slug'             => null, // show only products with matching slugs, array with product slugs  ['one','two']
		'related'          => null,
		'variant'          => null,
		'promotion'        => null,
		'points'           => null,
		'stock_status'     => null,
		'weight_type'      => null,
		'length_type'      => null,
		'rating'           => null,
		'reviews'          => null,
		'author'           => null,
		'image_size'       => 'medium', //options: xlarge, large, medium, thumb - if null site settings is use
		'image_resize'     => null, // options: cs = Crop & resize, c = crop, r = resize, s = stretch
		'variant'          => null,
		'variant_price'    => null,
		'order_by'         => null,
		'direction'        => null,
		'404'              => null, // show 404 page if no product returned, used when added to shop/category etc pages
	];

	/**
	 * Currency code for the current session (e.g. 'USD', 'EUR').
	 *
	 * @var string
	 */
	protected string $currentCurrency = '';

	/**
	 * Tax calculator instance.
	 *
	 * @var \Vvveb\System\Cart\Tax
	 */
	protected ?Tax $tax = null;

	/**
	 * Currency formatter instance.
	 *
	 * @var \Vvveb\System\Cart\Currency
	 */
	protected ?Currency $currency = null;

	public array $options = [];

	/**
	 * Disable caching for search, pagination, or specific product lookups.
	 *
	 * @return string|null Parent cache key or null to disable caching.
	 */
	public function cacheKey(): ?string {
		//page defaults to 1 and product_id to [] so only non-default values disable caching
		if (!empty($this->options['search']) || ($this->options['page'] ?? 1) > 1 || !empty($this->options['product_id'])) {
			return null;
		}

		return parent::cacheKey();
	}

	/**
	 * Get products list with filtering, sorting, pagination, and 404 handling.
	 *
	 * @return array<string, mixed> Products data with product list, limit, start, and search.
	 */
	public function results(): array {
		$products = new \Vvveb\Sql\ProductSQL();

		$this->options['limit'] = max(1, (int)($this->options['limit'] ?? 4) ?: 4);

		if (($page = $this->options['page'] ?? false) && is_numeric($page) && $page > 0) {
			$this->options['start'] = ($page - 1) * ($this->options['limit']);
		}

		$this->options['start'] = max(0, (int)($this->options['start'] ?? 0) ?: 0);

		if ($this->options['filter'] ?? false) {
			foreach ($this->options['filter'] as $name => $values) {
				if (in_array($name, ['attribute_id', 'option_value_id', 'field_id', 'manufacturer_id', 'vendor_id'])) {
					$this->options[$name] = $values;
				}
			}
		}

		if (isset($this->options['product_id']) &&
			(isset($this->options['related']) || isset($this->options['variant']) || (($this->options['source'] ?? '') === 'autocomplete'))) {
			if (!is_array($this->options['product_id'])) {
				$this->options['product_id'] = [$this->options['product_id'] => 1];
			}
			$this->options['product_id'] = array_keys($this->options['product_id']);
		} else {
			$this->options['product_id'] = [];
		}

		if (isset($this->options['order_by']) &&
				!in_array($this->options['order_by'], ['product_id', 'price', 'created_at', 'updated_at'])) {
			unset($this->options['order_by']);
		}

		if (isset($this->options['direction']) &&
				!in_array($this->options['direction'], ['asc', 'desc'])) {
			unset($this->options['direction']);
		}

		//if only one slug is provided then add it to array
		if (isset($this->options['slug']) && !is_array($this->options['slug'])) {
			$this->options['slug'] = [$this->options['slug']];
		}

		//if only one manufacturer_id is provided then add it to array
		if (isset($this->options['manufacturer_id']) && !is_array($this->options['manufacturer_id'])) {
			$this->options['manufacturer_id'] = [$this->options['manufacturer_id']];
		}

		//if only one vendor_id is provided then add it to array
		if (isset($this->options['vendor_id']) && !is_array($this->options['vendor_id'])) {
			$this->options['vendor_id'] = [$this->options['vendor_id']];
		}

		//if only one taxonomy_item_id is provided then add it to array
		if (isset($this->options['taxonomy_item_id']) && !is_array($this->options['taxonomy_item_id'])) {
			$this->options['taxonomy_item_id'] = [$this->options['taxonomy_item_id']];
		}

		//wildcard search for one word search
		if (isset($this->options['search']) && !str_contains($this->options['search'], ' ')) {
			$this->options['search_boolean'] = true;
		}

		if (isset($this->options['search'], $this->options['search_boolean'])) {
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

		if (!$results['product'] &&
			isset($this->options['page']) &&
			($this->options['page'] > 1) &&
			!empty($this->options['404']) &&
			!($_SERVER['QUERY_STRING'] ?? '')) {
			$results['404'] = true;
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
