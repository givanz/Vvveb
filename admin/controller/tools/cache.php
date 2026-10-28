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
use Vvveb\Controller\Base;
use Vvveb\System\CacheManager;

class Cache extends Base {
	/**
	 * Execute a cache clearing operation and display the result.
	 *
	 * @param callable(): bool $fn The cache clearing function to execute.
	 *
	 * @return void
	 */
	private function clear(callable $fn): void {
		if ($fn()) {
			$this->view->success[] = __('Cache deleted!');
		} else {
			$this->view->errors[] = __('Error purging cache!');
		}

		$this->index();
	}

	/**
	 * Delete all cache types.
	 *
	 * @return void
	 */
	public function delete(): void {
		$this->clear(fn () => CacheManager :: delete());
	}

	/**
	 * Clear compiled template cache.
	 *
	 * @return void
	 */
	public function template(): void {
		$this->clear(fn () => CacheManager :: clearCompiledFiles());
	}

	/**
	 * Clear page cache.
	 *
	 * @return void
	 */
	public function page(): void {
		$this->clear(fn () => CacheManager :: clearPageCache($this->global['site_url']));
	}

	/**
	 * Clear database object cache.
	 *
	 * @return void
	 */
	public function database(): void {
		$this->clear(fn () => CacheManager :: clearObjectCache());
	}

	/**
	 * Clear frontend asset cache.
	 *
	 * @return void
	 */
	public function asset(): void {
		$this->clear(fn () => CacheManager :: clearFrontend());
	}

	/**
	 * Clear model cache.
	 *
	 * @return void
	 */
	public function model(): void {
		$this->clear(fn () => CacheManager :: clearModelCache());
	}

	/**
	 * Clear image cache.
	 *
	 * @return void
	 */
	public function image(): void {
		$this->clear(fn () => CacheManager :: clearImageCache());
	}

	/**
	 * Display the cache management page with folder writability checks.
	 *
	 * @return void
	 */
	public function index(): void {
		$folders = [
			'/public/page-cache'          => DIR_PUBLIC . PAGE_CACHE_DIR,
			'/public/image-cache'         => DIR_PUBLIC . DS . 'image-cache',
			'/storage/model/app'          => DIR_STORAGE . 'model' . DS . 'app',
			'/storage/model/admin'        => DIR_STORAGE . 'model' . DS . 'admin',
			'/storage/compiled_templates' => DIR_COMPILED_TEMPLATES,
		];

		$unwritable = [];

		foreach ($folders as $folder => $path) {
			if (!is_writable($path)) {
				$unwritable[] = $folder;
			}
		}

		if ($unwritable) {
			$this->view->info[] = sprintf('Folders <b>%s</b> are not writable, clear cache might not work for these layers', implode(', ', $unwritable));
		}
	}
}
