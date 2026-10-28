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

namespace Vvveb\Controller\User;

use function Vvveb\__;
use function Vvveb\model;
use Vvveb\System\SubscriptionManager;
use Vvveb\System\User\User;

#[\AllowDynamicProperties]
class Subscription extends Base {
	/** Subscription status IDs matching the seed data. */
	public const STATUS_PENDING   = 1;
	public const STATUS_ACTIVE    = 2;
	public const STATUS_FAILED    = 3;
	public const STATUS_CANCELLED = 4;
	public const STATUS_DENIED    = 5;
	public const STATUS_EXPIRED   = 6;

	/**
	 * Get the subscription ID from the request.
	 *
	 * @return int|string|false
	 */
	private function getSubscriptionId(): int|string|false {
		return $this->request->get['subscription_id'] ?? false;
	}

	/**
	 * Verify that the subscription belongs to the current user.
	 *
	 * @param int|string $subscriptionId
	 *
	 * @return array<string, mixed>|false The subscription data or false if not found / not owned.
	 */
	private function getOwnedSubscription(int|string $subscriptionId): array|false {
		$user    = User::current();
		$userId  = $user['user_id'] ?? 0;

		$model   = model('subscription');
		$result  = $model->get([
			'subscription_id' => $subscriptionId,
			'language_id'     => $this->global['language_id'] ?? 1,
		] + $this->global);

		if ($result && (int)($result['user_id'] ?? 0) === (int)$userId) {
			return $result;
		}

		return false;
	}

	/**
	 * Change subscription status and record a log entry.
	 *
	 * @param int    $statusId
	 * @param string $note
	 *
	 * @return void
	 */
	protected function changeStatus(int $statusId, string $note): void {
		$subscriptionId = $this->getSubscriptionId();

		if (!$subscriptionId) {
			$this->view->errors[] = __('Subscription ID is required!');

			return;
		}

		$subscription = $this->getOwnedSubscription($subscriptionId);

		if (!$subscription) {
			$this->view->errors[] = __('Subscription not found!');

			return;
		}

		$this->writeSubscription($subscriptionId, $statusId, [
			'subscription_status_id' => $statusId,
		], $note);
	}

	/**
	 * Update subscription fields and record a log entry.
	 *
	 * @param int|string           $subscriptionId
	 * @param int                  $statusId
	 * @param array<string, mixed> $fields
	 * @param string               $note
	 *
	 * @return void
	 */
	protected function writeSubscription(int|string $subscriptionId, int $statusId, array $fields, string $note): void {
		$model   = model('subscription');
		$result  = $model->edit([
			'subscription' => $fields + [
				'updated_at' => date('Y-m-d H:i:s'),
			],
			'subscription_id' => $subscriptionId,
		] + $this->global);

		if ($result) {
			$logModel = model('subscription_log');
			$logModel->add([
				'subscription_log' => [
					'subscription_id'        => $subscriptionId,
					'subscription_status_id' => $statusId,
					'notify'                 => 1,
					'note'                   => $note,
					'created_at'             => date('Y-m-d H:i:s'),
				],
			] + $this->global);

			$this->view->success[] = __('Subscription updated!');
		} else {
			$this->view->errors[] = __('Error updating subscription!');
		}
	}

	/**
	 * Display subscription detail page.
	 *
	 * @return void
	 */
	public function index(): void {
		$subscriptionId = $this->getSubscriptionId();

		if ($subscriptionId) {
			$subscription = $this->getOwnedSubscription($subscriptionId);

			if ($subscription) {
				$this->view->subscription = $subscription;
				$this->view->plans        = $this->productPlans((int)($subscription['product_id'] ?? 0));

				//load subscription logs
				$logModel = model('subscription_log');
				$logs     = $logModel->getAll([
					'subscription_id' => $subscriptionId,
					'language_id'     => $this->global['language_id'] ?? 1,
				] + $this->global);

				$this->view->subscription_logs = $logs['subscription_log'] ?? [];

				return;
			}
		}

		$this->view->errors[] = __('Subscription not found!');
	}

