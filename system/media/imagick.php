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
	/** @var string */
	private string $file = '';

	/** @var mixed Image handle of the active driver. */
	private mixed $image = false;

	/** @var int */
	private int $width = 0;

	/** @var int */
	private int $height = 0;

	/** @var string */
	private string $mime = '';

	/**
	 * Load an image file using Imagick.
	 *
	 * @param string $file Path to the image file.
	 */
	public function __construct(string $file) {
		if (!file_exists($file)) {
			//leave the object empty like the gd driver does for missing files
			return;
		}

		try {
			$this->file  = $file;
			$this->image = new \Imagick();
			$this->image->readImage($file);
			$this->width  = $this->image->getImageWidth();
			$this->height = $this->image->getImageHeight();
			$this->mime   = $this->image->getImageMimeType();
			$profiles     = $this->image->getImageProfiles('icc', true);
			$this->image->stripImage();

			if (!empty($profiles)) {
				$this->image->profileImage('icc', $profiles['icc']);
			}
		} catch (\Throwable $e) {
			//corrupt or unsupported files degrade to an empty image
			//instead of failing construction, like the gd driver
			$this->image  = false;
			$this->width  = 0;
			$this->height = 0;
			$this->mime   = '';
		}
	}

	/**
	 * Get the file path.
	 *
	 * @return string
	 */
	public function getFile(): string {
		return $this->file;
	}

	/**
	 * Get the Imagick image instance.
	 *
	 * @return \Imagick|false
	 */
	public function getImage(): \Imagick|false {
		return $this->image;
	}

	/**
	 * Get the image width.
	 *
	 * @return int
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * Get the image height.
	 *
	 * @return int
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * Get the image MIME type.
	 *
	 * @return string
	 */
	public function getMime(): string {
		return $this->mime;
	}

	/**
	 * Save the image to a file.
	 *
	 * @param string       $file      Output file path.
	 * @param int          $quality   Compression quality (0-100).
	 * @param string|false $extension File extension override, or false to use current format.
	 *
	 * @return bool
	 */
	public function save(string $file, int $quality = 80, string|false $extension = false): bool {
		if (!$this->image) {
			return false;
		}

		try {
			if ($extension) {
				$this->image->setImageFormat($extension);
			}

			$this->image->setCompressionQuality($quality);

			$this->image->stripImage();

			return $this->image->writeImage($file);
		} catch (\Throwable $e) {
			return false;
		}
	}

	/**
	 * Resize the image using the specified method.
	 *
	 * @param int    $width  Target width.
	 * @param int    $height Target height (0 to auto-calculate).
	 * @param string $method Resize method: 'r' (ratio), 's' (stretch), 'c' (crop), 'cs' (cropsize).
	 *
	 * @return bool
	 */
	public function resize(int $width, int $height = 0, string $method = 's'): bool {
		if (!$this->width || !$this->height) {
			return false;
		}

		switch ($method) {
			case 'r':
				return $this->resizeRatio($width, $height);

			case 's':
				return $this->stretch($width, $height);

			case 'c':
				$this->crop(0, 0, $width, $height);

				return true;

			case 'cs':
				$this->cropsize($width, $height);

				return true;
		}

		return false;
	}

	/**
	 * Stretch the image to the specified dimensions.
	 *
	 * @param int $width  Target width.
	 * @param int $height Target height (0 to auto-calculate from ratio).
	 *
	 * @return bool
	 */
	public function stretch(int $width, int $height = 0): bool {
		if (!$this->width || !$this->height || !$this->image) {
			return false;
		}

		$newRatio = $this->width / $this->height;

		if ($width && $height) {
			$newRatio = $width / $height;
		}

		if (!$height) {
			$height = (int)ceil($width / $newRatio);
		}

		return $this->image->adaptiveResizeImage($width, $height);
	}

	/**
	 * Resize the image while preserving aspect ratio.
	 *
	 * @param int $width  Target width.
	 * @param int $height Target height (0 to auto-calculate from ratio).
	 *
	 * @return bool
	 */
	public function resizeRatio(int $width, int $height = 0): bool {
		if (!$this->width || !$this->height || !$this->image) {
			return false;
		}

		if ($width && $height) {
			$scaleW = $width / $this->width;
			$scaleH = $height / $this->height;

			$scale = min($scaleW, $scaleH);

			$width  = (int)($this->width * $scale);
			$height = (int)($this->height * $scale);
		} else {
			if ($width) {
				$height = (int)ceil($this->height / ($this->width / $width));
			} else {
				if ($height) {
					$width = (int)ceil($this->width / ($this->height / $height));
				}
			}
		}

		return $this->image->adaptiveResizeImage($width, $height);
	}

	/**
	 * Crop the image from given coordinates.
	 *
	 * @param int $topX    Top-left X coordinate.
	 * @param int $topY    Top-left Y coordinate.
	 * @param int $bottomX Bottom-right X coordinate (0 for image width).
	 * @param int $bottomY Bottom-right Y coordinate (0 for image height).
	 *
	 * @return void
	 */
	public function crop(int $topX, int $topY, int $bottomX = 0, int $bottomY = 0): void {
		if (!$this->width || !$this->height || !$this->image) {
			return;
		}

		$bottomX = $bottomX ?: $this->width;
		$bottomY = $bottomY ?: $this->height;
		$this->image->cropImage($bottomX - $topX, $bottomY - $topY, $topX, $topY);

		$this->width  = $this->image->getImageWidth();
		$this->height = $this->image->getImageHeight();
	}

	/**
	 * Crop and resize the image to the specified dimensions.
	 *
	 * @param int $width  Target width.
	 * @param int $height Target height (0 to auto-calculate from ratio).
	 *
	 * @return void
	 */
	public function cropsize(int $width, int $height = 0): void {
		if (!$this->width || !$this->height || !$this->image) {
			return;
		}

		$newRatio = $this->width / $this->height;

		if ($width && $height) {
			$newRatio = $width / $height;
		}

		if (!$height) {
			$height = (int)($width / $newRatio);
		}

		$ratio = $this->width / $this->height;

		if ($newRatio > $ratio) {
			$newWidth  = (int)$width;
			$newHeight = (int)floor($width / $this->width * $this->height);
			$crop_x    = 0;
			$crop_y    = (int)(int)(($newHeight - $height) / 2);
		} elseif ($newRatio < $ratio) {
			$newWidth  = (int)floor($height / $this->height * $this->width);
			$newHeight = (int)$height;
			$crop_x    = (int)(int)(($newWidth - $width) / 2);
			$crop_y    = 0;
		} else {
			$newWidth  = (int)$width;
			$newHeight = (int)$height;
			$crop_x    = 0;
			$crop_y    = 0;
		}

		if ($this->width !== $newWidth || $this->height !== $newHeight) {
			$this->image->resizeImage($newWidth, $newHeight, \Imagick::FILTER_LANCZOS, 1, true);
		}

		if ($width !== $newWidth || $newHeight !== $height) {
			$this->image->cropImage($width, (int)$height, $crop_x, $crop_y);
		}
	}

	/**
	 * Get supported image formats or check if a specific format is supported.
	 *
	 * @param string|false $format Format extension to check, or false to return all supported formats.
	 *
	 * @return array<array-key, mixed>|bool Array of supported format strings, or bool if a specific format was queried.
	 */
	public static function formats(string|false $format = false): array|bool {
		$formats = array_map('strtolower', \Imagick::queryformats());

		if ($format) {
			return in_array($format, $formats, true);
		}

		return $formats;
	}
}
