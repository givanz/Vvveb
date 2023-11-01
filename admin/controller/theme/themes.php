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
use Vvveb\System\Extensions\Themes as ThemesList;
use Vvveb\System\Import\Theme;
use Vvveb\System\Sites;

class Themes extends Base {
	function upload() {
		$files = $this->request->files;
		$error = false;

		foreach ($files as $file) {
			$this->themeSlug = str_replace('.zip', '', strtolower($file['name']));

			if ($file) {
				try {
					// use temorary file, php cleans temporary files on request finish.
					$this->themeSlug = ThemesList :: install($file['tmp_name'], $this->themeSlug, false);
				} catch (\Exception $e) {
					$error                = $e->getMessage();
					$this->view->errors[] = $error;
				}
			}

			if (! $error && $this->themeSlug) {
				$this->themeName         = \Vvveb\humanReadable($this->themeSlug);
				$this->themeName         = "<b>$this->themeName</b>";
				$this->themeActivateUrl  = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'activate', 'theme' => $this->themeSlug]);
				$successMessage          = sprintf(__('Theme %s was successfully installed!'), $this->themeSlug);
				$successMessage .= "<p><a href='{$this->themeActivateUrl}'>" . __('Activate theme') . '</a></p>';
				$this->view->success[] = $successMessage;
			}
		}

		return $this->index();
	}

	function index() {
		$themes             =  ThemesList :: getList();

		$this->view->themes = $themes;
		//$this->view->count  = count($themes);

		$themeImport       =  new Theme('landing');

		$structure                       = $themeImport->getStructure();
		$this->view->import              = $structure;
		$this->view->required_plugins    = ['seo'=> '', 'markdown' => '', 'test1' => ''];
		$this->view->recommended_plugins = $structure;
	}

	function processImport($data, $path, $type = false) {
		foreach ($data as $key => $value) {
			if (is_array($value)) {
				switch ($key) {
					case 'media':
						$type = 'media';

						break;

					case 'content':
						$type = 'content';

						break;
				}
				$this->processImport($value, $path . '/' . $key, $type);
			} elseif (is_numeric($key)) {
				echo $path . DS . $key . " - $type <br/>";
			}
		}
	}

	function import() {
	}

	function activate() {
		$theme = $this->request->get['theme'];

		if (Sites::setTheme($this->global['site_id'], $theme)) {
			$themeName               = \Vvveb\humanReadable($theme);
			$this->themeActivateUrl  = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'import', 'theme' => $theme]);
			$successMessage          = sprintf(__('Theme <b>%s</b> was activated!'), $themeName, $this->themeActivateUrl);
			$successMessage .= '<a class="btn btn-success btn-sm ms-4" href="' . $this->themeActivateUrl . '">' . __('Import theme content') . '</a>';
			$successMessage .= '<a class="btn btn-outline-primary btn-sm ms-2" target="_blank" href="/">' . __('View website') . '</a>';

			$this->view->success[] = $successMessage;
		} else {
			$error                = __('Error activating theme, check config/sites.php write permissions');
			$this->view->errors[] = $error;
		}

		return $this->index();
	}
}
