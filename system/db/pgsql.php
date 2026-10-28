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
 * PostgreSQL database driver using the pgsql extension.
 *
 * Provides connection management, query execution with parameterised queries,
 * result fetching, escaping, and schema introspection for PostgreSQL.
 */
class Pgsql extends DBDriver {
	/** @var ?\PgSql\Connection The shared database connection (singleton per process). */
	private static ?\PgSql\Connection $link = null;

	/** @var mixed The last query result resource. */
	private mixed $last_res;

	/** @var int Number of rows affected by the last query. */
	public int $affected_rows = 0;

	/** @var int Number of rows returned by the last query. */
	public int $num_rows = 0;

	/** @var ?int The OID of the last inserted row, or null if none. */
	public ?int $insert_id = null;

	/** @var bool Whether to use persistent connections. */
	private static bool $persistent = false;

	/** @var string Table name prefix. */
	public string $prefix = ''; //'vv_';

	/** @var string Identifier quote character for PostgreSQL. */
	public string $quote  = '"';

	/**
	 * Get the PostgreSQL client and server version information.
	 *
	 * @return array{client: string, server: string}|null Version info, or null if not connected.
	 */
	public static function version(): ?array {
		if (self :: $link) {
			return pg_version(self :: $link);
		}

		return null;
	}

	/**
	 * Get the PostgreSQL client and server version information.
	 *
	 * @return array{client: string, server: string}|null Version info, or null if not connected.
	 */
	public static function info(): ?array {
		if (self :: $link) {
			return pg_version(self :: $link);
		}

		return null;
	}

	/**
	 * Get the error message from the last PostgreSQL operation.
	 *
	 * @return string The error message, or empty string if not connected.
	 */
	public function error(): string {
		if (self :: $link) {
			return pg_last_error(self :: $link);
		}

		return '';
	}

	/**
	 * Get the error code from the last PostgreSQL operation.
	 *
	 * @return int|string The error code, or 0 if not connected.
	 */
	public function errorCode(): int|string {
		if (self :: $link) {
			return pg_last_error(self :: $link);
		}

		return 0;
	}

	/**
	 * Pass through the statement result unchanged (no transformation needed for pgsql).
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
	 * Connect to the PostgreSQL database.
	 *
	 * Uses a singleton pattern — returns the existing connection if already open.
	 * Supports both regular and persistent connections.
	 *
	 * @param string $host   Database host.
	 * @param string $dbname Database name.
	 * @param string $user   Database user.
	 * @param string $pass   Database password.
	 * @param string $port   Database port (5432 direct, 6432 for pgbouncer).
	 * @param string $prefix Table name prefix.
	 *
	 * @return ?\PgSql\Connection The database connection, or null on failure.
	 */
	public function connect(string $host = DB_HOST, string $dbname = DB_NAME, string $user = DB_USER, string $pass = DB_PASS, string $port = DB_PORT, string $prefix = DB_PREFIX): ?\PgSql\Connection {
		if (!self :: $link) {
			$this->prefix = $prefix;
			//port 5432 for direct pgsql connection 6432 for pgbouncer
			$port           = $port ?: 5432;
			$connect_string = "host=$host port=$port dbname=$dbname  user=$user password=$pass";

			if (self :: $persistent) {
				$link = pg_pconnect($connect_string);
			} else {
				$link = pg_connect($connect_string);
			}

			if (!$link) {
				throw new \Exception('PostgreSQL connection failed');
			}

			self :: $link = $link;
		}

		//sync database time
		//self :: $link->execute('SET `time_zone` = :zone', ['zone' => $this->escape(date('P'))]);

		return self :: $link;
	}

	/**
	 * Get all columns for a table used for sanitizing input.
	 *
	 * @param string $tableName The table name to introspect.
	 * @param bool   $comment   Whether to include column comments (unused for pgsql).
	 *
	 * @return array<string, array<string, string|null>>|false
	 *                                                         Associative array keyed by column name, or false on failure.
	 */
	public function getColumnsMeta(string $tableName, bool $comment = false): array|false {
		$sql =
		"SELECT data_type as t, column_name as name, column_default as d, is_nullable as n, character_maximum_length as l FROM information_schema.columns WHERE table_name ='$tableName'";

		if ($result = $this->query($sql)) {
			//$columns = $result->fetch_all(MYSQLI_ASSOC);
			$columns = [];

			while ($row = pg_fetch_assoc($result)) {
				$columns[$row['name']] = $row;
			}

			// free result set
			return $columns;
		}

		return false;
	}

