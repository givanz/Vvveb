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

namespace Vvveb\Controller\Order;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use function Vvveb\model;
use Vvveb\System\SubscriptionManager;

class Subscription extends Crud {
	protected string $type = 'subscription';

	protected string $controller = 'subscription';

	protected string $module = 'order';

	/** Subscription status IDs matching the seed data. */
	public const STATUS_PENDING   = 1;
	public const STATUS_ACTIVE    = 2;
	public const STATUS_FAILED    = 3;
	public const STATUS_CANCELLED = 4;
	public const STATUS_DENIED    = 5;
	public const STATUS_EXPIRED   = 6;

	/**
	 * Display the subscription edit form with period, trial, status, and log data.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();

		$this->view->period       = ['day' => __('Day'), 'week' => __('Week'), 'month' => __('Month'),  'year' => __('Year')];
		$this->view->trial_period = $this->view->period;

		//load subscription statuses
		$statusModel       = model('subscription_status');
		$statuses          = $statusModel->getAll(['language_id' => $this->global['language_id'] ?? 1] + $this->global);
		$statusOptions     = [];

		if (isset($statuses['subscription_status'])) {
			foreach ($statuses['subscription_status'] as $status) {
				$statusOptions[$status['subscription_status_id']] = $status['name'];
			}
		}

		$this->view->subscription_status = $statusOptions;

		//load subscription logs for the current subscription
		$subscriptionId = $this->request->get['subscription_id'] ?? false;

		if ($subscriptionId) {
			$this->loadLogs($subscriptionId);
		}
	}

	/**
	 * Load subscription history logs and pass them to the view.
	 *
	 * @param int|string $subscriptionId
	 *
	 * @return void
	 */
	private function loadLogs(int|string $subscriptionId): void {
		$logModel = model('subscription_log');
		$logs     = $logModel->getAll([
			'subscription_id' => $subscriptionId,
			'language_id'     => $this->global['language_id'] ?? 1,
		] + $this->global);

		$this->view->subscription_logs = $logs['subscription_log'] ?? [];
	}

	/**
	 * Change the subscription status and record a log entry.
	 *
	 * @param int    $statusId Target subscription_status_id.
	 * @param string $note     Optional note for the log entry.
	 *
	 * @return void
	 */
	protected function changeStatus(int $statusId, string $note = ''): void {
		$this->checkCsrf();

		$subscriptionId = $this->request->get['subscription_id'] ?? false;

		if (!$subscriptionId) {
			$this->view->errors[] = __('Subscription ID is required!');

			return;
		}

		$model    = model($this->type);
		$result   = $model->edit([
			'subscription' => [
				'subscription_status_id' => $statusId,
				'updated_at'             => date('Y-m-d H:i:s'),
			],
			'subscription_id' => $subscriptionId,
		] + $this->global);

		if ($result) {
			//record log entry
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

			$this->view->success[] = __('Subscription status updated!');
		} else {
			$this->view->errors[] = __('Error updating subscription status!');
		}
	}

