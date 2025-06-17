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

namespace Vvveb\System\Media;

class Font {
	const KEY_VALUE_REGEX = '/\s*(.+?)\s*:\s*([^;$]+)[;$]?/ms';

	const FONT_FACE_REGEX = '/@font-face\s*{([^}]+)}/ms';

	static function getParams($comments) {
		$results = [];

		if (preg_match_all(static :: KEY_VALUE_REGEX, $comments, $matches)) {
			$matches[1] = array_map(function ($key) {
				return trim($key);
			}, $matches[1]);

			$results = array_combine($matches[1], $matches[2]);
		}

		return $results;
	}

	static function arrayToFontFaces($fonts = []) {
		$css = '';

		if ($fonts) {
			foreach ($fonts as &$font) {
				//$font = array_filter($font, fn($var) => ($var !== NULL && $var !== FALSE && $var !== ''));
				//remove empty fields
				//$font = array_filter($font, 'strlen');

				if (isset($font['font-family'])) {
					$font['font-family'] = '"' . trim($font['font-family'], '\'"') . '"';
				}

				if (isset($font['src'])) {
					if (strncmp($font['src'], 'url(', 4) === 0) {
						$font['src']= substr($font['src'], 4);
					}

					$src         = trim($font['src'], '\'")(');
					$font['src'] = 'url("' . $src . '")';
				}

				$properties = '';

				foreach ($font as $property => $value) {
					if ($value) {
						$properties .= "$property: $value;";
					}
				}

				if ($properties) {
					$css .= '@font-face {' . $properties . "}\n";
				}
			}
		}

		return $css;
	}

	static function removeFontFaces($css) {
		return preg_replace(static :: FONT_FACE_REGEX, '', $css) ?? $css;
	}

	static function parseFontFaces($css, $allFonts = true, $variants = true) {
		$fontFaceRegex = '/@font-face\s*{([^}]+)}/ms';
		//$css           = file_get_contents($cssFile);
		$fonts         = [];
		$matches       = [];

		if (preg_match_all(static:: FONT_FACE_REGEX, $css, $matches)) {
			foreach ($matches[1] as $match) {
				//var_dump($match);
				$properties = $match;
				//var_dump($match);
				$props = self::getParams($properties);

				if (isset($props['font-family'])) {
					$props['font-family'] = trim($props['font-family'], '\'"');
				}

				if (isset($props['src'])) {
					if (strncmp($props['src'], 'url(', 4) === 0) {
						$props['src']= substr($props['src'], 4);
					}

					$props['src']= trim($props['src'], '\'")(');
				}

				$font = [];

				if ($allFonts) {
					$font = $props;
				} else {
					if ((! isset($props['font-weight']) || $props['font-weight'] == '400' || $props['font-weight'] == 'normal' || strpos($props['font-weight'], ' ') !== false)
						&& (! isset($props['font-style']) || $props['font-style'] == 'normal')) {
						$font = $props;
					}
				}

				if ($variants) {
					$family = $props['font-family'];

					if ($family) {
						$fonts[$family]['variants'][] = $font;
					}
				} else {
					if ($font) {
						$fonts[] = $font;
					}
				}
			}
		}

		return $fonts;
	}

	static function themeFonts($theme, $variants = false) {
		$fonts = [];

		if ($theme && is_dir($themeCssDir = DIR_THEMES . $theme . DS . 'css' . DS)) {
			$styleCss = $themeCssDir . 'style.css';
			$fontsCss = $themeCssDir . 'fonts.css';

			if (file_exists($styleCss) && ($css = file_get_contents($styleCss))) {
				$fonts = Font::parseFontFaces($css, false, $variants);
			}

			if (file_exists($fontsCss) && ($css = file_get_contents($fontsCss))) {
				$fonts = array_merge($fonts, Font::parseFontFaces($css, false, $variants));
			}
		}

		return $fonts;
	}
}
