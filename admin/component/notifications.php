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
use Vvveb\System\Cache;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Update;

class Notifications extends ComponentBase {
	public static $defaultOptions = [
		'start' => 0,
		'limit' => 10,
	];

	public $options = [];

	function results() {
		// return [];
		$cache = Cache::getInstance();

		$notifications = [
			'updates' => [
				'core' => ['hasUpdate' => 1, 'version' => '1.0'],
			],
			'orders' => [
				'processing' => ['count' => 0, 'icon' => 'icon-bag-handle-outline', 'badge' => 'bg-primary-subtle text-body'],
				'complete'   => ['count' => 0, 'icon' => 'icon-bag-handle-outline', 'badge' => 'bg-primary-subtle text-body'],
				'processed'  => ['count' => 0, 'icon' => 'icon-bag-handle-outline', 'badge' => 'bg-primary-subtle text-body'],
			],
			'users' => [
				'month' => ['count' => 0, 'icon' => 'icon-person-outline', 'badge' => 'bg-secondary-subtle text-body'],
				'year'  => ['count' => 0, 'icon' => 'icon-person-outline', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'products' => [
			],
			'comments' => [
				'pending'   => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'approved'  => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'      => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'     => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'reviews' => [
				'pending'   => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'approved'  => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'      => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'     => ['count' => 0, 'icon' => 'la la-comment', 'badge' => 'bg-secondary-subtle text-body'],
			],
			'questions' => [
				'pending'   => ['count' => 0, 'icon' => 'la la-question-circle', 'badge' => 'bg-secondary-subtle text-body'],
				'approved'  => ['count' => 0, 'icon' => 'la la-question-circle', 'badge' => 'bg-secondary-subtle text-body'],
				'spam'      => ['count' => 0, 'icon' => 'la la-question-circle', 'badge' => 'bg-secondary-subtle text-body'],
				'trash'     => ['count' => 0, 'icon' => 'la la-question-circle', 'badge' => 'bg-secondary-subtle text-body'],
			],
		];

		$stats = new StatSQL();

		$orderCount      = $stats->getOrdersCount($this->options)['orders'] ?? [];

		//set order name as array keys
		foreach ($orderCount as $type => $orders) {
			if (isset($orders['name'])) {
				$orders['badge']             = orderStatusBadgeClass($orders['order_status_id']);
				$orders['icon']              = 'icon-bag-handle-outline';
				$orderCount[$orders['name']] = $orders;
				unset($orderCount[$type]);
			}
		}

		$notifications['orders'] = $orderCount + $notifications['orders'];

		$productCount      = $stats->getProductStockCount($this->options)['products'] ?? [];

		foreach ($productCount as $type => &$products) {
			$products['icon']                     = 'icon-cube-outline';
			$products['badge']                    = commentStatusBadgeClass($products['stock_status_id']);
		}

		$notifications['products'] = $productCount + $notifications['products'];

		$commentCount      = $stats->getCommentsCount($this->options)['comments'] ?? [];
		$comment_status    = [
			0  => 'pending',
			1  => 'approved',
			2  => 'spam',
			3  => 'trash',
		];

		foreach ($commentCount as $type => $comments) {
			$comments['icon']                     = 'la la-comment';
			$comments['badge']                    = commentStatusBadgeClass($comments['status']);
			$commentCount[$comment_status[$type]] = $comments;
			unset($commentCount[$type]);
		}

		$notifications['comments'] = $commentCount + $notifications['comments'];

		$reviewCount      = $stats->getReviewsCount($this->options)['reviews'] ?? [];

		foreach ($reviewCount as $type => $reviews) {
			$reviews['icon']                     = ' la la-comments';
			$reviews['badge']                    =  commentStatusBadgeClass($reviews['status']);
			$reviewCount[$comment_status[$type]] = $reviews;
			unset($reviewCount[$type]);
		}

		$notifications['reviews'] = $reviewCount + $notifications['reviews'];

		$questionCount      = $stats->getQuestionsCount($this->options)['questions'] ?? [];

		foreach ($questionCount as $type => $questions) {
			$questions['icon']                     = 'la la-question-circle';
			$questions['badge']                    = commentStatusBadgeClass($questions['status']);
			$questionCount[$comment_status[$type]] = $questions;
			unset($questionCount[$type]);
		}

		$notifications['questions'] = $questionCount + $notifications['questions'];

		$update  = new Update();
		$updates = $update->checkUpdates('core');

		$notifications['updates']['core'] = $updates;
		$count                            = max($updates['hasUpdate'], 0);

		$results = [
			'notifications' => $notifications,
			'count'  		     => $count,
		];

		list($results) = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
