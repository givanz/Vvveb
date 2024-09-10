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

use function Vvveb\getCurrency;
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Product extends ComponentBase {
	public static $defaultOptions = [
		'product_id'    => 'url',
		'slug'          => 'url',
		'status'        => 1,
		'language_id'   => null,
		'site_id'       => null,
		'user_id'       => null,
		'user_group_id' => null,
		'reviews'       => true,
		'rating'        => true,
		'promotion'     => true,
		'image_size'    => '',
	];

	function results() {
		$product = new ProductSQL();
		$results = $product->get($this->options);

		$results['images'] = [];

		if (isset($results['product_image'])) {
			$results['images'] = Images::images($results['product_image'], 'product', $this->options['image_size']);
		}

		if (isset($results['image'])) {
			//$results['images'][] = ['image' => Images::image('product', $results['image'])];
			$results['image']= Images::image($results['image'], 'product', $this->options['image_size']);
		}

		$results['add_cart_url']     = url('cart/cart/add', ['product_id' => $results['product_id']]);
		$results['buy_url']          = url('checkout/checkout/index', ['product_id' => $results['product_id']]);
		$results['add_wishlist_url'] = url('user/wishlist/add', ['product_id' => $results['product_id']]);
		$results['add_compare_url']  = url('cart/compare/add', ['product_id' => $results['product_id']]);
		$results['manufacturer_url'] = url('product/manufacturer/index', ['slug' => $results['manufacturer_slug']]);
		$results['vendor_url']       = url('product/vendor/index', ['slug' => $results['vendor_slug']]);

		$tax                            = Tax::getInstance();
		$currency                       = Currency::getInstance();
		$results['price_currency']      = getCurrency();
		$results['price_tax']           = $tax->addTaxes($results['price'], $results['tax_type_id']);
		$results['price_tax_formatted'] = $currency->format($results['price_tax']);
		$results['price_formatted']     = $currency->format($results['price']);

		if ($results['promotion']) {
			$results['promotion_tax']           = $tax->addTaxes($results['promotion'], $results['tax_type_id']);
			$results['promotion_tax_formatted'] = $currency->format($results['promotion_tax']);
			$results['promotion_formatted']     = $currency->format($results['promotion']);
			$results['promotion_discount']      = 100 - ceil($results['promotion'] * 100 / $results['price']);
		}

		//rfc
		$results['pubDate'] = date('r', strtotime($results['created_at']));
		$results['modDate'] = date('r', strtotime($results['updated_at']));

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called on each request
	function request(&$results, $index = 0) {
		$request    = Request::getInstance();
		$created_at = $request->get['created_at'] ?? ''; //revision preview

		if ($created_at && $results['product_id']) {
			//check if admin user to allow revision preview
			$admin = Admin::current();

			if ($admin) {
				$revisions = model('post_content_revision');
				$revision  = $revisions->get(['created_at' => $created_at, 'product_id' => $results['product_id'], 'language_id' => $results['language_id']]);

				if ($revision && isset($revision['content'])) {
					$results['content']    = $revision['content'];
					$results['created_at'] = $revision['created_at'];
				}
			}
		}

		return $results;
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'product') {
		$products        = new ProductSQL();
		$product_content = [];
		$publicPath      = \Vvveb\publicUrlPath() . 'media/';

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name == 'name') {
				$product_content[$name] = strip_tags($value);
			} else {
				if ($name == 'content' || $name == 'excerpt') {
					$product_content[$name] = sanitizeHTML($value);
				} else {
					if ($name == 'image') {
						$value = str_replace($publicPath,'', $value);
					}
					$product[$name] = $value;
				}
			}
		}

		$product_content['language_id']   = 1;
		$product['product_content'][]     = $product_content;
		$product['product_id']            = $id;
		$result                           = $products->edit(['product' => $product, 'product_id' => $id]);
	}
}
