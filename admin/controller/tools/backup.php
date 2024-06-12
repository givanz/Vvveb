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
	private $tables = [];

	private $position = 0;

	private $db;

	function __construct() {
		$this->db = Db::getInstance();
	}

	public function getTableNames() {
		return $this->db->getTableNames();
	}

	public function truncateTableSQL($sql) {
		if (DB_ENGINE == 'sqlite') {
			$tableName = pregMatch('/TRUNCATE TABLE [`"\']?([^`"\';]+)[`"\']?;?/ms', $sql, 1);

			$sql = preg_replace('/TRUNCATE TABLE [`"\']?([^`"\';]+)[`"\']?;?/ms',
			'DELETE FROM \'\1\';DELETE FROM SQLITE_SEQUENCE WHERE name=\'\1\';',
			$sql);

			if ($tableName == 'post_content') {
				$sql = 'DELETE FROM \'post_content_search\';DELETE FROM SQLITE_SEQUENCE WHERE name=\'post_content_search\';' . $sql;
			}

			if ($tableName == 'product_content') {
				$sql = 'DELETE FROM \'product_content_search\';DELETE FROM SQLITE_SEQUENCE WHERE name=\'product_content_search\';' . $sql;
			}

			return $sql;
		}

		return $sql;
	}

	function getTableDump($tableName, $page = 1, $limit = 1000) {
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
								$v = '"' . $this->db->escape($v) . '"';
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

	function nextBackup() {
		$page      = $this->request->get['page'] ?? 1;
		$table     = $this->request->get['table'] ?? false;
		$position  = $this->request->get['position'] ?? 1;
		$file      = $this->request->get['file'] ?? date('Y-m-d H:i:s');
		$tables    = $this->getTableNames();
		$filename  =  DIR_BACKUP . $file . '.sql';

		$count    = count($tables);
		$position = (int)array_search($table, $tables);

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
			if (! $output) {
				$page = 1;
				$position++;
			}
		}

		fclose($handle);

		$tableName = $tables[$position] ?? false;

		if ($tableName) {
			$url = ['module'=>'tools/backup', 'action' => 'nextBackup', 'file' => $file, 'table' => $tableName, 'page' => $page, 'position' => $position, 'count' => $count];
		} else {
			$message = __('Backup finished!');
			$url     = ['module'=>'tools/backup', 'success' => $message];
		}

		if ($this->request->isAjax()) {
			die(json_encode($url + ['url' => url($url), 'page' => $page, 'position' => $position, 'count' => $count]));
		} else {
			$this->redirect($url);

			return $this->index();
		}
	}

	function nextRestore() {
		$page      = $this->request->get['page'] ?? 1;
		$position  = $this->request->get['position'] ?? 0;
		$file      = $this->request->get['file'];
		$filename  =  DIR_BACKUP . $file;
		$size 	    = filesize($filename);

		$time      = microtime(true);
		$elapsed   = 0;
		$i         = 0;

		$handle = fopen($filename, 'r');
		fseek($handle, $position, SEEK_SET);
		$start = false;

		while (! feof($handle) && ($i < 100000) && $elapsed < 8) {
			$line = fgets($handle, 1000000);

			if (substr($line, 0, 14) == 'TRUNCATE TABLE') {
				$sql   = '';
				$line  = $this->truncateTableSQL($line);
				$start = true;
			}

			if (substr($line, 0, 11) == 'INSERT INTO') {
				$sql   = '';
				$start = true;
			}

			if ($start) {
				$sql .= $line;
			}

			$end = substr($line, -2);

			if ($start && $end == ";\n") {
				$sql = substr($sql, 0, strlen($sql) - 2);
				$this->db->query($sql);

				$start = false;
			}

			$i++;
			$timeEnd     = microtime(true);
			$elapsed     = $timeEnd - $time;
		}

		$position = ftell($handle);

		$inProgress = $position && ! feof($handle);
		fclose($handle);

		if ($inProgress) {
			$url = ['module'=>'tools/backup', 'action' => 'nextRestore', 'file' => $file,  'table' => formatBytes($position) . ' - ' . formatBytes($size), 'position' => $position, 'count' => $size];

			if ($this->request->isAjax()) {
				die(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);

				die('Processing');
			}
		} else {
			$message = __('Restore finished!');
			$url     = ['module'=>'tools/backup', 'success' => $message, 'table' => $message, 'position' => $position, 'count' => $size];

			if ($this->request->isAjax()) {
				die(json_encode($url + ['url' => url($url)]));
			} else {
				$this->redirect($url);
				$this->view->info[] = $message;
				$this->index();
			}
		}
	}

	function delete() {
		$file = sanitizeFileName($this->request->get['file'] ?? '');

		if ($file) {
			$file = DIR_BACKUP . $file;

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

	function restore() {
		$file = sanitizeFileName($this->request->get['file'] ?? '');
		$url  = ['module'=>'tools/backup', 'action' => 'nextRestore', 'file' => $file];

		if ($file) {
			if (file_exists(DIR_BACKUP . $file)) {
				if ($this->request->isAjax()) {
					die(json_encode($url + ['url' => url($url)]));
				} else {
					$this->redirect($url);

					return $this->index();
				}
			} else {
				$error = __('Backup does not exist!');

				if ($this->request->isAjax()) {
					die(json_encode($url + ['error' => $error]));
				} else {
					$this->view->errors[] = $error;

					return $this->index();
				}
			}
		}
	}

	function download() {
		$filename = sanitizeFileName($this->request->get['file'] ?? '');

		if ($filename) {
			$file = DIR_BACKUP . $filename;

			if (file_exists($file)) {
				$fp = fopen($file, 'rb');

				header('Content-Type: text/plain');
				header('Content-Length: ' . filesize($file));
				header('Content-Disposition: attachment; filename="' . $filename . '"');

				fpassthru($fp);
			} else {
				$this->view->errors[] = __('Backup does not exist!');
			}
		}

		return $this->index();
	}

	function save() {
		$url = ['module'=>'tools/backup', 'action' => 'nextBackup'];

		if ($this->request->isAjax()) {
			die(json_encode($url + ['url' => url($url)]));
		} else {
			$this->redirect($url);

			return $this->index();
		}
	}

	function index() {
		$view        = $this->view;
		$backupFiles = glob(DIR_BACKUP . '*');

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
				'restore-url'  => url(['module'=>'tools/backup', 'action' => 'restore', 'file' => $name]),
				'download-url' => url(['module'=>'tools/backup', 'action' => 'download', 'file' => $name]),
				'delete-url'   => url(['module'=>'tools/backup', 'action' => 'delete', 'file' => $name]),
			];
		}

		$view->backups = $backups ?? [];
	}
}
