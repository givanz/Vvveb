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

namespace Vvveb\System;

use function Vvveb\siteSettings;
use Vvveb\System\Media\Image;

class Images {
	/**
	 * Resize an image and save it to the destination path.
	 *
	 * @param string $src     Source image path.
	 * @param string $dest    Destination image path.
	 * @param int    $width   Target width.
	 * @param int    $height  Target height.
	 * @param string $method  Resize method (e.g. 'cs' for crop-scale).
	 * @param int    $quality JPEG quality (0-100).
	 * @param string $format  Output format (e.g. 'webp', '' for auto).
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function resize(string $src, string $dest, int $width, int $height, string $method, int $quality = 80, string $format = ''): bool {
		$destDir = dirname($dest);

		if (!file_exists($destDir)) {
			@mkdir(dirname($dest), (0755 & ~umask()), true);
		}
		$img    = new Image($src);
		$result = $img->resize($width, $height, $method);

		return $img->save($dest, $quality, $format);
	}

	/**
	 * Get the URL for an image, resizing and caching as needed.
	 *
	 * @param string                         $image   Image path relative to media folder.
	 * @param string                         $type    Image type context (e.g. 'product', 'blog').
	 * @param string|array<array-key, mixed> $size    Size name from site settings or [width, height] array.
	 * @param string                         $method  Resize method.
	 * @param string                         $format  Output format override.
	 * @param int                            $quality JPEG quality.
	 *
	 * @return string The resolved image URL.
	 */
	public static function image(string $image, string $type = '', string|array $size = '', string $method = 'cs', string $format = '', int $quality = 80): string {
		$publicPath = \Vvveb\publicUrlPath();

		[$publicPath, $type, $image, $size] =
		Event :: trigger(__CLASS__, 'publicPath', $publicPath, $type, $image, $size);

		if ($publicPath == null) {
			$publicPath = \Vvveb\publicUrlPath();
			//if absolute path include subdir
			if ($image !== '' && $image[0] == '/') {
				$publicPath = V_SUBDIR_INSTALL . $publicPath;
			}
		}

		$mediaFolder = 'media/';
		$cacheFolder = 'image-cache/';

		if ($image && (substr($image, 0, 2) != '//') && (substr($image, 0, 4) != 'http')) {
			$src         = $image;

			if ($size) {
				$width  = 0;
				$height = 0;

				if (is_array($size)) {
					$width       = $size[0];
					$height      = $size[1];
				} else {
					$site        = siteSettings();
					$width       = $site["{$type}_{$size}_width"] ?? 0;
					$height      = $site["{$type}_{$size}_height"] ?? 0;
					$method      = $site["{$type}_{$size}_method"] ?? 'cs';
					$format      = $site['image_format'] ?? '';
					$quality     = $site['image_quality'] ?? 80;
				}

				$image = self::size($image, "{$width}x{$height}_$method");

				if ($format) {
					$image = self::extension($image, $format);
				}

				if ($width || $height) {
					if (file_exists(DIR_PUBLIC . $cacheFolder . $image)) {
						$mediaFolder = $cacheFolder;
					} else {
						if (self :: resize(DIR_PUBLIC . $mediaFolder . $src, DIR_PUBLIC . $cacheFolder . $image, $width, $height, $method, $quality, $format)) {
							$mediaFolder = $cacheFolder;
						} else {
							$image = $src;
						}
					}
				} else {
					$image = $src;
				}
			}

			$image = $publicPath . $mediaFolder . $image;
		} else {
			//return $public . 'media/placeholder.png';
		}

		[$image, $type, $size] =
		Event :: trigger(__CLASS__, __FUNCTION__, $image, $type, $size);

		return $image;
	}

	/**
	 * Append a size suffix to an image filename before the extension.
	 *
	 * @param string $image Image filename.
	 * @param string $size  Size suffix (e.g. '300x200_cs').
	 *
	 * @return string Image filename with size suffix.
	 */
	public static function size(string $image, string $size): string {
		$pos = strrpos($image, '.');

		if ($pos) {
			$image = substr_replace($image, "-$size", $pos, 0);
		}

		return $image;
	}

	/**
	 * Replace the file extension of an image filename.
	 *
	 * @param string $image     Image filename.
	 * @param string $extension New extension (e.g. 'webp').
	 *
	 * @return string Image filename with new extension.
	 */
	public static function extension(string $image, string $extension): string {
		$dot = strrpos($image, '.');

		if ($dot) {
			return substr_replace($image, $extension, $dot + 1);
		}

		return $image;
	}

	/**
	 * Process an array of images, resolving each image URL.
	 *
	 * @param array<int, array{image: string}> $images Array of image data with 'image' key.
	 * @param string                           $type   Image type context.
	 * @param string                           $size   Size name or dimensions.
	 * @param string                           $method Resize method.
	 *
	 * @return array<int, array{image: string}> The processed images array<array-key, mixed>.
	 */
	public static function images(array $images, string $type, string $size = '', string $method = 'cs'): array {
		foreach ($images as $key => &$image) {
			$image['image'] = self::image($image['image'], $type, $size);
		}

		return $images;
	}

	/**
	 * Get an image by type, id, size and attributes.
	 *
	 * @param string                         $type  Image type.
	 * @param int|string                     $id    Image identifier.
	 * @param string|array<array-key, mixed> $size  Size specification.
	 * @param array<string, mixed>           $attrs Additional attributes.
	 *
	 * @return void
	 */
	public function get(string $type, int|string $id, string|array $size, array $attrs): void {}

	/**
	 * Save an image to the given path.
	 *
	 * @param string               $type  Image type.
	 * @param string               $path  File path to save to.
	 * @param array<string, mixed> $attrs Additional attributes.
	 *
	 * @return void
	 */
	public function save(string $type, string $path, array $attrs): void {}
}
