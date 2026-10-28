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

use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\Product_variantSQL;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Event;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Product extends ComponentBase {
	public static array $defaultOptions = [
		'product_id'         => 'url',
		'slug'               => 'url',
		'status'             => 1,
		'language_id'        => null,
		'site_id'            => null,
		'user_group_id'      => null,
		'product_variant_id' => null,
		'variant'            => null,
		'variant_price'      => null,
		'promotion'          => null,
		'points'             => null,
		'stock_status'       => null,
		'weight_type'        => null,
		'length_type'        => null,
		'rating'             => null,
		'reviews'            => null,
		'image_size'         => 'large',    //options: xlarge, large, medium, thumb - if null site settings is used
		'image_resize'       => null,    //options: cs = Crop & resize, c = crop, r = resize, s = stretch - if null site settings is used
	];

	public int $cacheExpire = 0;

	/**
	 * Get single product details with images, variants, pricing, tax, and URLs.
	 *
	 * @return array<string, mixed> Product data with images, variants, prices, and action URLs.
	 */
	public function results(): array {
		$product = new ProductSQL();
		$results = $product->get($this->options);

		if (!isset($results['product_id'])) {
			return [];
		}

		$results['images'] = [];

		if (isset($results['product_image'])) {
			$results['images'] = Images::images($results['product_image'], 'product', $this->options['image_size'], $this->options['image_resize'] ?? 'cs');
		}

		if (isset($results['image'])) {
			$results['image'] = Images::image($results['image'], 'product', $this->options['image_size']);
		}

		$results['add_cart_url']     = url('cart/cart/add', ['product_id' => $results['product_id']]);
		$results['buy_url']          = url('checkout/checkout/index', ['product_id' => $results['product_id']]);
		$results['add_wishlist_url'] = url('user/wishlist/add', ['product_id' => $results['product_id']]);
		$results['add_compare_url']  = url('cart/compare/add', ['product_id' => $results['product_id']]);
		$results['manufacturer_url'] = url('product/manufacturer/index', ['slug' => $results['manufacturer_slug']]);
		$results['vendor_url']       = url('product/vendor/index', ['slug' => $results['vendor_slug']]);

		$variants = [];

		if (isset($this->options['product_id'])) {
			$variantSQL = new Product_variantSQL();
			$voptions   = ['product_id' => $this->options['product_id'], 'start' => 0, 'limit' => 1];

			if (isset($this->options['product_variant_id'])) {
				$voptions['product_variant_id'] = [$this->options['product_variant_id']];
			}

			$variants = $variantSQL->getAll($voptions);
		}

		$defaultVariant   = [];
		$defaultVariantId = null;
		$defaultOptions   = [];

		if ($variants && isset($variants['product_variant'])) {
			$defaultVariant = current($variants['product_variant']);

			//if product has variants set default variant price
			if ($defaultVariant) {
				$results['price'] = $defaultVariant['price'];
				$defaultVariantId = $defaultVariant['product_variant_id'];
			}
		}

		$results['product_variant_id']  = $defaultVariantId;

		$this->tax             = Tax::getInstance();
		$this->currency        = Currency::getInstance($this->options);
		$this->currentCurrency = Locale :: getCurrency();

		foreach (['price', 'old_price', 'min_price', 'max_price'] as $price) {
			$amount = 0;

			if (isset($results[$price]) && $results[$price]) {
				$amount = $results[$price];
			}
			$results["{$price}_tax"]            = $amount ? $this->tax->addTaxes($amount, $results['tax_type_id']) : 0;
			$results["{$price}_formatted"]      = $this->currency->format($amount);
			$results["{$price}_tax_formatted"]  = $this->currency->format($results["{$price}_tax"]);
			$results["{$price}_currency"]       = $this->currentCurrency;
		}

		$results['has_variants'] = false;

		if (isset($results['min_price']) || isset($results['max_price'])) {
			$results['has_variants'] = true;
		}

		if (isset($results['promotion']) && $results['promotion']) {
			$results['promotion_tax']           = $this->tax->addTaxes($results['promotion'], $results['tax_type_id']);
			$results['promotion_tax_formatted'] = $this->currency->format($results['promotion_tax']);
			$results['promotion_formatted']     = $this->currency->format($results['promotion']);

			//guard against division by zero when a product has no base price
			if (!empty($results['price'])) {
				$results['promotion_discount'] = 100 - ceil($results['promotion'] * 100 / $results['price']);
			}
		}

		if ($results['language_id'] != ($this->options['default_language_id'] ?? null)) {
			if ($results['name'] === null && isset($results['product_content'][$this->options['default_language_id'] ?? null])) {
				$langFallback            = $results['product_content'][$this->options['default_language_id']];
				$results['name']         = $langFallback['name'];
				$results['slug']         = $langFallback['slug'];
				$results['content']      = $langFallback['content'];
				$results['language_id']  = $langFallback['language_id'];
			}
		}

		//rfc
		$results['pubDate'] = date('r', strtotime($results['created_at']));
		$results['modDate'] = date('r', strtotime($results['updated_at']));

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Handle revision preview for product content on each request.
	 *
	 * @param array<string, mixed> &$results Product data to modify.
	 * @param int                  $index    Request index.
	 *
	 * @return array<string, mixed> Modified product data.
	 */
	public function request(array &$results, int $index = 0): array {
		$request    = Request::getInstance();
		$created_at = $request->get['created_at'] ?? '';

		if ($created_at && $results['product_id']) {
			//check if admin user to allow revision preview
			$admin = Admin::current();

			if ($admin) {
				$revisions = model('product_content_revision');
				$revision  = $revisions->get(['created_at' => $created_at, 'product_id' => $results['product_id'], 'language_id' => $results['language_id']]);

				if ($revision && isset($revision['content'])) {
					$results['content']    = $revision['content'];
					$results['created_at'] = $revision['created_at'];
				}
			}
		}

		return $results;
	}

	/**
	 * Save product content from editor on page save.
	 *
	 * @param int                              $id     Product ID.
	 * @param array<int, array<string, mixed>> $fields Field data from editor.
	 * @param string                           $type   Product type identifier.
	 *
	 * @return void
	 */
	public static function editorSave(int $id, array $fields, string $type = 'product'): void {
		$products        = new ProductSQL();
		$publicPath      = \Vvveb\publicUrlPath() . 'media/';

		$product         = [];
		$product_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name === 'name') {
				$product_content[$name] = strip_tags($value);
			} elseif ($name === 'images') {
				continue;
			} elseif ($name === 'content' || $name === 'excerpt') {
				$product_content[$name] = sanitizeHTML($value);
			} else {
				if ($name === 'image') {
					$value = str_replace($publicPath, '', $value);
				}
				$product[$name] = $value;
			}
		}

		if ($product_content) {
			$product_content['language_id'] = self :: $global['language_id'];
			$result                         = $products->editContent(['product_content' => $product_content, 'product_id' => $id, 'language_id' => self :: $global['language_id']]);
		}

		if ($product) {
			$product['product_id'] = $id;
			$result                = $products->edit(['product' => $product, 'product_content' => [], 'product_id' => $id]);
		}
	}
}
