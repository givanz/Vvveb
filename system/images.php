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
	static public function resize($src, $dest, $width, $height, $method) {
		@mkdir(dirname($dest), (0755 & ~umask()), true);
		$img = new Image($src);
		$img->resize($width,$height, $method);

		return $img->save($dest);
	}

	static public function image($image, $type = '', $size = '') {
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
				$site        = siteSettings();
				$width       = $site["{$type}_{$size}_width"] ?? 0;
				$height      = $site["{$type}_{$size}_height"] ?? 0;
				$method      = $site["{$type}_{$size}_method"] ?? 's';

				$image = self::size($image,"{$width}x{$height}");

				if ($width || $height) {
					if (! file_exists($image)) {
						if (self :: resize(DIR_PUBLIC . $mediaFolder . $src, DIR_PUBLIC . $cacheFolder . $image, $width, $height, $method)) {
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
