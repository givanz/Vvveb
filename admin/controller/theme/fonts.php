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

namespace Vvveb\Controller\Theme;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Import\Theme;
use Vvveb\System\Media\Font;
use Vvveb\System\Sites;
use Vvveb\System\Traits\Media as MediaTrait;

class Fonts extends Base {
	use MediaTrait;

	protected $dirMedia = DIR_PUBLIC . 'fonts' . DS;

	protected $fontsPath = '../../../fonts/'; // /public/fonts relative to /themes/theme-slug/css folder

	private function getTheme() {
		return $theme = sanitizeFileName($this->request->get['theme'] ?? Sites::getTheme($this->global['site_id']) ?? 'default');
	}

	function save() {
		$fonts   = $this->request->post['font'] ?? [];

		if ($fonts) {
			$theme       = $this->getTheme();
			$themeFolder = DIR_THEMES . $theme;
			$cssFile     = DS . 'css' . DS . 'fonts.css';

			//try to create file if it doesn't exist
			if ($theme && ! file_exists($themeFolder . $cssFile) && ! touch($themeFolder . $cssFile)) {
				$this->view->errors[] = sprintf(__('Can not create file %s!'), $theme . $cssFile);

				return $this->index();
			}

			if ($theme && is_writable($themeFolder . $cssFile)) {
				//set font src relative to fonts folder path
				foreach ($fonts as &$font) {
					if (isset($font['src'])) {
						$font['src'] = $this->fontsPath . trim($font['src'], '/');
					}
				}

				$fontsCss = Font::arrayToFontFaces($fonts);

				if ($fontsCss) {
					if ($css = file_get_contents($themeFolder . $cssFile)) {
						$css = trim(Font::removeFontFaces($css));
					}

					$css .= "\n$fontsCss";

					if (! file_put_contents($themeFolder . $cssFile, $css)) {
						$this->view->errors[] = sprintf(__('%s is not writable!'), $theme . $cssFile);
					}
				}
			} else {
				$this->view->errors[] = sprintf(__('%s is not writable!'), $theme . $cssFile);
			}
		}

		return $this->index();
	}

	function index() {
		$theme = $this->getTheme();
		//var_dump(Font::themeFonts($theme));
		if ($theme && is_dir($themeCssDir = DIR_THEMES . $theme . DS . 'css' . DS)) {
			$styleCss = $themeCssDir . 'style.css';
			$fontsCss = $themeCssDir . 'fonts.css';

			$builtIn  = [];
			$custom   = [];

			if (file_exists($styleCss) && ($css = file_get_contents($styleCss))) {
				$builtIn = Font::parseFontFaces($css);
			}

			if (file_exists($fontsCss) && ($css = file_get_contents($fontsCss))) {
				$custom = Font::parseFontFaces($css, true, false);
				//set font src relative to fonts folder path
				foreach ($custom as &$font) {
					if (isset($font['src'])) {
						$font['src'] = str_replace($this->fontsPath, '/', $font['src']);
					}
				}
			}

			$this->view->builtin  = $builtIn;
			$this->view->fonts    = $custom;
			//$this->view->theme    = $theme;
			$this->view->themename = humanReadable($theme);
		} else {
			$this->notFound(sprintf(__('Theme fonts not found for %s'), $theme));
		}

		$adminPath = \Vvveb\adminPath();

		$controllerPath        = $adminPath . 'index.php?module=theme/fonts';
		$this->setMediaEndpoints($controllerPath);
	}
}