	/**
	 * Get all table names in the database.
	 *
	 * @param string $db The database name (unused — queries pg_catalog instead).
	 *
	 * @return array<int, string>|false List of table names, or false on failure.
	 */
	public function getTableNames(string $db = DB_NAME): array|false {
		$sql =
		"SELECT tablename as name FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema' ORDER BY name";

		if ($result = $this->query($sql)) {
			//$names = $result->fetch_all(MYSQLI_ASSOC);
			$names = [];

			while ($row = pg_fetch_assoc($result)) {
				//$columns[] = $row;
				$names[] = $row['name'];
			}

			// free result set
			return $names;
		}

		return false;
	}

	/**
	 * Escape a value for safe inclusion in a SQL query.
	 *
	 * Strings are escaped with pg_escape_string. Null values become 'null'.
	 * All other types are returned as-is.
	 *
	 * @param mixed $string The value to escape.
	 *
	 * @return mixed The escaped value.
	 */
	public function escape(mixed $string): mixed {
		if (is_string($string)) {
			return pg_escape_string(self :: $link, $string);
		}

		if (is_null($string)) {
			return 'null';
		}

		return $string;
	}

	/**
	 * Escape a string value for use in a SQL literal.
	 *
	 * Returns the escaped value without surrounding quotes, matching the
	 * mysqli/sqlite drivers (callers add their own quotes).
	 *
	 * @param string $string The string to escape.
	 *
	 * @return string The escaped string.
	 */
	public function escapeLiteral(string $string): string {
		return pg_escape_string(self :: $link, $string);
	}

	/**
	 * Fetch a single row from a result resource.
	 *
	 * @param \PgSql\Result|false $result The pgsql result resource.
	 *
	 * @return array<string, mixed> The fetched row, or empty array if no rows.
	 */
	public function fetchArray(&$result): array {
		if ($result && pg_num_rows($result)) {
			$values = pg_fetch_assoc($result);

			return $values;
		}

		return [];
	}

	/**
	 * Fetch all rows from a result resource and free it.
	 *
	 * @param \PgSql\Result|false $result The pgsql result resource (set to false after freeing).
	 *
	 * @return array<int, array<string, mixed>> All result rows.
	 */
	public function fetchAll(&$result): array {
		if ($result && pg_num_rows($result)) {
			$values = pg_fetch_all($result);

			pg_free_result($result);
			$result = false;

			return $values;
		}

		return [];
	}

	/**
	 * Execute a raw SQL query with optional positional parameters.
	 *
	 * Automatically connects if not connected. Uses pg_query_params when
	 * parameters are provided, pg_query otherwise.
	 *
	 * @param string            $sql        The SQL query with $1, $2... placeholders.
	 * @param array<int, mixed> $parameters Positional parameter values.
	 *
	 * @throws \Exception On query failure.
	 *
	 * @return \PgSql\Result|false The query result, or false on failure.
	 */
	public function query(string $sql, array $parameters = []): \PgSql\Result|false {
		if (!self :: $link) {
			$this->connect();
		}

		$result = false;

		try {
			if ($parameters) {
				if (LOG_SQL_QUERIES) {
					error_log($this->debugSql($sql, $parameters, [], '$'));
				}

				$this->last_res = $result = @pg_query_params(self :: $link, $sql, $parameters);
			} else {
				if (LOG_SQL_QUERIES) {
					error_log($sql);
				}

				$this->last_res = $result = @pg_query(self :: $link, $sql);
			}

			if ($this->last_res == false) {
				$errorMessage = pg_last_error(self :: $link);
				$message      = $errorMessage . "\n" . $this->debugSql($sql, $parameters, [], '$') . "\n - " . $sql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

				throw new \Exception($message);
			}

			if ($result) {
				$this->affected_rows = @pg_affected_rows($this->last_res);
			}

			return $this->last_res;
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n" . $this->debugSql($sql, $parameters, [], '$') . "\n - " . $sql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

			throw new \Exception($message, $e->getCode());
		}
	}

