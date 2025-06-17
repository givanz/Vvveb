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
		if (file_exists($file)) {
			$this->file  = $file;
			$this->image = new \Imagick();
			$this->image->readImage($file);
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
			$this->mime   = $this->image->getFormat();
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

	public function save($file, $quality = 80, $extension = false) {
		if (! $this->image) {
			return;
		}

		$this->image->setImageFormat($extension ?: $this->mime);

		$this->image->setCompressionQuality($quality);
		//$this->image->setImageCompressionQuality($quality);

		$this->image->stripImage();

		return $this->image->writeImage($file);
	}

	public function resize($width, $height = 0, $method = 's') {
		if (! $this->width || ! $this->height) {
			return;
		}

		switch ($method) {
			case 's':
			return $this->stretch($width, $height);

			case 'c':
			return $this->crop($width, $height);

			case 'cs':
			return $this->cropsize($width, $height);
		}
	}

	public function stretch($width, $height = 0) {
		//$this->width  = $this->image->getImageWidth();
		//$this->height = $this->image->getImageHeight();

		if ($width && $height) {
			$scaleW = $width / $this->width;
			$scaleH = $height / $this->height;

			$scale = min($scaleW, $scaleH);

			$width  = (int)($this->width * $scale);
			$height = (int)($this->height * $scale);
		} else {
			if ($width) {
				$height = ceil($this->height / ($this->width / $width));
			} else {
				if ($height) {
					$width = ceil($this->width / ($this->height / $height));
				}
			}
		}

		if (method_exists($this->image, 'adaptiveResizeImage')) {
			return $this->image->adaptiveResizeImage($width, $height);
		} else {
			//$this->image->thumbnailImage($width, $height, true, true);
			return $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
		}
	}

	public function crop($top_x, $top_y, $bottom_x, $bottom_y) {
		$this->image->cropImage($bottom_x - $top_x, $bottom_y - $top_y, $top_x, $top_y);

		$this->width  = $this->image->getImageWidth();
		$this->height = $this->image->getImageHeight();
	}

	public function cropsize($width, $height = 0) {
		$width  = $width ?: $height;
		$height = $height ?: $width;

		$newRatio = $width / $height;
		$ratio    =  $this->width / $this->height;

		if ($newRatio > $ratio) {
			$new_width  = $width;
			$new_height = floor($width / $this->width * $this->height);
			$crop_x     = 0;
			$crop_y     = intval(($new_height - $height) / 2);
		} else {
			$new_width  = floor($height / $this->height * $this->width);
			$new_height = $height;
			$crop_x     = intval(($new_width - $width) / 2);
			$crop_y     = 0;
		}

		$this->image->resizeImage($new_width, $new_height, \Imagick::FILTER_LANCZOS, 1, true);

		$this->image->cropImage($width, $height, $crop_x, $crop_y);
	}

	public static function formats($format = false) {
		$formats = \Imagick::queryformats() ?? [];

		//return array_map(fn($value) => strtolower($value), $info);
		return array_map('strtolower', $formats);
	}
}
