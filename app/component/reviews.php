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

use Vvveb\Sql\Product_review_mediaSQL;
use Vvveb\System\Images;

class Reviews extends Comments {
	protected string $type = 'product_review';

	protected string $route = 'product/product/index';

	protected string $modelName = 'product_review';

	public static array $defaultOptions = [
		'product_id'    => 'url',
		'slug'          => 'url',
		'product_title' => null,
		'user_id'       => null,
		'status'        => 1,
		'start'         => 0,
		'limit'         => 10,
		'order'         => 'asc',
		'image_size'    => 'thumb',
	];

	public int $cacheExpire = 0;

	/**
	 * Get product reviews with images, gallery, and rating statistics.
	 *
	 * @return array<string, mixed> Reviews data with images, rating, summary, and stats.
	 */
	public function results(): array {
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
		$media   = new Product_review_mediaSQL();
		$gallery = $media->getAll($this->options + ['status' => 1])['product_review_media'] ?? [];

		if ($gallery) {
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

		$rating                 = $results['rating'] ?? 0;
		$results['rating_int']  = ceil($rating);
		$results['rating']      = number_format($rating, 1);

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

		return $results;
	}
}
