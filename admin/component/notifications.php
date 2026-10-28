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

use function Vvveb\commentStatusBadgeClass;
use function Vvveb\orderStatusBadgeClass;
use Vvveb\Sql\StatSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Update;
use function Vvveb\url;

class Notifications extends ComponentBase {
	/** @var array<string, mixed> Default options (empty for notifications). */
	public static array $defaultOptions = [
	];

	/** @var array<string, mixed> Resolved options for this component. */
	public array $options = [];

	/** @var StatSQL|null Statistics query instance. */
	private ?StatSQL $stats = null;

	/** @var int Total notification count. */
	private int $count = 0;

	/** @var array<string, mixed> Aggregated notification data. */
	private array $notifications = [];

	/** @var array<string, mixed> Menu badge data for sidebar notifications. */
	private array $menu = [];

	/**
	 * Gather order notification counts and update menu badges.
	 *
	 * @return void
	 */
	protected function orders(): void {
		$orderCount     = $this->stats->getOrdersCount($this->options)['orders'] ?? [];

		$orderStatusNew = 1; //get from site config
		$newOrders      = ($orderCount[$orderStatusNew]['count'] ?? 0);

		if ($newOrders > 0) {
			$this->count += $newOrders;
			$this->menu['sales']                = [];
			$this->menu['sales']['badge']       =  $newOrders;
			$this->menu['sales']['badge-class'] =  'badge bg-success-subtle text-body mx-2';
		}

		//link order status notifications to the orders list filtered by status
		$url = ['module' => 'order/orders'];
		//re-key by human readable status name
		foreach ($orderCount as $type => $orders) {
			if (isset($orders['name'])) {
				$orders['badge']             = orderStatusBadgeClass($orders['order_status_id']);
				$orders['icon']              = 'shopping-bag';
				$orders['url']               = url($url + ['filter[order_status_id]' => $orders['order_status_id']]);
				$orderCount[$orders['name']] = $orders;
				unset($orderCount[$type]);
			}
		}

		$this->notifications['orders'] = $orderCount + $this->notifications['orders'];
	}

	/**
	 * Gather user registration counts for current month and year.
	 *
	 * @return void
	 */
	protected function users(): void {
		$userCount      = $this->stats->getUsersCount($this->options)['users'] ?? [];

		$currentYear  = (int)(date('Y'));
		$currentMonth = (int)(date('n'));

		$monthCount = 0;
		$yearCount  = 0;

		foreach ($userCount as $m) {
			//db drivers return year/month as strings, compare as integers
			$year  = (int)($m['year'] ?? 0);
			$month = (int)($m['month'] ?? 0);

			if ($year === $currentYear) {
				$yearCount += $m['count'];
			}

			if ($year === $currentYear && $month === $currentMonth) {
				$monthCount += $m['count'];
			}
		}

		$url = url(['module' => 'user/users']);

		$this->notifications['users']['month']['count'] = $monthCount;
		$this->notifications['users']['year']['count']  = $yearCount;
		$this->notifications['users']['year']['url']    = $url;
		$this->notifications['users']['month']['url']   = $url;
	}

	/**
	 * Gather product stock notification counts.
	 *
	 * @return void
	 */
	protected function products(): void {
		$productCount = $this->stats->getProductStockCount($this->options)['products'] ?? [];
		$url          = ['module' => 'product/products'];

		foreach ($productCount as $type => &$products) {
			$products['icon']  = 'box';
			$products['badge'] = commentStatusBadgeClass($products['stock_status_id']);
			$products['url']   = url($url + ['filter[stock_status_id]' => $products['stock_status_id']]);
		}

		$this->notifications['products'] = $productCount + $this->notifications['products'];
	}

	/**
	 * Aggregate status notifications for comments-like content types.
	 *
	 * Shared by comments(), reviews() and questions(): counts new items into
	 * the total and menu badges, then re-keys counts by status name with
	 * icon, badge and URL for each status.
	 *
	 * @param string            $key      Result key in notifications.
	 * @param array<int, mixed> $counts   Status rows keyed by status id.
	 * @param string            $module   Module for status filter URLs.
	 * @param string            $icon     Icon class for all statuses.
	 * @param string            $menu     Menu key for the badge.
	 * @param string|null       $menuItem Submenu item key for the badge, or null for none.
	 *
	 * @return void
	 */
	protected function statusNotifications(string $key, array $counts, string $module, string $icon, string $menu, ?string $menuItem = null): void {
		$statusNames = [
			0 => 'pending',
			1 => 'approved',
			2 => 'spam',
			3 => 'trash',
		];

		$newItems = ($counts[0]['count'] ?? 0); //get from site config

		if ($newItems > 0) {
			$this->count += $newItems;
			$this->menu[$menu]['badge']       =  ($this->menu[$menu]['badge'] ?? 0) + $newItems;
			$this->menu[$menu]['badge-class'] =  'badge bg-primary-subtle text-body mx-2';

			if ($menuItem) {
				$this->menu[$menu]['items'][$menuItem]['badge']       =  $newItems;
				$this->menu[$menu]['items'][$menuItem]['badge-class'] =  'badge bg-primary-subtle text-body mx-2';
			}
		}

		$url = ['module' => $module];

		foreach ($counts as $type => $items) {
			$items['icon']                        = $icon;
			$items['badge']                       = commentStatusBadgeClass($items['status']);
			$items['url']                         = url($url + ['status' => $items['status']]);
			$counts[$statusNames[$type] ?? $type] = $items;
			unset($counts[$type]);
		}

		$this->notifications[$key] = $counts + $this->notifications[$key];
	}

