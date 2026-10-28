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
	 * Load an image file and initialize GD image resource.
	 *
	 * @param string $file Path to the image file.
	 */
	public function __construct(string $file) {
		if (is_file($file)) {
			$this->file = $file;

			$info = getimagesize($file);

			if ($info) {
				$this->width  = $info[0];
				$this->height = $info[1];
				$this->mime   = $info['mime'];

				$this->image = match ($this->mime) {
					'image/gif'  => imagecreatefromgif($file),
					'image/png'  => imagecreatefrompng($file),
					'image/jpeg' => imagecreatefromjpeg($file),
					'image/webp' => imagecreatefromwebp($file),
					default      => false,
				};

				if ($this->mime === 'image/png' && $this->image) {
					imageinterlace($this->image, false);
				}

				//preserve transparency for png/webp sources
				if ($this->image && in_array($this->mime, ['image/png', 'image/webp'])) {
					imagesavealpha($this->image, true);
				}
				/*
				if (STRIP_EXIF) {
					$profiles = $this->image->getImageProfiles('icc', true);
					$this->image->stripImage();

					if (! empty($profiles)) {
						$this->image->profileImage('icc', $profiles['icc']);
					}
				}
				*/
			}
		} else {
			//throw new \Exception("Could not load image $file");
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
	 * Get the GD image resource.
	 *
	 * @return \GdImage|false
	 */
	public function getImage(): \GdImage|false {
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
	 * @param string|false $extension File extension override, or false to detect from path.
	 *
	 * @return bool
	 */
	public function save(string $file, int $quality = 80, string|false $extension = false): bool {
		if (!$this->image) {
			return false;
		}

		$info = pathinfo($file);

		$extension = $extension ?: strtolower($info['extension'] ?? '');

		return match ($extension) {
			'jpeg', 'jpg' => @imagejpeg($this->image, $file, $quality),
			'png'         => @imagepng($this->image, $file, 8, PNG_ALL_FILTERS),
			'gif'         => @imagegif($this->image, $file),
			'webp'        => @imagewebp($this->image, $file, $quality),
			default       => false,
		};
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

		$scaled = imagescale($this->image, $width, $height, IMG_BICUBIC_FIXED);

		if (!$scaled) {
			return false;
		}

		$this->image = $scaled;
		imagesavealpha($scaled, true);

		return true;
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
			} elseif ($height) {
				$width = (int)ceil($this->width / ($this->height / $height));
			}
		}

		$scaled = imagescale($this->image, $width, $height, IMG_BICUBIC_FIXED);

		if (!$scaled) {
			return false;
		}

		$this->image  = $scaled;
		$this->width  = $width;
		$this->height = $height;
		imagesavealpha($scaled, true);

		return true;
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

		$imageOld    = $this->image;
		$this->image = imagecreatetruecolor($bottomX - $topX, $bottomY - $topY);

		imagecopy($this->image, $imageOld, 0, 0, $topX, $topY, $this->width, $this->height);
		imagedestroy($imageOld);

		$this->width  = $bottomX - $topX;
		$this->height = $bottomY - $topY;
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
		}

		$image = false;

		if ($this->width !== $newWidth || $this->height !== $newHeight) {
			$image = imagescale($this->image, $newWidth, $newHeight);

			if ($image) {
				imagesavealpha($image, true);
			}
		}

		if ($image) {
			if ($width !== $newWidth || $newHeight !== $height) {
				$image = imagecrop($image, ['x' => 0, 'y' => 0, 'width' => $width, 'height' => (int)$height]);
			}

			if ($image) {
				$this->image = $image;
			}
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
		$formats = [];
		$info    = gd_info();

		foreach ([
			'JPEG Support'       => 'jpg',
			'PNG Support'        => 'png',
			'WebP Support'       => 'webp',
			'AVIF Support'       => 'avif',
			'WBMP Support'       => 'wbmp',
			'GIF Create Support' => 'gif',
			'XBM Support'        => 'xbm',
		] as $name => $extension) {
			if (!empty($info[$name])) {
				$formats[] = $extension;
			}
		}

		if ($format) {
			return in_array($format, $formats, true);
		}

		return $formats;
	}
}
