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

namespace Vvveb\System\Cart;

use function Vvveb\model;
use Vvveb\Sql\Product_option_valueSQL;
use Vvveb\Sql\Product_variantSQL;
use Vvveb\Sql\Subscription_planSQL;
use Vvveb\System\Images;
use Vvveb\System\Locale;
use Vvveb\System\Session;
use function Vvveb\url;

class Cart {
	use CouponTrait;
	use DbTrait;
	use ProductOptionTrait;
	use TaxTrait;
	use TotalTrait;
	/** @var array<string, mixed> */
	protected array $cart = [];

	protected \Vvveb\System\Session $session;

	protected Currency $currency;

	protected Tax $tax;

	protected Weight $weight;

	protected string $productModel = 'product';

	/** @var int|string|null */
	protected int|string|null $cart_id = null;

	/** @var string|null */
	protected ?string $encrypted_cart_id = null;

	protected string $sessionKey = 'cart';

	protected string $persist = 'session';

	/** @var array<string, mixed> */
	protected array $options = [];

	/** @var array<string, mixed> */
	protected array $products = [];

	/** @var array<string, mixed> */
	protected array $taxes = [];

	/** @var array<string, mixed> */
	protected array $totals = [];

	protected float|int $total = 0;

	protected float|int $total_tax = 0;

	protected int $total_items = 0;

	protected bool $controlCache = true;

	protected bool $hasChanges = false;

	/**
	 * @param array<string, mixed> $options
	 */
	public static function getInstance(array $options = []): static {
		static $inst;

		if ($inst === null) {
			$inst = new self($options);
		}

		return $inst;
	}

	/**
	 * @param array<string, mixed> $options
	 */
	private function __construct(array $options = []) {
		$this->session  = Session::getInstance();
		$this->currency = Currency::getInstance($options);
		$this->tax      = Tax::getInstance();
		$this->weight   = Weight::getInstance();

		$this->options           = $options;
		$this->cart_id           = $options['cart_id'] ?? null;
		$this->encrypted_cart_id = $options['encrypted_cart_id'] ?? null;

		if ($this->encrypted_cart_id || ($this->cart_id && !is_numeric($this->cart_id))) {
			$cart_id = urldecode($this->encrypted_cart_id ?? $this->cart_id);
			$key     = \Vvveb\getConfig('app.key');
			$cart_id = \Vvveb\decrypt($key, $cart_id);

			if ($cart_id) {
				$this->encrypted_cart_id = $this->cart_id;
				$this->cart_id           = $cart_id;
			}
		}

		$this->read();

		if ($this->total_items) {
			$this->disableCache();
		} else {
			$this->enableCache();
		}
	}

	/**
	 * Create a new cart and return its ID.
	 *
	 * Call only if you need a cart id beforehand, otherwise a cart will be
	 * created automatically when adding a product if it does not exist.
	 */
	public function create(): int|string|null {
		$this->write();

		return $this->cart_id;
	}

	/**
	 * Set the encrypted cart id and decrypt it to retrieve the real cart id.
	 *
	 * @param string $encrypted_cart_id
	 *
	 * @return int|string|null
	 */
	public function setEncryptedId(string $encrypted_cart_id): int|string|null {
		$encrypted_cart_id = urldecode($encrypted_cart_id);
		$key               = \Vvveb\getConfig('app.key');
		$cart_id           = \Vvveb\decrypt($key, $encrypted_cart_id);

		if ($cart_id) {
			$this->encrypted_cart_id = $encrypted_cart_id;
			$this->cart_id           = $cart_id;
		}

		return $cart_id;
	}

	/**
	 * Get the encrypted version of the cart id.
	 */
	public function getEncryptedId(): string {
		if ($this->encrypted_cart_id) {
			return $this->encrypted_cart_id;
		}

		$key                     = \Vvveb\getConfig('app.key');
		$this->encrypted_cart_id = \Vvveb\encrypt($key, $this->cart_id);

		return $this->encrypted_cart_id;
	}

