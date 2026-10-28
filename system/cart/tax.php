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

use Vvveb\Sql\Tax_typeSQL;
use Vvveb\System\Cache;

/** @phpstan-consistent-constructor */
class Tax {
	/** @var array<int, array<int, array{tax_rate_id: int|string, name: string, rate: string, type: string, priority: mixed, based: string}>> */
	private array $taxRates = [];

	/**
	 * Tax amounts are rounded per order line to this many decimals
	 * (currency precision, set from the cart currency on each refresh).
	 * Line-level rounding keeps the order tax total identical to the sum
	 * of displayed line taxes, so invoices never drift by a cent.
	 *
	 * @var int
	 */
	public int $taxPrecision = 2;

	/**
	 * When true, product prices passed to addTaxes()/getRates() already
	 * include tax (e.g. EU catalogue prices) and the tax portion is backed
	 * out instead of added on top. Driven by the per-site
	 * `prices_include_tax` setting. Fee totals added via addTax() are
	 * always net and unaffected.
	 *
	 * @var bool
	 */
	public bool $pricesIncludeTax = false;

	public static function getInstance(): static {
		static $inst;

		if ($inst === null) {
			$inst = new static();
		}

		return $inst;
	}

	public function __construct() {}

	/**
	 * Current generation of the cached region rules.
	 *
	 * Tax rule rows are cached per country/region/based/group tuple and the
	 * individual tuples cannot be enumerated for deletion on every driver,
	 * so the generation is part of the cache key and invalidation bumps it.
	 * Stale generations expire naturally via the rules TTL.
	 *
	 * @return int
	 */
	public static function rulesCacheVersion(): int {
		$cache   = Cache::getInstance();
		$version = $cache->get(APP, 'tax_rates_version');

		if ($version === null || !is_numeric($version)) {
			$version = 1;
			$cache->set(APP, 'tax_rates_version', $version, 259200);
		}

		return (int)$version;
	}

	/**
	 * Invalidate all cached region rules.
	 *
	 * Rules are written under the storefront namespace while admin saves run
	 * under a different APP namespace, so bump the generation in both.
	 *
	 * @return void
	 */
	public static function clearRulesCache(): void {
		$cache = Cache::getInstance();

		foreach (array_unique([APP, 'app', 'admin']) as $namespace) {
			try {
				$cache->set($namespace, 'tax_rates_version', time(), 259200);
			} catch (\Throwable $e) {
			}
		}
	}

	/**
	 * Set tax rules for a specific region.
	 *
	 * @param int|string      $country_id
	 * @param int|string      $region_id
	 * @param string          $based
	 * @param int|string|null $user_group_id Customer group filter. Null keeps
	 *                                       the historic unfiltered behaviour.
	 */
	public function setRegionRules(int|string $country_id, int|string $region_id, string $based = 'store', int|string|null $user_group_id = null): void {
		$cache = Cache::getInstance();
		$group = ($user_group_id === null || $user_group_id === '') ? 'all' : (int)$user_group_id;
		$key   = sprintf('tax_rates.%d.%s.%s.%s.%s', static::rulesCacheVersion(), $country_id, $region_id, $based, $group);
		$rules = $cache->cache(APP, $key, function() use ($country_id, $region_id, $based, $user_group_id): array {
			$taxType = new Tax_typeSQL();
			$params  = ['country_id' => $country_id, 'region_id' => $region_id, 'based' => $based];

			if ($user_group_id !== null && $user_group_id !== '') {
				$params['user_group_id'] = $user_group_id;
			}

			$taxRules = $taxType->getRegionRules($params);

			return $taxRules['tax_rule'] ?? [];
		}, 259200);

		foreach ($rules as $rate) {
			$this->taxRates[$rate['tax_type_id']][$rate['tax_rate_id']] = [
				'tax_rate_id' => $rate['tax_rate_id'],
				'name'        => $rate['name'],
				'rate'        => $rate['rate'],
				'type'        => $rate['type'],
				'priority'    => $rate['priority'],
				'based'       => $based,
			];
		}
	}

	/**
	 * Split a tax-inclusive (gross) amount into net amount and tax amount
	 * using the currently resolved rates for the given tax type.
	 *
	 * Percent rates are summed, fixed amounts are subtracted first
	 * (clamped to the gross amount). The tax is rounded to taxPrecision
	 * and the net derived from it so net + tax === gross.
	 *
	 * @param float|int|string $gross
	 * @param int|string       $taxTypeId
	 *
	 * @return array{0: float, 1: float} [net, tax]
	 */
	public function extractTax(float|int|string $gross, int|string $taxTypeId): array {
		$gross   = (float)$gross;
		$percent = 0.0;
		$fixed   = 0.0;

		foreach ($this->taxRates[$taxTypeId] ?? [] as $taxRate) {
			if ($taxRate['type'] === 'f') {
				$fixed += (float)$taxRate['rate'];
			} else {
				$percent += (float)$taxRate['rate'];
			}
		}

		if ($percent <= 0 && $fixed <= 0) {
			return [$gross, 0.0];
		}

		$fixed = min($fixed, $gross);
		$tax   = round($gross - (($gross - $fixed) / (1 + $percent / 100)), $this->taxPrecision);

		return [$gross - $tax, $tax];
	}

