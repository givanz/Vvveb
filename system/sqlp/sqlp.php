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

namespace Vvveb\System\Sqlp;

use Vvveb\System\Db;

define('TAB', "\n\n\t\t");

#[\AllowDynamicProperties]
class SqlP {
	private $types = ['int' => 'i', 'double' => 'd', 'decimal' => 'd', 'blob' => 'b', 'array' => 'a'/*, 'CHAR' => 's'*/];

	private $prefix = DB_PREFIX;

	private $config = [];

	private $tree = [];

	function __construct() {
		$this->db     = Db::getInstance();
		$this->config = include 'config.php';
		//load model for selected database
		$modelFile = __DIR__ . DS . DB_ENGINE . '.php';
		$this->loadModel(file_get_contents($modelFile), $this->model);
	}

	function loadModel($text, &$config) {
		$regex = '/%(.+?)_start%(.+?)%\1_end%/ms';

		$text = preg_replace_callback($regex, function ($matches) use (&$config) {
			$config[$matches[1]] = $this->loadModel($matches[2], $config);

			return '%' . $matches[1] . '%';
		},$text);

		return $text;
	}

	/*
	 * Get all columns for a table used for sanitizing input
	 */
	function getColumnsMeta($tableName) {
		$db = '\Vvveb\System\Db\\' . DB_ENGINE;
		$db = new $db();

		$sql =
		'SELECT COLUMN_NAME as name, COLUMN_DEFAULT as d, IS_NULLABLE  as n, DATA_TYPE as t, EXTRA as e
		FROM `INFORMATION_SCHEMA`.`COLUMNS` 
		WHERE `TABLE_SCHEMA`= "' . DB_NAME . '" 
			AND `TABLE_NAME`="' . $tableName . '"';

		if ($result = $db->query($sql)) {
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

	/*
	 * Convert array dot notation to php notation 
	 * Ex: my.array.key to ['my']['array']['key']
	 */
	function sqlPhpArrayKey($key) {
		return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
	}

	/*
	 * Get table name or alias to use as array key when returning values for the query
	 */
	function getQueryArrayKey($query) {
		$arrayKeyRegex = '@SELECT.*?`?(\w+)`?\(?\)?\s+(as|AS)\s+(_|array_key)[\s,].*FROM@msi';

		if (preg_match($arrayKeyRegex, $query, $matches)) {
			return $matches[1];
		}

		return '';
	}

	/*
	 * Get the value for array_value alias to use as array key when returning values
	 */
	function getQueryArrayValue($query) {
		$arrayKeyRegex = '@SELECT.*?`?(\w+)`?\(?\)?\s+(as|AS)\s+array_value[\s,].*FROM@msi';

		if (preg_match($arrayKeyRegex, $query, $matches)) {
			return $matches[1];
		}

		return '';
	}

	/*
	 * Extract table name from sql statement
	 */
	function getTableName($query) {
		$tableName = '';

		//remove subselects
		$count = 1;

		while ($count != 0) {
			$query = preg_replace('/\([^\(\)]{10,}?\)/ms', 'replaced_subselect', $query, -1, $count);
		}

		//remove macros
		$count = 1;

		while ($count != 0) {
			$query = preg_replace('/@(\w+)[^@]+?@\1/ms', 'replaced_macro', $query, -1, $count);
		}

		//avoid subselects with negative lookbehind for (
		$selectRegex   = '/(@[^\s]+\s*)?(?<!\()\s*SELECT.*?FROM\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$updateRegex   = '/(@[^\s]+\s*)?UPDATE\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$insertRegex   = '/(@[^\s]+\s*)?INSERT\s*INTO\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$deleteRegex   = '/(@[^\s]+\s*)?DELETE.*FROM\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$functionRegex = '/(@[^\s]+\s*)?(?<!\()SELECT.*?\w+\(\)\s*AS\s*[`"]?(\w+)[`"]?/ims';
		$countRegex    = '/(@[^\s]+\s*)?SELECT\s*count\(.*?\s*AS\s*[`"]?(\w+)[`"]?$/ims';

		if (preg_match($selectRegex, $query, $matches1) ||
			preg_match($updateRegex, $query, $matches1) ||
			preg_match($insertRegex, $query, $matches1) ||
			preg_match($deleteRegex, $query, $matches1) ||
			preg_match($functionRegex, $query, $matches1) ||
			preg_match($countRegex, $query, $matches1)) {
			$tableName = ! empty($matches1[2]) ? $matches1[2] : $matches1[3];

			if (preg_match('@`?\w+`?$@i', $tableName, $matches2)) {
				$tableName = $matches2[0];
			}
		}

		return trim($tableName, '`"\'');
	}

	/*
	 * Add prefix to table name
	 */
	function prefixTable($query) {
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
				function ($matches) {
					return $matches[1] . $this->prefix . $matches[2];
				},
				$query
			);
		}

		return $query;
	}

