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

use function Vvveb\__;
use Vvveb\Controller\Base;

class Translate extends Base {
	function get() {
		$text         = $this->request->post['text'];
		$languages    = \Vvveb\availableLanguages();
		$translations = [];

		foreach ($languages as $lang) {
			$code = $lang['code'];
			\Vvveb\setLanguage($code);
			$translations[$code] = __($text);
		}
		//restore language
		\Vvveb\setLanguage(\Vvveb\getLanguage());

		$this->response->setType('json');
		$this->response->output($translations);
	}

	function save() {
		$translations = $this->request->post ?? [];
		$success      = true;
		$message      = __('Translations saved!');

		if ($translations) {
			require_once DIR_SYSTEM . 'functions' . DS . 'php-mo.php';

			$defaultLang  = key($translations); //'en_US';
			$domain       = 'vvveb';
			$text         =  $translations[$defaultLang];

			foreach ($translations as $langCode => $translation) {
				if ($langCode == $defaultLang || $success == false) {
					continue;
				}
				$folder     = DIR_ROOT . 'locale' . DS . $langCode . DS . 'LC_MESSAGES' . DS;
				$userpoFile = $folder . 'user.po';
				$poFile     = $folder . $domain . '.po';
				$moFile     = $folder . $domain . '.mo';

				foreach ([$poFile, $moFile] as $file) {
					if (! is_writable($file)) {
						$message = sprintf(__('File %s not writable!'), $file);
						$success = false;

						continue 2;
					}
				}

				$userTranslations = [];

				if (file_exists($userpoFile)) {
					$userTranslations = phpmo_parse_po_file($userpoFile);
				}

				$userTranslations[$text] = ['msgid' => $text, 'msgstr' => [$translation]];

				if (phpmo_write_po_file($userTranslations, $userpoFile)) {
					$userTranslations += phpmo_parse_po_file($poFile);

					if (phpmo_write_mo_file($userTranslations, $moFile)) {
					} else {
						$message .= __('Error compiling!');
						$success = false;
					}
				} else {
					$message = sprintf(__('Error saving %s file!'), $poFile);
					$success = false;
				}
			}
		}

		$this->response->setType('json');
		$this->response->output(['success' => $success, 'message' => $message]);
	}
}
