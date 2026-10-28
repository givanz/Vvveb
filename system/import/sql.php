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

use function Vvveb\globBrace;

#[\AllowDynamicProperties]
class Sql {
	/** @var \Vvveb\System\Db\DBDriver */
	private \Vvveb\System\Db\DBDriver $db;

	/**
	 * @var string
	 */
	private string $prefix;

	/**
	 * @var string Database driver name (mysqli, pgsql, sqlite).
	 */
	private string $driver = '';

	/**
	 * @var string
	 */
	private string $sqlPath = '';

	/**
	 * @param string $driver Database driver.
	 * @param string $dbname Database name.
	 * @param string $prefix Table name prefix.
	 */
	public function __construct(string $driver = DB_ENGINE, string $dbname = DB_NAME, string $prefix = DB_PREFIX) {
		$this->driver  = $driver;
		$this->sqlPath = DIR_ROOT . "install/sql/$driver/";
		$engine        = '\Vvveb\System\Db\\' . ucfirst($driver);

		$this->prefix = $prefix;

		try {
			$this->db = new $engine();
		} catch (\Exception $e) {
			//unknown database, try to create
			if ($e->getCode() == 1049) {
				$this->db = new $engine();

				if ($driver !== 'sqlite') {
					$this->createDb($dbname);
				}
			} else {
				throw $e;
			}
		}
	}

	/**
	 * @param string $path
	 *
	 * @return void
	 */
	public function setPath(string $path): void {
		$this->sqlPath = $path;
	}

	/**
	 * @param string $dbname
	 *
	 * @return void
	 */
	public function createDb(string $dbname): void {
		$q = $this->db->quote;

		if (!$this->db->query("CREATE DATABASE IF NOT EXISTS $q$dbname$q")) {
			throw new \Exception($this->db->error());
		}

		if (!$this->db->select_db($dbname)) {
			throw new \Exception($this->db->error());
		}
	}

	/**
	 * @param string $query
	 * @param string $prefix
	 *
	 * @return string
	 */
	public function prefixTable(string $query, string $prefix): string {
		$tableName = '';

		$regexes = [
			//'/(SELECT.+FROM\s+`?)(\w+`? AS \w+|\w+`?)/ims';
			'/(UPDATE\s+`?)(\w+`? AS \w+\s+SET|\w+`?\s+SET)/ims',
			'/(INSERT\s+INTO\s+`?)(\w+`? AS \w+|\w+`?)/ims',
			//'/(DELETE\s+FROM\s+`?)(\w+`? AS \w+|\w+`?)/ims';
			'/(\s+JOIN\s+`?)(\w+ AS \w+|\w+`?)/ims',
			'/(CREATE\s+TABLE\s+`?)(\w+ AS \w+|\w+`?)/ims',
			'/(\s+IF\s+EXISTS\s+`?)(\w+ AS \w+|\w+`?)/ims',
			'/(\s+FROM\s+`?)(\w+ AS \w+|\w+`?)/ims',
		];

		foreach ($regexes as $regex) {
			$query = preg_replace_callback(
				$regex,
				fn ($matches) => $matches[1] . $prefix . $matches[2],
				$query
			);
		}

		return $query;
	}

