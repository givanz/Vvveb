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
	protected $type = 'product_review';

	protected $route = 'product/product/index';

	protected $modelName = 'product_review';

	public static $defaultOptions = [
		'product_id'    => 'url',
		'slug'          => 'url',
		'product_title' => NULL, //include product title (for recent reviews etc)
		'user_id'       => NULL,
		'status'        => 1, //approved reviews
		'start'         => 0,
		'limit'         => 10,
		'image_size'    => 'thumb',
		'order'         => 'asc', //desc
	];

	function results() {
		$results = parent::results();

		//review images
		foreach ($results['product_review'] as $id => &$review) {
			if (isset($review['images'])) {
				$review['images'] = json_decode($review['images'], true);

				foreach ($review['images'] as &$image) {
					$image['thumb'] = Images::image($image['image'], 'product', $this->options['image_size']);
					$image['image'] = Images::image($image['image'], 'product', 'xlarge');
				}
			}
		}

		//all product reviews images
		$media   = new Product_Review_MediaSQL();
		$gallery = $media->getAll($this->options + ['status' => 1])['product_review_media'] ?? [];

		if ($gallery) {
			//$gallery = Images::images($gallery, 'product', $this->options['image_size']);
			foreach ($gallery as &$image) {
				$image['thumb'] = Images::image($image['image'], 'product', $this->options['image_size']);
				$image['image'] = Images::image($image['image'], 'product', 'xlarge');
			}
		}

		$results['images'] = $gallery;

		$stats = $this->modelInstance->getProductStats($this->options);

		if ($stats) {
			$results += $stats;
		}

		$results['rating'] = number_format($results['rating'] ?? 0, 1);

		//compute width and fill missing ratings
		foreach ([1, 2, 3, 4, 5] as $rating) {
			if (isset($results['summary'][$rating])) {
				$summary            = &$results['summary'][$rating];
				$summary['percent'] = ceil($summary['count'] * 100 / $results['count']);
			} else {
				$results['summary'][$rating]['rating']  = $rating;
				$results['summary'][$rating]['percent'] = 0;
				$results['summary'][$rating]['count']   = 0;
			}
		}

		//$results['buyers'] = 5;
		//$results['recommendations'] = 100;

		return $results;
	}
}
