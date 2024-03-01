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

use Vvveb\System\Core\View;

trait GlobalTrait {
	private function saveGlobalElements($content) {
		$document                      = new \DomDocument();
		$document->preserveWhiteSpace  = false;
		$document->recover             = true;
		$document->strictErrorChecking = false;
		$document->formatOutput        = false;
		$document->resolveExternals    = false;
		$document->validateOnParse     = false;
		$document->xmlStandalone       = true;

		$view = View::getInstance();
		libxml_use_internal_errors(true);

		@$document->loadHTML($content);

		$xpath = new \DOMXpath($document);

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

			$themeFolder = $this->getThemeFolder();

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
						file_put_contents($file, $html);
					}
				}
			}
		}
	}
}