	/**
	 * Load the owned subscription with its original order (if any).
	 *
	 * @param int|string $subscriptionId Subscription id.
	 *
	 * @return array{0: array<string, mixed>|false, 1: array<string, mixed>|null} [subscription, order].
	 */
	private function loadOwnedSubscription(int|string $subscriptionId): array {
		$subscription = $this->getOwnedSubscription($subscriptionId);
		$order        = null;

		if ($subscription && !empty($subscription['order_id'])) {
			try {
				$orderResult   = model('order')->get(['order_id' => (int)$subscription['order_id']] + $this->global);
				$order         = $orderResult['order'] ?? null;
			} catch (\Throwable $e) {
			}
		}

		return [$subscription, $order];
	}

	/**
	 * Validate a state-changing request (POST + CSRF token).
	 *
	 * Lifecycle actions now propagate to the live gateway, so plain GET
	 * links must never trigger them.
	 *
	 * @return bool True when the request is a valid POST with matching token.
	 */
	private function validPost(): bool {
		$token   = (string)($this->request->post['csrf'] ?? '');
		$session = (string)$this->session->get('csrf');

		if ($this->request->getMethod() !== 'post' || $session === '' || !hash_equals($session, $token)) {
			$this->view->errors[] = __('Invalid request!');
			$this->index();

			return false;
		}

		return true;
	}

	/**
	 * Run a gateway lifecycle operation before changing local status.
	 *
	 * @param string $operation Gateway operation (cancelSubscription, ...).
	 * @param int    $statusId  Target local status on success.
	 * @param string $note      Log note.
	 *
	 * @return void
	 */
	private function applyLifecycle(string $operation, int $statusId, string $note): void {
		if (!$this->validPost()) {
			return;
		}

		$subscriptionId = $this->getSubscriptionId();

		if ($subscriptionId) {
			[$subscription, $order] = $this->loadOwnedSubscription($subscriptionId);

			if ($subscription && ($error = SubscriptionManager::gatewayOperation($operation, $subscription, $order))) {
				$this->view->errors[] = $error;
				$this->index();

				return;
			}
		}

		$this->changeStatus($statusId, $note);
		$this->index();
	}

	/**
	 * Available plans (with prices) for a product, for the swap selector.
	 *
	 * @param int $productId Local product id.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function productPlans(int $productId): array {
		if (!$productId) {
			return [];
		}

		try {
			$rows = model('product_subscription')->getAll([
				'product_id'  => $productId,
				'language_id' => $this->global['language_id'] ?? 1,
			]);
		} catch (\Throwable $e) {
			return [];
		}

		foreach ((array)$rows as $list) {
			if (is_array($list) && $list && is_array(reset($list))) {
				return array_values($list);
			}
		}

		return [];
	}

	/**
	 * Swap the subscription to another plan of the same product.
	 *
	 * Gateway first (price ensured from the plan mapping), then local plan
	 * fields. No new trial is granted on swap.
	 *
	 * @return void
	 */
	public function swap(): void {
		if (!$this->validPost()) {
			return;
		}

		$subscriptionId = $this->getSubscriptionId();
		$planId         = (int)($this->request->post['subscription_plan_id'] ?? 0);

		if (!$subscriptionId || !$planId) {
			$this->view->errors[] = __('Subscription and plan are required!');
			$this->index();

			return;
		}

		[$subscription, $order] = $this->loadOwnedSubscription($subscriptionId);

		if (!$subscription) {
			$this->view->errors[] = __('Subscription not found!');
			$this->index();

			return;
		}

		if ((int)($subscription['subscription_status_id'] ?? 0) !== self::STATUS_ACTIVE) {
			$this->view->errors[] = __('Only active subscriptions can change plan!');
			$this->index();

			return;
		}

		if ((int)($subscription['subscription_plan_id'] ?? 0) === $planId) {
			$this->view->errors[] = __('Already on this plan!');
			$this->index();

			return;
		}

		$offer = SubscriptionManager::planOffer((int)$subscription['product_id'], $planId, (int)($this->global['language_id'] ?? 1));

		if (!$offer) {
			$this->view->errors[] = __('Plan not available for this product!');
			$this->index();

			return;
		}

		$extra = [
			'subscription_plan_id' => $planId,
			'product_id'           => (int)$subscription['product_id'],
			'language_id'          => (int)($this->global['language_id'] ?? 1),
			'currency'             => $order['currency'] ?? '',
		];

		if (($error = SubscriptionManager::gatewayOperation('swapSubscription', $subscription, $order, $extra))) {
			$this->view->errors[] = $error;
			$this->index();

			return;
		}

		$this->writeSubscription($subscriptionId, (int)$subscription['subscription_status_id'], [
			'subscription_plan_id' => $planId,
			'price'                => $offer['price'] ?? $subscription['price'],
			'period'               => $offer['period'] ?? $subscription['period'],
			'cycle'                => $offer['cycle'] ?? $subscription['cycle'],
			'length'               => $offer['length'] ?? $subscription['length'],
			'trial_status'         => 0,
			'trial_left'           => 0,
		], sprintf(__('Swapped to plan %s by customer'), $offer['name'] ?? $planId));
		$this->index();
	}

