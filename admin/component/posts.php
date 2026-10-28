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

use Vvveb\Sql\PostSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Posts extends ComponentBase {
	/** @var array<string, mixed> Default options for post listing. */
	public static array $defaultOptions = [
		'start'           => 0,
		'language_id'     => 1,
		'site_id'         => 1,
		'post_id'         => 'url',
		'limit'           => ['url', 4],
		'id_manufacturer' => null,
		'order'           => ['url', 'price asc'],
		'id_category'     => 'url',
	];

	/** @var array<string, mixed> Resolved options for this component. */
	public array $options = [];

	/**
	 * Get posts with image data resolved.
	 *
	 * @return array<string, mixed> Posts with resolved images.
	 */
	public function results(): array {
		$posts = new PostSQL();

		$results = $posts->getAll($this->options) ?? [];

		foreach ($results['post'] ?? [] as $id => &$post) {
			if (isset($post['images'])) {
				$post['images'] = json_decode($post['images'], true);

				foreach ($post['images'] as &$image) {
					$image = Images::image($image, 'post');
				}
			}

			if (isset($post['image'])) {
				$post['images'][] = Images::image($post['image'], 'post');
			}
		}

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
