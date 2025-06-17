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

			if ($info) {
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

	public function save($file, $quality = 80, $extension = false) {
		if (! $this->image) {
			return;
		}

		$info = pathinfo($file);

		$extension = $extension ?: strtolower($info['extension']);

		$result = false;

		if (is_object($this->image) || is_resource($this->image)) {
			if ($extension == 'jpeg' || $extension == 'jpg') {
				$result = imagejpeg($this->image, $file, $quality);
			} elseif ($extension == 'png') {
				$result = imagepng($this->image, $file, 8, PNG_ALL_FILTERS);
			} elseif ($extension == 'gif') {
				$result = imagegif($this->image, $file);
			} elseif ($extension == 'webp') {
				$result = imagewebp($this->image, $file, $quality);
			}

			imagedestroy($this->image);

			return $result;
		}
	}

	public function resize($width, $height = 0, $method = 's') {
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
		if (! $this->width || ! $this->height || ! $this->image) {
			return;
		}

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

		$this->image = imagescale($this->image, $width, $height, IMG_BICUBIC_FIXED);

		$this->width  = $width;
		$this->height = $height;
	}

	public function crop($topX, $topY, $bottomX, $bottomY) {
		if (! $this->width || ! $this->height || ! $this->image) {
			return;
		}

		$imageOld    = $this->image;
		$this->image = imagecreatetruecolor($bottomX - $topX, $bottomY - $topY);

		imagecopy($this->image, $imageOld, 0, 0, $topX, $topY, $this->width, $this->height);
		imagedestroy($imageOld);

		$this->width  = $bottomX - $topX;
		$this->height = $bottomY - $topY;
	}

	public function cropsize($width, $height = 0) {
		if (! $this->width || ! $this->height || ! $this->image) {
			return;
		}

		$width  = $width ?: $height;
		$height = $height ?: $width;

		$newRatio = $width / $height;
		$ratio    =  $this->width / $this->height;

		if ($newRatio > $ratio) {
			$newWidth  = $width;
			$newHeight = floor($width / $this->width * $this->height);
			$crop_x    = 0;
			$crop_y    = intval(($newHeight - $height) / 2);
		} else {
			$newWidth  = floor($height / $this->height * $this->width);
			$newHeight = $height;
			$crop_x    = intval(($newWidth - $width) / 2);
			$crop_y    = 0;
		}

		$image = imagescale($this->image, $newWidth, $newHeight, IMG_BICUBIC_FIXED);

		if ($image) {
			$image = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => $height]);

			if ($image) {
				$this->image = $image;
			}
		}
	}

	public static function formats($format = false) {
		$formats = [];
		$info    = gd_info();

		foreach ([
			'JPEG Support' => 'jpg',
			'PNG Support' => 'png',
			'WebP Support' => 'webp',
			'AVIF Support' => 'avif',
			'WBMP Support' => 'wbmp',
			'GIF Create Support' => 'gif',
			'XBM Support"' => 'xbm',
		] as $name => $extension) {
			if (isset($info[$name]) && $info[$name]) {
				$formats[] = $extension;
			}
		}

		if ($format) {
			return in_array($format, $formats);
		}

		return $formats;
	}
}
