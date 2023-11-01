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
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Themes;
use Vvveb\System\Validator;

class Market extends Base {
	function install() {
		$slug = $this->request->get['slug'];

		try {
			if ($slug) {
				$theme =  Themes :: getMarketList(['slug' => $slug])['themes'];

				if ($theme && isset($theme[0])) {
					$themeInfo = $theme[0];
					extract($themeInfo);
					$url          = Themes :: marketUrl();
					$downloadLink = "$url$download_link";

					$this->view->log[] = sprintf(__('Installing "%s"'), $name);
					$this->view->log[] = sprintf(__('Downloading "%s"'), $downloadLink);

					if ($tempFile = Themes :: download($downloadLink)) {
						$this->view->log[] = sprintf(__('Unpacking "%s"'), $tempFile);

						if (Themes :: install($tempFile, $slug, false)) {
							$themeName        = \Vvveb\humanReadable($slug);
							$themeName        = "<b>$themeName</b>";
							$themeActivateUrl = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'activate', 'theme' => $slug]);

							$successMessage    = sprintf(__('Theme %s was successfully installed!'), $themeName, $themeActivateUrl);
							$this->view->log[] = $successMessage;

							$successMessage .= "<a class='btn btn-primary btn-sm m-2'  href='$themeActivateUrl'>" . __('Activate theme') . '</a>';
							$this->view->success[] = $successMessage;
						} else {
							$error                = sprintf(__('Error installing "%s"!'), $slug);
							$this->view->log[]    = $error;
							$this->view->errors[] = $error;
						}

						unlink($tempFile);
					} else {
						$this->view->errors[] = sprintf(__('Error downloading "%s" from %s!'), $slug, $downloadLink);
					}
				} else {
					$this->view->errors[] = sprintf(__('Theme "%s" not found!'), $slug);
				}
			}
		} catch (\Exception $e) {
			$error                = $e->getMessage();
			$this->view->errors[] = $error;
		}

		if (isset($this->request->get['json'])) {
			$this->view->setType('json');
		}
	}

	function index() {
		$view = View :: getInstance();

		$validator = new Validator(['themes']);

		//allow only fields that are in the validator
		$request = $validator->filter($this->request->get);
		$themes  = [];

		try {
			$themes    =  Themes :: getMarketList($request);
			$installed = Themes :: getList($this->global['site_id']);

			foreach ($themes['themes'] as &$theme) {
				$theme['installed']  = isset($installed[$theme['slug']]);
			}
		} catch (\ErrorException $e) {
			$view->errors[] =  $e->getMessage();
		}

		$view->set($themes);
	}
}
