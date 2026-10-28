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

/**
 * Stub Image class used when neither Imagick nor GD extensions are available.
 *
 * All methods return false or empty values to maintain interface compatibility
 * while allowing the application to gracefully degrade.
 */
class Image {
	/** @var string */
	private string $file = '';

	/** @var mixed */
	private mixed $image = null;

	/** @var int */
	private int $width = 0;

	/** @var int */
	private int $height = 0;

	/** @var string */
	private string $mime = '';

	/**
	 * Stub constructor for when no image extension is available.
	 *
	 * @param string $file Path to the image file.
	 */
	public function __construct(string $file) {
		$this->file = $file;
	}

	/**
	 * Get the file path (stub).
	 *
	 * @return string
	 */
	public function getFile(): string {
		return $this->file;
	}

	/**
	 * Get the image resource (stub).
	 *
	 * @return mixed null for stub.
	 */
	public function getImage(): mixed {
		return $this->image;
	}

	/**
	 * Get the image width (stub).
	 *
	 * @return int Always 0 for stub.
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * Get the image height (stub).
	 *
	 * @return int Always 0 for stub.
	 */
	public function getHeight(): int {
		return $this->height;
	}

	/**
	 * Get the image MIME type (stub).
	 *
	 * @return string Empty string for stub.
	 */
	public function getMime(): string {
		return $this->mime;
	}

	/**
	 * Save the image to a file (stub).
	 *
	 * @param string       $file      Output file path.
	 * @param int          $quality   Compression quality (0-100).
	 * @param string|false $extension File extension override, or false to detect from path.
	 *
	 * @return bool Always false for stub.
	 */
	public function save(string $file, int $quality = 80, string|false $extension = false): bool {
		return false;
	}

	/**
	 * Resize the image (stub).
	 *
	 * @param int    $width  Target width.
	 * @param int    $height Target height.
	 * @param string $method Resize method.
	 *
	 * @return bool Always false for stub.
	 */
	public function resize(int $width, int $height = 0, string $method = 's'): bool {
		return false;
	}

	/**
	 * Crop the image (stub).
	 *
	 * @param int $topX    Top-left X coordinate.
	 * @param int $topY    Top-left Y coordinate.
	 * @param int $bottomX Bottom-right X coordinate.
	 * @param int $bottomY Bottom-right Y coordinate.
	 *
	 * @return void
	 */
	public function crop(int $topX, int $topY, int $bottomX = 0, int $bottomY = 0): void {}

	/**
	 * Get supported image formats (stub).
	 *
	 * @param string|false $format Format extension to check, or false to return all supported formats.
	 *
	 * @return array<array-key, mixed> Empty array for stub.
	 */
	public static function formats(string|false $format = false): array {
		return [];
	}
}
