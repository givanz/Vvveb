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

namespace Vvveb\Controller\Plugin;

use function Vvveb\__;
use Vvveb\Controller\Base;
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Validator;

class Market extends Base {
	function install() {
		$slug = $this->request->get['slug'];

		try {
			if ($slug) {
				$plugin =  Plugins :: getMarketList(['slug' => $slug])['plugins'];

				if ($plugin && isset($plugin[0])) {
					$pluginInfo = $plugin[0];
					extract($pluginInfo);
					$url          = Plugins :: marketUrl();
					$downloadLink = "$url$download_link";

					$this->view->log[] = sprintf(__('Installing "%s"'), $name);
					$this->view->log[] = sprintf(__('Downloading "%s"'), $downloadLink);

					if ($tempFile = Plugins :: download($downloadLink)) {
						$this->view->log[] = sprintf(__('Unpacking "%s"'), $tempFile);

						if (Plugins :: install($tempFile, $slug)) {
							$pluginName        = \Vvveb\humanReadable($slug);
							$pluginName        = "<b>$pluginName</b>";
							$pluginActivateUrl = \Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'activate', 'plugin' => $slug]);

							$successMessage    = sprintf(__('Plugin %s was successfully installed!'), $pluginName, $pluginActivateUrl);
							$this->view->log[] = $successMessage;

							$successMessage .= "<a class='btn btn-primary btn-sm m-2'  href='$pluginActivateUrl'>" . __('Activate plugin') . '</a>';
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
					$this->view->errors[] = sprintf(__('Plugin "%s" not found!'), $slug);
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

	function download() {
		$slug = $this->request->get['slug'];

		if ($slug) {
		}
	}

	function index() {
		$view = View :: getInstance();

		$validator = new Validator(['plugins']);

		//allow only fields that are in the validator list and remove the rest
		$request = $validator->filter($this->request->get);
		$plugins = [];

		try {
			$plugins   =  Plugins :: getMarketList($request);
			$installed = Plugins :: getList($this->global['site_id']);

			foreach ($plugins['plugins'] as &$plugin) {
				$plugin['installed']  = isset($installed[$plugin['slug']]);
			}
		} catch (\ErrorException $e) {
			$view->errors[] =  $e->getMessage();
		}

		$view->installUrl = 'admin/?module=plugin/market&action=install&json';
		$view->set($plugins);
	}
}
