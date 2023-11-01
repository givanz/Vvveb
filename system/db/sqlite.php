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

namespace Vvveb\System\Db;

use Vvveb\System\Event;

class Sqlite extends DBDriver {
	private static $link = null;

	//public $error;

	private $stmt;

	public $affected_rows = 0;

	public $num_rows = 0;

	public $insert_id = null;

	public $prefix = ''; //'vv_';

	public $quote  = '`';

	public static function version() {
		if (self :: $link) {
			return mysqli_get_server_version(self :: $link);
		}
	}

	public static function info() {
		if (self :: $link) {
			return self :: $link->version();
		}
	}

	public function error() {
		if (self :: $link) {
			return self :: $link->lastErrorMsg() ?? '';
		}
	}

	public function errorCode() {
		if (self :: $link) {
			return self :: $link->lastErrorCode() ?? 0;
		}
	}

	public function get_result($stmt) {
		return $stmt;
		$result = new \SQLite3Stmt($stmt);

		return $result;
	}

	public function __construct($filename = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS,  $prefix = DB_PREFIX) {
		if (self :: $link) {
			return self :: $link;
		}

		try {
			self :: $link = new \SQLite3($filename); //, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		} catch (\Exception  $e) {
			$errorMessage = str_replace($pass,'*****', $e->getMessage());

			throw new \Exception($errorMessage, $e->getCode());
		}

		self :: $link->enableExceptions(true);

		return self :: $link;
	}

	/*
	 * Get all columns for a table used for sanitizing input
	 */
	function getColumnsMeta($tableName) {
		$sql =
		"SELECT type as t, name, dflt_value as d, `notnull` as n FROM pragma_table_info('$tableName');";

		if ($result = $this->query($sql)) {
			//$columns = $result->fetch_all(MYSQLI_ASSOC);
			$columns = [];

			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$columns[] = $row;
			}

			/* free result set */
			$result->finalize();

			return $columns;
		} else {
		}

		return false;
	}

	function getTableNames($db = DB_NAME) {
		$sql = "SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%'  AND name NOT LIKE '%_search%' ORDER BY name";

		if ($result = $this->query($sql)) {
			//$columns = $result->fetch_all(MYSQLI_ASSOC);
			$names = [];

			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				//$names[] = $row;
				$names[] = $row['name'];
			}

			/* free result set */
			$result->finalize();

			return $names;
		} else {
		}

		return false;
	}

	public function escape($string) {
		if (is_string($string)) {
			return self :: $link->escapeString($string);
		}

		if (is_null($string)) {
			return 'null';
		}

		return $string;
	}

	public function sqlLimit($start, $limit) {
		return "LIMIT $start, $limit";
	}

	public function fetchOne($result) {
		$return = false;

		if ($result) {
			$return = $result->fetchArray(SQLITE3_NUM)[0] ?? null;
		}

		return $return;
	}	
	
	public function fetchArray($result) {
		$return = false;

		if ($result) {
			$return = $result->fetchArray(SQLITE3_ASSOC);
		}

		return $return;
	}	
	
	public function fetchAll($result) {
		$return = [];

		if ($result) {
			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$return[] = $row;
			}
		}

		return $return;
	}

	public function query($sql) {
		$result = false;

		try {
			$result = self :: $link->query($sql);

			if ($result) {
				$this->affected_rows = self :: $link->changes();
				$this->insert_id     = self :: $link->lastInsertRowID();
				$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;
				//$result->finalize();
			} else {
				throw new \Exception($this->error(), $this->errorCode());
			}
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n$sql\n";

			throw new \Exception($message, $e->getCode());
		}

		return $result;
	}

	public function multi_query($sql) {
		$result = self :: $link->query($sql);

		if ($result) {
			$this->affected_rows = self :: $link->changes();
			$this->insert_id     = self :: $link->lastInsertRowID();
			$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;
		}

		return $result;
	}

	public function close() {
		return self :: $link->close();
	}

	// Prepare
	public function execute($sql, $params = [], $paramTypes = []) {
		list($sql, $params) = Event::trigger(__CLASS__,__FUNCTION__, $sql, $params);
		//save orig sql for debugging info
		$origSql = $sql;

		list($parameters, $types) = $this->paramsToQmark($sql, $params, $paramTypes);

		try {
			$stmt = self::$link->prepare($sql);
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n - " . $origSql;

			throw new \Exception($message, $e->getCode());
		}

		if ($stmt && ! empty($paramTypes)) {
			foreach ($parameters as $key => $value) {
				$type  = $types[$key] ?? 's';
				$type  = ($type == 'i') ? SQLITE3_INTEGER : SQLITE3_TEXT;
				$index = (int)$key + 1;
				$stmt->bindValue($index, $value, $type);
			}
		} else {
			if (DEBUG) {
				error_log((self :: $link->lastErrorMsg ?? '') . ' ' . $this->debugSql($origSql, $params, $paramTypes));
			}
		}

		if (LOG_SQL_QUERIES) {
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		if ($stmt) {
			try {
				if ($result = $stmt->execute()) {
					$this->affected_rows = self :: $link->changes();
					$this->insert_id     = self :: $link->lastInsertRowID();
					$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;

					return $result;
				} else {
					error_log(print_r($result, 1));
					error_log($this->debugSql($sql, $params, $paramTypes));
				}
			} catch (\Exception $e) {
				$message = $e->getMessage() . "\n" . $origSql . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n" . print_r($parameters, 1) . $types;

				throw new \Exception($message, $e->getCode());
			}
		} else {
			error_log(print_r($stmt, 1));
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		return $stmt;
	}

	// Bind
	public function bind($param, $value, $type = null) {
		$this->stmt->bindValue($param, $value, $type);
	}
}
