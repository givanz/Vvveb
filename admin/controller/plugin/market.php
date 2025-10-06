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
use Vvveb\System\CacheManager;
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Plugins;
use Vvveb\System\Validator;

class Market extends Base {
	function install() {
		$slug = $this->request->post['slug'] ?? false;

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
							CacheManager::clearObjectCache('vvveb', 'plugins_list_' . $this->global['site_id']);
							$pluginName        = \Vvveb\humanReadable($slug);
							$pluginName        = "<b>$pluginName</b>";
							$pluginActivateUrl = \Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'activate', 'plugin' => $slug]);

							$successMessage    = sprintf(__('Plugin %s was successfully installed!'), $pluginName, $pluginActivateUrl);
							$this->view->log[] = $successMessage;

							$successMessage .= "<button class='btn btn-primary btn-sm m-2' formaction='$pluginActivateUrl' name='plugin' value='$slug'>" . __('Activate plugin') . '</button>';
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
		$request = array_filter($validator->filter($this->request->get));
		$plugins = [];

		$request['limit'] = $this->view->limit = 8;

		try {
			$plugins    = Plugins :: getMarketList($request);
			$categories = Plugins :: getMarketCategories(['limit' => 100] + $request, 'categories');
			$installed  = Plugins :: getList($this->global['site_id']);

			foreach ($plugins['plugins'] as &$plugin) {
				$plugin['installed']  = isset($installed[$plugin['slug']]);
			}
		} catch (\Exception $e) {
			$view->warning[] =  __('Failed to connect to marketplace');

			if (DEBUG) {
				$view->errors[] =  $e->getMessage();
			}
		}

		$admin_path       = \Vvveb\adminPath();
		$view->installUrl = $admin_path . 'index.php?module=plugin/market&action=install&json';
		$view->categories = $categories['categories'];
		$view->set($request);
		$view->set($plugins);
	}
}