	/**
	 * Execute multiple SQL statements (delegates to query for PostgreSQL).
	 *
	 * @param string $sql The SQL statements to execute.
	 *
	 * @return \PgSql\Result|false The query result, or false on failure.
	 */
	public function multi_query(string $sql): \PgSql\Result|false {
		return $this->query($sql);
	}

	/**
	 * Close the database connection.
	 *
	 * @return bool|null True on success, null if not connected.
	 */
	public function close(): ?bool {
		if (self :: $link) {
			return pg_close(self :: $link);
		}

		return null;
	}

	/**
	 * Execute a query and return a single scalar value.
	 *
	 * @param string                 $query      The SQL query.
	 * @param array<int, mixed>|null $parameters Positional parameter values.
	 *
	 * @return string|false The first column of the first row, or false if no results.
	 */
	public function get_one(string $query, ?array $parameters = null): string|false {
		$res = $this->query($query, $parameters);

		if (pg_num_rows($res)) {
			return pg_fetch_result($res, 0, 0);
		} else {
			return false;
		}
	}

	/**
	 * Execute a query and return a single row.
	 *
	 * @param string            $query      The SQL query (LIMIT 1 is appended automatically).
	 * @param array<int, mixed> $parameters Positional parameter values.
	 *
	 * @return array<string, mixed>|null The row as an associative array<array-key, mixed>, or null on failure.
	 */
	public function get_row(string $query, array $parameters): ?array {
		$res    = $this->query($query . ' LIMIT 1', $parameters);
		$values = pg_fetch_assoc($res);

		if ($res) {
			pg_free_result($res);
		}

		return $values ?: null;
	}

	/**
	 * Execute a query and return all rows.
	 *
	 * @param string                 $query      The SQL query.
	 * @param array<int, mixed>|null $parameters Positional parameter values.
	 *
	 * @return array<int, array<string, mixed>>|false All rows, or false if no results.
	 */
	public function get_all(string $query, ?array $parameters = null): array|false {
		$res = $this->query($query, $parameters);

		if (pg_num_rows($res)) {
			$values = pg_fetch_all($res);

			if ($res) {
				pg_free_result($res);
			}

			return $values;
		} else {
			return false;
		}
	}

	/**
	 * Fetch a single row from a result resource and free it.
	 *
	 * @param resource|null $res The pgsql result resource, or null to use the last result.
	 *
	 * @return array<string, mixed>|null The row as an associative array<array-key, mixed>, or null if no rows.
	 */
	public function fetch_row($res = null): ?array {
		if ($res === null) {
			$res = $this->last_res;
		}

		$values = pg_fetch_assoc($res);

		if ($res) {
			pg_free_result($res);
		}

		return $values ?: null;
	}

	/**
	 * Prepare and execute a parameterised query.
	 *
	 * Fires an Event::trigger hook before execution. Converts named parameters
	 * to positional $1, $2... placeholders for pg_query_params. Updates
	 * affected_rows on success.
	 *
	 * @param string                $sql        The SQL query with :named parameters.
	 * @param array<string, mixed>  $params     Named parameter values.
	 * @param array<string, string> $paramTypes Optional explicit type overrides.
	 *
	 * @throws \Exception On query failure.
	 *
	 * @return \PgSql\Result|false The query result, or false on failure.
	 */
	public function execute(string $sql, array $params = [], array $paramTypes = []): \PgSql\Result|false {
		[$sql, $params] = Event::trigger(__CLASS__, __FUNCTION__, $sql, $params);
		//save orig sql for debugging info
		$origSql = $sql;

		if (!self :: $link) {
			$this->connect();
		}

		[$parameters, $types] = $this->paramsToQmark($sql, $params, $paramTypes, '$');

		if (LOG_SQL_QUERIES) {
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		if ($parameters) {
			$this->last_res = pg_query_params(self :: $link, $sql, $parameters);
		} else {
			$this->last_res = pg_query(self :: $link, $sql);
			//pg_send_query(self :: $link, $sql);
			//$this->last_res = pg_get_result(self :: $link);
		}

		if ($this->last_res) {
			$this->affected_rows = @pg_affected_rows($this->last_res);
		}

		if ($this->last_res == false) {
			$errorMessage = pg_last_error(self :: $link);
			//error_log('pgsql error: ' . pg_last_error(self :: $link));
			$message = $errorMessage . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n - " . $origSql . "\n - " . print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1), true);

			throw new \Exception($message);
		}

		return $this->last_res;
	}
}
