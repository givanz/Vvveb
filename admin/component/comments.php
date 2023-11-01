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

use Vvveb\Sql\CommentSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Images;

class Comments extends ComponentBase {
	public static $defaultOptions = [
		'start'           => 0,
		'limit'           => 10,
		'language_id'     => null,
		'site_id'         => null,
		'status'          => 1,
	];

	public $options = [];

	function results() {
		$comments = new CommentSQL();

		$results = $comments->getAll($this->options) ?? [];

		if (isset($results['comment'])) {
			foreach ($results['comment'] as $id => &$comment) {
				if (isset($comment['images'])) {
					$comment['images'] = json_decode($comment['images'], 1);

					foreach ($comment['images'] as &$image) {
						$image = Images::image('comment', $image);
					}
				}

				if (isset($comment['image'])) {
					$comment['images'][] = Images::image($comment['image'], 'comment');
				}
			}
		}

		list($results) = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