	/**
	 * Gather comment notification counts and update menu badges.
	 *
	 * @return void
	 */
	protected function comments(): void {
		$commentCount = $this->stats->getCommentsCount($this->options)['comments'] ?? [];

		$this->statusNotifications('comments', $commentCount, 'content/comments', 'message', 'post', 'comments');
	}

	/**
	 * Gather review notification counts and update menu badges.
	 *
	 * @return void
	 */
	protected function reviews(): void {
		$reviewCount = $this->stats->getReviewsCount($this->options)['reviews'] ?? [];

		$this->statusNotifications('reviews', $reviewCount, 'product/product-reviews', 'messages', 'product', 'reviews');
	}

	/**
	 * Gather question notification counts and update menu badges.
	 *
	 * @return void
	 */
	protected function questions(): void {
		$questionCount = $this->stats->getQuestionsCount($this->options)['questions'] ?? [];

		$this->statusNotifications('questions', $questionCount, 'product/product-questions', 'help-circle', 'product', 'questions');
	}

	/**
	 * Apply notification menu badges to the view menu on first request.
	 *
	 * @param array<string, mixed> $results Component results.
	 * @param int                  $index   Request index.
	 *
	 * @return void
	 */
	public function request(array &$results, int $index = 0): void {
		//add menu notification count

		if ($results['menu'] && $index === 0) {
			$view       = View::getInstance();

			if (isset($view->menu) && $view->menu) {
				$view->menu = array_replace_recursive($view->menu, $results['menu']);
			}
		}
	}

	/**
	 * Get aggregated notification results for orders, users, products, comments,
	 * reviews, questions, and system updates.
	 *
	 * @return array{notifications: array<string, mixed>, count: int, menu: array<string, mixed>}
	 */
	public function results(): array {
		// return [];
		$this->notifications = [
			'updates' => [
				'core' => ['hasUpdate' => 0, 'version' => V_VERSION],
			],
			'orders' => [
				'processing' => ['count' => 0, 'icon' => 'shopping-bag', 'badge' => 'bg-primary-subtle text-body'],
				'complete'   => ['count' => 0, 'icon' => 'shopping-bag', 'badge' => 'bg-success-subtle text-body'],
				'processed'  => ['count' => 0, 'icon' => 'shopping-bag', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'users' => [
				'month' => ['count' => 0, 'icon' => 'user', 'badge' => 'bg-secondary-subtle text-body'],
				'year'  => ['count' => 0, 'icon' => 'user', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'products' => [
			],
			'comments' => [
				'pending'  => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-primary-subtle text-body'],
				'approved' => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'     => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'    => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'reviews' => [
				'pending'  => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-primary-subtle text-body'],
				'approved' => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'     => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'    => ['count' => 0, 'icon' => 'message', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'questions' => [
				'pending'  => ['count' => 0, 'icon' => 'help-circle', 'badge' => 'bg-primary-subtle text-body'],
				'approved' => ['count' => 0, 'icon' => 'help-circle', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'     => ['count' => 0, 'icon' => 'help-circle', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'    => ['count' => 0, 'icon' => 'help-circle', 'badge' => 'bg-secondary-subtle text-body'],
			],
		];

		$this->stats = new StatSQL();
		$this->orders();
		$this->users();
		$this->products();
		$this->comments();
		$this->reviews();
		$this->questions();

		$update  = new Update();
		//$updates = $update->checkUpdates('core') ?? [];

		//$this->notifications['updates']['core'] = $updates;
		//$this->count += max($updates['hasUpdate'] ?? 0, 0);

		$results = [
			'notifications' => $this->notifications,
			'count'         => $this->count,
			'menu'          => $this->menu,
		];

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
