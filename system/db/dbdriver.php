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

define(
	'SQL_VAR_REGEX',
	'/:([a-zA-Z0-9\[\]_][\.\'a-zA-Z0-9\[\]_-]+)/ms'
);

class DBDriver {
	/** @var string Quote character used to delimit identifiers (e.g. backtick for MySQL, double-quote for PostgreSQL). */
	public string $quote = '';

	/**
	 * Filter a single data row against a column metadata definition.
	 *
	 * Removes keys not present in the column schema, applies default values for
	 * null entries, and truncates values exceeding their maximum length.
	 *
	 * @param array<string, mixed>                                                            $data               The data row to filter.
	 * @param array<string, array{t?: string, d?: mixed, n?: bool, l?: int|null, e?: string}> $columns            Column metadata from getColumnsMeta().
	 * @param bool                                                                            $addMissingDefaults Whether to populate missing non-nullable columns with defaults.
	 *
	 * @return array<string, mixed> The filtered data row.
	 */
	public function _filter(array $data, array $columns, bool $addMissingDefaults = false): array {
		//remove fields that are not table columns, $colums is returned by sqlp->getColumnsMeta()
		foreach ($data as $key => $name) {
			if (!isset($columns[$key])) {
				unset($data[$key]);
			} else {
				if ($data[$key] === null) {
					//set default value is empty value provided
					if (isset($columns[$key]['d'])) {
						$data[$key] = $columns[$key]['d'];
						if ($data[$key] === 'NULL') {
							$data[$key] = null;
						}
					}
				}
				//if max length make sure that content does not exceed it
				if (isset($columns[$key]['l']) && $columns[$key]['l'] && is_string($data[$key]) && $data[$key]) {
					$data[$key] = substr($data[$key], 0, $columns[$key]['l']);
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
				if (!isset($data[$name]) && $options['n'] == false) {
					if ($options['d']) {
						$data[$name] = $options['d'];
					} else {
						if ($options['d'] == null) {
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

	/**
	 * Determine the SQL parameter type code for a given PHP value.
	 *
	 * @param mixed $var The value to classify.
	 *
	 * @return 'a'|'d'|'i'|'s' Type code: array<array-key, mixed>, decimal, integer, or string.
	 */
	public function varType(mixed $var): string {
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

	/**
	 * Filter data rows (single or collection) against a column metadata definition.
	 *
	 * Detects whether the input is a single row or a collection of rows and
	 * delegates to _filter() accordingly.
	 *
	 * @param array<int|string, mixed>                                                        $data               Data row or collection of rows.
	 * @param array<string, array{t?: string, d?: mixed, n?: bool, l?: int|null, e?: string}> $columns            Column metadata.
	 * @param bool                                                                            $addMissingDefaults Whether to populate missing defaults.
	 *
	 * @return array<int|string, mixed> Filtered data.
	 */
	public function filter(array $data, array $columns, bool $addMissingDefaults = false): array {
		//check if collection of rows or individual row
		reset($data);

		if (is_numeric(key($data))) {
			//rows
			$return = [];

			foreach ($data as $key => $row) {
				$return[$key] = $this->_filter($row, $columns, $addMissingDefaults);
			}

			return $return;
		} else {
			//row
			return $this->_filter($data, $columns, $addMissingDefaults);
		}
	}

	/**
	 * Validate input data against a set of rules.
	 *
	 * Supports simple type checks (int, string, array) and complex column-level
	 * validation with default values, max lengths, and nullable checks.
	 *
	 * @param array<string, mixed>                                         $data          The data to validate.
	 * @param array<string, string|array<string, array<array-key, mixed>>> $params        Validation rules — type code or column metadata array.
	 * @param string                                                       $table         Table name used to auto-skip primary key columns.
	 * @param bool                                                         $ignoreMissing Whether to skip missing-field errors.
	 *
	 * @return array<int, string> List of validation error messages.
	 */
	public function validate(array $data, array $params, string $table = '', bool $ignoreMissing = false): array {
		$errors = [];

		foreach ($params as $name => $type) {
			if (!isset($data[$name])) {
				if (!$ignoreMissing) {
					$errors[] = sprintf('%s missing!', $name);
				}

				continue;
			}

			if (is_array($type)) {
				if (isset($data[$name]) && is_array($data[$name])) {
					//check if collection
					if (is_numeric(key($data[$name]))) {
						$arr = $data[$name][0] ?? [];
					} else {
						$arr = $data[$name];
					}

					foreach ($type as $col => $attr) {
						//skip auto increment
						if ((isset($attr['e']) && $attr['e'] == 'auto_increment')
							|| ($table && $col == $table . '_id')) {
							continue;
						}

						if (!isset($arr[$col])) {
							//skip columns that have default values
							if (($attr['d'] === 'null' || $attr['d'] === null) && !$ignoreMissing) {
								$errors[] = sprintf('%s missing!', $name . '[' . $col . ']');
							}
						} else {
							if (($attr['t'] == 'int' || $attr['t'] == 'decimal' || $attr['t'] == 'tinyint') && !is_numeric($arr[$col])) {
								$errors[] = sprintf('%s is not an integer!', $name . '[' . $col . ']');
							}

							if (isset($attr['l']) && $attr['l'] && (strlen($arr[$col]) > $attr['l'])) {
								$errors[] = sprintf('%s is longer than %s!', $name . '[' . $col . ']', $attr['l']);
							}
						}
					}
				} else {
					$errors[] = sprintf('%s is not an array!', $name);
				}
			} else {
				switch ($type) {
					case 'a':
						if (!isset($data[$name]) || !is_array($data[$name])) {
							$errors[] = sprintf('%s is not an array!', $name);
						} else {
							if (substr_compare($name, '_id', -3, 3) === 0 &&
								!empty($data[$name]) &&
								!is_numeric($data[$name][0])) {
								$errors[] = sprintf('%s is not an integer!', $name . '[0]');
							}
						}

						break;

					case 'i':
						if (!isset($data[$name]) || !is_numeric($data[$name])) {
							$errors[] = sprintf('%s is not an integer!', $name);
						}

						break;

					case 's':
						if (!isset($data[$name]) || !is_string($data[$name])) {
							$errors[] = sprintf('%s is not a string!', $name);
						}

						break;
				}
			}
		}

		return $errors;
	}

	/**
	 * Expand an associative array into a SQL comma-separated parameter list.
	 *
	 * Converts ['param' => 'value', 'second' => 'val'] to :arrayName['param'], :arrayName['second'].
	 *
	 * @param array<string, mixed> $array     The array to expand.
	 * @param string               $arrayName The parameter array name.
	 *
	 * @return array{string, array<string, string>} The SQL fragment and parameter type map.
	 */
	public function expandArray(array $array, string $arrayName): array {
		$first      = true;
		$sql        = '';
		$parameters = [];

		foreach ($array as $key => $value) {
			if (!$first) {
				$sql .= ',';
			}
			$arrayKey = "['" . $arrayName . "']['" . $key . "']";
			$sql .= ':' . $arrayKey;
			$type                  = $this->varType($value);
			$parameters[$arrayKey] = $type;
			$first                 = false;
		}

		return [$sql, $parameters];
	}

	/**
	 * Sanitize array keys used as SQL identifiers (eg. from the @KEYS macro).
	 *
	 * The keys of user supplied arrays can end up as SQL identifiers in the
	 * expansion of the @KEYS macro, an attacker with a back-office account could
	 * close the quoted identifier with the quote character in the key and inject
	 * SQL. Escape the quote character inside the key by doubling it and drop
	 * non-scalar keys.
	 *
	 * @param array<array-key, mixed> $keys Array keys from user supplied data.
	 *
	 * @return array<string> Sanitized keys, safe to embed as quoted identifiers.
	 */
	public function escapeKeys(array $keys): array {
		$return = [];
		$quote  = $this->quote;

		foreach ($keys as $key) {
			if (!is_scalar($key)) {
				continue;
			}

			$key = (string)$key;

			if ($quote !== '') {
				$key = str_replace($quote, $quote . $quote, $key);
			}

			$return[] = $key;
		}

		return $return;
	}

	/**
	 * Generate a SQL SET clause from an associative array for UPDATE statements.
	 *
	 * Converts ['var1' => 1, 'var2' => 2] to `var1` = :arrayName['var1'], `var2` = :arrayName['var2'].
	 *
	 * @param array<string, mixed> $array     The key-value pairs to set.
	 * @param string               $arrayName The parameter array name.
	 *
	 * @return array{string, array<string, string>} The SQL SET fragment and parameter type map.
	 */
	public function expandList(array $array, string $arrayName): array {
		$first      = true;
		$sql        = '';
		$parameters = [];
		$quote 		   = $this->quote;

		foreach ($array as $key => $value) {
			if (!is_scalar($key)) {
				continue;
			}

			$key = (string)$key;

			if ($quote !== '') {
				$key = str_replace($quote, $quote . $quote, $key);
			}

			if (!$first) {
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

	/**
	 * Convert a SELECT query into a COUNT query by stripping LIMIT, ORDER BY,
	 * and replacing the selected columns.
	 *
	 * @param string $query  The original SELECT query.
	 * @param string $column The column expression to count (e.g. '*' or 'DISTINCT id').
	 * @param string $table  The table name.
	 *
	 * @return string The modified COUNT query.
	 */
	public function sqlCount(string $query, string $column, string $table): string {
		//remove limit
		//pgsql
		$query = preg_replace('/LIMIT\s+(\d+|:\w+)\s+OFFSET\s*(\d+|:\w+)\s*;?$/', '', $query);
		//mysql
		$query = preg_replace('/LIMIT\s+(\d+|:\w+),\s*(\d+|:\w+)\s*;?$/', '', $query);
		//remove order by
		$query = preg_replace('/ORDER BY\s+([a-z\._\-`]+|,|\s+|DESC|ASC)+/', '', $query);

		return preg_replace("/^\s*SELECT .*?\s*FROM\s*$table\s/ms", "SELECT $column FROM $table ", $query);
	}

	/**
	 * Convert a dot-notation key to PHP nested-array notation.
	 *
	 * Converts 'my.array.key' to ['my']['array']['key'].
	 *
	 * @param string $key The dot-notation key.
	 *
	 * @return string The PHP array<array-key, mixed> key notation.
	 */
	public function sqlPhpArrayKey(string $key): string {
		return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
	}

	/**
	 * Replace :named_params in SQL with positional placeholders (? or $N).
	 *
	 * Extracts parameter values and builds a type string for prepared statements.
	 * Supports nested array parameters, dot-notation keys, and IN-clause expansion.
	 *
	 * @param string                $sql         The SQL query with named parameters (modified in place).
	 * @param array<string, mixed>  $params      Named parameter values (modified in place).
	 * @param array<string, string> $paramTypes  Optional explicit type overrides (modified in place).
	 * @param string                $placeholder Placeholder style: '?' for MySQL/SQLite, '$' for PostgreSQL.
	 *
	 * @return array{array<int, mixed>, string} The extracted positional parameters and concatenated type string.
	 */
	public function paramsToQmark(string &$sql, array &$params = [], array &$paramTypes = [], string $placeholder = '?'): array {
		$parameters = [];
		$types      = '';
		$index      = 1;
		$sql        = preg_replace_callback(
			SQL_VAR_REGEX,
			function($matches) use (&$params, &$parameters, &$types, $paramTypes, &$index, $placeholder) {
				$varName = $matches[1];

				if (str_contains($varName, '.')) {
					$varName = $this->sqlPhpArrayKey($varName);
				}
				//if parameters is array element
				if ($varName[0] == '[') {
					if (preg_match_all('/[\w_-]+/', $varName, $arrayKeys)) {
						$type = $paramTypes[$varName] ?? 's';

						$types .= $type;

						$key1 = $arrayKeys[0][0];
						$key2 = $arrayKeys[0][1];

						$parameter    = &$params[$key1][$key2];
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
							$types .= $type ?: $this->varType($parameter);

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
			$sql
		) ?? $sql;

		return [$parameters, $types];
	}

	/**
	 * Generate a debug-safe SQL string with all placeholders replaced by actual values.
	 *
	 * @param string                   $sql         The SQL query with positional placeholders.
	 * @param array<array-key, mixed>  $params      The parameter values.
	 * @param array<array-key, string> $paramTypes  Optional explicit type overrides.
	 * @param string                   $placeholder Placeholder style: '?' or '$'.
	 *
	 * @return string The SQL string with embedded literal values for debugging.
	 */
	public function debugSql(string $sql, array $params = [], array $paramTypes = [], string $placeholder = '?'): string {
		[$parameters, $types] = $this->paramsToQmark($sql, $params, $paramTypes, $placeholder);

		if ($placeholder == '$') {
			$regex = '\$\d+';
		} else {
			$regex = "\\$placeholder";
		}
		$index = 0;

		return preg_replace_callback(
			"/$regex/",
			function($matches) use (&$parameters, &$index) {
				$value = $parameters[$index++];

				if (is_array($value)) {
					$value = var_export($value, true);
				} else {
					if (!is_numeric($value)) {
						$value = "'$value'";
					}
				}

				return $value;
			},
			$sql
		);
	}
}
