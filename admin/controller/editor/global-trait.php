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

trait GlobalTrait {
	private function saveGlobalElements($content, $options = []) {
		$document                      = new \DomDocument();
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

		$xpath = new \DOMXpath($document);

		$themeFolder = $this->getThemeFolder();

		if (! isset($options['inline-css']) || $options['inline-css'] == false) {
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
			$toDocument                      = new \DomDocument();
			$toDocument->preserveWhiteSpace  = false;
			$toDocument->recover             = true;
			$toDocument->strictErrorChecking = false;
			$toDocument->formatOutput        = false;
			$toDocument->resolveExternals    = false;
			$toDocument->validateOnParse     = false;
			$toDocument->xmlStandalone       = true;

			foreach ($elements as $element) {
				$attribute = $element->getAttribute('data-v-save-global');

				if (strpos($attribute, ',') !== false) {
					list($file, $selector) = explode(',',$attribute);

					$file     = html_entity_decode($file);
					$selector = html_entity_decode($selector);
					$file     = $themeFolder . DS . $file;

					$toDocument->loadHTMLFile($file);

					$toXpath = new \DOMXpath($toDocument);

					$toElements = $toXpath->query(\Vvveb\cssToXpath($selector));

					$count  = 0;

					if ($elements && $elements->length) {
						foreach ($toElements as $externalNode) {
							$parent = $externalNode->parentNode;

							$importedNode = $toDocument->importNode($element, true);

							if ($parent) {
								if ($count) {
									$parent->appendChild($importedNode);
								} else {
									$parent->replaceChild($importedNode, $externalNode);
								}
								$externalNode = $importedNode;
								$parent       = $externalNode->parentNode;
								$count++;
							}
						}

						$html= $toDocument->saveHTML();

						if (is_writable($file)) {
							if (@file_put_contents($file, $html)) {
							}
						}
					}
				}
			}
		}

		$content= $document->saveHTML();

		return $content;
	}
}
