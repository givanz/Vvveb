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
	/** @var string */
	public const KEY_VALUE_REGEX = '/\s*(.+?)\s*:\s*([^;$]+)[;$]?/ms';

	/** @var string */
	public const FONT_FACE_REGEX = '/@font-face\s*{([^}]+)}/ms';

	/**
	 * Parse key-value pairs from a CSS comment block.
	 *
	 * @param string $comments The CSS comments to parse.
	 *
	 * @return array<string, string> Associative array<array-key, mixed> of key-value pairs.
	 */
	public static function getParams(string $comments): array {
		$results = [];

		if (preg_match_all(static::KEY_VALUE_REGEX, $comments, $matches)) {
			$matches[1] = array_map(fn ($key) => trim($key), $matches[1]);

			$results = array_combine($matches[1], $matches[2]);
		}

		return $results;
	}

	/**
	 * Convert an array of font definitions to CSS @font-face declarations.
	 *
	 * @param array<int, array<string, string>> $fonts Array of font property maps.
	 *
	 * @return string CSS @font-face rules.
	 */
	public static function arrayToFontFaces(array $fonts = []): string {
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
					if (str_starts_with($font['src'], 'url(')) {
						$font['src'] = substr($font['src'], 4);
					}

					$src         = trim($font['src'], '\'")(');
					$font['src'] = 'url("' . $src . '")';
				}

				$properties = '';

				foreach ($font as $property => $value) {
					$properties .= "$property: $value;";
				}

				if ($properties) {
					$css .= '@font-face {' . $properties . "}\n";
				}
			}
			unset($font);
		}

		return $css;
	}

	/**
	 * Remove all @font-face declarations from a CSS string.
	 *
	 * @param string $css The CSS string to process.
	 *
	 * @return string CSS string without @font-face rules.
	 */
	public static function removeFontFaces(string $css): string {
		return preg_replace(static::FONT_FACE_REGEX, '', $css) ?? $css;
	}

	/**
	 * Parse @font-face declarations from a CSS string.
	 *
	 * @param string $css      The CSS string to parse.
	 * @param bool   $allFonts Whether to include all font weights/styles.
	 * @param bool   $variants Whether to group fonts by family with variants.
	 *
	 * @return array<int|string, mixed> Parsed font data, keyed by family name if variants is true.
	 */
	public static function parseFontFaces(string $css, bool $allFonts = true, bool $variants = true): array {
		$fonts   = [];
		$matches = [];

		if (preg_match_all(static::FONT_FACE_REGEX, $css, $matches)) {
			foreach ($matches[1] as $match) {
				$properties = $match;
				$props      = self::getParams($properties);

				if (isset($props['font-family'])) {
					$props['font-family'] = trim($props['font-family'], '\'"');
				}

				if (isset($props['src'])) {
					if (str_starts_with($props['src'], 'url(')) {
						$props['src'] = substr($props['src'], 4);
					}

					$props['src'] = trim($props['src'], '\'")(');
				}

				$font = [];

				if ($allFonts) {
					$font = $props;
				} else {
					if ((!isset($props['font-weight']) || $props['font-weight'] === '400' || $props['font-weight'] === 'normal' || str_contains($props['font-weight'], ' '))
						&& (!isset($props['font-style']) || $props['font-style'] === 'normal')) {
						$font = $props;
					}
				}

				if ($variants) {
					$family = $props['font-family'] ?? '';

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

	/**
	 * Get fonts defined in a theme's CSS files.
	 *
	 * @param string $theme    Theme directory name.
	 * @param bool   $variants Whether to group fonts by family with variants.
	 *
	 * @return array<int|string, mixed> Parsed font data from theme CSS.
	 */
	public static function themeFonts(string $theme, bool $variants = false): array {
		$fonts = [];

		if ($theme && is_dir($themeCssDir = DIR_THEMES . $theme . DS . 'css' . DS)) {
			$styleCss = $themeCssDir . 'style.css';
			$fontsCss = $themeCssDir . 'fonts.css';

			if (file_exists($styleCss) && ($css = file_get_contents($styleCss))) {
				$fonts = self::parseFontFaces($css, false, $variants);
			}

			if (file_exists($fontsCss) && ($css = file_get_contents($fontsCss))) {
				$fonts = array_merge($fonts, self::parseFontFaces($css, false, $variants));
			}
		}

		return $fonts;
	}
}
