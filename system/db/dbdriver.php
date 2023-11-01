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

define('SQL_VAR_REGEX',
	'/:([a-zA-Z0-9\[][\.\'a-zA-Z0-9\[\]_-]+)/ms');

class DBDriver {
	public function _filter($data, $columns, $addMissingDefaults = false) {
		//remove fields that are not table columns, $colums is returned by sqlp->getColumnsMeta()
		foreach ($data as $key => $name) {
			if (! isset($columns[$key])) {
				unset($data[$key]);
			} else {
				if ($data[$key] === NULL) {
					//set default value is empty value provided
					if (isset($columns[$key]['d'])) {
						$data[$key] = $columns[$key]['d'];
					}
				}
			}
		}

		if ($addMissingDefaults) {
			foreach ($columns as $name => $options) {
				$options['t'] = strtolower($options['t']);

				if (isset($options['e']) && $options['e'] == 'auto_increment') {
					continue;
				}
				//todo: validate based on data type (t)
				//if there is no data for column and column is not nullable set default
				if (! isset($data[$name]) && $options['n'] == false) {
					if ($options['d']) {
						$data[$name] = $options['d'];
					} else {
						if ($options['d'] == NULL) {
							$data[$name] = '';
						}

						if ($options['t'] == 'int' || $options['t'] == 'decimal' || $options['t'] == 'tinyint') {
							$data[$name] = 0;
						}

						if ($options['t'] == 'datetime') {
							$data[$name] = date('Y-m-d H:i:s');
						}

						if ($options['t'] == 'date') {
							$data[$name] = date('Y-m-d');
						}
					}
				}
			}
		}

		return $data;
	}

	public function varType($var) {
		//$type = gettype($var);
		if (is_array($var)) {
			$type = 'a';
		} else {
			//if (is_float($var)) {
			if ($var == (string)(float)$var) {
				$type = 'd';
			} else {
				if (is_numeric($var)) {
					$type = 'i';
				} else {
					$type = 's';
				}
			}
		}

		return $type;
	}

	public function filter($data, $columns, $addMissingDefaults = false) {
		//check if collection of rows or individual row
		reset($data);

		if (is_numeric(key($data))) {
			//rows
			foreach ($data as $key => $row) {
				$return[$key] = $this->_filter($row, $columns, $addMissingDefaults);
			}

			return $return;
		} else {
			//row
			return $this->_filter($data, $columns, $addMissingDefaults);
		}
	}

