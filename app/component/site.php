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

use function Vvveb\siteSettings;
use Vvveb\Sql\SiteSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;
use function Vvveb\url;

class Site extends ComponentBase {
	public static array $defaultOptions = [
		'site_id'     => null,
		'language_id' => null,
	];

	protected array $options = [];

	public int $cacheExpire = 0;

	/**
	 * Disable caching for site component.
	 *
	 * @return null Always returns null to disable caching.
	 */
	public function cacheKey(): ?string {
		return null;
	}

	/**
	 * Get site settings with logos, URLs, and site configuration.
	 *
	 * @return array<string, mixed> Site data with logo URLs, site URL, and settings.
	 */
	public function results(): array {
		$results = siteSettings($this->options['site_id'], $this->options['language_id']);

		if (!$results) {
			$results    = ['logo' => 'logo.png', 'logo-sticky' => 'logo.png', 'logo-dark' => 'logo-white.png', 'logo-dark-sticky' => 'logo-white.png', 'favicon' => 'favicon.ico'];
			$publicPath = \Vvveb\publicUrlPath() . 'media';

			foreach ($results as $key => &$value) {
				$value = "$publicPath/$value";
			}
		}

		$urlOptions = [];

		if ($this->options['default_language'] != $this->options['language'] || self :: $global['default_lang_slug']) {
			$urlOptions = ['language' => $this->options['language']];
		}

		$results['url']      = V_SUBDIR_INSTALL . '/';
		$results['full-url'] = SITE_URL;

		$results['site_id'] = $this->options['site_id'];
		[$results]          = Event :: trigger(__CLASS__, __FUNCTION__, $results);

		return $results;
	}

	/**
	 * Save site settings from editor on page save.
	 *
	 * @param int                              $id     Site ID.
	 * @param array<int, array<string, mixed>> $fields Field data from editor.
	 * @param string                           $type   Site type identifier.
	 *
	 * @return void
	 */
	public static function editorSave(int $id, array $fields, string $type = 'site'): void {
		$sites      = new SiteSQL();
		$result     = [];
		$site       = $sites->get(['site_id' => $id]);

		if ($site) {
			$settings = json_decode($site['settings'], true);

			$publicPath = \Vvveb\publicUrlPath() . 'media/';

			foreach ($fields as $field) {
				$name  = $field['name'];
				$value = $field['value'];

				if ($name === 'favicon' || $name === 'webbanner' || str_contains($name, 'logo')) {
					$value = str_replace($publicPath, '', $value);
				}
				$settings[$name] = $value;
			}

			$site             = [];
			$data['site_id']  = $id;
			$site['settings'] = json_encode($settings);
			$data['site']     = $site;
			$result           = $sites->edit($data);
		}
	}
}
