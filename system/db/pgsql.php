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

class Pgsql extends DBDriver {
	private static $link = null;

	//public $error;

	private $stmt;

	private $last_res;

	public $affected_rows = 0;

	public $num_rows = 0;

	public $insert_id = null;

	private static $persistent = false;

	public $prefix = ''; //'vv_';

	public $quote  = '"';

	public static function version() {
		if (self :: $link) {
			return pg_version(self :: $link);
		}
	}

	public static function info() {
		if (self :: $link) {
			return pg_version(self :: $link);
		}
	}

	public function error() {
		if (self :: $link) {
			return pg_last_error(self :: $link) ?? '';
		}
	}

	public function errorCode() {
		if (self :: $link) {
			return pg_last_error(self :: $link) ?? 0;
		}
	}

	public function get_result($stmt) {
		return $stmt;
	}

	public function __construct($host = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS,  $prefix = DB_PREFIX) {
		if (! self :: $link) {
			//port 5432 for direct pgsql connection 6432 for pgbouncer
			$port           = 5432;
			$connect_string = "host=$host port=$port dbname=$dbname  user=$user password=$pass";

			if (self :: $persistent) {
				self :: $link = pg_pconnect($connect_string);
			} else {
				self :: $link = pg_connect($connect_string);
			}

			if (self :: $link) {
				//				pg_set_error_verbosity(self :: $link, PGSQL_ERRORS_VERBOSE);
			} else {
			}
		}

		//sync database time
		//self :: $link->execute('SET `time_zone` = :zone', ['zone' => $this->escape(date('P'))]);

		return self :: $link;
	}

	/*
	 * Get all columns for a table used for sanitizing input
	 */
	function getColumnsMeta($tableName) {
		$sql =
		"SELECT data_type as t, column_name as name, column_default as d, is_nullable as n FROM information_schema.columns WHERE table_name ='$tableName'";

		if ($result = $this->query($sql)) {
			//$columns = $result->fetch_all(MYSQLI_ASSOC);
			$columns = [];

			while ($row = pg_fetch_assoc($result)) {
				$columns[] = $row;
			}

			/* free result set */
			return $columns;
		} else {
		}

		return false;
	}

	function getTableNames($db = DB_NAME) {
		$sql =
		"SELECT tablename as name FROM pg_catalog.pg_tables WHERE schemaname != 'pg_catalog' AND schemaname != 'information_schema' ORDER BY name";

		if ($result = $this->query($sql)) {
			//$names = $result->fetch_all(MYSQLI_ASSOC);
			$names = [];

			while ($row = pg_fetch_assoc($result)) {
				//$columns[] = $row;
				$names[] = $row['name'];
			}

			/* free result set */
			return $names;
		} else {
		}

		return false;
	}

	public function escape($string) {
		if (is_string($string)) {
			return pg_escape_string(self :: $link, $string);
		}

		if (is_null($string)) {
			return 'null';
		}

		return $string;
	}

	public function sqlLimit($start, $limit) {
		return "LIMIT  $limit OFFSET $start";
	}

	public function fetchAll(&$result) {
		if (pg_num_rows($result)) {
			$values = pg_fetch_all($result);

			if ($result) {
				pg_free_result($result);
				$result = false;
			}

			return $values;
		}

		return [];
	}

	public function query($sql, $parameters = []) {
		if (! self :: $link) {
			return false;
		}
		$result = false;

		try {
			if ($parameters) {
				$this->last_res = @pg_query_params(self :: $link, $sql, $parameters);
			} else {
				$this->last_res = @pg_query(self :: $link, $sql);
			}

			if ($this->last_res == false) {
				$errorMessage = pg_last_error(self :: $link);

				throw new \Exception($errorMessage);
			}

			return $this->last_res;
		} catch (\Exception $e) {
			$message = $e->getMessage() . "\n$sql\n";

			throw new \Exception($message, $e->getCode());
		}

		return $result;
	}

	public function multi_query($sql) {
		return $this->query($sql);
	}

	public function close() {
		if (self :: $link) {
			return pg_close(self :: $link);
		}
	}

	public function get_one($query, $parameters = null) {
		$res = $this->exec($query, $parameters);

		if (pg_num_rows($res)) {
			return pg_fetch_result($res, 0, 0);
		} else {
			return false;
		}
	}

	function get_row($query, $parameters) {
		$res = $this->exec($query . ' LIMIT 1', $parameters);

		if ($res === null) {
			$res = $this->last_res;
		}

		$values = pg_fetch_assoc($res);

		if ($res) {
			pg_free_result($res);
		}

		return $values;
	}

	public function get_all($query, $parameters = null) {
		$res = $this->exec($query, $parameters);

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

	function fetch_row($res = null) {
		if ($res === null) {
			$res = $this->last_res;
		}

		$values = pg_fetch_assoc($res);

		if ($res) {
			pg_free_result($res);
		}

		return $values;
	}

	// Prepare
	public function execute($sql, $params = [], $paramTypes = []) {
		list($sql, $params) = Event::trigger(__CLASS__,__FUNCTION__, $sql, $params);
		//save orig sql for debugging info
		$origSql = $sql;

		list($parameters, $types) = $this->paramsToQmark($sql, $params, $paramTypes, '$');

		if (DEBUG) {
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		if ($parameters) {
			$this->last_res = pg_query_params(self :: $link, $sql, $parameters);
		} else {
			$this->last_res = pg_query(self :: $link, $sql);
			//pg_send_query(self :: $link, $sql);
			//$this->last_res = pg_get_result(self :: $link);
		}

		if ($this->last_res == false) {
			error_log('pgsql error: ' . pg_result_error($this->last_res) . pg_last_error(self :: $link));
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		return $this->last_res;
	}

	// Bind
	public function bind($param, $value, $type = null) {
		$this->stmt->bindValue($param, $value, $type);
	}
}
