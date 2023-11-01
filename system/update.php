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

namespace Vvveb\System;

use function Vvveb\download;
use function Vvveb\getUrl;
use function Vvveb\rcopy;
use function Vvveb\rrmdir;

define('CHMOD_DIR', (0755 & ~umask()));
define('CHMOD_FILE', (0644 & ~umask()));

class Update {
	protected $url 		= 'https://www.vvveb.com/update.json';

	protected $workDir	= DIR_STORAGE . 'upgrade';

	protected $zipFile	= false;

	function checkUpdates($type = 'core',$force = false) {
		if ($force) {
			//delete update cache
			$cacheKey    = md5($this->url);
			$cacheDriver = Cache :: getInstance();
			$cacheDriver->delete('url', $cacheKey);
		}

		//cache results for one week
		try {
			$result = getUrl($this->url, true, 3600 * 24 * 7);
		} catch (\Exception $e) {
			$result = '{}';
		}

		if ($result) {
			$info = json_decode($result, true);

			if ($type == 'core') {
				$info['hasUpdate'] = max(version_compare($info['version'], V_VERSION), 0);
			}

			return $info;
		}

		return [];
	}

	static function backup() {
		$skipFolders  = ['plugins', 'public', 'storage', 'install'];
		$backupFolder = DIR_BACKUP . 'update-' . V_VERSION . '-' . date('Y-m-d_H:i:s');

		rcopy(DIR_ROOT, $backupFolder, $skipFolders);
	}

	static function download($url) {
		//$temp = tmpfile();
		$f    = false;
		$temp = tempnam(sys_get_temp_dir(), 'vvveb_update');

		if ($content = download($url)) {
			$f  = file_put_contents($temp, $content, LOCK_EX);

			return $temp;
		}

		return $f;
	}

	function setPermissions() {
		$folders = [DIR_ROOT . 'app', DIR_ROOT . 'admin', DIR_ROOT . 'system'];
		$files   = [DIR_ROOT . 'index.php', DIR_ROOT . 'admin' . DS . 'index.php', DIR_ROOT . 'install' . DS . 'index.php'];

		foreach ($folders as $folder) {
			@chmod($folder, CHMOD_DIR);
		}

		foreach ($files as $file) {
			@chmod($file, CHMOD_FILE);
		}

		return true;
	}

	function copyAdmin() {
		ignore_user_abort(true);
		//$skipFolders = ['plugins', 'public', 'storage'];
		rcopy($this->workDir . DS . 'admin', DIR_ROOT . DS . 'admin');

		return true;
	}

	function copyApp() {
		ignore_user_abort(true);
		//$skipFolders = ['plugins', 'public', 'storage'];
		rcopy($this->workDir . DS . 'app', DIR_ROOT . DS . 'app');

		return true;
	}

	function copySystem() {
		ignore_user_abort(true);
		//$skipFolders = ['plugins', 'public', 'storage'];
		rcopy($this->workDir . DS . 'system', DIR_ROOT . DS . 'system');

		return true;
	}

	function copyInstall() {
		ignore_user_abort(true);
		//$skipFolders = ['plugins', 'public', 'storage'];
		rcopy($this->workDir . DS . 'install', DIR_ROOT . DS . 'install');

		return true;
	}

	function copyCore() {
		ignore_user_abort(true);
		$skipFolders = ['plugins', 'public', 'storage', 'system', 'app', 'admin', 'install', 'config', 'env.php'];
		rcopy($this->workDir, DIR_ROOT, $skipFolders);

		return true;
	}

	function copyConfig() {
		ignore_user_abort(true);
		$skip = ['plugins.php', 'mail.php', 'sites.php', 'app.php', 'admin.php', 'routes.php', 'env.php'];
		rcopy($this->workDir . DS . 'config', DIR_ROOT . DS . 'config', $skip);

		return true;
	}

	function copyPublic() {
		ignore_user_abort(true);
		$skipFolders = ['plugins', 'themes', 'admin', 'media'];
		rcopy($this->workDir . DS . 'public', DIR_PUBLIC, $skipFolders);

		return true;
	}

	function copyPublicAdmin() {
		ignore_user_abort(true);
		$skipFolders = ['plugins'];
		rcopy($this->workDir . DS . 'public' . DS . 'admin', DIR_PUBLIC . DS . 'admin', $skipFolders);

		return true;
	}

	function copyPublicMedia() {
		ignore_user_abort(true);
		$skipFolders = ['plugins', 'themes', 'admin'];
		rcopy($this->workDir . DS . 'public' . DS . 'media', DIR_PUBLIC . DS . 'media', $skipFolders);

		return true;
	}

	function clearCache() {
		return CacheManager::delete();
	}

	function cleanup() {
		rrmdir($this->workDir);

		if ($this->zipFile) {
			unlink($this->zipFile);
		}

		return true;
	}

	function install($zipFile) {
		$this->unzip($zipFile, $this->workDir);

		$success     = true;
		//$dest        = substr(DIR_ROOT,0, -1); //remove trailing slash
		rrmdir($this->workDir);
		//plugins and themes are updated individually
		$skipFolders = ['plugins', 'public' . DS . 'themes', 'storage'];

		rcopy($this->workDir, DIR_ROOT, $skipFolders);
		rrmdir($this->workDir);
		unlink($zipFile);
		CacheManager::delete();
	}

	function unzip($zipFile) {
		if (! is_dir($this->workDir)) {
			mkdir($this->workDir);
		}

		$result        = false;
		$this->zipFile = $zipFile;
		$zip           = new \ZipArchive();

		if ($zip->open($zipFile) === true) {
			$result = $zip->extractTo($this->workDir);
		}

		Event :: trigger(__CLASS__, __FUNCTION__, $zipFile, $result);

		return $result;
	}
}
