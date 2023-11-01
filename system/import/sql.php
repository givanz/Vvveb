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

namespace Vvveb\System\Import;

#[\AllowDynamicProperties]
class Sql {
	public $db;

	function __construct($driver = DB_ENGINE, $host = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS, $prefix = DB_PREFIX) {
		$this->sqlPath = DIR_ROOT . "install/sql/$driver/";
		$engine        = '\Vvveb\System\Db\\' . ucfirst($driver);

		$this->prefix = $prefix;

		try {
			$this->db = new $engine($host, $dbname, $user, $pass, $prefix);
		} catch (\Exception $e) {
			//unknown database, try to create
			if ($e->getCode() == 1049) {
				$this->db = new $engine($host, '', $user, $pass, $prefix);

				if ($driver !== 'sqlite') {
					$this->createDb($dbname);
				}
			} else {
				throw($e);
			}
		}
	}

	private $sqlPath = '';

	function setPath($path) {
		$this->sqlPath = $path;
	}

	function createDb($dbname) {
		if (! $this->db->query("CREATE DATABASE IF NOT EXISTS `$dbname`")) {
			throw new \Exception($this->db->error);
		}

		if (! $this->db->select_db($dbname)) {
			throw new \Exception($this->db->error);
		}
	}

	function prefixTable($query, $prefix) {
		$tableName = '';

		//$regexs[] = '/(SELECT.+FROM\s+`?)(\w+`? AS \w+|\w+`?)/ims';
		$regexs[] = '/(UPDATE\s+`?)(\w+`? AS \w+\s+SET|\w+`?\s+SET)/ims';
		$regexs[] = '/(INSERT\s+INTO\s+`?)(\w+`? AS \w+|\w+`?)/ims';
		//$regexs[] = '/(DELETE\s+FROM\s+`?)(\w+`? AS \w+|\w+`?)/ims';
		$regexs[] = '/(\s+JOIN\s+`?)(\w+ AS \w+|\w+`?)/ims';
		$regexs[] = '/(CREATE\s+TABLE\s+`?)(\w+ AS \w+|\w+`?)/ims';
		$regexs[] = '/(\s+IF\s+EXISTS\s+`?)(\w+ AS \w+|\w+`?)/ims';
		$regexs[] = '/(\s+FROM\s+`?)(\w+ AS \w+|\w+`?)/ims';

		foreach ($regexs as $regex) {
			$query = preg_replace_callback(
				$regex,
				function ($matches) use ($prefix) {
					return $matches[1] . $prefix . $matches[2];
				},
				$query
			);
		}

		return $query;
	}

	function multiQuery($sql, $filename = '') {
		if (! $sql) {
			return;
		}

		if ($this->prefix) {
			$sql = $this->prefixTable($sql, $this->prefix);
		}

		try {
			if (DB_ENGINE == 'mysqli' || DB_ENGINE == 'pgsql') {
				if (! ($stmt = $this->db->multi_query($sql))) {
					throw new \Exception($this->db->error() . "\n\n in $filename\n\n" . substr($sql, 0, 256));
				}
			} else {
				if (($stmt = $this->db->query($sql)) === false) {
					throw new \Exception($this->db->error() . "\n\n in $filename\n\n" . substr($sql, 0, 256));
				}
			}
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage() . "\n\n" . substr($sql, 0, 256) . "\n\n in $filename", $e->getCode());
		}

		if (DB_ENGINE == 'mysqli') {
			try {
				do {
					/* store first result set */
					if ($result = $this->db->store_result()) {
						/*
						while ($row = $result->fetchRow()) {
						}*/
						$result->free();
					}
				} while ($this->db->more_results() && $this->db->next_result());
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage() . "\n\n" . substr($sql, 0, 256) . "\n\n in `$filename`", $e->getCode());
			}
		} else {
			if (DB_ENGINE == 'sqlite') {
				$result = [];

				if ($stmt) {
					try {
						$num_rows = $stmt->numColumns() && $stmt->columnType(0) != SQLITE3_NULL;

						if ($num_rows) {
							while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
								$result[] = $row;
							}
							//$stmt->finalize();
						}
					} catch (\Exception $e) {
						throw new \Exception($e->getMessage() . "\n\n" . substr($sql, 0, 256) . "\n\n in `$filename`", $e->getCode());
					}
				}

				return $result;
			}
		}

		return true;
	}

	function createTables() {
		if (DB_ENGINE == 'sqlite') {
			//check if sql file has minimum version and if current version is supported
			//$currentVersion = \SQLite3::version()['versionString'] ?? '3.0.0';
			//$fts5Support = (version_compare($currentVersion,'3.9.0') >= 0);
			$fts5Support = true;
			$query       = 'DROP TABLE IF EXISTS fts5_module_available_test';
			$this->db->query($query , 'fts5 test module');

			try {
				$query = 'CREATE VIRTUAL TABLE fts5_module_available_test USING fts5(sender, title, body)';
				$this->db->query($query , 'fts5 test module');
				$fts5Support = ! $this->db->errorCode();
			} catch (\Exception $e) {
				$fts5Support = false;
			}

			$query = 'DROP TABLE IF EXISTS fts5_module_available_test';
			$this->db->query($query , 'fts5 test module');
		}

		foreach (glob($this->sqlPath . '{,**/}*.sql', GLOB_BRACE) as $filename) {
			$sql      = file_get_contents($filename);
			$filename = str_replace($this->sqlPath, '', $filename);

			if (DB_ENGINE == 'mysqli' || DB_ENGINE == 'pgsql') {
				$this->multiQuery($sql, $filename);
			} else {
				if (DB_ENGINE == 'sqlite') {
					//$this->multiQuery($sql, $filename);
					//sqlite has problems running multiple queries

					//older sqlite bellow 3.9.0 do not have fts5 full text search module use fts4 instead
					$fts = \Vvveb\pregMatch('/fts(\d+).sql$/', $filename, 1);

					if ($fts) {
						if ($fts5Support) {
							if ($fts == '4') {
								continue;
							}
						} else {
							if ($fts == '5') {
								continue;
							}
						}
					}
					$queries = preg_split('/;\s*[\n\r]/', $sql);

					foreach ($queries as $query) {
						$query = trim($query);

						if (empty($query) || substr_compare($query, '-- ', 0, 3) === 0) {
							continue;
						}
						$this->multiQuery($query . ';', $filename);
					}
				}
			}
		}
	}

	function insertEscape($sql) {
		//replace ` with database escape quote eg: "
		$quote = $this->db->quote;

		if ($quote == '`') {
			return $sql;
		}

		$sql = preg_replace_callback('/INSERT\s+INTO\s*`.+?`\s*(\(.+?\))?\s*VALUES/i', function ($m) use ($quote) {
			return str_replace('`', $quote, $m[0]);
		}, $sql);

		return $sql;
	}

	function insertData($filter = []) {
		foreach (glob($this->sqlPath . '{,**/}*.sql', GLOB_BRACE) as $filename) {
			$name = basename($filename);

			if ($filter && ! in_array($name, $filter)) {
				continue;
			}

			$sql = file_get_contents($filename);
			$sql = $this->insertEscape($sql);
			$this->multiQuery($sql,  str_replace($this->sqlPath, '', $filename));
		}
	}
}
