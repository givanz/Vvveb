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

namespace Vvveb\Controller\Tools;

use function Vvveb\__;
use function Vvveb\config;
use Vvveb\Controller\Base;
use function Vvveb\setConfig;
use Vvveb\System\Queue as QueueApi;
use Vvveb\System\Sites;

class Queue extends Base {
	/**
	 * Display queue monitoring page: provider info, worker commands,
	 * per-queue stats and failed jobs list.
	 *
	 * @return void
	 */
	public function index(): void {
		$providerName = QueueApi::providerName();
		$config       = config('queue', []);
		$queues       = $config['queues'] ?? ['default'];

		if (!in_array('default', $queues)) {
			$queues[] = 'default';
		}

		try {
			foreach ($queues as $queue) {
				$this->view->queueStats[$queue] = QueueApi::stats($queue);
			}
		} catch (\Throwable $e) {
			$this->view->errors[]   = sprintf(__('Error connecting to queue provider: %s'), $e->getMessage());
			$this->view->queueStats = [];
		}

		try {
			$this->view->failedJobs   = QueueApi::failedJobs(null, 100);
			$this->view->failedCount  = count($this->view->failedJobs);
		} catch (\Throwable $e) {
			$this->view->failedJobs  = [];
			$this->view->failedCount = 0;
		}

		$site                   = Sites::getSiteById($this->global['site_id'] ?? 1);
		$this->view->cronkey    = \Vvveb\getConfig('app.cronkey');
		$this->view->worker_cli = 'php ' . DIR_ROOT . 'cli.php queue:work --queue=default --sleep=1 --max-jobs=0';
		$this->view->drain_cli  = 'php ' . DIR_ROOT . 'cli.php queue:work --drain=1';
		$this->view->cron_url   = ($site['url'] ?? '') . '/run-cron/' . $this->view->cronkey;

		$this->view->config         = $config;
		$this->view->provider       = $providerName;
		$this->view->providers      = [
			'database' => [
				'name'      => __('Database'),
				'available' => true,
				'note'      => __('Reuses the site database connection. Driver auto-detected: PostgreSQL, MySQL/MariaDB or SQLite.'),
			],
			'redis' => [
				'name'      => __('Redis'),
				'available' => class_exists('\Redis'),
				'note'      => __('Requires the phpredis extension. Recommended for multiple workers and higher throughput.'),
			],
		];
		$this->view->driverLabel    = match (DB_ENGINE) {
			'pgsql'  => __('PostgreSQL'),
			'sqlite' => __('SQLite'),
			default  => __('MySQL / MariaDB'),
		};
	}

	/**
	 * Save queue settings (provider selection + retry options).
	 *
	 * @return void
	 */
	public function save(): void {
		$settings = $this->request->post['settings'] ?? null;

		if ($settings && is_array($settings)) {
			$current = config('queue', []);

			$provider = $settings['default'] ?? 'database';

			if (!in_array($provider, ['database', 'redis'])) {
				$provider = 'database';
			}

			$current['default']                     = $provider;
			$current['max_attempts']                = max(1, (int)($settings['max_attempts'] ?? 3));
			$current['retry']['base_delay_seconds'] = max(1, (int)($settings['retry']['base_delay_seconds'] ?? 30));
			$current['retry']['max_delay_seconds']  = max(1, (int)($settings['retry']['max_delay_seconds'] ?? 3600));
			$current['reserved_timeout_seconds']    = max(10, (int)($settings['reserved_timeout_seconds'] ?? 300));
			$current['cron_pass']['enabled']        = !empty($settings['cron_pass']['enabled']);

			if (setConfig('queue', $current)) {
				$this->view->success[] = __('Settings saved!');
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		} else {
			$this->view->errors[] = __('Invalid settings data!');
		}

		$this->index();
	}

	/**
	 * Retry a single failed job.
	 *
	 * @return void
	 */
	public function retry(): void {
		$jobId = $this->request->post['job_id'] ?? '';

		if ($jobId && QueueApi::retryFailedJob((string)$jobId)) {
			$this->view->success[] = __('Job queued for retry!');
		} else {
			$this->view->errors[] = __('Error retrying job!');
		}

		$this->index();
	}

	/**
	 * Retry all failed jobs (bulk action).
	 *
	 * @return void
	 */
	public function retryAll(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$count = 0;

		foreach (QueueApi::failedJobs() as $job) {
			if (QueueApi::retryFailedJob((string)$job['id'])) {
				$count++;
			}
		}

		if ($count > 0) {
			$this->view->success[] = sprintf(__('%d jobs queued for retry!'), $count);
		} else {
			$this->view->errors[] = __('No failed jobs to retry!');
		}

		$this->index();
	}

	/**
	 * Delete a single failed job.
	 *
	 * @return void
	 */
	public function delete(): void {
		$jobId = $this->request->post['job_id'] ?? '';

		if ($jobId && QueueApi::deleteFailedJob((string)$jobId)) {
			$this->view->success[] = __('Failed job deleted!');
		} else {
			$this->view->errors[] = __('Error deleting job!');
		}

		$this->index();
	}

	/**
	 * Delete all failed jobs (bulk action).
	 *
	 * @return void
	 */
	public function clearFailed(): void {
		if ($this->request->getMethod() !== 'post') {
			$this->notFound(__('Invalid request method!'), 405);
		}

		$count = QueueApi::clearFailedJobs();

		if ($count > 0) {
			$this->view->success[] = sprintf(__('%d failed jobs deleted!'), $count);
		} else {
			$this->view->errors[] = __('No failed jobs to delete!');
		}

		$this->index();
	}
}
