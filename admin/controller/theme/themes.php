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
use function Vvveb\fileUploadErrMessage;
use function Vvveb\humanReadable;
use function Vvveb\rcopy;
use function Vvveb\rrmdir;
use function Vvveb\sanitizeFileName;
use function Vvveb\slugify;
use Vvveb\System\CacheManager;
use Vvveb\System\Extensions\Themes as ThemesList;
use Vvveb\System\Import\Theme;
use Vvveb\System\Sites;

class Themes extends Base {
	function update() {
		$slug = $this->request->post['theme'] ?? false;

		try {
			if ($slug) {
				$theme =  ThemesList :: getMarketList(['slug' => $slug])['themes'];

				if ($theme && isset($theme[0])) {
					$themeInfo = $theme[0];
					extract($themeInfo);
					$url          = ThemesList :: marketUrl();
					$downloadLink = "$url$download_link";

					$this->view->log[] = sprintf(__('Installing "%s"'), $name);
					$this->view->log[] = sprintf(__('Downloading "%s"'), $downloadLink);

					if ($tempFile = ThemesList :: download($downloadLink)) {
						$this->view->log[] = sprintf(__('Backup theme "%s"'), $slug);

						$newSlug     = $slug . '-backup-' . date('Y-m-d_H_i_s');
						$newName     = humanReadable($slug) . ' backup ' . date('Y-m-d H:i:s');
						$srcDir      = DIR_THEMES . $slug;
						$destDir     = DIR_THEMES . $newSlug;

						$skipFolders = [/*'backup', */'src', 'node_modules', '.git'];
						if ($theme && is_dir($srcDir)) {
							if (rcopy($srcDir, $destDir, $skipFolders)) {
								$themePhp = $destDir . DS . 'theme.php';
								$content  = file_get_contents($themePhp);

								if ($content) {
									$content = preg_replace('/[Nn]ame:.+/', "Name: $newName", $content);
									$content = preg_replace('/[Ss]lug:.+/', "Slug: $newSlug", $content);
									$content = preg_replace('/[Tt]ext [Dd]omain:.+/', "Text Domain: $newSlug", $content);

									if (file_put_contents($themePhp, $content)) {
										ThemesList :: clearThemesCache();
									} else {
										$this->view->errors[] = _('Error setting theme name!');
									}
								} else {
									$this->view->errors[] = _('Error getting theme info!');
								}

								$this->view->log[] = __('Backup complete') . ' - ' . $newName;
							} else {
								$this->view->log[] = __('Backup error');
							}
						}

						$this->view->log[] = sprintf(__('Unpacking "%s"'), $tempFile);

						if (! is_writable(DIR_THEMES . $slug) && ! @chmod($path, 0750)) {
							$this->view->errors[] = sprintf(__('"%s" is not writable'), DIR_THEMES . $slug);
						}

						if (ThemesList :: install($tempFile, $slug, false)) {
							CacheManager::clearObjectCache('vvveb', 'themes_list_' . $this->global['site_id']);
							CacheManager::clearObjectCache('site');
							CacheManager::clearFrontend();
							CacheManager::clearCompiledFiles();
							CacheManager::clearPageCache();
							ThemesList :: clearThemesCache();

							$themeName        = humanReadable($slug);
							$themeActivateUrl = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'activate', 'theme' => $slug]);

							$successMessage    = sprintf(__('Theme %s was successfully updated!'), $themeName, $themeActivateUrl);
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

	function duplicate() {
		$theme   = sanitizeFileName(basename($this->request->post['theme'] ?? ''));
		$dest    = sanitizeFileName(basename($this->request->post['dest'] ?? ''));
		$newSlug = slugify($dest);

		$srcDir      = DIR_THEMES . $theme;
		$destDir     = DIR_THEMES . $newSlug;
		$skipFolders = [/*'backup', */'src', 'node_modules', '.git'];

		if ($dest) {
			if (file_exists($destDir)) {
				$this->view->errors[] = _('Destination directory already exists!');
			} else {
				if ($theme && is_dir($srcDir)) {
					if (rcopy($srcDir, $destDir, $skipFolders)) {
						$themePhp = $destDir . DS . 'theme.php';
						$content  = file_get_contents($themePhp);

						if ($content) {
							$content = preg_replace('/[Nn]ame:.+/', "Name: $dest", $content);
							$content = preg_replace('/[Ss]lug:.+/', "Slug: $newSlug", $content);
							$content = preg_replace('/[Tt]ext [Dd]omain:.+/', "Text Domain: $newSlug", $content);

							if (file_put_contents($themePhp, $content)) {
								$this->view->success[] = _('Theme duplicated!');
								ThemesList :: clearThemesCache();
							} else {
								$this->view->errors[] = _('Error setting theme name!');
							}
						} else {
							$this->view->errors[] = _('Error getting theme info!');
						}
					} else {
						$this->view->errors[] = _('Error duplicating theme!');
					}
				}
			}
		}

		return $this->index();
	}

	function delete() {
		$theme = sanitizeFileName(basename($this->request->post['theme'] ?? ''));

		if ($theme && is_dir(DIR_THEMES . $theme)) {
			if (rrmdir(DIR_THEMES . $theme)) {
				ThemesList :: clearThemesCache();
				$this->view->success[] = _('Theme removed!');
			} else {
				$this->view->errors[] = _('Error removing theme!');
			}
		}

		return $this->index();
	}

	function upload() {
		$files = $this->request->files;
		$error = false;

		foreach ($files as $file) {
			$this->themeSlug = str_replace('.zip', '', strtolower($file['name']));

			if ($file && $file['error'] == UPLOAD_ERR_OK) {
				try {
					// use temorary file, php cleans temporary files on request finish.
					$this->themeSlug = ThemesList :: install($file['tmp_name'], $this->themeSlug, false);

					if ($this->themeSlug) {
						ThemesList :: fixIfMissingTemplates($this->themeSlug);
					}
				} catch (\Exception $e) {
					$error                = $e->getMessage();
					$this->view->errors[] = $error;
				}
			} else {
				$error                 = true;
				$this->view->errors[]  = sprintf(__('Error uploading %s!'), $this->themeSlug);
				$this->view->warning[] = sprintf(fileUploadErrMessage($file['error']));
			}

			if (! $error && $this->themeSlug) {
				$this->themeName         = humanReadable($this->themeSlug);
				$this->themeName         = "<b>$this->themeName</b>";
				$this->themeActivateUrl  = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'activate', 'theme' => $this->themeSlug]);
				$successMessage          = sprintf(__('Theme %s was successfully installed!'), '<b>' . $this->themeSlug . '</b>');
				$successMessage         .= '<button type="submit" name="theme" value="' . $this->themeSlug . '" class="btn btn-primary btn-sm ms-2" onclick="document.getElementById(\'action\').value=\'activate\';">' . __('Activate theme') . '</button>';
				$this->view->success[]   = $successMessage;
				ThemesList :: clearThemesCache();
			}
		}

		return $this->index();
	}

	function index() {
		$themes             =  ThemesList :: getList($this->global['site_id']);

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
		/*
		$import              = $this->request->post['import'];
		$required_plugins    = $this->request->post['required_plugins'];
		$recommended_plugins = $this->request->post['recommended_plugins'];*/

		//print_r($import);
		//$this->processImport($import, '');
		//print_r($required_plugins);
		//print_r($recommended_plugins);

		return;
	}

	function activate() {
		$theme = $this->request->post['theme'] ?? false;

		if ($theme && Sites::setTheme($this->global['site_id'], $theme, 'index.html')) {
			$themeName               = humanReadable($theme);
			$this->themeActivateUrl  = \Vvveb\url(['module' => 'theme/themes', 'action'=> 'import', 'theme' => $theme]);
			$successMessage          = sprintf(__('Theme <b>%s</b> was activated!'), $themeName, $this->themeActivateUrl);
			//$successMessage .= '<a class="btn btn-success btn-sm ms-4" href="' . $this->themeActivateUrl . '">' . __('Import theme content') . '</a>';
			$successMessage .= '<a class="btn btn-outline-primary btn-sm ms-2" target="_blank" href="/">' . __('View website') . '</a>';

			$this->view->success[] = $successMessage;
			CacheManager::clearObjectCache('site');
			CacheManager::clearFrontend();
			CacheManager::clearCompiledFiles();
			CacheManager::clearPageCache();
		} else {
			$error                = __('Error activating theme, check config/sites.php write permissions');
			$this->view->errors[] = $error;
		}

		return $this->index();
	}
}