	/**
	 * Load a subscription row with its original order (if any).
	 *
	 * @param int|string $subscriptionId Subscription id.
	 *
	 * @return array{0: array<string, mixed>|null, 1: array<string, mixed>|null} [subscription, order].
	 */
	private function loadSubscription(int|string $subscriptionId): array {
		$subscription = null;
		$order        = null;

		try {
			$result = model('subscription')->get([
				'subscription_id' => $subscriptionId,
				'language_id'     => $this->global['language_id'] ?? 1,
			] + $this->global);

			if (is_array($result) && (isset($result['subscription_id']) || isset($result['subscription']))) {
				$subscription = $result['subscription'] ?? $result;
			}
		} catch (\Throwable $e) {
		}

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
	 * Run a gateway lifecycle operation before changing local status.
	 *
	 * The local status only changes when there is no gateway provisioning
	 * or the gateway call succeeded; gateway failures are shown and the
	 * local status is left untouched.
	 *
	 * @param string $operation Gateway operation (cancelSubscription, ...).
	 * @param int    $statusId  Target local status on success.
	 * @param string $note      Log note.
	 *
	 * @return void
	 */
	private function applyLifecycle(string $operation, int $statusId, string $note): void {
		//lifecycle actions propagate to the live gateway: never accept plain GET links
		if ($this->request->getMethod() !== 'post') {
			$this->view->errors[] = __('Invalid request!');
			$this->index();

			return;
		}

		$subscriptionId = $this->request->get['subscription_id'] ?? $this->request->post['subscription_id'] ?? false;

		if ($subscriptionId) {
			[$subscription, $order] = $this->loadSubscription($subscriptionId);

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
	 * Cancel the subscription.
	 *
	 * @return void
	 */
	public function cancel(): void {
		$note = $this->request->post['note'] ?? __('Cancelled by admin');
		$this->applyLifecycle('cancelSubscription', self::STATUS_CANCELLED, $note);
	}

	/**
	 * Pause the subscription (set to pending).
	 *
	 * @return void
	 */
	public function pause(): void {
		$note = $this->request->post['note'] ?? __('Paused by admin');
		$this->applyLifecycle('pauseSubscription', self::STATUS_PENDING, $note);
	}

	/**
	 * Resume/activate the subscription.
	 *
	 * @return void
	 */
	public function resume(): void {
		$note = $this->request->post['note'] ?? __('Resumed by admin');
		$this->applyLifecycle('resumeSubscription', self::STATUS_ACTIVE, $note);
	}

	/**
	 * Mark subscription as expired.
	 *
	 * For gateway-provisioned active subscriptions billing stops at the end
	 * of the current term instead of immediately; the gateway webhook flips
	 * the local status to cancelled when the term actually ends. Rows that
	 * already ended (or were never provisioned) expire locally only.
	 *
	 * @return void
	 */
	public function expire(): void {
		$note = $this->request->post['note'] ?? __('Expired by admin');

		if ($this->request->getMethod() !== 'post') {
			$this->view->errors[] = __('Invalid request!');
			$this->index();

			return;
		}

		$subscriptionId = $this->request->get['subscription_id'] ?? $this->request->post['subscription_id'] ?? false;

		if ($subscriptionId) {
			[$subscription, $order] = $this->loadSubscription($subscriptionId);
			$statusId               = (int)($subscription['subscription_status_id'] ?? 0);

			if ($subscription && in_array($statusId, [self::STATUS_PENDING, self::STATUS_ACTIVE], true)) {
				if ($error = SubscriptionManager::gatewayOperation('cancelSubscription', $subscription, $order, ['at_period_end' => true])) {
					$this->view->errors[] = $error;
					$this->index();

					return;
				}

				$note .= ' ' . __('(billing ends at term end)');
			}
		}

		$this->changeStatus(self::STATUS_EXPIRED, $note);
		$this->index();
	}

	/**
	 * Add a log/note entry to a subscription without changing status.
	 *
	 * @return void
	 */
	public function addLog(): void {
		$this->checkCsrf();

		$subscriptionId = $this->request->get['subscription_id'] ?? false;
		$note           = $this->request->post['note'] ?? '';
		$statusId       = (int)($this->request->post['subscription_status_id'] ?? 0);

		if (!$subscriptionId || !$note) {
			$this->view->errors[] = __('Subscription ID and note are required!');

			return;
		}

		$logModel = model('subscription_log');
		$result   = $logModel->add([
			'subscription_log' => [
				'subscription_id'        => $subscriptionId,
				'subscription_status_id' => $statusId ?: self::STATUS_ACTIVE,
				'notify'                 => 0,
				'note'                   => $note,
				'created_at'             => date('Y-m-d H:i:s'),
			],
		] + $this->global);

		if ($result) {
			$this->view->success[] = __('Log entry added!');
		} else {
			$this->view->errors[] = __('Error adding log entry!');
		}

		$this->index();
	}
}