	function paramType($type) {
		return $this->types[strtolower($type)] ?? 's';
	}

	function fetchType($type) {
		switch ($type) {
			case 'insert_id':
				return $this->model['insert_id'];
				//return  '$this->db->insert_id';

			case 'affected_rows':
				return $this->model['affected_rows'];
				//return  '$this->db->affected_rows';

			case 'fetch_row':
				return $this->model['fetch_row'];
				//return  '$result->fetch_array(MYSQLI_ASSOC)';

			case 'fetch_one':
				return $this->model['fetch_one'];
				//return  '$result->fetch_array(MYSQLI_NUM)[0] ?? null';

			case (isset($type[0]) && $type[0] == '@'):
				 $key = str_replace('@result.', '', $type);

				 return $this->template($this->model['fetch_result'], ['key' => $key]);
				 //return sprintf($this->model['fetch_result'], $key, $key);
				 //return "isset(\$results['$key']) ? \$results['$key'] : 'NULL'";

			case 'fetch_all':
			default:
				return $this->model['fetch_all'];
				//return '$result->fetch_all(MYSQLI_ASSOC)';
		}
	}

	function template($template, $variables) {
		//$template = preg_replace('/\t+/', "\t\t", $template);

		$keys = array_map(function ($value) {
			return "%$value%";
		}, array_keys($variables));

		$values = array_values($variables);

		$result = str_replace($keys, $values, $template);
		//remove unmatched variables
		//$result = preg_replace('/%\w+%/', '', $result);

		return $result;
	}

	function parseMacro($statement, $params, $regex, $template) {
		$macro = $template;

		if (preg_match_all($regex, $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$macro = $template;

				//replace macro template variables %$variable
				$macro = preg_replace_callback(
				'@\$%(\w+)@',
				function ($varMatch) use ($match) {
					return
					preg_replace_callback(
						$this->config['varRegex'],
						function ($matches) {
							return '$params[\'' . $matches[1] . '\']';
						},
					$match[$varMatch[1]]);
				},
				$macro);

				//replace macro template placeholders %placeholder
				$macro = preg_replace_callback(
				'@\%(\w+)@',
					function ($varMatch) use ($match) {
						return $match[$varMatch[1]];
					},
				$macro);

				$statement = str_replace($match[0], $macro, $statement);
			}
		}

		return $statement;
	}