	/*
	* Expands arrays a = ['param' => 'value', 'second' => value]; to a[param], a[second]
	*/
	public function expandArray($array, $arrayName) {
		$first      = true;
		$sql        = '';
		$parameters = [];

		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (! $first) {
					$sql .= ',';
				}
				$arrayKey = "['" . $arrayName . "']['" . $key . "']";
				$sql .= ':' . $arrayKey;
				$type                  = $this->varType($value);
				$parameters[$arrayKey] = $type;
				$first                 = false;
			}
		}

		return [$sql, $parameters];
	}

	/**
	 * Generate a SQL list used for inserts 
	 * input ['var1' => 1, 'var2'=> 2, 'var3'=> 3] output var1 = 1, var2 = 2, var3 = 3.
	 * @param mixed $array 
	 * @param mixed $arrayName 
	 *
	 * @return mixed 
	 */
	public function expandList($array, $arrayName) {
		$first      = true;
		$sql        = '';
		$parameters = [];
		$quote 		   = $this->quote;

		foreach ($array as $key => $value) {
			if (! $first) {
				$sql .= ',';
			}
			$arrayKey = "['$arrayName']['$key']";
			$sql .= "$quote$key$quote = :$arrayKey";
			$type                  = $this->varType($value);
			$parameters[$arrayKey] = $type;
			$first                 = false;
		}

		return [$sql, $parameters];
	}

	public function sqlCount($query, $column, $table) {
		//remove limit
		//pgsql
		$query = preg_replace('/LIMIT\s+(\d+|:\w+)\s+OFFSET\s*(\d+|:\w+)\s*;?$/', '', $query);
		//mysql
		$query = preg_replace('/LIMIT\s+(\d+|:\w+),\s*(\d+|:\w+)\s*;?$/', '', $query);

		$query = preg_replace("/^\s*SELECT .*?\s*FROM\s*$table /ms", "SELECT $column FROM $table ", $query);

		return $query;
	}

	/*
	 * Convert array dot notation to php notation 
	 * Ex: my.array.key to ['my']['array']['key']
	 */
	function sqlPhpArrayKey($key) {
		return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
	}

	/*
	 * Replace :named_params with ?
	 */
	public function paramsToQmark(&$sql, &$params = [], &$paramTypes = [], $placeholder = '?') {
		$parameters = [];
		$index      = 1;
		$sql        = preg_replace_callback(
		SQL_VAR_REGEX,
		function ($matches) use (&$params, &$parameters, &$types, $paramTypes, &$index, $placeholder) {
			$varName = $matches[1];

			if (strpos($varName, '.') !== false) {
				$varName = $this->sqlPhpArrayKey($varName);
			}
			//if parameters is array element
			if ($varName[0] == '[') {
				if (preg_match_all('/[\w_-]+/', $varName, $arrayKeys)) {
					$type = $paramTypes[$varName] ?? 's';

					$types .= $type;

					$key1 = $arrayKeys[0][0];
					$key2 = $arrayKeys[0][1];

					$parameter = &$params[$key1][$key2];
					//if (strpos($parameter, ')') && )
					$parameters[] = $parameter;
				}
			} else {
				if (isset($params[$varName])) {
					$parameter = &$params[$varName];

					if (isset($paramTypes[$varName])) {
						$type = $paramTypes[$varName];
					} else {
						$type = $this->varType($parameter);
					}

					if ($type == 'a') {
						$return = false;

						foreach ($parameter as $key => $value) {
							$parameters[] = $value;

							if ($placeholder == '$') {
								$holder = '$' . $index;
							} else {
								$holder = '?';
							}

							if ($return) {
								$return .= ",$holder";
							} else {
								$return = $holder;
							}

							$type = $this->varType($value);
							$types .= $type;

							$index++;
						}

						return $return;
					} else {
						if (! $type) {
							$type = $this->varType($parameter);
						}
						$types .= $type;

						$parameters[] = $parameter;
					}
				} else {
					return 'null';
				}
			}

			if ($placeholder == '$') {
				$holder = '$' . $index;
			} else {
				$holder = '?';
			}

			$return = $holder;
			$index++;

			return $return;
		},
		$sql);

		return [$parameters, $types];
	}

	public function debugSql($sql, $params = [], $paramTypes = [], $placeholder = '?') {
		list($parameters, $types) = $this->paramsToQmark($sql, $params, $paramTypes, $placeholder);

		if ($placeholder == '$') {
			$regex = '\$\d+';
		} else {
			$regex = "\\$placeholder";
		}
		$index = 0;
		$sql   = preg_replace_callback(
		"/$regex/",
		function ($matches) use (&$parameters, &$sql, &$index) {
			$value = $parameters[$index++];

			if (! is_numeric($value)) {
				$value = "'$value'";
			}

			return $value;
		}, $sql);

		return $sql;
		$sql = preg_replace_callback(
		SQL_VAR_REGEX,
		function ($matches) use (&$params, &$types, $paramTypes) {
			//if parameters is array element
			$varName = $matches[1];
			$value = $params[$varName] ?? '';

			if (strpos($varName, '.') !== false) {
				$varName = $this->sqlPhpArrayKey($varName);
			}

			if ($varName[0] == '[') {
				if (preg_match_all('/[\w_-]+/', $varName, $arrayKeys)) {
					$type = $paramTypes[$varName] ?? 's';

					$types .= $type;

					$key1 = $arrayKeys[0][0];
					$key2 = $arrayKeys[0][1];

					$parameter = &$params[$key1][$key2];
					//if (strpos($parameter, ')') && )
					if ($type == 's') {
						$parameter = '"' . (string)$parameter . '"';
					}

					return $parameter;
				}
			} else {
				if (isset($params[$varName])) {
					$parameter = &$params[$varName];

					if (isset($paramTypes[$varName])) {
						$type = $paramTypes[$varName];
					} else {
						$type = $this->varType($parameter);
					}

					if ($type == 'a') {
						$return = false;

						if ($parameter) {
							foreach ($parameter as $key => $value) {
								if ($return) {
									$return .= ',';
								}

								if ($value) {
									$return .= '"' . $value . '"';
								}
							}
						}

						return $return;
					} else {
						if (! $type) {
							$type = 's';
						}
						$types .= $type;
					}

					if (! $type) {
						$type = 's';
					}
					$types .= $type;

					if ($type == 's') {
						$parameter = '"' . $parameter . '"';
					}

					return $parameter;
				} else {
					return 'null';
				}
			}

			return '?';
		},
		$sql);

		return $sql;
	}
}