	/**
	 * Build a URL for the cart view page.
	 */
	public function getCartUrl(): string {
		$id  = urlencode($this->getEncryptedId());
		$url = url('cart/cart/index', ['cart_id' => $id]);
		//app does not have url
		if (!$url) {
			$url = "/checkout/$id";
		}

		return $url;
	}

	/**
	 * Build a URL for the checkout page.
	 */
	public function getCheckoutUrl(): string {
		$id  = urlencode($this->getEncryptedId());
		$url = url('checkout/checkout/index', ['cart_id' => $id]);
		//app does not have url
		if (!$url) {
			$url = "/checkout/$id";
		}

		return $url;
	}

	/**
	 * Get the raw cart id.
	 */
	public function getId(): int|string|null {
		return $this->cart_id;
	}

	/**
	 * Recalculate all cart totals, prices, taxes and product data.
	 *
	 * @return array{products: array<int|string, mixed>, count: int}
	 */
	public function updateCart(): array {
		$this->total       = 0;
		$this->total_items = 0;

		$this->tax->taxPrecision = $this->currency->getDecimalPlace();

		$results  = ['products' => [], 'count' => 0];
		$currency = Locale::getCurrency();

		$refs = $this->collectProductReferences();
		if ($refs['productIds']) {
			$data = $this->loadCartProductData($refs);
		} else {
			$data = ['results' => $results, 'optionResults' => [], 'subscriptionResults' => [], 'variantsResults' => []];
		}

		$results              = $data['results'];
		$optionResults        = $data['optionResults'];
		$subscriptionResults  = $data['subscriptionResults'];
		$variantsResults      = $data['variantsResults'];
		$products             = $results['product'] ?? [];

		if ($products) {
			foreach ($this->products as $key => &$prod) {
				if (!isset($products[$prod['product_id']])) {
					unset($this->products[$key]);

					continue;
				}

				$productId = $prod['product_id'];
				$product   = $products[$productId];

				$prod['price']  = (float)$product['price'];
				$prod['weight'] = (float)$product['weight'];

				//add option value data and adjust price if necessary
				$this->applyProductOptionValues($prod, $product, $optionResults);

				//add subscription data
				if (!empty($prod['subscription_plan_id'])) {
					$prod['subscription']      = $subscriptionResults[$prod['subscription_plan_id']] ?? [];
					$prod['subscription_name'] = $prod['subscription']['name'] ?? '';
				}

				//if product variant override price and stock
				$this->applyVariantOverrides($prod, $variantsResults);

				$url                     = ['slug' => $product['slug'], 'product_id' => $product['product_id']];
				$prod['key']             = $key;
				$prod['url']             = htmlspecialchars(url('product/product/index', $url));
				$prod['remove-url']      = htmlspecialchars(url('cart/cart/remove', $url + ['key' => $key]));

				$prod['total']           = $prod['price'] * $prod['quantity'];

				$prod['price_tax']      = $this->tax->addTaxes($prod['price'], $product['tax_type_id']);
				$prod['tax']            = $this->tax->lineTax($prod['price'], $product['tax_type_id'], $prod['quantity']);
				$prod['total_tax']      = $prod['tax'];
				$prod['price_currency'] = $currency;

				$prod['price_tax_formatted'] = $this->currency->format($prod['price_tax']);
				$prod['price_formatted']     = $this->currency->format($product['price']);
				$prod['total_formatted']     = $this->currency->format($prod['total']);
				$prod['total_tax_formatted'] = $this->currency->format($prod['total_tax']);
				$prod['total_weight']        = $prod['weight'] * $prod['quantity'];

				$this->total += $prod['total'];
				$this->total_tax += $prod['total_tax'];
				$this->total_items += $prod['quantity'];

				$prod = array_replace($product, $prod);

				if (isset($product['image'])) {
					$prod['image'] = Images::image($product['image'], 'product', 'thumb');
				}

				unset($prod['content']);
			}
		}

		$this->addTotal('total', 'sub_total', 'Sub-total', $this->total);

		//set cart cookie to disable cache if products in cart
		if ($this->total_items) {
			$this->disableCache();
		} else {
			$this->enableCache();
		}

		$this->hasChanges = true;

		return $results;
	}

