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

namespace Vvveb\System\Import;

class Theme {
	private $theme;

	private $path;

	function __construct($theme) {
		$this->theme = $theme;
		$this->path  = DIR_THEMES . $theme . '/import-data/';
	}

	function getStructure() {
		$path   = $this->path;
		$glob   = glob($this->path . '{*,*/*,*/*/*}', GLOB_BRACE | GLOB_ONLYDIR);
		$result = [];

		foreach ($glob as $path => &$value) {
			$dir  = str_replace($this->path,'', $value);
			$temp = &$result;

			foreach (explode('/', $dir) as $key) {
				$temp = &$temp[$key];
			}
		}

		return $result;
	}

	function getList() {
		$list = $this->getFiles();

		foreach ($list as $name) {
		}
	}
}
