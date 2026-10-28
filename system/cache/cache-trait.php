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

namespace Vvveb\System\Cache;

trait CacheTrait {
	/**
	 * Get cache statistics for files matching the current cache prefix.
	 *
	 * @param bool|int $time If numeric, used as expiry threshold in seconds.
	 *
	 * @return array{curr_items: int, total_items: int, bytes: int}|null Returns stats array<array-key, mixed>, or null if no files found.
	 */
	public function stats(bool|int $time = false): ?array {
		$stats = [
			'curr_items'  => 0,
			'total_items' => 0,
			'bytes'       => 0,
		];
		$files = glob($this->cacheDir . $this->cachePrefix . '*');

		if ($files) {
			foreach ($files as $file) {
				$stats['total_items']++;
				//suppress warnings for files removed between glob() and filesize()
				$stats['bytes'] += @filesize($file) ?: 0;

				$fileTime = filemtime($file);

				if ($fileTime && (time() - $fileTime > $time)) {
					$stats['curr_items']++;
				}
			}
		}

		return $stats;
	}

	/**
	 * Delete expired cache files matching the namespace.
	 *
	 * @param string|false $namespace  Cache namespace to filter by, or false for all.
	 * @param int          $time_delay Minimum age in seconds before a file is considered expired.
	 *
	 * @return void
	 */
	public function delete(string|false $namespace = false, int $time_delay = 0): void {
		$files = glob($this->cacheDir . $this->cachePrefix . '*');

		if ($files) {
			foreach ($files as $file) {
				$filename = basename($file);

				$fileTime = (int)substr(strrchr($file, '.'), 1);

				if (time() - $fileTime > $time_delay) {
					$this->delete(substr($filename, 6, strrpos($filename, '.') - 6));
				}
			}
		}
	}
}
