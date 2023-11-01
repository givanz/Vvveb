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

//ini_set('mysql.trace_mode', '0');
//ini_set('mysqli.trace_mode', '0');

//define('SQL_ALLOW_FUNCTIONS','NOW,DATE,CURTIME');
/*
 Define and use mysqli_result if native is missing
 */
class mysqli_result {
	private $stmt;

	private $meta;

	public function __construct($stmt) {
		$this->stmt = $stmt;
	}

	public function fetch_all() {
		return $this->fetch_assoc();
	}

	public function fetch_array($resulttype) {
		return $this->fetch_assoc();
	}

	public function fetch_assoc() {
		//$meta = $this->stmt->store_result();
		$meta = $this->stmt->result_metadata();

		while ($field = $meta->fetch_field()) {
			$params[] = &$row[$field->name];
		}

		call_user_func_array([$this->stmt, 'bind_result'], $params);

		while ($this->stmt->fetch()) {
			foreach ($row as $key => $val) {
				$c[$key] = $val;
			}
			$result[] = $c;
		}

		//$this->stmt->free_result();
		$this->stmt->close();

		return $result;
	}

	public function fetch_field() {
	}

	public function fetch_fields() {
	}

	public function fetchRow() {
		return $this->fetch_assoc();
	}
}

class Mysqli extends DBDriver {
	private static $link = null;

	//public $error;

	private $stmt;

	public $affected_rows = 0;

	public $insert_id = null;

	public $quote  = '`';

	public $prefix = ''; //'vv_';

	public static function version() {
		if (self :: $link) {
			return mysqli_get_server_version(self :: $link);
		}
	}

	public static function info() {
		if (self :: $link) {
			return mysqli_get_server_info(self :: $link);
		}
	}

	public function error() {
		if (self :: $link) {
			return self :: $link->error;
		}
	}

	public function errorCode() {
		if (self :: $link) {
			return self :: $link->errno;
		}
	}

	public function get_result($stmt) {
		$result = new mysqli_result($stmt);

		return $result;
	}

	public function __construct($host = DB_HOST, $dbname = DB_NAME, $user = DB_USER, $pass = DB_PASS,  $prefix = DB_PREFIX) {
		//mysqli_report(MYSQLI_REPORT_OFF);
		//connect to database
		if (self :: $link) {
			return self :: $link;
		}
		$this->prefix = $prefix;

		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

		try {
			self :: $link = new \Mysqli(/*'p:' . */$host, $user, $pass, $dbname);
			//self :: $link = $this;
		} catch (\mysqli_sql_exception $e) {
			$errorMessage = str_replace($pass,'*****', $e->getMessage());

			throw new \Exception($errorMessage, $e->getCode());
		}

		// check if a connection established
		if (\mysqli_connect_errno()) {
			$error        = mysqli_connect_error();
			$errorMessage = str_replace($pass,'*****', $error);

			throw new \Exception($errorMessage, mysqli_connect_errno());
		}

		if ((! self :: $link instanceof \MySQLi)) {
			throw new \Exception('Mysqli not an object', mysqli_connect_errno());
		}

		return self :: $link;
	}

	/*
	 * Get all columns for a table used for sanitizing input
	 */
	function getColumnsMeta($tableName) {
		$sql =
		'SELECT COLUMN_NAME as name, COLUMN_DEFAULT as d, IS_NULLABLE  as n, DATA_TYPE as t, EXTRA as e
		FROM `INFORMATION_SCHEMA`.`COLUMNS` 
		WHERE `TABLE_SCHEMA`= "' . DB_NAME . '" 
			AND `TABLE_NAME`="' . $tableName . '"';

		if ($result = $this->query($sql)) {
			//$columns = $result->fetch_all(MYSQLI_ASSOC);
			$columns = [];

			while ($row = $result->fetch_assoc()) {
				$columns[] = $row;
			}

			/* free result set */
			$result->close();

			return $columns;
		} else {
		}

		return false;
	}

	function getTableNames($db = DB_NAME) {
		$sql ="SELECT table_name as name
			FROM information_schema.tables 
			WHERE table_schema = '$db' ORDER BY table_name";

		if ($result = $this->query($sql)) {
			//$names = $result->fetch_all(MYSQLI_ASSOC);
			$names = [];

			while ($row = $result->fetch_assoc()) {
				$names[] = $row['name'];
			}

			/* free result set */
			$result->close();

			return $names;
		} else {
		}

		return false;
	}

	public function select_db($db) {
		return self :: $link->select_db($db);
	}

	public function query($sql) {
		$result = self :: $link->query($sql);

		if ($result) {
			$this->affected_rows = self :: $link->affected_rows;
			$this->insert_id     = self :: $link->insert_id;
		}

		return $result;
	}

	public function multi_query($sql) {
		$result = self :: $link->multi_query($sql);

		if ($result) {
			$this->affected_rows = self :: $link->affected_rows;
			$this->insert_id     = self :: $link->insert_id;
		}

		return $result;
	}

	public function escape($string) {
		if (is_string($string)) {
			return self :: $link->real_escape_string($string);
		}

		if (is_null($string)) {
			return 'null';
		}

		return $string;
	}

	public function sqlLimit($start, $limit) {
		return "LIMIT $start, $limit";
	}

	public function fetchAll($stmt) {
		$result = $stmt->get_result();

		if ($result) {
			return $result->fetch_all(MYSQLI_ASSOC);
		}

		return [];
	}

	public function store_result() {
		return self :: $link->store_result();
	}

	public function more_results() {
		return self :: $link->more_results();
	}

	public function next_result() {
		return self :: $link->next_result();
	}

	public function close() {
		if ((self :: $link instanceof \MySQLi)/* && self :: $link->ping()*/) {
			return self :: $link->close();
		}
	}

	// Prepare
	public function execute($sql, $params = [], $paramTypes = []) {
		list($sql, $params) = Event::trigger(__CLASS__,__FUNCTION__, $sql, $params);
		//save orig sql for debugging info
		$origSql = $sql;

		list($parameters, $types) = $this->paramsToQmark($sql, $params, $paramTypes);

		try {
			$stmt = self::$link->prepare($sql);
		} catch (\mysqli_sql_exception $e) {
			$message = $e->getMessage() . "\n" . $this->debugSql($origSql, $params, $paramTypes) . "\n - " . $origSql;

			throw new \Exception($message, $e->getCode());
		}

		if ($stmt && ! empty($types)) {
			array_unshift($parameters, $types);

			//hack for php 7.x bind_param "expected to be a reference, value given" stupid warning
			$referenceArray = [];

			foreach ($parameters as $key => $value) {
				$referenceArray[$key] = &$parameters[$key];
			}

			@call_user_func_array([$stmt, 'bind_param'], $referenceArray);
		}

		if (LOG_SQL_QUERIES) {
			error_log($this->debugSql($origSql, $params, $paramTypes));
		}

		if ($stmt) {
			try {
				if ($stmt->execute()) {
					$this->affected_rows = self :: $link->affected_rows;
					$this->insert_id     = self :: $link->insert_id;

					return $stmt;
				} else {
					error_log(print_r($stmt, 1));
					error_log($this->debugSql($sql, $params, $paramTypes));
				}
			} catch (\mysqli_sql_exception $e) {
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
