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
use function Vvveb\pregMatch;
use function Vvveb\rcopy;
use function Vvveb\rrmdir;

define('CHMOD_DIR', (0755 & ~umask()));
define('CHMOD_FILE', (0644 & ~umask()));

class Update {
	/** @var string URL to check for updates. */
	protected string $url = 'https://www.vvveb.com/update.json';

	/** @var string Working directory for upgrade extraction. */
	protected string $workDir = DIR_STORAGE . 'upgrade';

	/** @var string|false Path to the downloaded zip file. */
	protected string|false $zipFile = false;

	/**
	 * Check for available updates.
	 *
	 * @param string $type  Update type ('core').
	 * @param bool   $force Force re-fetch, skipping cache.
	 *
	 * @return array<string, mixed> Update information with 'hasUpdate' and optional 'error' keys.
	 */
	public function checkUpdates(string $type = 'core', bool $force = false): array {
		if ($force) {
			//delete update cache
			$cacheKey    = md5($this->url);
			$cacheDriver = Cache :: getInstance();
			$cacheDriver->delete('url', $cacheKey);
		}

		$error  = '';
		$result = false;

		//cache results for one week
		try {
			$result = getUrl($this->url, true);
		} catch (\Exception $e) {
			if (DEBUG) {
				$error =  $e->getMessage();
			}
		}

		$info = ['hasUpdate' => false];

		if ($result) {
			$info = json_decode($result, true);

			if ($type == 'core') {
				$info['hasUpdate'] = max(version_compare($info['version'] ?? 0, V_VERSION), 0);
			}

			return $info;
		}

		if ($error) {
			$info['error'] = $error;
		}

		return $info;
	}

