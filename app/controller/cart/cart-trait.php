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

namespace Vvveb\Controller\Cart;

use function Vvveb\siteSettings;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Payment;
use Vvveb\System\Shipping;

trait CartTrait {
	/**
	 * Sync posted country/region address fields into checkout info before
	 * shipping/payment methods are resolved.
	 *
	 * Country/region changes arrive via AJAX (see reloadRegions() in
	 * checkout.js) without a full checkout submit, so without this sync
	 * getMethods() would keep filtering by the stale session country and
	 * the cart totals would keep the old shipping method.
	 *
	 * @param array<string, mixed> $checkoutInfo Checkout data updated in place.
	 */
	private function syncCheckoutAddresses(array &$checkoutInfo): void {
		$post = $this->request->post ?? [];

		foreach (['billing_address', 'shipping_address'] as $key) {
			if (isset($post[$key]) && is_array($post[$key])) {
				$checkoutInfo[$key] = array_merge($checkoutInfo[$key] ?? [], $post[$key]);
			}
		}

		if (array_key_exists('different_shipping_address', $post)) {
			$checkoutInfo['different_shipping_address'] = $post['different_shipping_address'];
		}

		//when shipping to the billing address the shipping filter must follow the billing country
		if (empty($checkoutInfo['different_shipping_address']) && !empty($checkoutInfo['billing_address'])) {
			$checkoutInfo['shipping_address'] = $checkoutInfo['billing_address'];
		}

		foreach (['billing' => 'billing_address', 'shipping' => 'shipping_address'] as $prefix => $addressKey) {
			$address = $checkoutInfo[$addressKey] ?? false;

			if (is_array($address)) {
				if (isset($address['country_id']) && $address['country_id'] !== '') {
					$checkoutInfo["{$prefix}_country_id"] = $address['country_id'];
				}

				if (isset($address['region_id']) && $address['region_id'] !== '') {
					$checkoutInfo["{$prefix}_region_id"] = $address['region_id'];
				}
			}
		}

		//fall back to the site default country/region on first load when the session has no address yet
		if (empty($checkoutInfo['shipping_country_id']) && empty($checkoutInfo['billing_country_id'])) {
			$site = $this->global['site'] ?? siteSettings();

			if (!empty($site['country_id'])) {
				$checkoutInfo['billing_country_id'] ??= $site['country_id'];
				$checkoutInfo['shipping_country_id'] ??= $site['country_id'];
			}

			if (!empty($site['region_id'])) {
				$checkoutInfo['billing_region_id'] ??= $site['region_id'];
				$checkoutInfo['shipping_region_id'] ??= $site['region_id'];
			}
		}

		$this->session->set('checkout', $checkoutInfo);
	}

	/**
	 * Re-resolve tax rules for the customer addresses.
	 *
	 * Page init loads store-based rules so browsing prices are taxed.
	 * Once addresses are known the destination basis wins: shipping geo
	 * with `shipping` rules, otherwise billing geo with `payment` rules.
	 * If that matches nothing (e.g. a store configured with store-based
	 * rules only) fall back to the legacy store-based set so tax never
	 * silently drops to zero. The singleton accumulates across calls,
	 * so it is cleared before each resolution.
	 *
	 * @param array<string, mixed> $checkoutInfo
	 * @param bool                 $hasShipping
	 *
	 * @return void
	 */
	private function refreshTaxRules(array $checkoutInfo, bool $hasShipping): void {
		$site         = $this->global['site'] ?? [];
		$storeCountry = (int)($site['country_id'] ?? 0);
		$storeRegion  = (int)($site['region_id'] ?? 0);
		$group        = $this->global['user_group_id'] ?? null;
		$tax          = Tax::getInstance();
		$tax->clear();

		if ($hasShipping && !empty($checkoutInfo['shipping_country_id'])) {
			$tax->setRegionRules(
				(int)$checkoutInfo['shipping_country_id'],
				(int)($checkoutInfo['shipping_region_id'] ?? 0),
				'shipping',
				$group
			);
		} elseif (!empty($checkoutInfo['billing_country_id'])) {
			$tax->setRegionRules(
				(int)$checkoutInfo['billing_country_id'],
				(int)($checkoutInfo['billing_region_id'] ?? 0),
				'payment',
				$group
			);
		}

		if (!$tax->hasRates()) {
			$tax->clear();
			$tax->setRegionRules($storeCountry, $storeRegion, 'store', $group);
		}
	}

	/**
	 * Resolve default shipping/payment methods from site settings.
	 *
	 * @param array<string, mixed> $checkoutInfo
	 * @param bool                 $hasShipping
	 * @param bool                 $hasPayment
	 *
	 * @return array<string, mixed>
	 */
	protected function shippingPayment(array &$checkoutInfo, bool $hasShipping, bool $hasPayment): array {
		$this->view->payment  = [];
		$this->view->shipping = [];
		$shipping             = null;
		$payment              = null;

		if ($hasShipping || $hasPayment) {
			$this->syncCheckoutAddresses($checkoutInfo);
			$this->refreshTaxRules($checkoutInfo, $hasShipping);
		}

		if ($hasShipping) {
			$shipping             = Shipping::getInstance();
			$this->view->shipping = $shipping->getMethods($checkoutInfo);

			if (isset($this->request->post['shipping_method'])) {
				$checkoutInfo['shipping_method'] = $this->request->post['shipping_method'];
			}

			if (!isset($checkoutInfo['shipping_method']) || !$checkoutInfo['shipping_method']) {
				$checkoutInfo['shipping_method'] = siteSettings()['shipping_method'] ?? '';
			}

			//drop a stale selection (e.g. UK-only method after switching to US) so
			//the first available zone is auto-selected and totals are recalculated
			if (!isset($this->view->shipping[$checkoutInfo['shipping_method']])) {
				$default      = siteSettings()['shipping_method'] ?? '';

				if ($default && isset($this->view->shipping[$default])) {
					$checkoutInfo['shipping_method'] = $default;
				} else {
					$checkoutInfo['shipping_method'] = array_key_first($this->view->shipping) ?? '';
				}

				$this->session->set('checkout', $checkoutInfo);
			}
		}

		if ($hasPayment) {
			$payment             = Payment::getInstance();
			$this->view->payment = $payment->getMethods($checkoutInfo);

			if (isset($this->request->post['payment_method'])) {
				$checkoutInfo['payment_method'] = $this->request->post['payment_method'];
			}

			if (!isset($checkoutInfo['payment_method']) || !$checkoutInfo['payment_method']) {
				$checkoutInfo['payment_method'] = siteSettings()['payment_method'] ?? '';
			}

			if ($this->view->payment && !isset($this->view->payment[$checkoutInfo['payment_method']])) {
				$default      = siteSettings()['payment_method'] ?? '';

				if ($default && isset($this->view->payment[$default])) {
					$checkoutInfo['payment_method'] = $default;
				} else {
					$checkoutInfo['payment_method'] = array_key_first($this->view->payment);
				}

				$this->session->set('checkout', $checkoutInfo);
			}
		}

		if (isset($checkoutInfo['shipping_method']) && $checkoutInfo['shipping_method'] && isset($shipping)) {
			$shipping->setMethod($checkoutInfo['shipping_method']);
			$this->view->shipping_method = $checkoutInfo['shipping_method'];
		}

		if (isset($checkoutInfo['payment_method']) && $checkoutInfo['payment_method'] && isset($payment)) {
			$payment->setMethod($checkoutInfo['payment_method']);
			$this->view->payment_method = $checkoutInfo['payment_method'];
		}

		return ['shipping' => $shipping, 'payment' => $payment];
	}
}
