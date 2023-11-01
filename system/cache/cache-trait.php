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
	public function stats($time = false) {
		$stats = [
			'curr_items'  => 0,
			'total_items' => 0,
			'bytes'       => 0,
		];
		$files = glob($this->cacheDir . $this->cachePrefix . '*');

		if ($files) {
			foreach ($files as $file) {
				$filename = basename($file);
				$stats['total_items']++;
				$stats['bytes'] += filesize($file);

				$time = (int)substr(strrchr($file, '.'), 1);

				if (time() - $time > $time) {
					$stats['curr_items']++;
				}
			}
		}
	}

	public function delete($namespace = false, $time_delay = 0) {
		$files = glob($this->cacheDir . $this->cachePrefix . '*');

		if ($files) {
			foreach ($files as $file) {
				$filename = basename($file);

				$time = (int)substr(strrchr($file, '.'), 1);

				if (time() - $time > $time) {
					$this->delete(substr($filename, 6, strrpos($filename, '.') - 6));
				}
			}
		}
	}
}