	/**
	 * Recursively check folder permissions and attempt to fix them.
	 *
	 * @param string $dir Directory to check.
	 *
	 * @return string|true True if all permissions are fine, or the first unwritable path.
	 */
	private function checkFolderPermissions(string $dir): string|true {
		$skip       = ['install', 'locale', 'vendor', 'plugins', 'config'];
		$unwritable = [];

		$handle = @opendir($dir);

		while (false !== ($file = readdir($handle))) {
			$full = $dir . DS . $file;

			if (($file != '.') &&
				($file != '..') && !in_array($file, $skip)) {
				if (is_dir($full)) {
					if (!is_writable($full)) {
						if (!@chmod($full, CHMOD_DIR)) {
							return $full;
						}
					}

					$result = $this->checkFolderPermissions($full);

					if ($result !== true) {
						return $result;
					}
				}
				if ((substr_compare($full, '.php', -4) === 0) && !is_writable($full)) {
					if (!@chmod($full, CHMOD_FILE)) {
						return $full;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Check if all files are writable, fixing permissions where possible.
	 *
	 * @return string|true True if OK, or the first unwritable path.
	 */
	public function checkPermissions(): string|true {
		return $this->checkFolderPermissions(DIR_ROOT);
	}

	/**
	 * Create a backup of the current installation.
	 *
	 * @return void
	 */
	public static function backup(): void {
		$skipFolders  = ['plugins', 'public', 'storage', 'install'];
		$backupFolder = DIR_BACKUP . 'update-' . V_VERSION . '-' . date('Y-m-d_H:i:s');

		rcopy(DIR_ROOT, $backupFolder, $skipFolders);
	}

	/**
	 * Download a file from a URL to a temporary location.
	 *
	 * @param string $url URL to download.
	 *
	 * @return string|false Path to the downloaded file, or false on failure.
	 */
	public static function download(string $url): string|false {
		$temp = @tempnam(sys_get_temp_dir(), 'vvveb_update');

		if (!$temp) {
			$temp = @tmpfile();
		}

		//if temp still fails use cache dir, some shared hosting have this issue
		if (!$temp) {
			$temp = DIR_CACHE . APP . '.update.' . md5($url) . '.zip';
		}

		if ($content = download($url)) {
			$f  = file_put_contents($temp, $content, LOCK_EX);
			if ($f) {
				return $temp;
			}
		}

		return false;
	}

	/**
	 * Set permissions on core folders and files.
	 *
	 * @return bool Always returns true.
	 */
	public function setPermissions(): bool {
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

	/**
	 * Copy files from one folder to another, skipping specified folders.
	 *
	 * @param string   $src         Source directory.
	 * @param string   $dest        Destination directory.
	 * @param string[] $skipFolders Folder names to skip.
	 *
	 * @return bool Result of rcopy.
	 */
	private function copyFolder(string $src, string $dest, array $skipFolders = []): bool {
		ignore_user_abort(true);

		if (!is_writable($dest)) {
			throw new \Exception(sprintf('Folder "%s" not writable!', $dest));
		}

		return rcopy($src, $dest, $skipFolders);
	}

	/**
	 * Move folders from working dir to root folder and move existing folders to backup.
	 *
	 * @return bool Result of rcopy.
	 */
	public function moveFolders(): bool {
		ignore_user_abort(true);

		$skipFolders  = ['plugins', 'public', 'storage', 'install', 'config', 'locale'];
		$backupFolder = DIR_BACKUP . 'update-' . V_VERSION . '-' . date('Y-m-d_H-i-s');

		if (!is_dir($backupFolder) && !@mkdir($backupFolder, 0755, true)) {
			throw new \Exception(sprintf('Could not create backup folder %s', $backupFolder));
		}

		$success = true;
		$files   = scandir($this->workDir, SCANDIR_SORT_NONE);
		foreach ($files as $file) {
			if ($file[0] == '.') {
				continue;
			}
			if (in_array($file, $skipFolders)) {
				continue;
			}

			$source = $this->workDir . DS . $file;
			$dest   = DIR_ROOT . DS . $file;
			$backup = $backupFolder . DS . $file;

			if (file_exists($dest) || is_link($dest)) {
				if (!@rename($dest, $backup)) {
					if (!@rename($source, $dest)) {
						throw new \Exception(sprintf('Could not move %s to %s', $source, $dest));
					}

					continue;
				}

				//backup succeeded: copy the new entry into place
				if (is_dir($source) && !is_link($source)) {
					$success = rcopy($source, $dest, []);
				} else {
					$success = copy($source, $dest);
				}

				if (!$success) {
					throw new \Exception(sprintf('Could not move %s to %s', $source, $dest));
				}
			} else {
				//new top-level entry introduced by this update
				if (is_dir($source) && !is_link($source)) {
					$success = rcopy($source, $dest, []);
				} else {
					$success = @rename($source, $dest) || copy($source, $dest);
				}

				if (!$success) {
					throw new \Exception(sprintf('Could not move %s to %s', $source, $dest));
				}
			}
		}

		return $success;
	}

	/**
	 * Copy admin files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyAdmin(): bool {
		return $this->copyFolder($this->workDir . DS . 'admin', DIR_ROOT . DS . 'admin');
	}

	/**
	 * Copy app files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyApp(): bool {
		return $this->copyFolder($this->workDir . DS . 'app', DIR_ROOT . DS . 'app');
	}

	/**
	 * Copy system files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copySystem(): bool {
		return $this->copyFolder($this->workDir . DS . 'system', DIR_ROOT . DS . 'system');
	}

	/**
	 * Copy install files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyInstall(): bool {
		return $this->copyFolder($this->workDir . DS . 'install', DIR_ROOT . DS . 'install');
	}

	/**
	 * Copy core files from the update package, skipping specific folders.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyCore(): bool {
		$skipFolders = ['plugins', 'public', 'storage', 'system', 'app', 'admin', 'install', 'config', 'vendor', 'env.php'];

		return $this->copyFolder($this->workDir, DIR_ROOT, $skipFolders);
	}

	/**
	 * Copy config files from the update package, skipping specific files.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyConfig(): bool {
		$skip = ['plugins.php', 'mail.php', 'sites.php', 'app.php', 'admin.php', 'rest.php', 'graphql.php'];

		return $this->copyFolder($this->workDir . DS . 'config', DIR_ROOT . DS . 'config', $skip);
	}

	/**
	 * Copy public files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyPublic(): bool {
		$skipFolders = ['plugins', 'themes', 'admin', 'media'];

		return $this->copyFolder($this->workDir . DS . 'public', DIR_PUBLIC, $skipFolders);
	}

	/**
	 * Copy public admin files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyPublicAdmin(): bool {
		ignore_user_abort(true);
		$skipFolders = ['demo'];

		return $this->copyFolder($this->workDir . DS . 'public' . DS . 'admin', DIR_PUBLIC . DS . 'admin', $skipFolders);
	}

	/**
	 * Copy public media files from the update package.
	 *
	 * @return bool Result of copyFolder.
	 */
	public function copyPublicMedia(): bool {
		$skipFolders = ['plugins', 'themes', 'admin'];

		return $this->copyFolder($this->workDir . DS . 'public' . DS . 'media', DIR_PUBLIC . DS . 'media', $skipFolders);
	}

	/**
	 * Create new database tables that exist in SQL files but not in the database.
	 *
	 * @return bool Always returns true.
	 */
	public function createNewTables(): bool {
		$db         = \Vvveb\System\Db::getInstance();
		$tableNames = $db->getTableNames();

		$driver  = DB_ENGINE;
		$sqlPath = DIR_ROOT . "install/sql/$driver/";
		$files   = \Vvveb\globBrace($sqlPath, ['', '*/*/'], '*.sql');

		$diff = [];
		//if the number of tables is less than in the install dir
		if (count($tableNames) < count($files)) {
			$tableSql = [];
			//get table names from sql files
			foreach ($files as $filename) {
				$tableSql[] = basename($filename, '.sql');
			}
			//get the names of missing tables
			$diff = array_diff($tableSql, $tableNames);
		}

		$sqlFiles = [];
		//get files for missing tables
		foreach ($diff as $key => $tableName) {
			$sqlFiles[] = $files[$key];
		}

		//create missing tables
		if ($sqlFiles) {
			$sqlImport = new \Vvveb\System\Import\Sql();
			$sqlImport->createTables($sqlFiles);
		}

		return true;
	}

	/**
	 * Parse CREATE TABLE SQL to extract table name and column definitions.
	 *
	 * @param string $sql CREATE TABLE SQL statement.
	 *
	 * @return array{0: string, 1: array<string, string>} Table name and column definitions.
	 */
	public function tableColumns(string $sql): array {
		$cols      = [];
		$tableName = '';

		if (preg_match('/CREATE TABLE ([^\s]+?)\s*\((.+)\).*;/ms', $sql, $matches)) {
			$tableName = trim($matches[1], ' "\'`');
			$columns   = preg_split('/\r\n|\r|\n/', trim($matches[2]));

			foreach ($columns as $key => &$column) {
				$column = preg_replace('/-- .+$/', '', $column); //remove comments
				$column = trim($column, ' ,');

				if ($column && in_array($column[0], ['`', '"', '\''])) {
					$colName        = pregMatch('/^[\'`"](.+?)[\'`"]/', $column, 1);
					$cols[$colName] = trim($column, ',');
				}
			}

			ksort($cols);
		}

		return [$tableName, $cols];
	}

	/**
	 * Add, modify, or drop database columns based on SQL file changes.
	 *
	 * @return bool Always returns true.
	 */
	public function addNewColumns(): bool {
		$db         = \Vvveb\System\Db::getInstance();
		$tableNames = $db->getTableNames();
		$q          = $db->quote;

		$driver     = DB_ENGINE;
		$sqlPath    = DIR_ROOT . "install/sql/$driver/";
		$newSqlPath = $this->workDir . DS . "install/sql/$driver/";
		$files      = \Vvveb\globBrace($sqlPath, ['', '*/*/'], '*.sql');

		$diff     = [];
		$tableSql = [];

		//get table names from sql files
		foreach ($files as $filename) {
			$tableName   = basename($filename, '.sql');
			$newFilename = str_replace($sqlPath, $newSqlPath, $filename);

			if (file_exists($newFilename)) {
				$currentSql   = file_get_contents($filename);
				$currentTable = $this->tableColumns($currentSql);
				$tableName    = $currentTable[0];
				$columns      = $currentTable[1];

				if (!$columns) {
					continue;
				}

				$newSql       = file_get_contents($newFilename);
				$newColumns   = $this->tableColumns($newSql)[1];

				if (!$newColumns) {
					continue;
				}

				//newly added columns
				$tableColumns = $db->getColumnsMeta($tableName);
				//if table does not exist yet skip, it will be created
				if (!$tableColumns) {
					continue;
				}

				$addedColumns = array_diff_key($newColumns, $tableColumns);

				//changed columns
				$changedColumns = [];
				$deletedColumns = [];

				foreach ($columns as $name => $column) {
					if (isset($newColumns[$name])) {
						if ($column != $newColumns[$name]) {
							$changedColumns[$name] = $newColumns[$name];
						}
					} else {
						$deletedColumns[$name] = $column;
					}
				}

				//check deleted columns agains existing table
				$deletedColumns += array_diff_key($tableColumns, $newColumns);
				//add new columns
				if ($addedColumns) {
					if (!$db) {
						//don't connect to db unless we need to alter table
						$db = Db::getInstance();
					}

					foreach ($addedColumns as $name => $definition) {
						if (isset($tableColumns[$name])) {
							//column already exists skip
							continue;
						}
						$definition = $newColumns[$name];

						$query  = "ALTER TABLE $q$tableName$q ADD $definition";
						$result = $db->query($query);
					}
				}

				//change columns
				if ($changedColumns) {
					if (!$db) {
						//don't connect to db unless we need to alter table
						$db = Db::getInstance();
					}

					foreach ($changedColumns as $name => $definition) {
						if (!isset($tableColumns[$name])) {
							//column does not exist skip
							continue;
						}

						$query  = "ALTER TABLE $q$tableName$q MODIFY COLUMN $definition";
						$result = $db->query($query);
					}
				}

				//delete columns
				//disabled to avoid deleting user added columns
				if (false/*$deletedColumns*/) {
					if (!$db) {
						//don't connect to db unless we need to alter table
						$db = Db::getInstance();
					}

					foreach ($deletedColumns as $name => $definition) {
						if (!isset($tableColumns[$name])) {
							//column does not exist skip
							continue;
						}

						$query  = "ALTER TABLE $tableName DROP COLUMN $name";
						$result = $db->query($query);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Clear all caches.
	 *
	 * @return bool Result of CacheManager::delete.
	 */
	public function clearCache(): bool {
		return CacheManager::delete();
	}

	/**
	 * Clean up temporary update files.
	 *
	 * @return bool Always returns true.
	 */
	public function cleanup(): bool {
		rrmdir($this->workDir);

		if ($this->zipFile) {
			unlink($this->zipFile);
		}

		return true;
	}

	/**
	 * Install an update from a zip file.
	 *
	 * @param string $zipFile Path to the update zip file.
	 *
	 * @return void
	 */
	public function install(string $zipFile): void {
		if (!$this->unzip($zipFile)) {
			return;
		}

		//plugins and themes are updated individually
		$skipFolders = ['plugins', 'public' . DS . 'themes', 'storage', 'vendor'];

		rcopy($this->workDir, DIR_ROOT, $skipFolders);

		//remove the extracted package and the downloaded archive
		$this->cleanup();
		CacheManager::delete();
	}

	/**
	 * Extract a zip file to the work directory.
	 *
	 * @param string $zipFile Path to the zip file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function unzip(string $zipFile): bool {
		if (!is_file($zipFile)) {
			return false;
		}

		//start from a clean work dir so stale files/symlinks cannot be reused
		if (is_dir($this->workDir) || is_link($this->workDir)) {
			rrmdir($this->workDir);
		}

		if (!is_dir($this->workDir) && !@mkdir($this->workDir, 0755, true)) {
			return false;
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
