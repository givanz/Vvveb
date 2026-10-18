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

namespace Vvveb\Controller\Tools;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\formatBytes;
use function Vvveb\pregMatch;
use function Vvveb\sanitizeFileName;
use Vvveb\System\Db;
use function Vvveb\url;

class Backup extends Base {
	/**
	 * Backup folder path
	 *
	 * @var string
	 */
	private string $dirBackup = '';

	/**
	 * Database instance.
	 *
	 * @var Db
	 */
	private object $db;

	/**
	 * Initialize the database connection.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db        = Db::getInstance();
		$this->dirBackup = DIR_BACKUP . DS . 'db' . DS;
	}

	/**
	 * Get all database table names.
	 *
	 * @return list<string>
	 */
	public function getTableNames(): array {
		return $this->db->getTableNames();
	}

	/**
	 * Convert TRUNCATE TABLE SQL syntax for SQLite compatibility.
	 *
	 * @param string $sql The original TRUNCATE TABLE SQL statement.
	 *
	 * @return string The converted SQL statement.
	 */
	public function truncateTableSQL(string $sql): string {
		if (DB_ENGINE == 'sqlite') {
			$tableName = pregMatch('/TRUNCATE TABLE [`"\']?([^`"\';]+)[`"\']?;?/ms', $sql, 1);

			$sql = preg_replace(
				'/TRUNCATE TABLE [`"\']?([^`"\';]+)[`"\']?;?/ms',
				"DELETE FROM '\\1';\nDELETE FROM SQLITE_SEQUENCE WHERE name='\\1';",
				$sql
			);

			if ($tableName == 'post_content') {
				//$sql .= ';DELETE FROM \'post_content_search\';DELETE FROM SQLITE_SEQUENCE WHERE name=\'post_content_search\';';
				$sql = trim($sql);
				$sql .= "\nDELETE FROM 'post_content_search';\n";
			}

			if ($tableName == 'product_content') {
				//$sql .= ';DELETE FROM \'product_content_search\';DELETE FROM SQLITE_SEQUENCE WHERE name=\'product_content_search\';';
				$sql = trim($sql);
				$sql .= "\nDELETE FROM 'product_content_search';\n";
			}

			return $sql;
		} else {
			if (DB_ENGINE == 'pgsql') {
				$sql = preg_replace(
					'/TRUNCATE TABLE [`"\']?([^`"\';]+)[`"\']?;?/ms',
					'TRUNCATE TABLE "\1";',
					$sql
				);
			}
		}

		return $sql;
	}

	/**
	 * Convert INSERT INTO SQL syntax for PostgreSQL compatibility.
	 *
	 * @param string $sql The original INSERT INTO SQL statement.
	 *
	 * @return string The converted SQL statement.
	 */
	public function insertSQL(string $sql): string {
		if (DB_ENGINE == 'pgsql') {
			$sql = preg_replace_callback(
				'/INSERT INTO .+?\) VALUES \(/ms',
				fn ($matches) => str_replace('`', '"', $matches[0]),
				$sql
			);
		}

		return $sql;
	}

	/**
	 * Dump table data as INSERT statements with pagination.
	 *
	 * @param string $tableName The database table name to dump.
	 * @param int    $page      The page number for pagination.
	 * @param int    $limit     The number of rows per page.
	 *
	 * @return string The SQL dump output.
	 */
	public function getTableDump(string $tableName, int $page = 1, int $limit = 1000): string {
		$output = '';

		if ($page == 1) {
			$output  = "TRUNCATE TABLE `$tableName`;\n\n";
		}

		//don't dump sqlite virtual tables used for full text search
		if (substr($tableName, -7) == '_search') {
			return $output;
		}

		$start  = (($page - 1) * $limit);
		$sql    = "SELECT * FROM `$tableName` LIMIT $start, $limit";
		$stmt   = $this->db->execute($sql, [], []);
		$rows   = $this->db->fetchAll($stmt);

		if ($rows) {
			$columns = $this->db->getColumnsMeta($tableName);
			array_walk($columns, fn (&$v) => $v = $v['name']);

			//$output .= "INSERT INTO `$tableName` ";
			//$output .= '(`' . implode('`,`', $columns) . "`) VALUES \n";

			$len = count($rows);
			$i 	 = 0;

			foreach ($rows as $row) {
				array_walk($row, function (&$v) {
					if (is_null($v)) {
						$v = 'null';
					} else {
						if (is_numeric($v)) {
						} else {
							if (is_string($v)) {
								$v = '\'' . $this->db->escape($v) . '\'';
							}
						}
					}
				});

				$output .= "INSERT INTO `$tableName` ";
				$output .= '(`' . implode('`,`', $columns) . '`) VALUES ';
				$output .= '(' . implode(',', $row) . ')';

				if (++$i < $len) {
					//$output .= ",\n";
					$output .= ";\n";
				} else {
					$output .= ";\n";
				}
			}

			$output .= "\n";
		}

		return $output;
	}

