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

use function Vvveb\availableLanguages;
use function Vvveb\getCurrentUrl;
use Vvveb\Sql\LanguageSQL;
use Vvveb\System\Cache;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Session;
use function Vvveb\url;

class Language extends ComponentBase {
	public static $defaultOptions = [
		'start'   => 1,
		'limit'   => 1000,
		'status'  => 1,
		'default' => 'en_US',
	];

	function cacheKey() {
		//disable caching
		return false;
	}

	//called when fetching data, when cache expires
	function results() {
		$results = [];

		$options = $this->options;
		/*
		$cache   = Cache::getInstance();
		//manually cache language db query
		$results = $cache->cache(APP,'languages',function () use ($options) {
			$languages             = new LanguageSQL();

			return $languages->getAll($options);
		}, 259200);
		*/
		$results['language'] = availableLanguages();

		$publicPath = \Vvveb\publicUrlPath();
		$request    = Request::getInstance();
		$view   	   = View::getInstance();

		if ($results) {
			$results['current']    = $code    = Session::getInstance()->get('language') ?? 'en_US';
			$language              = $results['language'][$code] ?? [];

			if (! $language) {
				$language = $results['language'][$options['default']] ?? [];
			}

			$code                  = $language['code'] ?? '';
			$shortcode             = \Vvveb\filter('/[a-z]+/',$code);
			$img                   = "{$publicPath}img/flags/$shortcode.png";
			$results['active'] 	   = [];

			if ($language) {
				$results['active']     = ['name' => $language['name'], 'code' => $language['code'], 'id' => $language['language_id'], 'img' => $img];
			}

			$hreflang = [];

			foreach ($results['language'] as $code => &$language) {
				$shortcode       = \Vvveb\filter('/[a-z]+/',$code);
				$content 		      = [];
				$lang	 		        = [];
				$language['img'] = "{$publicPath}img/flags/$shortcode.png";

				if (! $language['default']) {
					$lang = ['language' => $code];
				}

				//if post or product page check if content available
				if (isset($view->content)) {
					$content = $view->content[$code] ?? [];
				}

				$get = $request->get;
				unset($get['language']);

				//$url = getCurrentUrl();
				if (true/* && $options['default'] != $code*/) {
					$url             = url($request->get['route'] ?? '', $lang + $content + $get, false); //"/$shortcode" . getCurrentUrl();
					$hreflang[$code] = $url;
				}

				$language['url'] = $url;
			}

			$view->hreflang = $hreflang;
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}
}