	/**
	 * Calculate one tax rate amount for a value.
	 *
	 * @param float                $value
	 * @param array<string, mixed> $taxRate Must contain rate/type keys.
	 *
	 * @return float
	 */
	private function rateAmount(float $value, array $taxRate): float {
		$rate = (float)($taxRate['rate'] ?? 0);

		if (($taxRate['type'] ?? '') === 'f') {//fixed
			return $rate;
		}

		if (($taxRate['type'] ?? '') === 'p') {//percent
			return ($value / 100) * $rate;
		}

		return 0.0;
	}

	/**
	 * Calculate and add taxes for a given value.
	 *
	 * @param float|int|string $value
	 * @param int|string       $taxTypeId
	 * @param bool             $onlyTax   When true only the tax amount is returned, otherwise value + tax.
	 *
	 * @return float|int
	 */
	public function addTaxes(float|int|string $value, int|string $taxTypeId, bool $onlyTax = false): float|int {
		if ($this->pricesIncludeTax) {
			//value already includes tax, back the tax portion out so the
			//total stays unchanged instead of stacking tax on tax
			[, $tax] = $this->extractTax((float)$value, $taxTypeId);

			if ($onlyTax) {
				return $tax;
			}

			return $value;
		}

		$amount = 0;

		if (isset($this->taxRates[$taxTypeId])) {
			foreach ($this->taxRates[$taxTypeId] as $taxRate) {
				$amount += $this->rateAmount((float)$value, $taxRate);
			}
		}

		if ($onlyTax) {
			return $amount;
		}

		return $value + $amount;
	}

	/**
	 * Get individual tax rates for a given value and tax type.
	 *
	 * @param float|int|string $value
	 * @param int|string       $taxTypeId
	 *
	 * @return array<int, array{tax_rate_id: int|string, name: string, rate: string, type: string, amount: float|int}>
	 */
	public function getRates(float|int|string $value, int|string $taxTypeId): array {
		$taxRate_data = [];

		if ($this->pricesIncludeTax) {
			//back the net value out once so per-rate amounts below sum to
			//the included tax instead of adding tax on top of gross
			[$value] = $this->extractTax((float)$value, $taxTypeId);
		}

		if (isset($this->taxRates[$taxTypeId])) {
			foreach ($this->taxRates[$taxTypeId] as $taxRate) {
				$amount = $taxRate_data[$taxRate['tax_rate_id']]['amount'] ?? 0;
				$amount += $this->rateAmount((float)$value, $taxRate);

				$taxRate_data[$taxRate['tax_rate_id']] = [
					'tax_rate_id' => $taxRate['tax_rate_id'],
					'name'        => $taxRate['name'],
					'rate'        => $taxRate['rate'],
					'type'        => $taxRate['type'],
					'amount'      => $amount,
				];
			}
		}

		return $taxRate_data;
	}

	/**
	 * Tax for one order line: per-rate amounts rounded to taxPrecision
	 * before summing, so the line total matches the displayed rate
	 * breakdown exactly. Same rounding as TaxTrait::getTaxes().
	 *
	 * @param float|int|string $price
	 * @param int|string       $taxTypeId
	 * @param float|int        $quantity
	 *
	 * @return float
	 */
	public function lineTax(float|int|string $price, int|string $taxTypeId, float|int $quantity = 1): float {
		$total = 0.0;

		foreach ($this->getRates($price, $taxTypeId) as $rate) {
			$total += round($rate['amount'] * $quantity, $this->taxPrecision);
		}

		return $total;
	}

	/**
	 * Split a refund amount into net and tax portions, pro-rata from the
	 * still-unrefunded tax and total. The tax share is rounded to
	 * taxPrecision and clamped to the remaining recorded tax so sequential
	 * partial refunds can never reverse more tax than was collected; net is
	 * derived so net + tax === amount.
	 *
	 * @param float|int|string $amount         Refund amount (gross).
	 * @param float|int|string $remainingTax   Recorded tax not yet refunded.
	 * @param float|int|string $remainingTotal Order balance not yet refunded.
	 *
	 * @return array{0: float, 1: float} [net, tax]
	 */
	public function refundSplit(float|int|string $amount, float|int|string $remainingTax, float|int|string $remainingTotal): array {
		$amount         = (float)$amount;
		$remainingTax   = max(0, (float)$remainingTax);
		$remainingTotal = (float)$remainingTotal;

		if ($amount <= 0 || $remainingTax <= 0 || $remainingTotal <= 0) {
			return [$amount, 0.0];
		}

		$tax = min(round($amount * ($remainingTax / $remainingTotal), $this->taxPrecision), $remainingTax);

		return [$amount - $tax, $tax];
	}

	public function clear(): void {
		$this->taxRates = [];
	}

	/**
	 * Whether any region rules are currently loaded.
	 *
	 * Used to detect a rule set that matches nothing (e.g. destination
	 * zones configured for a different `based` value) so callers can
	 * fall back to the legacy store-based rules.
	 *
	 * @return bool
	 */
	public function hasRates(): bool {
		return $this->taxRates !== [];
	}
}
