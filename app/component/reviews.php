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

namespace Vvveb\Component;
use Vvveb\Sql\Product_Review_MediaSQL;
use Vvveb\System\Images;

class Reviews extends Comments {
	protected $type = 'review';

	protected $route = 'product/product/index';

	protected $model = 'product_review';

	public static $defaultOptions = [
		'product_id'   => 'url',
		'user_id'      => NULL,
		'status'       => 1, //approved reviews
		'start'        => 0,
		'limit'        => 10,
		'image_size'   => 'thumb',
		'order'        => 'asc', //desc
	];

	function results() {
		$results = parent::results();

		//review images
		foreach ($results['product_review'] as $id => &$review) {
			if (isset($review['images'])) {
				$review['images'] = json_decode($review['images'], true);

				foreach ($review['images'] as &$image) {
					$image['thumb'] = Images::image($image['image'], 'product', $this->options['image_size']);
					$image['image'] = Images::image($image['image'], 'product');
				}
			}
		}		

		//all product reviews images
		$media   = new Product_Review_MediaSQL();
		$gallery = $media->getAll($this->options + ['status'=> 1])['product_review_media'] ?? [];
		
		if ($gallery) {
			//$gallery = Images::images($gallery, 'product', $this->options['image_size']);
			foreach ($gallery as &$image) {
				$image['thumb'] = Images::image($image['image'], 'product', $this->options['image_size']);
				$image['image'] = Images::image($image['image'], 'product');
			}
		}

		$results['images'] = $gallery;
		
		return $results;
	}
}
