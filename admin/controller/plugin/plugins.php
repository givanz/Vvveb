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
use function Vvveb\fileUploadErrMessage;
use function Vvveb\rrmdir;
use Vvveb\System\CacheManager;
use function Vvveb\System\Core\exceptionToArray;
use Vvveb\System\Core\FrontController;
use Vvveb\System\Core\View;
use Vvveb\System\Extensions\Plugins as PluginsList;
use Vvveb\System\User\Admin;

class Plugins extends Base {
	function init() {
		parent::init();

		$this->plugin = $this->request->post['plugin'] ?? null;
	}

	function update() {
		$slug = $this->request->post['plugin'] ?? false;

		try {
			if ($slug) {
				$plugin =  PluginsList :: getMarketList(['slug' => $slug])['plugins'];

				if ($plugin && isset($plugin[0])) {
					$pluginInfo = $plugin[0];
					extract($pluginInfo);
					$url          = PluginsList :: marketUrl();
					$downloadLink = "$url$download_link";

					$this->view->log[] = sprintf(__('Installing "%s"'), $name);
					$this->view->log[] = sprintf(__('Downloading "%s"'), $downloadLink);

					if ($tempFile = PluginsList :: download($downloadLink)) {
						$this->view->log[] = sprintf(__('Unpacking "%s"'), $tempFile);

						if (! is_writable(DIR_PLUGINS . $slug) && ! @chmod($path, 0750)) {
							$this->view->errors[] = sprintf(__('"%s" is not writable'), DIR_PLUGINS . $slug);
						}

						if (PluginsList :: install($tempFile, $slug)) {
							CacheManager::clearObjectCache('vvveb', 'plugins_list_' . $this->global['site_id']);
							$pluginName        = \Vvveb\humanReadable($slug);
							$pluginName        = "<b>$pluginName</b>";
							$pluginActivateUrl = \Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'activate', 'plugin' => $slug]);

							$successMessage    = sprintf(__('Plugin %s was successfully updated!'), $pluginName, $pluginActivateUrl);
							$this->view->log[] = $successMessage;

							$this->view->success[] = $successMessage;
						} else {
							$error                = sprintf(__('Error updating "%s"!'), $slug);
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

	function deactivate() {
		if (PluginsList::deactivate($this->plugin, $this->global['site_id'])) {
			$this->view->success[] = sprintf(__('Plugin `%s` deactivated!'), '<b>' . \Vvveb\humanReadable($this->plugin) . '</b>');
			CacheManager::clearObjectCache('admin-menu');
			CacheManager::clearCompiledFiles();
			CacheManager::clearPageCache();
		} else {
			$this->view->errors[] = sprintf(__('Error deactivating plugin `%s`!'), '<b>' . \Vvveb\humanReadable($this->plugin) . '</b>');
		}

		return $this->index();
	}

	function checkPluginAndActivate() {
		//allow plugin activation through get parameter if csrf is provided
		if (($csrf = $this->request->get['csrf'] ?? false) &&
			($plugin = $this->request->get['plugin'] ?? null) &&
			($csrf == $this->session->get('csrf'))) {
			$this->plugin = $plugin;
		}

		$this->category             = $this->request->get['category'] ?? false;
		$this->pluginCheckUrl       = \Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'activate', 'plugin' => $this->plugin, 'category' => $this->category, 'csrf' => $this->session->get('csrf')]);
		$this->view->checkPluginUrl = $this->pluginCheckUrl;
		$this->view->info[]         = sprintf(__('Activating %s plugin ...'), '<b>' . \Vvveb\humanReadable($this->plugin) . '</b> <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>');

		return $this->index();
	}

	function index() {
		$view             = View :: getInstance();
		$view->category   = $this->request->get['category'] ?? false;
		$cache            = (bool)($this->request->get['cache'] ?? true);
		$view->plugins    = PluginsList :: getList($this->global['site_id'], $view->category, $cache);
		$view->categories = PluginsList :: getCategories($this->global['site_id']);
		$view->safemode   = ($admin = Admin::current()) && isset($admin['safemode']) && $admin['safemode'];
	}

	function delete() {
		if ($this->plugin) {
			if (!is_array($this->plugin)) {
				$this->plugin[] = $this->plugin;
			}

			foreach ($this->plugin as $plugin) { 
				try {
					if (PluginsList::uninstall($plugin, $this->global['site_id'])) {
						$this->view->success[] = sprintf(__('Plugin "%s" removed!'), \Vvveb\humanReadable($plugin));
					} else {
						$this->view->errors[] = sprintf(__('Error removing "%s" plugin!'), \Vvveb\humanReadable($plugin));
					}
				} catch (\Exception $e) {
					$this->view->errors[] = sprintf(__('Error removing "%s" plugin!'), \Vvveb\humanReadable($plugin)) . ' - ' . $e->getMessage();
				}
			}
		}

		return $this->index();
	}

	function upload() {
		$files = $this->request->files;
		$error = false;

		foreach ($files as $file) {
			if ($file && $file['error'] == UPLOAD_ERR_OK) {
				try {
					// use temorary file, php cleans temporary files on request finish.
					$this->pluginSlug = PluginsList :: install($file['tmp_name'], str_replace('.zip', '', strtolower($file['name'])));
				} catch (\Exception $e) {
					$error                = $e->getMessage();
					$this->view->errors[] = $error;
				}
			} else {
				$error                 = true;
				$this->view->errors[]  = sprintf(__('Error uploading %s!'), $file['name']);
				$this->view->warning[] = sprintf(fileUploadErrMessage($file['error']));
			}

			if (! $error) {
				if ($this->pluginSlug) {
					$this->pluginName        = \Vvveb\humanReadable($this->pluginSlug);
					$this->pluginName        = "<b>$this->pluginName</b>";
					$this->pluginActivateUrl = \Vvveb\url(['module' => 'plugin/plugins', 'action'=> 'checkPluginAndActivate', 'plugin' => $this->pluginSlug, 'csrf' => $this->session->get('csrf')]);
					$successMessage          = sprintf(__('Plugin %s was successfully installed!'), $this->pluginName, $this->pluginActivateUrl);
					$successMessage         .= '<button type="submit" name="plugin" value="' . $this->themeSlug . '" class="btn btn-primary btn-sm ms-2" onclick="document.getElementById(\'action\').value=\'checkPluginAndActivate\';">' . __('Activate plugin') . '</button>';
					$this->view->success[]   = $successMessage;
				} else {
					$errorMessage            = sprintf(__('Failed to install %s plugin!'), $this->pluginName);
					$this->view->error[]     = $errorMessage;
				}
			}
		}

		return $this->index();
	}

	function activate() {
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
		$this->category = $this->request->get['category'] ?? false;
		$this->plugin   = $this->request->post['plugin'] ?? $this->request->get['plugin'] ?? null;
		$csrf           = $this->request->post['csrf'] ?? $this->request->get['csrf'] ?? false;

		if (! defined('CLI') && $csrf != $this->session->get('csrf')) {
			die('Invalid csrf!');
		}

		$active = false;
		$error  = false;

		try {
			if (PluginsList::loadPlugin($this->plugin)) {
				if (PluginsList::activate($this->plugin, $this->global['site_id'])) {
					$active = true;
				}
			}
		} catch (\ParseError $e) {
			$error = exceptionToArray($e);
		} catch (\Exception $e) {
			$error = exceptionToArray($e);
		}

		if ($error) {
			$error['minimal'] = true;
			$error['title']   = sprintf(__('Error activating plugin `%s`!'), $this->plugin);
			FrontController::notFound(false, $error, 500);

			die(0);
		}

		if ($active) {
			$refreshUrl = \Vvveb\url(['module' => 'plugin/plugins', 'cache' => false, 'category' => $this->category], false) . '&t=' . time();
			$success    =  sprintf(__('Plugin `%s` activated!'), '<b>' . \Vvveb\humanReadable($this->plugin) . '</b>');
			$plugin     =  PluginsList :: getList($this->global['site_id'])[$this->plugin] ?? [];

			if (isset($plugin['settings']) && $plugin['settings']) {
				$success .= "<a class='btn btn-primary btn-sm m-2' href='{$plugin['settings']}'>" . __('Settings') . '</a>';
			}
			$this->session->set('success', $success);

			ignore_user_abort(1);
			clearstatcache(true);

			CacheManager::clearObjectCache('admin-menu');
			CacheManager::clearCompiledFiles();
			CacheManager::clearPageCache();

			if (defined('CLI')) {
				$this->view->success = [$success];
			} else {
				$response   = "
					<html>
					<head>
					<script>
					function reloadPage() {
						//setTimeout(() => parent.location='$refreshUrl&success=$success', 100);
						setTimeout(() => parent.location='$refreshUrl', 100);
					}
					</script>
					</head>
					<body onload='reloadPage()'><!-- Plugin valid -->
					</body>
					</html>";

				die($response);
			}
		} else {
			die(sprintf(__('Error activating plugin `%s`!'), $this->plugin) . '<br/>' . __('Check config file permissions!'));
		}

		return false;
	}
}
