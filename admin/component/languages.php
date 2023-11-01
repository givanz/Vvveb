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
use Vvveb\Sql\LanguageSQL;
use Vvveb\System\Cache;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use Vvveb\System\Session;

class Languages extends ComponentBase {
	public static $defaultOptions = [
		'start'  => 1,
		'limit'  => 1000,
		'status' => 1,
	];

	protected $options = [];

	public $cacheExpire = 0; //seconds

	function cacheKey() {
		// disable caching
		return false;
	}

	function results() {
		$results = [];
		$results['language'] = availableLanguages();

		if ($results['language']) {
			$results['current'] = $code = Session::getInstance()->get('language') ?? 'en_US';

			if (! isset($results['language'][$code])) {
				$results['current'] = $code	= key($results['language']);
			}
			$language           = $results['language'][$code] ?? [];
			$results['active']  = [];

			// if selected language not install default to english
			if ($language) {
				$results['active'] = [
					'name' => $language['name'],
					'code' => $language['code'],
					'id'   => $language['language_id'],
				];
			} else {
			}
		}

		list($results) = Event::trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
