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

use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;
use function Vvveb\url;

class Category extends ComponentBase {
	public static array $defaultOptions = [
		'taxonomy_item_id' => 'url',
		'slug'             => 'url',
		'type'             => null,
		'post_type'        => null,
		'language_id'      => null,
		'site_id'          => null,
	];

	public int $cacheExpire = 0;

	/**
	 * Disable caching for category component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get single category details with URL and image.
	 *
	 * @return array<string, mixed> Category data with URL, full-url, and image_url.
	 */
	public function results(): array {
		$product = new \Vvveb\Sql\CategorySQL();
		$results = $product->getCategory($this->options);

		if ($results) {
			$taxonomy_type = 'category';

			if (isset($this->options['type']) && $this->options['type'] === 'tags') {
				$taxonomy_type = 'tag';
			}

			if (isset($this->options['post_type']) && $this->options['post_type']) {
				$results['post_type'] = $this->options['post_type'];
			}

			$url = ['slug' => $results['slug']];

			$results['url']      = url('content/' . $taxonomy_type . '/index', $url);
			$results['full-url'] = url('content/' . $taxonomy_type . '/index', $url + ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'http']);

			if (isset($results['image'])) {
				$results['image_url'] = Images::image($results['image'], 'taxonomy_item');
			}
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
