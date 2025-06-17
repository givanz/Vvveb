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

namespace Vvveb\Controller\Feed;

use \Vvveb\Controller\Base;
use \Vvveb\System\Images;
use function Vvveb\siteSettings;
use function Vvveb\url;

#[\AllowDynamicProperties]
class Manifest extends Base {
	function index() {
		$site        = siteSettings();
		$description = $site['description'][$this->global['language_id']] ?? [];

		if ($site) {
			$logo    = Images::image($site['logo-src'], 'logo', [144, 144]);
			$logo192 = Images::image($site['logo-src'], 'logo', [192, 192]);
			$logo512 = Images::image($site['logo-src'], 'logo', [512, 512]);
			$logo192 = Images::image($site['logo-src'], 'logo', [144, 144]);

			$webbanner     = Images::image($site['webbanner-src'], 'webbanner', [540, 720]);
			$webbanner1280 = Images::image($site['webbanner-src'], 'webbanner', [1280, 920]);

			$favicon = Images::image($site['favicon-src'], 'favicon', [96, 96]);
			$url     = url('index/index', ['host' => SITE_URL, 'scheme' => $_SERVER['REQUEST_SCHEME'] ?? 'https']);

			$manifest = [
				'short_name' => $description['title'] ?? '',
				'lang'       => $this->global['language'],
				'dir'        => 'ltr',
				'name'       => $description['title'] ?? '',
				'icons'      => [
					0 => [
						'src' => $logo, //$description['logo'] ?? '',
						//'type' => 'image/svg+xml',
						'type'  => 'image/png',
						'sizes' => '144x144',
					],
					1 => [
						'src'   => $logo512,
						'type'  => 'image/png',
						'sizes' => '192x192',
					],
					2 => [
						'src'   => $logo192,
						'type'  => 'image/png',
						'sizes' => '512x512',
					],
				],
				'id'               => $url,
				'start_url'        => "$url/?source=pwa",
				'background_color' => '#3367D6',
				'display'          => 'standalone',
				//'orientation'      => 'landscape',
				'scope'            => '/',
				'theme_color'      => '#3367D6',
				'shortcuts'        => [
					0 => [
						'name'        => $description['description'] ?? '',
						'short_name'  => $description['title'] ?? '',
						'description' => $description['meta-description'] ?? '',
						'url'         => '/?source=pwa',
						'icons'       => [
							0 => [
								'src'   => $favicon, //$description['favicon'],
								'sizes' => '96x96',
							],
						],
					],
				],
				'description' => $description['meta-description'] ?? '',
				'screenshots' => [
					0 => [
						'src'         => $webbanner,
						'type'        => 'image/png',
						'sizes'       => '540x720',
						'form_factor' => 'narrow',
					],
					1 => [
						'src'         => $webbanner1280,
						'type'        => 'image/jpg',
						'sizes'       => '1280x920',
						'form_factor' => 'wide',
					],
				],
			];

			$this->response->setType('json');
			$this->response->output($manifest);
		} else {
			$this->notFound();
		}
	}
}