	/**
	 * Process the next batch of tables for backup, with time-limited execution.
	 *
	 * @return void
	 */
	public function nextBackup(): void {
		$page      = (int)($this->request->get['page'] ?? 1);
		$table     = $this->request->get['table'] ?? false;
		$position  = (int)($this->request->get['position'] ?? 1);
		$file      = sanitizeFileName($this->request->get['file'] ?? date('Y-m-d H:i:s'));
		$tables    = $this->getTableNames();
		$filename  =  $this->dirBackup . $file . '.sql';

		$count    = count($tables);
		$position = (int)array_search($table, $tables);

		if ($table !== false && !in_array($table, $tables, true)) {
			$this->notFound('Invalid table!', 403);
		}

		$start     = microtime(true);
		$elapsed   = 0;
		$tableName = 0;

		$handle = fopen($filename, 'a');
		$rows   = '';
		//if takes longer than 8 seconds start a new process to avoid php timeout
		while ($elapsed < 8 && ($position < $count)) {
			$tableName = $tables[$position];

			$output = $this->getTableDump($tableName, $page++);

			fwrite($handle, $output);

			$end     = microtime(true);
			$elapsed = $end - $start;
			//usleep(100000);
			if (!$output) {
				$page = 1;
				$position++;
			}
		}

		fclose($handle);

		$tableName = $tables[$position] ?? false;

		if ($tableName) {
			$url = ['module' => 'tools/backup', 'action' => 'nextBackup', 'file' => $file, 'table' => $tableName, 'page' => $page, 'position' => $position, 'count' => $count];
		} else {
			$message = __('Backup finished!');
			$url     = ['module' => 'tools/backup', 'success' => $message];
		}

		if ($this->request->isAjax()) {
			exit(json_encode($url + ['url' => url($url), 'page' => $page, 'position' => $position, 'count' => $count]));
		} else {
			$this->redirect($url);

			$this->index();
		}
	}

