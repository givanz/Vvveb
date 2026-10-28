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

/**
 * SQLite database driver using the SQLite3 extension.
 *
 * Provides connection management, query execution with parameterised statements,
 * result fetching, escaping, and schema introspection for SQLite databases.
 */
class Sqlite extends DBDriver {
	/** @var ?\SQLite3 The shared database connection (singleton per process). */
	private static ?\SQLite3 $link = null;

	/** @var int Number of rows affected by the last query. */
	public int $affected_rows = 0;

	/** @var int|bool Number of rows returned by the last query, or 0 if not applicable. */
	public int|bool $num_rows = 0;

	/** @var ?int The row ID of the last inserted row, or null if none. */
	public ?int $insert_id = null;

	/** @var string Table name prefix. */
	public string $prefix = ''; //'vv_';

	/** @var string Identifier quote character for SQLite. */
	public string $quote  = '"';

	/**
	 * Get the SQLite library version number.
	 *
	 * @return int|false The version number (e.g. 3039000), or false if not connected.
	 */
	public static function version(): int|false {
		if (self :: $link) {
			return \SQLite3::version()['versionNumber'] ?? false;
		}

		return false;
	}

	/**
	 * Get the SQLite library version string.
	 *
	 * @return string|false The version string (e.g. '3.39.0'), or false if not connected.
	 */
	public static function info(): string|false {
		if (self :: $link) {
			return \SQLite3::version()['versionString'] ?? false;
		}

		return false;
	}

	/**
	 * Get the error message from the last SQLite operation.
	 *
	 * @return string The error message, or empty string if not connected.
	 */
	public function error(): string {
		if (self :: $link) {
			return self :: $link->lastErrorMsg();
		}

		return '';
	}

	/**
	 * Get the error code from the last SQLite operation.
	 *
	 * @return int The error code, or 0 if not connected.
	 */
	public function errorCode(): int {
		if (self :: $link) {
			return self :: $link->lastErrorCode();
		}

		return 0;
	}

	/**
	 * Pass through the statement result unchanged.
	 *
	 * @param mixed $stmt The statement result.
	 *
	 * @return mixed The same statement result.
	 */
	public function get_result(mixed $stmt): mixed {
		return $stmt;
	}

	public function __construct() {
		//return $this->connect($host, $dbname, $user, $pass, $port,  $prefix);
	}

	/**
	 * Connect to the SQLite database.
	 *
	 * Uses a singleton pattern — returns the existing connection if already open.
	 * Enables exceptions for error handling.
	 *
	 * @param string  $filename The SQLite database file path.
	 * @param string  $dbname   Database name (unused for SQLite).
	 * @param string  $user     Database user (unused for SQLite).
	 * @param string  $pass     Database password (unused for SQLite).
	 * @param ?string $port     Port (unused for SQLite).
	 * @param string  $prefix   Table name prefix.
	 *
	 * @throws \Exception On connection failure.
	 *
	 * @return \SQLite3 The database connection.
	 */
	public function connect(string $filename = DB_HOST, string $dbname = DB_NAME, string $user = DB_USER, string $pass = DB_PASS, ?string $port = null, string $prefix = DB_PREFIX): \SQLite3 {
		if (self :: $link) {
			return self :: $link;
		}

		$this->prefix = $prefix;

		try {
			self :: $link = new \SQLite3($filename); //, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		} catch (\Exception  $e) {
			$errorMessage = str_replace($pass, '*****', $e->getMessage());

			throw new \Exception($errorMessage, $e->getCode());
		}

		self :: $link->enableExceptions(true);

		return self :: $link;
	}

	/**
	 * Get all columns for a table used for sanitizing input.
	 *
	 * @param string $tableName The table name to introspect.
	 * @param bool   $comment   Whether to include column comments (unused for SQLite).
	 *
	 * @return array<string, array{name: string, d: mixed, n: string, t: string}>|false
	 *                                                                                  Associative array keyed by column name, or false on failure.
	 */
	public function getColumnsMeta(string $tableName, bool $comment = false): array|false {
		$sql =
		"SELECT type as t, name, dflt_value as d, `notnull` as n FROM pragma_table_info('$tableName');";

		if ($result = $this->query($sql)) {
			$columns = [];

			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				$columns[$row['name']] = $row;
			}

			// free result set
			$result->finalize();

			return $columns;
		}

