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
use function Vvveb\siteSettings;

#[\AllowDynamicProperties]
class Manifest extends Base {
	function index() {
		$site   = siteSettings();

		$manifest = [
			'short_name' => $site['title'],
			'lang'       => $this->global['language'],
			'dir'        => 'ltr',
			'name'       => $site['description'],
			'icons'      => [
				0 => [
					'src' => $site['logo'],
					//'type' => 'image/svg+xml',
					'type'  => 'image/png',
					'sizes' => '512x512',
				], /*
			1 => [
			  'src' => '/images/icons-192.png',
			  'type' => 'image/png',
			  'sizes' => '192x192',
			],
			2 => [
			  'src' => '/images/icons-512.png',
			  'type' => 'image/png',
			  'sizes' => '512x512',
			],*/
			],
			'id'               => '/?source=pwa',
			'start_url'        => '/?source=pwa',
			'background_color' => '#3367D6',
			'display'          => 'standalone',
			//'orientation'      => 'landscape',
			'scope'            => '/',
			'theme_color'      => '#3367D6',
			'shortcuts'        => [
				0 => [
					'name'        => $site['description'],
					'short_name'  => $site['title'],
					'description' => $site['meta-description'],
					'url'         => '/?source=pwa',
					'icons'       => [
						0 => [
							'src'   => $site['favicon'],
							'sizes' => '192x192',
						],
					],
				],
			],
			'description' => $site['meta-description'],
			/*
		  'screenshots' => [
			0 => [
			  'src' => '/images/screenshot1.png',
			  'type' => 'image/png',
			  'sizes' => '540x720',
			  'form_factor' => 'narrow',
			],
			1 => [
			  'src' => '/images/screenshot2.jpg',
			  'type' => 'image/jpg',
			  'sizes' => '720x540',
			  'form_factor' => 'wide',
			],
		  ],*/
		];

		header('Content-type: application/json; charset=utf-8');

		die(json_encode($manifest));
	}
}