	/**
	 * @param string $sql
	 * @param string $filename
	 *
	 * @return bool|array<array-key, mixed>|null
	 */
	public function multiQuery(string $sql, string $filename = ''): bool|array|null {
		if (!$sql) {
			return null;
		}

		if ($this->prefix) {
			$sql = $this->prefixTable($sql, $this->prefix);
		}

		try {
			if ($this->driver == 'mysqli' || $this->driver == 'pgsql') {
				if (!($stmt = $this->db->multi_query($sql))) {
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

		if ($this->driver == 'mysqli') {
			try {
				do {
					// store first result set
					if ($result = $this->db->store_result()) {
						$result->free();
					}
				} while ($this->db->more_results() && $this->db->next_result());
			} catch (\Exception $e) {
				throw new \Exception($e->getMessage() . "\n\n" . substr($sql, 0, 256) . "\n\n in `$filename`", $e->getCode());
			}
		} else {
			if ($this->driver == 'sqlite') {
				$result = [];

				if ($stmt) {
					try {
						$num_rows = $stmt->numColumns() && $stmt->columnType(0) != SQLITE3_NULL;

						if ($num_rows) {
							while ($row = $stmt->fetchArray(SQLITE3_ASSOC)) {
								$result[] = $row;
							}
						}
					} catch (\Exception $e) {
						throw new \Exception($e->getMessage() . "\n\n" . substr($sql, 0, 256) . "\n\n in `$filename`", $e->getCode());
					}
				}

				$return = $this->db->insert_id ?: $this->db->affected_rows ?: $result;

				if (!$return && !$this->db->errorCode()) {
					$return = true;
				}

				return $return;
			}
		}

		return true;
	}

	/**
	 * @param array<array-key, mixed> $files
	 *
	 * @return void
	 */
	public function createTables(array $files = []): void {
		if ($this->driver == 'sqlite') {
			//try to speed up install
			$query       = 'pragma journal_mode = WAL;pragma synchronous = normal;pragma temp_store = memory;pragma mmap_size = 30000000000;PRAGMA writable_schema = 1;';
			$this->db->query($query, 'journal_mode WAL');

			//check if sql file has minimum version and if current version is supported
			//$currentVersion = \SQLite3::version()['versionString'] ?? '3.0.0';
			//$fts5Support = (version_compare($currentVersion,'3.9.0') >= 0);
			$fts5Support = true;

			$query       = 'DROP TABLE IF EXISTS fts5_module_available_test';
			$this->db->query($query, 'fts5 test module');

			try {
				$query = 'CREATE VIRTUAL TABLE fts5_module_available_test USING fts5(sender, title, body)';
				$this->db->query($query, 'fts5 test module');
				$fts5Support = !$this->db->errorCode();
			} catch (\Exception $e) {
				$fts5Support = false;
			}

			$query = 'DROP TABLE IF EXISTS fts5_module_available_test';
			$this->db->query($query, 'fts5 test module');
		}

		if (!$files) {
			$files = globBrace($this->sqlPath, ['', '**/', '*/*/'], '*.sql');
		}

		foreach ($files as $filename) {
			$sql      = file_get_contents($filename);
			$filename = str_replace($this->sqlPath, '', $filename);
			//remove comments
			$sql = preg_replace('/-- .+/', '', $sql);

			if ($this->driver == 'mysqli' || $this->driver == 'pgsql') {
				$this->multiQuery($sql, $filename);
			} else {
				if ($this->driver == 'sqlite') {
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

						if (empty($query) || strncmp($query, '-- ', 3) === 0) {
							continue;
						}
						$this->multiQuery($query . ';', $filename);
					}
				}
			}
		}
	}

	/**
	 * @param string $sql
	 *
	 * @return string
	 */
	public function insertEscape(string $sql): string {
		//replace ` with database escape quote eg: "
		$quote = $this->db->quote;

		if ($quote == '`') {
			return $sql;
		}

		return preg_replace_callback('/INSERT\s+INTO\s*`.+?`\s*(\(.+?\))?\s*VALUES/i', fn ($m) => str_replace('`', $quote, $m[0]), $sql);
	}

	/**
	 * Expand * wildcards and transform a filter list to a regex.
	 *
	 * @param array<array-key, mixed> $filters
	 *
	 * @return string Empty string when no filters are given.
	 */
	private function buildFilterRegex(array $filters): string {
		if (!$filters) {
			return '';
		}

		$parts = [];

		foreach ($filters as $filter) {
			$parts[] = str_replace('*', '.*', addslashes($filter));
		}

		return '/' . implode('|', $parts) . '/';
	}

	/**
	 * @param array<array-key, mixed> $include
	 * @param array<array-key, mixed> $exclude
	 * @param array<array-key, mixed> $template
	 * @param callable|null           $callback
	 *
	 * @return void
	 */
	public function insertData(array $include = [], array $exclude = [], array $template = [], ?callable $callback = null): void {
		$files = globBrace($this->sqlPath, ['', '**/', '*/*/'], '*.sql');

		//expand * and transform to regex
		$includeRegex = $this->buildFilterRegex($include);
		$excludeRegex = $this->buildFilterRegex($exclude);

		foreach ($files as $filename) {
			$name = basename($filename);

			if ($excludeRegex && preg_match($excludeRegex, $name)) {
				continue;
			}

			if ($includeRegex && !preg_match($includeRegex, $name)) {
				continue;
			}

			$sql = file_get_contents($filename);
			$sql = $this->insertEscape($sql);

			if ($callback) {
				$sql = $callback($sql);
			}

			$this->multiQuery($sql, str_replace($this->sqlPath, '', $filename));
		}
	}

	/**
	 * @param string $newSqlPath
	 * @param string $sqlPath
	 *
	 * @return void
	 */
	public function upgradeDb(string $newSqlPath, string $sqlPath): void {
		$db         = $this->db;
		$tableNames = $db->getTableNames();

		$files      = \Vvveb\globBrace($sqlPath, ['', '*/*/'], '*.sql');

		$q = $this->db->quote;

		//get table names from sql files
		foreach ($files as $filename) {
			$tableName   = basename($filename, '.sql');
			$newFilename = str_replace($sqlPath, $newSqlPath, $filename);

			if (file_exists($newFilename)) {
				$currentSql = file_get_contents($filename);
				$columns    = $this->tableColumns($currentSql);

				if (!$columns) {
					continue;
				}

				$newSql     = file_get_contents($newFilename);
				$newColumns = $this->tableColumns($newSql);

				if (!$newColumns) {
					continue;
				}

				//newly added columns
				$tableColumns = $db->getColumnsMeta($tableName);
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
						$deletedColumns[$name] = $column[$name];
					}
				}

				//add new columns
				if ($addedColumns) {
					//$tableColumns = $db->getColumnsMeta($tableName);

					foreach ($addedColumns as $name => $definition) {
						if (isset($tableColumns[$name])) {
							//column already exists skip
							continue;
						}

						$query  = "ALTER TABLE $q$tableName$q ADD $definition";
						$result = $db->query($query);
					}
				}

				//change columns
				if ($changedColumns) {
					if (!$tableColumns) {
						$tableColumns = $db->getColumnsMeta($tableName);
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
				if ($deletedColumns) {
					if (!$tableColumns) {
						$tableColumns = $db->getColumnsMeta($tableName);
					}

					foreach ($deletedColumns as $name => $definition) {
						if (!isset($tableColumns[$name])) {
							//column does not exist skip
							continue;
						}

						$query  = "ALTER TABLE $q$tableName$q DROP COLUMN $name";
						$result = $db->query($query);
					}
				}
			}
		}
	}
}
