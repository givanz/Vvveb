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

use Vvveb\System\Cache;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Import\Rss;
use function Vvveb\getUrl;

class News extends ComponentBase {
	protected $domain = 'https://blog.vvveb.com';

	protected $url = '/feed/news';

	public static $defaultOptions = [
		'start' => 0,
		'limit' => 10,
	];

	public $options = [];

	function getNews() {

		$feed = getUrl($this->domain . $this->url, true, 604800, 1, false);

		if ($feed) {
			$rss = new Rss($feed);

			$result = $rss->get(1, $this->options['limit']);

			return $result;
		}

		return [];
	}

	function results() {
		list($this->domain, $this->url) = Event::trigger(__CLASS__, 'url', $this->domain, $this->url);

		$cache = Cache::getInstance();
		// check for news ~twice a week
		$news = $cache->cache('vvveb', 'news', function () {
			return $this->getNews();
		}, 259200);

		$results = [
			'domain' => $this->domain,
			'url'    => $this->url,
			'news'   => $news,
			'count'  => $this->options['limit'],
		];

		list($results) = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
