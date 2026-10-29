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

namespace Vvveb\Controller\Settings;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\Tax_rateSQL;
use Vvveb\Sql\Tax_ruleSQL;
use Vvveb\System\Cart\Tax;

class TaxType extends Crud {
	/** @var string The entity type identifier */
	protected string $type = 'tax_type';

	/** @var string The controller route slug */
	protected string $controller = 'tax-type';

	/** @var string The module namespace */
	protected string $module = 'settings';

	/**
	 * Save a tax type and its associated tax rules.
	 *
	 * @return void
	 */
	public function save(): void {
		$tax_rule     = $this->request->post['tax_rule'] ?? [];
		$tax_type_id  = $this->request->get['tax_type_id'] ?? false;

		if ($tax_type_id) {
			$taxRules = new Tax_ruleSQL();
			$taxRules->add(['tax_type_id' => $tax_type_id, 'tax_rule' => $tax_rule]);
		}

		parent::save();
		Tax::clearRulesCache();
	}

	/**
	 * Delete tax types and drop the cached region rules.
	 *
	 * @return void
	 */
	public function delete(): void {
		parent::delete();
		Tax::clearRulesCache();
	}

	/**
	 * Display the tax type edit/create form with tax rules and rates.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();
		$tax_type_id = $this->request->get['tax_type_id'] ?? false;

		if ($tax_type_id) {
			$taxRules              = new Tax_ruleSQL();
			$taxRates              = new Tax_rateSQL();
			$tax_rates             = $taxRates->getAll(['tax_type_id' => $tax_type_id]);
			$this->view->tax_rates = $tax_rates['tax_rate'];

			$tax_rules             = $taxRules->getAll(['tax_type_id' => $tax_type_id]);
			$this->view->tax_rules = $tax_rules['tax_rule'];
		}

		$this->view->tax_type_id  = $tax_type_id;
		$this->view->based        = ['shipping' => __('Shipping Address'), 'payment' => __('Payment Address'), 'store' => __('Store Address')];
	}
}
