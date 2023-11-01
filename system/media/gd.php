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
		if (is_file($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			$this->width  = $info[0];
			$this->height = $info[1];
			$this->mime   = $info['mime'] ?? '';

			if ($this->mime == 'image/gif') {
				$this->image = imagecreatefromgif($file);
			} elseif ($this->mime == 'image/png') {
				$this->image = imagecreatefrompng($file);

				imageinterlace($this->image, false);
			} elseif ($this->mime == 'image/jpeg') {
				$this->image = imagecreatefromjpeg($file);
			} elseif ($this->mime == 'image/webp') {
				$this->image = imagecreatefromwebp($file);
			}
		} else {
			//throw new \Exception("Could not load image $file");
		}
	}

	public function getFile() {
		return $this->file;
	}

	public function getImage() {
		return $this->image;
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getMime() {
		return $this->mime;
	}

	public function save($file, $quality = 80) {
		if (! $this->image) {
			return;
		}

		$info = pathinfo($file);

		$extension = strtolower($info['extension']);

		if (is_object($this->image) || is_resource($this->image)) {
			if ($extension == 'jpeg' || $extension == 'jpg') {
				imagejpeg($this->image, $file, $quality);
			} elseif ($extension == 'png') {
				imagepng($this->image, $file, 8, PNG_ALL_FILTERS);
			} elseif ($extension == 'gif') {
				imagegif($this->image, $file);
			} elseif ($extension == 'webp') {
				imagewebp($this->image, $file, $quality);
			}

			return imagedestroy($this->image);
		}
	}

	public function resize($width, $height, $method = 's') {
		if (! $this->width || ! $this->height) {
			return;
		}

		if ($width && $height) {
		} else {
			if ($width) {
				$height = ceil($this->height / ($this->width / $width));
			} else {
				if ($height) {
					$width = ceil($this->width / ($this->height / $height));
				}
			}
		}

		$this->image = imagescale($this->image, $width, $height, IMG_BICUBIC_FIXED);

		$this->width  = $width;
		$this->height = $height;
	}

	public function crop($topX, $topY, $bottomX, $bottomY) {
		$imageOld    = $this->image;
		$this->image = imagecreatetruecolor($bottomX - $topX, $bottomY - $topY);

		imagecopy($this->image, $imageOld, 0, 0, $topX, $topY, $this->width, $this->height);
		imagedestroy($imageOld);

		$this->width  = $bottomX - $topX;
		$this->height = $bottomY - $topY;
	}
}