	/**
	 * Collect unique product/option/variant/subscription references from the
	 * cart rows so each can be fetched with a single query.
	 *
	 * @return array{productIds: array<string, string>, options: array<string, string>, subscriptions: array<string, string>, variants: array<string, string>}
	 */
	protected function collectProductReferences(): array {
		$productIds           = [];
		$productOptions       = [];
		$productVariants      = [];
		$productSubscriptions = [];

		foreach ($this->products as $product) {
			$productId              = $product['product_id'];
			$productIds[$productId] = $productId;

			if (!empty($product['option']) && is_array($product['option'])) {
				foreach ($product['option'] as $value) {
					if (is_numeric($value)) {
						$productOptions[(string)$value] = $value;
					} elseif (isset($value['product_option_value_id'])) {
						$id                          = $value['product_option_value_id'];
						$productOptions[(string)$id] = $id;
					}
				}
			}

			if (!empty($product['subscription_plan_id'])) {
				$productSubscriptions[$product['subscription_plan_id']] = $product['subscription_plan_id'];
			}

			if (!empty($product['product_variant_id'])) {
				$productVariants[$product['product_variant_id']] = $product['product_variant_id'];
			}
		}

		return [
			'productIds'    => $productIds,
			'options'       => $productOptions,
			'subscriptions' => $productSubscriptions,
			'variants'      => $productVariants,
		];
	}

	/**
	 * Load product, option value, subscription plan and variant data in bulk.
	 *
	 * @param array{productIds: array<array-key, mixed>, options: array<array-key, mixed>, subscriptions: array<array-key, mixed>, variants: array<array-key, mixed>} $refs
	 *
	 * @return array{results: array<array-key, mixed>, optionResults: array<array-key, mixed>, subscriptionResults: array<array-key, mixed>, variantsResults: array<array-key, mixed>}
	 */
	protected function loadCartProductData(array $refs): array {
		$options = ['product_id' => $refs['productIds']] + $this->options;

		$results = model($this->productModel)->getAll($options);

		$optionResults = [];

		if ($refs['options']) {
			$optionValueSql = new Product_option_valueSQL();
			$optionResults  = $optionValueSql->getAll(
				['product_option_value_id' => array_values($refs['options'])] + $this->options
			)['product_option_value'] ?? [];
		}

		$subscriptionResults = [];

		if ($refs['subscriptions']) {
			$subscriptionPlanSql = new Subscription_planSQL();
			$subscriptionResults = $subscriptionPlanSql->getAll(
				['subscription_plan_id' => array_values($refs['subscriptions'])] + $this->options
			)['subscription_plan'] ?? [];
		}

		$variantsResults = [];

		if ($refs['variants']) {
			$productVariantSql = new Product_variantSQL();
			$variantsResults   = $productVariantSql->getAll(
				['product_variant_id' => array_values($refs['variants'])] + $this->options
			)['product_variant'] ?? [];
		}

		return [
			'results'             => $results,
			'optionResults'       => $optionResults,
			'subscriptionResults' => $subscriptionResults,
			'variantsResults'     => $variantsResults,
		];
	}

