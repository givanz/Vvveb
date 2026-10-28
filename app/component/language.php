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

use function Vvveb\isSecure;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Core\Request;
use Vvveb\System\Core\View;
use Vvveb\System\Event;
use Vvveb\System\Locale;
use function Vvveb\url;

class Language extends ComponentBase {
	public static array $defaultOptions = [
		'start'     => 1,
		'limit'     => 1000,
		'status'    => 1,
		'site_only' => true,
		'default'   => null,
	];

	/**
	 * Disable caching for language component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get available languages with URLs, hreflang tags, and active language info.
	 *
	 * @return array<string, mixed> Language data with language list, active language, and hreflang.
	 */
	public function results(): array {
		$results = [];

		$options             = $this->options;
		$results['language'] = Locale::availableLanguages();

		$publicPath = \Vvveb\publicUrlPath();
		$request    = Request::getInstance();
		$view       = View::getInstance();

		if ($results['language']) {
			if (isset($options['site_only']) && $options['site_only'] && self :: $global['languages']) {
				$results['language'] = array_intersect_key($results['language'], self :: $global['languages']);
			}

			$results['current']    = $code = self :: $global['language'];
			$language              = $results['language'][$code] ?? [];

			if (!$language && isset($options['default'])) {
				$language = $results['language'][$options['default']] ?? [];
			}

			$code                  = $language['code'] ?? '';
			$slug                  = $language['slug'] ?? '';
			$shortcode             = \Vvveb\filter('/[a-z]+/', $code);
			$img                   = "{$publicPath}img/flags/$shortcode.png";
			$results['active'] 	   = [];

			if ($language) {
				$results['active']     = ['name' => $language['name'], 'code' => $language['code'], 'slug' => $language['slug'], 'id' => $language['language_id'], 'img' => $img];
			}

			$hreflang = [];

			$scheme = isSecure() ? 'https' : 'http';

			foreach ($results['language'] as $code => &$language) {
				$shortcode       = \Vvveb\filter('/[a-z]+/', $code);
				$content         = [];
				$lang            = [];
				$language['img'] = "{$publicPath}img/flags/$shortcode.png";

				//if post or product page check if content available
				if (isset($view->content)) {
					$content = $view->content[$language['slug']] ?? [];
					//if translation is missing there is no slug
					if (!isset($content['slug']) || !$content['slug']) {
						unset($content['slug']);
					}
				}

				$params = ['language' => $language['slug'], 'host' => $_SERVER['HTTP_HOST'] ?? '', 'scheme' => $scheme] + $content + $request->get;

				if (($language['language_id'] == $options['default_language_id']) && !self :: $global['default_lang_slug']) {
					unset($params['language']);
				}

				$url                  = url($request->get['module'] ?? '', $params, false);

				$language['url']      = $url;
				$hreflang[$shortcode] = $url;
			}

			$view->hreflang = $hreflang;
		}

		[$results] = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}
}
