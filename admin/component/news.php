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

use function Vvveb\getUrl;
use Vvveb\System\Cache;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Import\Rss;

class News extends ComponentBase {
	/** @var string Domain URL for the news feed. */
	protected string $domain = 'https://blog.vvveb.com';

	/** @var string Path to the news feed endpoint. */
	protected string $url = '/feed/news';

	/** @var array{page: int, limit: int} Default options for pagination. */
	public static array $defaultOptions = [
		'page'  => 0,
		'limit' => 10,
	];

	/** @var array<string, mixed> Resolved options for this component. */
	public array $options = [];

	/**
	 * Fetch news items from the RSS feed.
	 *
	 * @return array<int, mixed> Parsed news items, or empty array on failure.
	 */
	public function getNews(): array {
		$feed = getUrl($this->domain . $this->url, true, 604800, 3, false);

		if ($feed) {
			$rss = new Rss($feed);

			return $rss->get($this->options['page'], $this->options['limit']);
		}

		return [];
	}

	/**
	 * Get news component results including domain, URL, and news items.
	 *
	 * @return array{domain: string, url: string, news: array<int, mixed>, count: int}
	 */
	public function results(): array {
		[$this->domain, $this->url] = Event::trigger(__CLASS__, 'url', $this->domain, $this->url);

		$cache = Cache::getInstance();
		// check for news ~twice a week
		$news = $cache->cache('vvveb', 'news', fn (): array => $this->getNews(), 259200);

		$results = [
			'domain' => $this->domain,
			'url'    => $this->url,
			'news'   => $news,
			'count'  => count($news),
		];

		[$results] = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
