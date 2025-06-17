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

/**
 * Manages image retrival and saving.
 *
 * @package Vvveb
 * @subpackage System
 * @since 0.0.1
 */

namespace Vvveb\System;

use function Vvveb\siteSettings;
use Vvveb\System\Media\Image;

class Images {
	static public function resize($src, $dest, $width, $height, $method, $quality = 80, $format = '') {
		$destDir = dirname($dest);

		if (! file_exists($destDir)) {
			@mkdir(dirname($dest), (0755 & ~umask()), true);
		}
		$img    = new Image($src);
		$result = $img->resize($width,$height, $method);

		return $img->save($dest, $quality, $format);
	}

	static public function image($image, $type = '', $size = '', $method = 'cs', $format = '', $quality = 80) {
		$publicPath = \Vvveb\publicUrlPath();

		list($publicPath, $type, $image, $size) =
		Event :: trigger(__CLASS__, 'publicPath', $publicPath, $type, $image, $size);

		if ($publicPath == null) {
			$publicPath = \Vvveb\publicUrlPath();
			//if absolute path include subdir
			if ($image[0] == '/') {
				$publicPath = (V_SUBDIR_INSTALL ? V_SUBDIR_INSTALL : '') . $publicPath;
			}
		}

		$mediaFolder = 'media/';
		$cacheFolder = 'image-cache/';

		if ($image && (substr($image, 0, 2) != '//') && (substr($image, 0, 4) != 'http')) {
			$src         = $image;

			if ($size) {
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

				$image = self::size($image,"{$width}x{$height}_$method");

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

		list($image, $type, $size) =
		Event :: trigger(__CLASS__,__FUNCTION__, $image, $type, $size);

		return $image;
	}

	static public function size($image, $size) {
		$pos = strrpos($image, '.');

		if ($pos) {
			$image = substr_replace($image, "-$size", $pos, 0);
		}

		return $image;
	}

	static public function extension($image, $extension) {
		$dot = strrpos($image , '.');

		if ($dot) {
			return substr_replace($image , $extension, $dot + 1);
		}

		return $image;
	}

	static public function images($images, $type, $size = '') {
		foreach ($images as $key => &$image) {
			$image['image'] = Images::image($image['image'], $type, $size);
		}

		/*
		uasort($images, function ($a, $b) {
			if ($a['sort_order'] == $b['sort_order']) {
				return 0;
			}

			return ($a['sort_order'] < $b['sort_order']) ? -1 : 1;
		});
		 */

		return $images;
	}

	public function get($type, $id, $size, $attrs) {
	}

	public function save($type, $path, $attrs) {
	}
}