	/**
	 * Process the next batch of SQL statements for restore, with time-limited execution.
	 *
	 * @return void
	 */
	public function nextRestore(): void {
		$position  = $this->request->get['position'] ?? 0;
		$file      = sanitizeFileName($this->request->get['file'] ?? '');
		$filename  =  $this->dirBackup . $file;
		$size 	    = filesize($filename);

		$time      = microtime(true);
		$elapsed   = 0;
		$i         = 0;

		$handle = fopen($filename, 'r');
		fseek($handle, $position, SEEK_SET);
		$start    = false;
		$isInsert = false;
		$sql      = '';

		while (!feof($handle) && ($i < 10000 || $start) && ($elapsed < 8 || $start)) {
			$line = fgets($handle, 1000000);

			if (substr($line, 0, 14) == 'TRUNCATE TABLE') {
				$sql      = '';
				$line     = $this->truncateTableSQL($line);
				$start    = true;
				$isInsert = false;
			}

			if (substr($line, 0, 11) == 'INSERT INTO') {
				$sql      = '';
				$line     = $this->insertSQL($line);
				$isInsert = true;
				$start    = true;
			}

			if ($start) {
				$sql .= $line;
			}

			$end = substr($line, -2);

			if ($start && $end == ";\n") {
				//$sql = substr($sql, 0, strlen($sql) - 2);
				if ($isInsert) {
					$current = ftell($handle);

					while (($peek = fgets($handle, 8)) && empty(trim($peek)));

					if (feof($handle)) {
						$isInsert = false;
						$peek     = '';
					}

					fseek($handle, $current);
					$next = substr($peek, 0, 6);

					if ($next == 'TRUNCA' || $next == 'INSERT') {
						$isInsert = false;
					}
				}

				if (!$isInsert) {
					$this->db->query($sql);
					$sql   = '';
					$start = false;
				}
			}

			$i++;
			$timeEnd     = microtime(true);
			$elapsed     = $timeEnd - $time;
		}

		$position = ftell($handle);

		$inProgress = $position && !feof($handle);
		fclose($handle);

		if ($inProgress) {
			$url = ['module' => 'tools/backup', 'action' => 'nextRestore', 'file' => $file,  'table' => formatBytes($position) . ' - ' . formatBytes($size), 'position' => $position, 'count' => $size];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);

				exit('Processing');
			}
		} else {
			$message = __('Restore finished!');
			$url     = ['module' => 'tools/backup', 'success' => $message, 'table' => $message, 'position' => $position, 'count' => $size];

			if ($this->request->isAjax()) {
				exit(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);
				$this->view->info[] = $message;
				$this->index();
			}
		}
	}

	/**
	 * Delete a backup file.
	 *
	 * @return void
	 */
	public function delete(): void {
		$file = sanitizeFileName($this->request->post['file'] ?? '');

		if ($file) {
			$file = $this->dirBackup . $file;

			if (file_exists($file)) {
				if (unlink($file)) {
					$this->view->success[] = __('Backup deleted!');
				} else {
					$this->view->errors[] = __('Error deleting backup!');
				}
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		}

		$this->index();
	}

	/**
	 * Initiate a database restore from a backup file.
	 *
	 * @return void
	 */
	public function restore(): void {
		$file = sanitizeFileName($this->request->post['file'] ?? '');
		$url  = ['module' => 'tools/backup', 'action' => 'nextRestore', 'file' => $file];

		if ($file) {
			if (file_exists($this->dirBackup . $file)) {
				if ($this->request->isAjax()) {
					exit(json_encode($url + ['url' => url($url)]));
				} else {
					$this->redirect($url);

					$this->index();
				}
			} else {
				$error = __('Backup does not exist!');

				if ($this->request->isAjax()) {
					exit(json_encode($url + ['error' => $error]));
				} else {
					$this->view->errors[] = $error;

					$this->index();
				}
			}
		}
	}

	/**
	 * Download a backup file as a plain text attachment.
	 *
	 * @return void
	 */
	public function download(): void {
		$filename = sanitizeFileName($this->request->post['file'] ?? '');

		if ($filename) {
			$file = $this->dirBackup . $filename;

			if (file_exists($file)) {
				$fp = fopen($file, 'r');

				header('Content-Type: text/plain');
				header('Content-Length: ' . filesize($file));
				header('Content-Disposition: attachment; filename="' . $filename . '"');

				fpassthru($fp);

				exit();
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		}

		$this->index();
	}

	/**
	 * Initiate the backup process.
	 *
	 * @return void
	 */
	public function save(): void {
		$url = ['module' => 'tools/backup', 'action' => 'nextBackup'];

		if ($this->request->isAjax()) {
			exit(json_encode($url + ['url' => url($url)]));
		} else {
			$this->redirect($url);

			$this->index();
		}
	}

	/**
	 * Display the backup management page with existing backup files.
	 *
	 * @return void
	 */
	public function index(): void {
		$view        = $this->view;
		$backupFiles = glob($this->dirBackup . '*');

		$tableNames = $this->db->getTableNames();

		foreach ($backupFiles as $index => $file) {
			$name      = basename($file);
			$size      = filesize($file);
			$backups[] = [
				'name'         => $name,
				'key'          => $index,
				'file'         => $file,
				'size_bytes'   => $size,
				'size'         => formatBytes($size),
				'created_at'   => date('Y/m/d H:i:s', filemtime($file)),
				'restore-url'  => url(['module' => 'tools/backup', 'action' => 'restore']),
				'download-url' => url(['module' => 'tools/backup', 'action' => 'download']),
				'delete-url'   => url(['module' => 'tools/backup', 'action' => 'delete']),
			];
		}

		$view->backupUrl = url(['module' => 'tools/backup', 'action' => 'save']);
		$view->backups   = $backups ?? [];
	}
}
