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

namespace Vvveb\Controller\Editor;

use function Vvveb\sanitizeFileName;

trait GlobalTrait {
	/**
	 * Saves global elements (header/footer) from the editor page to their source files.
	 *
	 * @param string               $content The HTML content to process.
	 * @param array<string, mixed> $options Options such as 'inline-css'.
	 *
	 * @return string The processed HTML content.
	 */
	private function saveGlobalElements(string $content, array $options = []): string {
		$document                      = new \DOMDocument();
		$document->preserveWhiteSpace  = true;
		$document->recover             = true;
		$document->strictErrorChecking = false;
		$document->substituteEntities  = false;
		$document->formatOutput        = false;
		$document->resolveExternals    = false;
		$document->validateOnParse     = false;
		$document->xmlStandalone       = true;

		libxml_use_internal_errors(true);

		@$document->loadHTML($content);

		$xpath = new \DOMXPath($document);

		$themeFolder = $this->getThemeFolder();

		if (!isset($options['inline-css']) || $options['inline-css'] == false) {
			//save vvvebjs css to custom.css
			$style   = $xpath->query('//style[ @id="vvvebjs-styles" ]');
			$cssFile = $themeFolder . DS . 'css' . DS . 'custom.css';

			if ($style && $style->length && is_writable($cssFile)) {
				$element = $style[0];
				$content = trim($element->nodeValue);

				if ($content && file_put_contents($cssFile, $element->nodeValue)) {
					$link = $document->createElement('link');
					$link->setAttribute('href', 'css/custom.css');
					$link->setAttribute('rel', 'stylesheet');
					$link->setAttribute('media', 'screen');
					$link->setAttribute('id', 'vvvebjs-css');
					$element->parentNode->replaceChild($link, $element);
				}
			}
		}

		//save common global elements like footer/header
		$elements = $xpath->query('//*[ @data-v-save-global ]');

		if ($elements && $elements->length) {
			$toDocument                      = new \DOMDocument();
			$toDocument->preserveWhiteSpace  = false;
			$toDocument->recover             = true;
			$toDocument->strictErrorChecking = false;
			$toDocument->formatOutput        = false;
			$toDocument->resolveExternals    = false;
			$toDocument->validateOnParse     = false;
			$toDocument->xmlStandalone       = true;

			foreach ($elements as $element) {
				if (!$element->hasChildNodes()) {
					continue;
				}//skip empty elements

				$attribute = $element->getAttribute('data-v-save-global');

				if (str_contains($attribute, ',')) {
					[$file, $selector] = explode(',', $attribute);

					$file     = sanitizeFileName(html_entity_decode($file));
					$selector = html_entity_decode($selector);
					$file     = $themeFolder . DS . $file;

					$toDocument->loadHTMLFile($file);

					$toXpath    = new \DOMXPath($toDocument);
					$cssQuery   = \Vvveb\cssToXpath($selector);
					$toElements = $toXpath->query($cssQuery);

					$count  = 0;

					if ($toElements && $toElements->length) {
						foreach ($toElements as $externalNode) {
							$parent = $externalNode->parentNode;

							$importedNode = $toDocument->importNode($element, true);

							if ($parent) {
								/*
								if ($count) {
									$parent->appendChild($importedNode);
								} else {
									$parent->replaceChild($importedNode, $externalNode);
								}*/
								if (!$parent->replaceChild($importedNode, $externalNode)) {
									$externalNode->replaceWith($importedNode);
								}

								$externalNode = $importedNode;
								$parent       = $externalNode->parentNode;
								$count++;
							}
						}

						$html = $toDocument->saveHTML();

						if (is_writable($file)) {
							if (file_put_contents($file, $html)) {
							}
						}
					}
				}
			}
		}

		return $document->saveHTML();
	}
}