	/**
	 * Apply option value price/weight/points adjustments to one cart row.
	 *
	 * @param array<string, mixed>                &$prod
	 * @param array<string, mixed>                $product
	 * @param array<string, array<string, mixed>> $optionResults
	 */
	protected function applyProductOptionValues(array &$prod, array $product, array $optionResults): void {
		if (empty($prod['option']) || !is_array($prod['option'])) {
			return;
		}

		foreach ($prod['option'] as $option_id => $option) {
			$product_option_value_id = null;

			if (is_numeric($option)) {
				$product_option_value_id = $option;
			} elseif (isset($option['product_option_value_id'])) {
				$product_option_value_id = $option['product_option_value_id'];
			}

			if ($product_option_value_id === null || !isset($optionResults[$product_option_value_id])) {
				continue;
			}

			$value = $optionResults[$product_option_value_id];

			if (!empty($value['price'])) {
				if (($value['price_operator'] ?? '+') === '-') {
					$value['price'] = -(float)$value['price'];
				}

				$prod['price'] += (float)$value['price'];

				$value['price_tax']       = $this->tax->addTaxes($value['price'], $product['tax_type_id'] ?? 0);
				$value['price_formatted'] = $value['price_operator'] . $this->currency->format($value['price_tax']);
			}

			if (!empty($value['weight'])) {
				if (($value['weight_operator'] ?? '+') === '-') {
					$value['weight'] = -$value['weight'];
				}

				$prod['weight']            = ($prod['weight'] ?? 0) + $value['weight'];
				$value['weight_formatted'] = $value['weight'];
			}

			if (!empty($value['points'])) {
				if (($value['points_operator'] ?? '+') === '-') {
					$value['points'] = -$value['points'];
				}

				$prod['points']            = ($prod['points'] ?? 0) + $value['points'];
				$value['points_formatted'] = $value['points'];
			}

			$prod['option_value'][$product_option_value_id] = $value;
		}
	}

	/**
	 * Override price and stock from the selected product variant when present.
	 *
	 * @param array<string, mixed>                                                &$prod
	 * @param array<int|string, array<string, mixed>>|array<array<string, mixed>> $variantsResults
	 */
	protected function applyVariantOverrides(array &$prod, array $variantsResults): void {
		if (empty($prod['product_variant_id'])) {
			return;
		}

		$variant         = $variantsResults[$prod['product_variant_id']] ?? [];
		if (!$variant) {
			foreach ($variantsResults as $var) {
				if ($prod['product_variant_id'] == $var['product_variant_id']) {
					$variant = $var;
				}
			}
		}

		if ($variant && $variant['product_variant_id'] == $prod['product_variant_id']) {
			$prod['variant']        = $variant;
			$prod['price']          = $variant['price'];
			$prod['stock_quantity'] = $variant['stock_quantity'];
		}
	}

