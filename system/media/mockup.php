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

namespace Vvveb\System\Media;

class Image {
	private $file;

	private $image;

	private $width;

	private $height;

	private $mime;

	public function __construct($file) {
	}

	public function getFile() {
		return false;
	}

	public function getImage() {
		return false;
	}

	public function getWidth() {
		return false;
	}

	public function getHeight() {
		return false;
	}

	public function getMime() {
		return false;
	}

	public function save($file, $quality = 80) {
		return false;
	}

	public function resize($width, $height, $method = 's') {
		return false;
	}

	public function crop($topX, $topY, $bottomX, $bottomY) {
		return false;
	}
}
