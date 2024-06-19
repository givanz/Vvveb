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
	private function clear($fn) {
		if ($fn()) {
			$this->view->success[] = __('Cache deleted!');
		} else {
			$this->view->errors[] = __('Error purging cache!');
		}

		return $this->index();
	}

	function delete() {
		return $this->clear(fn () => CacheManager :: delete());
	}

	function template() {
		return $this->clear(fn () => CacheManager :: clearCompiledFiles());
	}

	function page() {
		return $this->clear(fn () => CacheManager :: clearPageCache($this->global['site_url']));
	}

	function database() {
		return $this->clear(fn () => CacheManager :: clearObjectCache());
	}

	function asset() {
		return $this->clear(fn () => CacheManager :: clearFrontend());
	}

	function model() {
		return $this->clear(fn () => CacheManager :: clearModelCache());
	}

	function image() {
		return $this->clear(fn () => CacheManager :: clearImageCache());
	}

	function stale() {
		return $this->index();
	}

	function index() {
		$folders = [
			'/public/page-cache'          => DIR_PUBLIC . PAGE_CACHE_DIR,
			'/public/image-cache'         => DIR_PUBLIC . DS . 'image-cache',
			'/storage/model/app'          => DIR_STORAGE . 'model' . DS . 'app',
			'/storage/model/admin'        => DIR_STORAGE . 'model' . DS . 'admin',
			'/storage/compiled_templates' => DIR_COMPILED_TEMPLATES,
		];

		$unwritable = [];

		foreach ($folders as $folder => $path) {
			if (! is_writable($path)) {
				$unwritable[] = $folder;
			}
		}

		if ($unwritable) {
			$this->view->info[] = sprintf('Folders <b>%s</b> are not writable, clear cache might not work for these layers', implode(', ', $unwritable));
		}
	}
}
