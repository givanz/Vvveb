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

namespace Vvveb\Controller\Localization;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\url;

require_once DIR_SYSTEM . 'functions' . DS . 'php-mo.php';

class Translations extends Base {
	function domains() {
		$lang   = $this->request->get['lang'] ?? false;

		if ($lang) {
			$folder = DIR_ROOT . 'locale' . DS . $lang . DS . 'LC_MESSAGES' . DS;
			$files  = glob($folder . '*.po');

			$url = ['module' => 'localization/translations', 'action' => 'domain', 'lang' => $lang];
			//add user if does not exist yet
			$domains['user'] = url($url + ['domain' => 'user']);

			foreach ($files as $file) {
				$domain           = str_replace('.po', '', basename($file));
				$domains[$domain] =  url($url + ['domain' => $domain]);
			}

			$this->view->domains = $domains;
			$this->view->count   = count($domains);
		} else {
			$this->notFound(false, __('Invalid request!'));
		}
	}

	function save() {
		$view         = $this->view;
		$translations = $this->request->post['translations'] ?? [];
		$this->response->setType('json');

		$message = __('No data!');

		if ($translations) {
			//use common check po file and parse
			$this->domain();

			$folder = DIR_ROOT . 'locale' . DS . $view->lang . DS . 'LC_MESSAGES' . DS;

			foreach ($translations as $text => $translation) {
				$view->translations[$text] = ['msgid' => $text, 'msgstr' => [$translation]];
			}

			if (phpmo_write_po_file($view->translations, $folder . $view->domain . '.po')) {
				$message = __('Saved!');

				//append user translations
				if ($view->domain != 'user') {
					$user = $folder . 'user.po';

					if (file_exists($user) && ($translations = phpmo_parse_po_file($user) ?: []) && is_array($translations)) {
						$view->translations = array_merge($view->translations, $translations);
					}
				}
				//compile
				if (phpmo_write_mo_file($view->translations, $folder . $view->domain . '.mo')) {
					$message .= "\n" . __('Compiled!');
				} else {
					$message .= "\n" . __('Error compiling!');
				}
			} else {
				$message = __('Error saving!');
			}
		}

		$result = ['message' => $message];
		$this->response->output($result);
	}

	function domain() {
		$view             = $this->view;
		$view->lang       =  $lang       = $this->request->get['lang'] ?? false;
		$view->domain     =  $domain     = $this->request->get['domain'] ?? false;
		$url              = ['module' => 'localization/translations', 'lang' => $lang, 'domain' => $domain];
		$view->domainsUrl = url($url + ['action' => 'domains']);
		$view->saveUrl    = url($url + ['action' => 'save']);

		if ($lang && $domain) {
			$poFile       = DIR_ROOT . 'locale' . DS . $lang . DS . 'LC_MESSAGES' . DS . $domain . '.po';
			$view->poFile = $poFile;

			if (file_exists($poFile)) {
				$view->translations = phpmo_parse_po_file($poFile);

				if (! is_writeable($poFile)) {
					$view->info = [__('Po file not writable, saving will not work!')];
				}
			} else {
				if ($domain == 'user') {
					//if domain is user then create it
					$view->translations = [];
				} else {
					$this->notFound(false, __('Po file does not exist!'));
				}
			}
		} else {
			$this->notFound(false, __('Invalid request!'));
		}
	}
}
