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
use function Vvveb\model;
use Vvveb\System\CacheManager;
use Vvveb\System\Locale;

class Language extends Crud {
	/**
	 * Entity type identifier for languages.
	 *
	 * @var string
	 */
	protected string $type = 'language';

	/**
	 * Singular controller name for detail views.
	 *
	 * @var string
	 */
	protected string $controller = 'language';

	/**
	 * Module path this controller belongs to.
	 *
	 * @var string
	 */
	protected string $module = 'localization';

	/**
	 * URL template for downloading language packs from GitHub.
	 *
	 * @var string
	 */
	protected string $installUrl = 'https://raw.githubusercontent.com/Vvveb/{code}/master/LC_MESSAGES/';

	/**
	 * List of translation files to download for a language pack.
	 *
	 * @var list<string>
	 */
	protected array $files = ['vvveb.po'];

	/**
	 * URL to the translations contribution page.
	 *
	 * @var string
	 */
	protected string $listUrl = 'https://www.vvveb.com/page/contribute#language';

	/**
	 * Save language data, handling default language logic and cache clearing.
	 *
	 * @return void
	 */
	public function save(): void {
		$this->model = model('language');
		//check if a default language is set
		$default = $this->model->get(['default' => 1]);

		//if language set as default set other default language as false
		if (isset($this->request->post['language']['default']) && $this->request->post['language']['default'] == '1') {
			$this->model->edit(['language' => ['default' => 0]]);
		} else {
			//if no default language set then set this language as default
			if (!$default) {
				$this->request->post['language']['default']  = 1;
			}
		}

		CacheManager::clearObjectCache(APP, 'languages');
		parent::save();
		$this->index();
	}

	/**
	 * Download and install a language pack from the remote repository.
	 *
	 * @return void
	 */
	public function install(): void {
		$code      = filter('/[-\w]+/', $this->request->post['code']);
		$url       = str_replace('{code}', $code, $this->installUrl);
		$available = false;

		require DIR_SYSTEM . 'functions' . DS . 'php-mo.php';

		foreach ($this->files as $file) {
			$translations = download($url . $file);

			if ($translations) {
				$available = true;
				$folder    = DIR_ROOT . 'locale' . DS . $code . DS . 'LC_MESSAGES';
				$poFile    = $folder . DS . $file;
				@mkdir($folder, 0755 & ~umask(), true);

				if (file_put_contents($poFile, $translations)) {
					if (phpmo_convert($poFile)) {
						$this->view->success['language'] = __('Language pack installed!');
					} else {
						$this->view->errors['language'] = __('Language compilation failed!');

						break;
					}
				} else {
					$this->view->errors[] = __('Error writing language files!');

					break;
				}
			} else {
				break;
			}
		}

		if (!$available) {
			$this->view->errors[] = __('Language pack not available!');
			$this->view->info[]   = sprintf(__('Check available translations at %s'), '<a href="' . $this->listUrl . '" target="_blank">' . $this->listUrl . '</a>');
		}

		$this->index();
	}

	/**
	 * Display the languages management page with installed and available languages.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();
		$languageList = include DIR_SYSTEM . 'data' . DS . 'languages-list.php';
		$installed    = Locale::installedLanguages();

		foreach ($installed as $l) {
			$languageList[$l]['installed'] = true;
		}

		$this->view->language_list  = $languageList;

		$this->view->status  = [1 => __('Active'), 0 => __('Inactive')];
		$this->view->default = $this->view->rtl =  [0 => __('No'), 1 => __('Yes')];
	}
}