	/**
	 * Skip the next payment (push the next billing date out one interval).
	 *
	 * @return void
	 */
	public function skip(): void {
		if (!$this->validPost()) {
			return;
		}

		$subscriptionId = $this->getSubscriptionId();

		if (!$subscriptionId) {
			$this->view->errors[] = __('Subscription ID is required!');
			$this->index();

			return;
		}

		[$subscription, $order] = $this->loadOwnedSubscription($subscriptionId);

		if (!$subscription) {
			$this->view->errors[] = __('Subscription not found!');
			$this->index();

			return;
		}

		if ((int)($subscription['subscription_status_id'] ?? 0) !== self::STATUS_ACTIVE) {
			$this->view->errors[] = __('Only active subscriptions can skip a payment!');
			$this->index();

			return;
		}

		$base    = max(strtotime($subscription['date_next'] ?? 'now') ?: time(), time());
		$newDate = SubscriptionManager::addInterval(date('Y-m-d H:i:s', $base), (string)($subscription['period'] ?? 'month'), (int)($subscription['cycle'] ?? 1));

		if (($error = SubscriptionManager::gatewayOperation('skipSubscription', $subscription, $order, ['delay_until' => strtotime($newDate)]))) {
			$this->view->errors[] = $error;
			$this->index();

			return;
		}

		$this->writeSubscription($subscriptionId, (int)$subscription['subscription_status_id'], [
			'date_next' => $newDate,
		], sprintf(__('Skipped one payment by customer, next payment %s'), $newDate));
		$this->index();
	}

	/**
	 * Cancel the subscription (customer-initiated).
	 *
	 * @return void
	 */
	public function cancel(): void {
		$note = $this->request->post['note'] ?? __('Cancelled by customer');
		$this->applyLifecycle('cancelSubscription', self::STATUS_CANCELLED, $note);
	}

	/**
	 * Pause the subscription (customer-initiated).
	 *
	 * @return void
	 */
	public function pause(): void {
		$note = $this->request->post['note'] ?? __('Paused by customer');
		$this->applyLifecycle('pauseSubscription', self::STATUS_PENDING, $note);
	}

	/**
	 * Resume the subscription (customer-initiated).
	 *
	 * @return void
	 */
	public function resume(): void {
		$note = $this->request->post['note'] ?? __('Resumed by customer');
		$this->applyLifecycle('resumeSubscription', self::STATUS_ACTIVE, $note);
	}
}
