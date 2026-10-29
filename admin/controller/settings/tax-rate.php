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
use Vvveb\Sql\Region_groupSQL;
use Vvveb\Sql\Tax_rate_to_user_groupSQL;
use Vvveb\Sql\User_groupSQL;
use Vvveb\System\Cart\Tax;

class TaxRate extends Crud {
	/** @var string The entity type identifier */
	protected string $type = 'tax_rate';

	/** @var string The controller route slug */
	protected string $controller = 'tax-rate';

	/** @var string The module namespace */
	protected string $module = 'settings';

	/**
	 * Display the tax rate edit/create form with region groups and
	 * customer group restrictions.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$geoRegion = new Region_groupSQL();
		$regions	  = $geoRegion->getAll();

		$region_group_id = [];

		foreach ($regions['region_group'] as $region_group) {
			$region_group_id[$region_group['region_group_id']] = $region_group['name'];
		}

		$this->view->type              = ['f' => __('Fixed'), 'p' => __('Percent')];
		$this->view->region_group_id   = $region_group_id;
		$this->view->user_groups       = $this->userGroups();
		$this->view->rate_user_groups  = $this->rateUserGroups();
		$this->view->user_groups_label = __('Customer groups');
		$this->view->user_groups_hint  = __('Leave all unticked to apply this rate to every group.');
	}

	/**
	 * Save a tax rate and its customer group restrictions, then drop the
	 * cached region rules so the new rate applies immediately.
	 *
	 * @return void
	 */
	public function save(): void {
		$tax_rate_id = $this->request->get['tax_rate_id'] ?? false;

		if (!$tax_rate_id) {
			//Crud::save() redirects (and dies) on create, before we could
			//store the group mapping, so suppress the redirect, save the
			//mapping, then redirect manually.
			$this->redirect = false;
			parent::save();
			$new_id         = (int)($this->tax_rate_id ?? 0);

			if (!$new_id) {
				//save failed, errors are already in the view, re-render
				$this->index();

				//return $result;
			}

			$this->saveUserGroups($new_id);
			Tax::clearRulesCache();
			$this->redirect(['module' => $this->module . '/' . $this->controller, 'tax_rate_id' => $new_id]);

			//return $result;
		}

		parent::save();
		$this->saveUserGroups((int)$tax_rate_id);
		Tax::clearRulesCache();
	}

	/**
	 * Delete tax rates and drop the cached region rules.
	 *
	 * Group mappings are removed by the model delete procedure.
	 *
	 * @return void
	 */
	public function delete(): void {
		parent::delete();
		Tax::clearRulesCache();
	}

	/**
	 * All customer groups for the restriction checkboxes.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function userGroups(): array {
		try {
			$options                = $this->global;
			$options['language_id'] = $this->global['language_id'] ?? 1;
			unset($options['limit'], $options['start'], $options['page']);

			$groups = (new User_groupSQL())->getAll($options);

			return $groups['user_group'] ?? [];
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Customer group ids currently restricting the edited rate.
	 * Empty means the rate applies to every group.
	 *
	 * @return array<int, int>
	 */
	private function rateUserGroups(): array {
		$tax_rate_id = (int)($this->request->get['tax_rate_id'] ?? 0);

		if (!$tax_rate_id) {
			return [];
		}

		try {
			$rows     = (new Tax_rate_to_user_groupSQL())->getAll(['tax_rate_id' => $tax_rate_id]);
			$selected = [];

			foreach ($rows['tax_rate_to_user_group'] ?? [] as $row) {
				$selected[] = (int)$row['user_group_id'];
			}

			return $selected;
		} catch (\Throwable $e) {
			return [];
		}
	}

	/**
	 * Replace the customer group restrictions for one rate.
	 * No checkboxes ticked clears the restriction (rate applies to all).
	 *
	 * @param int $tax_rate_id
	 *
	 * @return void
	 */
	private function saveUserGroups(int $tax_rate_id): void {
		if (!$tax_rate_id) {
			return;
		}

		$posted = array_unique(array_map('intval', (array)($this->request->post['user_group'] ?? [])));
		$valid  = [];

		foreach ($this->userGroups() as $group) {
			$id = (int)($group['user_group_id'] ?? 0);

			if ($id && in_array($id, $posted, true)) {
				$valid[] = ['user_group_id' => $id];
			}
		}

		try {
			(new Tax_rate_to_user_groupSQL())->add(['tax_rate_user_group' => $valid, 'tax_rate_id' => $tax_rate_id]);
		} catch (\Throwable $e) {
			$this->view->errors[] = __('Error saving customer groups!');
		}
	}
}