		return false;
	}

	/**
	 * Get all table names in the database.
	 *
	 * @param string $db The database name (unused for SQLite — reads sqlite_master).
	 *
	 * @return array<int, string>|false List of table names, or false on failure.
	 */
	public function getTableNames(string $db = DB_NAME): array|false {
		$sql = "SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%'  AND name NOT LIKE '%_search%' ORDER BY name";

		if ($result = $this->query($sql)) {
			$names = [];

			while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
				//$names[] = $row;
				$names[] = $row['name'];
			}

			// free result set
			$result->finalize();

			return $names;
		}

		return false;
	}

	/**
	 * Escape a value for safe inclusion in a SQL query.
	 *
	 * Strings are escaped with SQLite3::escapeString. Null values become 'null'.
	 * All other types are returned as-is.
	 *
	 * @param mixed $string The value to escape.
	 *
	 * @return mixed The escaped value.
	 */
	public function escape(mixed $string): mixed {
		if (is_string($string)) {
			return self :: $link->escapeString($string);
		}

		if (is_null($string)) {
			return 'null';
		}

		return $string;
	}

	/**
	 * Escape a string value for use in a SQL literal.
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string The escaped string.
	 */
	public function escapeLiteral(string $string): string {
		return $this->escape($string);
	}

	/**
	 * Fetch a single scalar value from a result.
	 *
	 * @param \SQLite3Result $result The query result.
	 *
	 * @return mixed The first column value, or null if no row or the value is null.
	 */
	public function fetchOne(\SQLite3Result $result): mixed {
		return $result->fetchArray(SQLITE3_NUM)[0] ?? null;
	}

	/**
	 * Fetch a single row from a result.
	 *
	 * @param \SQLite3Result $result The query result.
	 *
	 * @return array<string, mixed>|false The row as an associative array<array-key, mixed>, or false if no results.
	 */
	public function fetchArray(\SQLite3Result $result): array|false {
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	/**
	 * Fetch all rows from a result.
	 *
	 * @param \SQLite3Result $result The query result.
	 *
	 * @return array<int, array<string, mixed>> All result rows.
	 */
	public function fetchAll(\SQLite3Result $result): array {
		$return = [];

		while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
			$return[] = $row;
		}

		return $return;
	}

	/**
	 * Execute a raw SQL query.
	 *
	 * Automatically connects if not connected. Updates affected_rows, insert_id,
	 * and num_rows on success.
	 *
	 * @param string $sql The SQL query to execute.
	 *
	 * @throws \Exception On query failure.
	 *
	 * @return \SQLite3Result|false The query result, or false on failure.
	 */
	public function query(string $sql): \SQLite3Result|false {
		if (!self :: $link) {
			$this->connect();
		}

		$result = false;

		try {
			$result = self :: $link->query($sql);

			if (LOG_SQL_QUERIES) {
				error_log($sql);
			}

			if ($result) {
				$this->affected_rows = self :: $link->changes();
				$this->insert_id     = self :: $link->lastInsertRowID();
				$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;
				//$result->finalize();
			} else {
				$message = $this->error() . "\n" . $sql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

				throw new \Exception($message, $this->errorCode());
			}
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n$sql\n" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

			throw new \Exception($message, $e->getCode());
		}

		return $result;
	}

	/**
	 * Execute multiple SQL statements by splitting on semicolons.
	 *
	 * @param string $sql The SQL statements to execute (semicolon-separated).
	 *
	 * @throws \Exception On any statement failure.
	 *
	 * @return array<int, \SQLite3Result> Array of results from each statement.
	 */
	public function multi_query(string $sql): array {
		$results = [];

		$queries = preg_split('/;\n/', $sql);

		foreach ($queries as $query) {
			if (empty(trim($query))) {
				continue;
			}

			if (LOG_SQL_QUERIES) {
				error_log($sql);
			}

			try {
				$result = self :: $link->query("$query;");

				if ($result) {
					$this->affected_rows = self :: $link->changes();
					$this->insert_id     = self :: $link->lastInsertRowID();
					$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;
					//$result->finalize();
				} else {
					$message = $this->error() . "\n" . $sql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

					throw new \Exception($message, $this->errorCode());
				}

				$results[] = $result;
			} catch (\Exception $e) {
				$message = $e->getMessage() . "\n$sql\n" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

				throw new \Exception($message, $e->getCode());
			}
		}

		return $results;
	}

	/**
	 * Close the database connection.
	 *
	 * @return bool|null True on success, null if not connected.
	 */
	public function close(): ?bool {
		if (self :: $link) {
			return self :: $link->close();
		}

		return null;
	}

	/**
	 * Prepare and execute a parameterised query.
	 *
	 * Fires an Event::trigger hook before execution. Converts named parameters
	 * to positional ? placeholders, binds values with appropriate SQLite3 type
	 * constants, and executes the statement. Updates affected_rows, insert_id,
	 * and num_rows on success.
	 *
	 * @param string                $sql        The SQL query with :named parameters.
	 * @param array<string, mixed>  $params     Named parameter values.
	 * @param array<string, string> $paramTypes Optional explicit type overrides.
	 *
	 * @throws \Exception On prepare or execute failure.
	 *
	 * @return \SQLite3Result|false|null The query result, false, or null on failure.
	 */
	public function execute(string $sql, array $params = [], array $paramTypes = []): \SQLite3Result|false|null {
		[$sql, $params] = Event::trigger(__CLASS__, __FUNCTION__, $sql, $params);
		//save orig sql for debugging info
		$origSql = $sql;

		if (!self :: $link) {
			$this->connect();
		}

		[$parameters, $types] = $this->paramsToQmark($sql, $params, $paramTypes);

		try {
			$stmt = self::$link->prepare($sql);
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n - " . $origSql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

			throw new \Exception($message, $e->getCode());
		}

		if ($stmt/* && ! empty($paramTypes)*/) {
			foreach ($parameters as $key => $value) {
				$bindType = match ($types[$key] ?? 's') {
					'i'     => SQLITE3_INTEGER,
					'd'     => SQLITE3_FLOAT,
					'b'     => SQLITE3_BLOB,
					default => SQLITE3_TEXT,
				};
				$index = (int)$key + 1;
				$stmt->bindValue($index, $value, $bindType);
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
			$result = null;

			try {
				if ($result = $stmt->execute()) {
					$this->affected_rows = self :: $link->changes();
					$this->insert_id     = self :: $link->lastInsertRowID();
					$this->num_rows      = $result->numColumns() && $result->columnType(0) != SQLITE3_NULL;

					return $result;
				} else {
					error_log(print_r($result, true));
					error_log($this->debugSql($sql, $params, $paramTypes));
				}
			} catch (\Exception $e) {
				$message = $e->getMessage() . "\n" . $origSql . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n" . print_r($parameters, true) . $types . "\n" . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

				throw new \Exception($message, $e->getCode());
			}
		} else {
			error_log(print_r($stmt, true));
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		return $stmt;
	}
}
