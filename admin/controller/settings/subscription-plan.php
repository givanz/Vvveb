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
use function Vvveb\getSetting;
use function Vvveb\model;

class SubscriptionPlan extends Crud {
	/** @var string The entity type identifier */
	protected string $type = 'subscription_plan';

	/** @var string The controller route slug */
	protected string $controller = 'subscription-plan';

	/** @var string The module namespace */
	protected string $module = 'settings';

	/**
	 * Save the plan and propagate interval changes to live gateway subscriptions.
	 *
	 * Gateway prices are immutable, so a period/cycle change creates a new
	 * price and swaps every active Stripe-provisioned subscription on the
	 * plan. Local-only rows are untouched.
	 *
	 * @return void
	 */
	public function save(): void {
		$planId = $this->request->post['subscription_plan_id'] ?? $this->request->get['subscription_plan_id'] ?? false;
		$old    = null;

		if ($planId) {
			try {
				$old = model('subscription_plan')->get([
					'subscription_plan_id' => (int)$planId,
					'language_id'          => $this->global['language_id'] ?? 1,
				] + $this->global);
			} catch (\Throwable $e) {
			}
		}

		parent::save();

		if (!empty($this->view->errors) || !$planId || !is_array($old)) {
			return;
		}

		$new = null;

		try {
			$new = model('subscription_plan')->get([
				'subscription_plan_id' => (int)$planId,
				'language_id'          => $this->global['language_id'] ?? 1,
			] + $this->global);
		} catch (\Throwable $e) {
			return;
		}

		if (!is_array($new)
			|| ((($old['period'] ?? null) === ($new['period'] ?? null))
				&& (($old['cycle'] ?? null) == ($new['cycle'] ?? null)))
		) {
			return;
		}

		if (!class_exists('Vvveb\Plugins\Stripe\System\Stripe')) {
			return;
		}

		$settings = getSetting('stripe', []);

		if (empty($settings['secret_key'])) {
			return;
		}

		try {
			$api    = new \Vvveb\Plugins\Stripe\System\Stripe();
			$result = $api->propagatePlanInterval($new, $settings['secret_key']);
		} catch (\Throwable $e) {
			$this->view->errors[] = __('Plan saved, but interval propagation failed!');

			return;
		}

		$updated = count($result['updated']);
		$failed  = count($result['failed']);

		if ($updated || $failed) {
			$this->view->success[] = sprintf(__('Plan saved! Interval applied to %d subscription(s).'), $updated)
				. ($failed ? ' ' . sprintf(__('%d failed.'), $failed) : '');
		}
	}

	/**
	 * Display the subscription plan edit/create form with period and trial options.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$this->view->period          = ['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'), 'year' => __('Year')];
		$this->view->trial_period    = $this->view->period;
		$this->view->trial_status    = $this->view->status;
	}
}
