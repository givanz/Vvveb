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
use Vvveb\Controller\Crud;
use function Vvveb\download;
use function Vvveb\filter;
use function Vvveb\installedLanguages;
use Vvveb\System\CacheManager;

class Language extends Crud {
	protected $type = 'language';

	protected $module = 'localization';

	protected $installUrl = 'https://raw.githubusercontent.com/Vvveb/{code}/master/LC_MESSAGES/vvveb.po';

	protected $listUrl = 'https://www.vvveb.com/page/contribute#language';

	function save() {
		CacheManager::delete(APP . 'cache.languages');

		return parent::save();
	}

	function install() {
		$code         = filter('/[-\w]+/', $this->request->post['code']);
		$url          = str_replace('{code}', $code, $this->installUrl);
		$translations = download($url);

		if ($translations) {
			$folder = DIR_ROOT . 'locale' . DS . $code . DS . 'LC_MESSAGES';
			$poFile = $folder . DS . 'vvveb.po';
			@mkdir($folder, 0755 & ~umask(), true);

			if (file_put_contents($poFile, $translations)) {
				require DIR_SYSTEM . 'functions' . DS . 'php-mo.php';

				if (phpmo_convert($poFile)) {
					$this->view->success[] = __('Language pack installed!');
				} else {
					$this->view->errors[] = __('Language compilation failed!');
				}
			} else {
				$this->view->errors[] = __('Error writing language files!');
			}
		} else {
			$this->view->errors[] = __('Language pack not available!');
			$this->view->info[]   = sprintf(__('Check available translations at %s'), '<a href="' . $this->listUrl . '" target="_blank">' . $this->listUrl . '</a>');
		}

		return $this->index();
	}

	function index() {
		parent::index();
		$languageList = include DIR_SYSTEM . 'data' . DS . 'languages-list.php';
		$installed    = installedLanguages();

		foreach ($installed as $l) {
			$languageList[$l]['installed'] = true;
		}

		$this->view->language_list  = $languageList;

		$this->view->status  = [1 => 'Active', 0 => 'Inactive'];
		$this->view->default = [0 => 'No', 1 => 'Yes'];
	}
}