	/**
	 * Add a product to the cart.
	 *
	 * @param int|string               $productId
	 * @param int|float                $quantity
	 * @param array<int|string, mixed> $option
	 * @param int|string|false         $productVariantId
	 * @param int|string|false         $subscriptionPlanId
	 *
	 * @return string|false The cart item key or false on failure.
	 */
	public function add(int|string $productId, int|float $quantity = 1, array $option = [], int|string|false $productVariantId = false, int|string|false $subscriptionPlanId = false): string|false {
		if (!$productId) {
			return false;
		}

		$key       = $productId;
		$options   = '';

		if ($option) {
			ksort($option);
			$options = trim(str_replace('"', '', json_encode($option)), '{}');
			$key .= '_' . $options;
		} else {
			$option = [];
		}

		if ($subscriptionPlanId) {
			$key .= "_$subscriptionPlanId";
		}

		$key = str_replace('"', '', $key);

		$quantity = max(1, $quantity);

		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] += $quantity;
		} else {
			$this->products[$key] = [
				'product_id'           => (int)$productId,
				'quantity'             => (int)$quantity,
				'option'               => $option,
				'options'              => $options,
				'subscription_plan_id' => $subscriptionPlanId,
				'product_variant_id'   => $productVariantId,
			];
		}

		$this->updateCart();

		return $key;
	}

	/**
	 * Update a cart item by key.
	 *
	 * @param string                   $key
	 * @param int|float                $quantity
	 * @param array<int|string, mixed> $option
	 * @param int|string|false         $productVariantId
	 * @param int|string|false         $subscriptionPlanId
	 *
	 * @return string|false The cart item key or false if item not found.
	 */
	public function update(string $key, int|float $quantity = 1, array $option = [], int|string|false $productVariantId = false, int|string|false $subscriptionPlanId = false): string|false {
		if (isset($this->products[$key])) {
			$this->products[$key]['quantity'] = max(1, $quantity);

			if ($option) {
				$this->products[$key]['option'] = $option;
			}

			if ($subscriptionPlanId) {
				$this->products[$key]['subscription_plan_id'] = $subscriptionPlanId;
			}

			if ($productVariantId) {
				$this->products[$key]['product_variant_id'] = $productVariantId;
			}

			$this->updateCart();

			return $key;
		}

		return false;
	}

	/**
	 * Get all products in the cart.
	 *
	 * @return array<string, mixed>
	 */
	public function getAll(): array {
		return $this->products;
	}

	/**
	 * Get the total quantity of all products in the cart.
	 */
	public function getNoProducts(): int {
		$total = 0;

		foreach ($this->products as $product) {
			$total += $product['quantity'];
		}

		return $total;
	}

	/**
	 * Remove one or more products from the cart by key(s).
	 *
	 * @param string|array<int, string> $key
	 *
	 * @return string|array<int, string>|false The key(s) removed or false.
	 */
	public function remove(string|array $key): string|array|false {
		if ($key) {
			if (is_array($key)) {
				foreach ($key as $value) {
					if (isset($this->products[$value])) {
						unset($this->products[$value]);
					}
				}
			} else {
				if (isset($this->products[$key])) {
					unset($this->products[$key]);
				}
			}

			$this->updateCart();

			return $key;
		}

		return false;
	}

	/**
	 * Get products that have a subscription.
	 *
	 * @return array<int, mixed>
	 */
	public function getSubscription(): array {
		$product_data = [];

		foreach ($this->products as $value) {
			if (isset($value['subscription'])) {
				$product_data[] = $value;
			}
		}

		return $product_data;
	}

	/**
	 * Get the total weight of all shippable products.
	 */
	public function getWeight(): float|int {
		$weight = 0;

		foreach ($this->products as $product) {
			if (isset($product['requires_shipping']) && $product['requires_shipping']) {
				$weight += (float)($product['weight'] ?? 0) * $product['quantity'];
			}
		}

		return $weight;
	}

	/**
	 * Check whether the cart has any products.
	 */
	public function hasProducts(): int {
		return count($this->products);
	}

	/**
	 * Check whether the cart has any subscription products.
	 */
	public function hasSubscription(): int {
		return count($this->getSubscription());
	}

	/**
	 * Check whether all products in the cart are in stock.
	 *
	 * Products with stock tracking disabled (subtract_stock off) are always
	 * considered in stock; otherwise the cart quantity is compared against the
	 * available stock_quantity.
	 */
	public function hasStock(): bool {
		foreach ($this->products as $product) {
			if (!empty($product['subtract_stock']) &&
				(int)($product['stock_quantity'] ?? 0) < (int)($product['quantity'] ?? 1)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check whether any product in the cart requires shipping.
	 */
	public function hasShipping(): bool {
		foreach ($this->products as $product) {
			if (!empty($product['requires_shipping'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether any product in the cart has a digital asset.
	 */
	public function hasDownload(): bool {
		foreach ($this->products as $product) {
			if (!empty($product['digital_asset'])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enable browser caching for the cart.
	 */
	public function enableCache(): void {
		//enable cache back by clearing the cart cookie
		if ($this->controlCache && isset($_COOKIE['cart'])) {
			setcookie('cart', '', time() - 3600, '/');
		}
	}

	/**
	 * Disable browser caching for the cart.
	 */
	public function disableCache(): void {
		//disable cache back setting the cart cookie
		if ($this->controlCache && !isset($_COOKIE['cart'])) {
			setcookie('cart', '1', 0, '/');
		}
	}

	/**
	 * Persist cart on destruction if there are pending changes.
	 */
	public function __destruct() {
		if ($this->hasChanges) {
			$this->write();
		}
	}
}