	function parseEach($statement, $params) {
		//EACH VAR
		if (preg_match_all($this->config['eachVarRegex'], $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$resultKey = $this->sqlPhpArrayKey($match[1]);

				$each = "\n" . TAB . 'if (isset($params' . $resultKey . ') && is_array($params' . $resultKey . '))' .
				'foreach ($params' . $resultKey . ' as $key => $rowParent) { ' . "\n" . TAB . ' 
					$params[\'each\'] = $rowParent;
					if (is_array($params[\'each\'])) {
						$paramTypes[\'each\'] = \'a\';
					} else if (is_int($params[\'each\'])) {
						$paramTypes[\'each\'] = \'i\';
					} else {
						$paramTypes[\'each\'] = \'s\';
					}
					$sql = \'';

				$statement = str_replace('$sql = \'' . $match[0], $each, $statement) . "\n" . TAB . ' }
					unset($params[\'each\']);
					unset($paramTypes[\'each\']);
					';
			}
		}

		return $statement;
	}

	function parseSQLCount($statement, $params) {
		//EACH VAR
		$statement = '$sql = ' . $statement;

		return $statement;
	}

	function parseMacros($statement, $params) {
		//if then else
		$space = "\n\n\t\t";

		//replace variables
		$statement =
				preg_replace_callback(
					'/\$([\w_-]+)/',
					function ($matches) {
						$key = $matches[1];

						return "' . (isset(\$params['$key']) ? \$params['$key'] : 'NULL') . '";
					//return "' . \$results['". $matches[1] . "'] . '";
					},
				$statement);

		$lex       = new Lexer($this->config['tokenMap'],  $this->config['macroMap']);
		$structure = $lex->lex($statement);
		//\Vvveb\d($structure);
		//die();
		$output    = $lex->treeMacro($structure);
		$statement = $lex->treeToPhp($output, $this->config['macroMap']);

		//@result
		//replace result variables
		$statement =
					preg_replace_callback(
						'/@result.(\w+)/',
						function ($matches) {
							$key = $matches[1];

							return "' . (isset(\$results['$key']) ? \$results['$key'] : 'NULL') . '";
						//return "' . \$results['". $matches[1] . "'] . '";
						},
					$statement);

		//$variable

		//FILTER
		if (preg_match_all($this->config['filterRegex'], $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$filter             = [];
				$columns            = $this->db->getColumnsMeta($this->prefix . $match['columns']);
				$addMissingDefaults = $match['addmissing'] ?? 'false';
				$isArray            = $match['array'] ?? 'false';

				foreach ($columns as $column) {
					$name = $column['name'];
					unset($column['name']);

					if (empty($column['e'])) {
						unset($column['e']);
					}
					$column['n']   = ($column['n'] == 'NO') ? false : true;

					if (isset($column['d'])) {
						$column['d']   = trim($column['d'], '\'');
					}
					$filter[$name] = $column;
				}

				$filterArray = var_export($filter, true);
				$return      = ! empty($match['return']) ? $match['return'] : $match['data'];
				$return      = '$params' . $this->sqlPhpArrayKey($return);
				$key         = '$params' . $this->sqlPhpArrayKey($match['data']);

				$filterFunction = '\';' . TAB . '$filterArray = ' . $filterArray . ";\n" . TAB;

				if ($isArray == 'true') {
					$filterFunction .= 'foreach ( ' . $key . ' as $key => &$filter) ' . $return . '[$key] = $this->db->filter($filter, $filterArray,' . $addMissingDefaults . ');' . TAB . '$sql = \'';
				} else {
					$filterFunction .= $return . '= $this->db->filter(' . $key . ', $filterArray,' . $addMissingDefaults . ');' . TAB . '$sql = \'';
				}

				$statement = str_replace($match[0], $filterFunction, $statement);
			}
		}

		return $statement;
	}

	function parseParameters($params) {
		$parameters = [];

		if (preg_match_all($this->config['paramRegex'], $params, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$param['in_out'] = trim($match[1]);
				$param['name']   = trim($match[2]);

				$param['type'] = $param['length'] = '';

				if (isset($match[3])) {
					$param['type'] = trim($match[3]);
				}

				if (isset($match[4])) {
					$param['length'] = (int)preg_replace('/[^\d]/', '',$match[4]);
				}

				$parameters[] = $param;
			}
		}

		return $parameters;
	}

	function processImports($sql) {
		$sql = preg_replace_callback($this->config['importRegex'], function ($matches) {
			$file =  $matches[1];
			//absolute path
			if ($file[0] == '/') {
				$path = explode('/', substr($file, 1));
				$app = $path[0];
				unset($path[0]);

				if ($app == 'plugins') {
					$plugin = $path[1];
					unset($path[1]);
				} else {
					$plugin = '';
				}

				$file = implode('/', $path);
				$file = DIR_ROOT . $app . ($plugin ? '/' . $plugin : '') . '/sql/' . DB_ENGINE . '/' . $file;
			} else {//relative path
				$file =  DIR_SQL . $matches[1];
			}

			if (file_exists($file)) {
				return file_get_contents($file);
			}

			return '';
		}, $sql);

		return $sql;
	}

	function parseSqlPfile($filename, $modelName = false, $namespace = '') {
		if ($modelName) {
			$this->modelName = $modelName;
		} else {
			$this->modelName = str_replace('.sql', '',  preg_replace_callback('/_(\w)/',
				function ($m) {
					return ucfirst($m[1]);
				} , basename($filename)));
		}

		$this->namespace = $namespace;
		$this->filename  = $filename;

		$sql = file_get_contents($filename);
		//process imports
		$sql = $this->processImports($sql);

		//remove comments
		$sql = preg_replace('@(--.*)\s+@', '', $sql);

		if (preg_match_all($this->config['functionRegex'], $sql, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$method['name']      = trim($match['name'], '`"\'');
				//add slashes only for single quotes
				$method['statement'] = str_replace("'", "\'",trim($match['statement']));

				$method['params'] = $this->parseParameters($match['params']);

				//$this->tree[] = $method;
				//overwrite method if redefined
				$this->tree[$method['name']] = $method;
			}
		}
	}

	function generateModel() {
		$methods = '';

		if ($this->tree) {
			foreach ($this->tree as $i => $method) {
				$statement = "/*{$this->modelName} - {$method['name']}*/\n\t\t";

				$queries      = explode(';', $method['statement']);
				$queriesCount = count($queries);

				$statements = '';

				$method['fetch'] = $this->fetchType('fetch_all');

				$fetch = [];

				foreach ($method['params'] as $param) {
					if ($param['in_out'] == 'OUT') {
						$fetch[] = $param['name'];
					}
				}

				foreach ($queries as $qIndex => $query) {
					$statement = '';
					$query     = $this->prefixTable(trim($query), DB_PREFIX);

					if (empty($query)) {
						continue;
					}
					$hasEach = (0 === substr_compare($query, '@EACH', 0, 5));

					$template = /*$hasEach?$this->config['eachQuery']:*/$this->model['query'];

					$queryId    = preg_replace('/^' . $this->prefix . '/', '',  $this->getTableName($query));
					$arrayKey   = $this->getQueryArrayKey($query);
					$arrayValue = $this->getQueryArrayValue($query);

					//$query = $this->parseSQLCount($query);
					$statement .= $this->template($template,
				[
					'statement'   => $this->parseMacros($query, $method['params']),
					'query_id'    => $queryId,
					'array_key'   => $arrayKey,
					'array_value' => $arrayValue,
				]);

					$statement = $this->parseEach($statement, $method['params']);

					//expand array parameters
					foreach ($method['params'] as $param) {
						if ($param['type'] == 'ARRAY') {
							$expandArray = '\';' . TAB . ' list($_sql, $_params) = $this->db->expandArray($params[\'' . $param['name'] . '\'], \'' . $param['name'] . '\');'
											 . TAB . '$sql .= $_sql;'
											 . TAB . 'if (is_array($_params)) $paramTypes = array_merge($paramTypes, $_params);'
											 . TAB . '$sql .= \' ';

							//$statement = str_replace(':' . $param['name'],  $expandArray, $statement);
							$statement = preg_replace('@:' . $param['name'] . '(?!_\.) @', $expandArray, $statement);
						}
					}

					//clean empty sql strings
					$statement = preg_replace('@\s*\$sql .= \'\s*\';@ms', '', $statement);

					if (isset($fetch[$qIndex])) {
						$method['fetch'] = $this->fetchType($fetch[$qIndex]);
					} else {
						if (isset($fetch[0])) {
							$method['fetch'] = $this->fetchType($fetch[0]);
						}
					}

					$statement = $this->template($statement, ['fetch' => $method['fetch']]);

					$statements .= $statement;
				}

				$method['statement'] = $statements;

				//generate function parameter list
				/*
				$method['vars'] = trim(implode("\n\t", array_map(
								function ($param) {
									if ($param['in_out'] == 'IN' && ($param['type'] = $this->paramType($param['type']))) {
										return $this->template($this->model['varsTemplate'], $param);
									}
								} ,$method['params'])), "\n\t");
				*/
				$method['param_types'] = 'array(' . trim(implode(', ', array_map(
								function ($param) {
									if ($param['in_out'] == 'IN' && ($type = $this->paramType($param['type']))) {
										return '\'' . $param['name'] . '\' => \'' . $type . '\'';
									}
								} ,$method['params'])), ', ') . ')';

				$method['fetch'] = $this->fetchType('fetch_all');

				$fetch = false;
				$o     = 0;

				foreach ($method['params'] as $param) {
					if ($param['in_out'] == 'OUT') {
						if (! $fetch) {
							$fetch = $param['name'];
						}

						if ($o == $i) {
							$fetch = $param['name'];

							break;
						}
						$o++;
					}
				}

				$method['fetch'] = $this->fetchType($fetch);

				$method['params'] = trim(implode(', ', array_map(
								function ($param) {
									if ($param['in_out'] == 'IN') {
										return '$' . $param['name'];
									}
								} ,$method['params'])), ', ');

				//if ($queriesCount > 1)
				//{
				$methods .= $this->template($this->model['methodMultipleTemplate'], $method);
				//} else
			//{
				//$methods .= $this->template($this->config['METHOD_TEMPLATE'], $method);
			//}
			}
		}

		$model = $this->template($this->model['model'],[
			'name'         => ucfirst($this->modelName),
			'namespace'    => ucfirst($this->namespace),
			'filename'     => $this->filename,
			'methods'      => $methods,
		]);

		return $model;
	}
}
