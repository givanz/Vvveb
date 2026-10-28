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

/**
 * SQL Parser — parses .sqlp files and generates PHP model classes.
 */
#[\AllowDynamicProperties]
class Sqlp {
	/** @var string Indentation used when generating PHP code. */
	private const TAB = "\n\n\t\t";

	/** @var array<string, string> Map of PHP type names to parameter-type codes. */
	private array $types = ['int' => 'i', 'double' => 'd', 'decimal' => 'd', 'blob' => 'b', 'array' => 'a', 'rows' => 'a', 'row' => 'a'/*, 'CHAR' => 's'*/];

	/** @var string Table-name prefix applied to every query. */
	private string $prefix = DB_PREFIX;

	/** @var array<string, mixed> Configuration loaded from config.php. */
	private array $config = [];

	/** @var array<string, array{name: string, statement: string, params: list<array<string, mixed>>, fetch?: string, param_types?: string, vars?: string}> Parsed method tree. */
	private array $tree = [];

	/** @var array<string, array<string, array{name: string, n: bool, d?: string, e?: string}>> Column-filter definitions keyed by prefixed table name. */
	private array $filters = [];

	/** @var array<string, array<string, string>> Parameter type maps keyed by method name. */
	private array $paramTypes = [];

	/** @var Db Database driver instance. */
	private object $db;

	/** @var string Namespace for the generated model class. */
	private string $namespace;

	/** @var array<array-key, mixed> Model config. */
	private array $model = [];

	/** @var string Model name derived from the .sqlp filename or passed explicitly. */
	private string $modelName;

	/** @var string Full path to the .sqlp file being parsed. */
	private string $filename;

	/**
	 * Initialise the parser: connect to the database and load the engine-specific model template.
	 */
	public function __construct() {
		$this->db     = Db::getInstance();
		$this->config = include __DIR__ . '/config.php';
		//load model for selected database
		$modelFile = __DIR__ . DS . DB_ENGINE . '.tpl';
		$this->loadModel(file_get_contents($modelFile), $this->model);
	}

	/**
	 * Recursively load a model template by expanding nested `%name_start%…%name_end%` blocks.
	 *
	 * @param string               $text   Raw template text.
	 * @param array<string, mixed> $config Populated in-place with extracted blocks.
	 *
	 * @return string Template text with block markers replaced by single `%name%` placeholders.
	 */
	public function loadModel(string $text, array &$config): string {
		$regex = '/%(.+?)_start%(.+?)%\1_end%/ms';

		$text = preg_replace_callback($regex, function($matches) use (&$config) {
			$config[$matches[1]] = $this->loadModel($matches[2], $config);

			return '%' . $matches[1] . '%';
		}, $text);

		return $text;
	}

	/*
	 * Convert array dot notation to php notation
	 * Ex: my.array.key to ['my']['array']['key']
	 */
	/**
	 * Convert a dot-notation key to a PHP array-access string.
	 *
	 * @param string $key Dot-separated key, e.g. "my.array.key".
	 *
	 * @return string PHP array-access suffix, e.g. "['my']['array<array-key, mixed>']['key']".
	 */
	public function sqlPhpArrayKey(string $key): string {
		return '[\'' . str_replace('.', '\'][\'', $key) . '\']';
	}

	// Get table name or alias to use as array key when returning values for the query
	/**
	 * Extract the alias intended for use as an array key from a SELECT query.
	 *
	 * @param string $query SQL SELECT statement.
	 *
	 * @return string The alias name, or an empty string if none is found.
	 */
	public function getQueryArrayKey(string $query): string {
		$arrayKeyRegex = '@SELECT.*?`?(\w+)`?\(?\)?\s+(as|AS)\s+(_|array_key)[\s,].*FROM@msi';

		if (preg_match($arrayKeyRegex, $query, $matches)) {
			return $matches[1];
		}

		return '';
	}

	// Get the value for array_value alias to use as array key when returning values
	/**
	 * Extract the column aliased as `array_value` from a SELECT query.
	 *
	 * @param string $query SQL SELECT statement.
	 *
	 * @return string The column name, or an empty string if none is found.
	 */
	public function getQueryArrayValue(string $query): string {
		$arrayKeyRegex = '@SELECT.*?`?(\w+)`?\(?\)?\s+(as|AS)\s+array_value[\s,].*FROM@msi';

		if (preg_match($arrayKeyRegex, $query, $matches)) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Recursively replace balanced parenthesis groups (sub-queries / macros) with a replacement string.
	 *
	 * @param string $query     SQL to process.
	 * @param string $replace   Replacement for each matched group.
	 * @param string $startChar Opening delimiter (default "(").
	 * @param string $endChar   Closing delimiter (default ")").
	 *
	 * @return string Processed SQL.
	 */
	public function removeParanthesis(string $query, string $replace = '', string $startChar = '(', string $endChar = ')'): string {
		$level = 0;
		$start = null;

		for ($i = 0, $len = strlen($query); $i < $len; $i++) {
			$char = $query[$i];

			if ($char == $startChar) {
				if ($start === null) {
					$start = $i;
				}
				$level++;
			} else {
				if ($char === $endChar) {
					//clamp at zero so stray closing delimiters don't break the balance
					$level = max(0, $level - 1);
				}
			}

			//strict null check - a group starting at position 0 is falsy but valid
			if ($start !== null && $level == 0) {
				$end   = $i + 1;
				$query = substr_replace($query, $replace, $start, $end - $start);
				//reset
				$i     = 0;
				$start = null;
				$len   = strlen($query);
			}
		}

		return $query;
	}

	/**
	 * Extract the primary table name from a SQL statement.
	 *
	 * Handles SELECT, INSERT INTO, UPDATE, DELETE FROM, and aggregate/function forms.
	 *
	 * @param string $query SQL statement.
	 *
	 * @return string Table name (without quotes or prefix).
	 */
	public function getTableName(string $query): string {
		$tableName = '';

		//remove subselects
		$query = $this->removeParanthesis($query, 'replace_subselect');
		//remove macros
		$count = 1;
		while ($count !== 0) {
			$query = preg_replace('/@(\w+)[^@]+?@\1/ms', 'replaced_macro', $query, -1, $count);
		}

		//avoid subselects with negative lookbehind for (
		$selectRegex   = '/(@[^\s]+\s*)?(?<!\()\s*SELECT.*?FROM\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$insertRegex   = '/(@[^\s]+\s*)?INSERT\s*INTO\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$updateRegex   = '/(@[^\s]+\s*)?UPDATE\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$deleteRegex   = '/(@[^\s]+\s*)?DELETE.*FROM\s*([`"]?\w+[`"]? AS \w+|[`"]?\w+[`"]?)/ims';
		$functionRegex = '/(@[^\s]+\s*)?(?<!\()SELECT.*?\w+\(\)\s*AS\s*[`"]?(\w+)[`"]?/ims';
		$countRegex    = '/(@[^\s]+\s*)?SELECT\s*count\(.*?\s*AS\s*[`"]?(\w+)[`"]?$/ims';

		if (preg_match($selectRegex, $query, $matches1) ||
			preg_match($insertRegex, $query, $matches1) ||
			preg_match($updateRegex, $query, $matches1) ||
			preg_match($deleteRegex, $query, $matches1) ||
			preg_match($functionRegex, $query, $matches1) ||
			preg_match($countRegex, $query, $matches1)) {
			$tableName = $matches1[2];

			if (preg_match('@`?\w+`?$@i', $tableName, $matches2)) {
				$tableName = $matches2[0];
			}
		}

		return trim($tableName, '`"\'');
	}

	/**
	 * Prefix all table references in a SQL statement with {@see $this->prefix}.
	 *
	 * @param string $query SQL statement.
	 *
	 * @return string SQL with prefixed table names.
	 */
	public function prefixTable(string $query): string {
		$regexs   = [];
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
				fn ($matches) => $matches[1] . $this->prefix . $matches[2],
				$query
			);
		}

		return $query;
	}

	/**
	 * Return the parameter-type character for a given SQL type name.
	 *
	 * @param string $type SQL type name (e.g. "int", "double").
	 *
	 * @return string Single-character type code (i / d / b / a / s).
	 */
	public function paramType(string $type): string {
		return $this->types[strtolower($type)] ?? 's';
	}

	/**
	 * Return the PHP expression that fetches the desired result shape.
	 *
	 * @param string|null $type One of 'insert_id', 'affected_rows', 'fetch_row', 'fetch_one',
	 *                          'fetch_all', or '@result.<key>'.
	 *
	 * @return string PHP expression string to embed in generated code.
	 */
	public function fetchType(?string $type): string {
		if (isset($type[0]) && $type[0] == '@') {
			$key = str_replace('@result.', '', $type);

			$return = $this->template($this->model['fetch_result'], ['key' => $key]);
		} else {
			$return = match ($type) {
				'insert_id'     => $this->model['insert_id'],
				'affected_rows' => $this->model['affected_rows'],
				'fetch_row'     => $this->model['fetch_row'],
				'fetch_one'     => $this->model['fetch_one'],
				default         => $this->model['fetch_all'],
			};
		}

		return trim($return);
	}

	/**
	 * Replace `%variable%` placeholders in a template string.
	 *
	 * @param string               $template  Template containing `%key%` placeholders.
	 * @param array<string, mixed> $variables Values to substitute.
	 *
	 * @return string Rendered string.
	 */
	public function template(string $template, array $variables): string {
		$keys = array_map(fn ($value) => "%$value%", array_keys($variables));

		$values = array_values($variables);

		return str_replace($keys, $values, $template);
		//remove unmatched variables
		//$result = preg_replace('/%\w+%/', '', $result);
	}

	/**
	 * Parse a single macro block against its regex and expand the template for every match.
	 *
	 * @param string                           $statement SQL statement containing macro tokens.
	 * @param array<int, array<string, mixed>> $params    Method parameters.
	 * @param string                           $regex     PREG pattern that captures macro placeholders.
	 * @param string                           $template  PHP code template to expand for each match.
	 *
	 * @return string Expanded SQL statement.
	 */
	public function parseMacro(string $statement, array $params, string $regex, string $template): string {
		$macro = $template;

		if (preg_match_all($regex, $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$macro = $template;

				//replace macro template variables %$variable
				$macro = preg_replace_callback(
					'@\$%(\w+)@',
					function($varMatch) use ($match) {
						return
						preg_replace_callback(
							$this->config['varRegex'],
							function($matches) {
								return '$' . \Vvveb\dotToArrayKey('params.' . $matches[1]);
								//return '$params[\'' . $matches[1] . '\']';
							},
							$match[$varMatch[1]]
						);
					},
					$macro
				);

				//replace macro template placeholders %placeholder
				$macro = preg_replace_callback(
					'@\%(\w+)@',
					fn ($varMatch) => $match[$varMatch[1]],
					$macro
				);

				$statement = str_replace($match[0], $macro, $statement);
			}
		}

		return $statement;
	}

	/**
	 * Expand `@EACH` variable macros into foreach loops within the generated PHP code.
	 *
	 * @param string                           $statement SQL statement containing @EACH tokens.
	 * @param array<int, array<string, mixed>> $params    Method parameters.
	 *
	 * @return string Statement with @EACH blocks expanded.
	 */
	public function parseEach(string $statement, array $params): string {
		//EACH VAR
		if (preg_match_all($this->config['eachVarRegex'], $statement, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$resultKey = $this->sqlPhpArrayKey($match[1]);

				$each = "\n" . self::TAB . 'if (isset($params' . $resultKey . ') && is_array($params' . $resultKey . '))' .
				'foreach ($params' . $resultKey . ' as $key => $rowParent) { ' . "\n" . self::TAB .
					' $params[\'each\'] = $rowParent;
					$params[\'each_key\'] = $key;
					if (is_array($params[\'each\'])) {
						$paramTypes[\'each\'] = \'a\';
					} else if (is_int($params[\'each\'])) {
						$paramTypes[\'each\'] = \'i\';
					} else {
						$paramTypes[\'each\'] = \'s\';
					}
					$sql = \'';

				$statement = str_replace('$sql = \'' . $match[0], $each, $statement) . "\n" . self::TAB . ' }
					unset($params[\'each\']);
					unset($paramTypes[\'each\']);
					';
			}
		}

		return $statement;
	}

	/**
	 * Parse all macro constructs in a SQL statement: variables, conditionals, result references, and filters.
	 *
	 * @param string                           $statement Raw SQL with macro tokens.
	 * @param array<int, array<string, mixed>> $params    Method parameters.
	 *
	 * @return string PHP code string ready for embedding in a generated method.
	 */
	public function parseMacros(string $statement, array $params): string {
		//replace variables
		$statement =
				preg_replace_callback(
					'/\$([\w_-]+)/',
					function($matches) {
						$key = $matches[1];

						return "' . (isset(\$params['$key']) ? \$params['$key'] : 'NULL') . '";
						//return "' . \$results['". $matches[1] . "'] . '";
					},
					$statement
				);

		$lex       = new Lexer($this->config['tokenMap'], $this->config['macroMap']);
		$structure = $lex->lex($statement);

		$output    = $lex->treeMacro($structure);
		$statement = $lex->treeToPhp($output, $this->config['macroMap']);

		/**
		 * @result
		 * replace result variables
		 */
		$statement =
					preg_replace_callback(
						'/@result\.([\w\.]+)/',
						function($matches) {
							$key = \Vvveb\dotToArrayKey('$results.' . $matches[1]);

							return "' . (isset($key) ? $key : 'NULL') . '";
						},
						$statement
					);

		return $this->processFilters($statement);
	}

	/**
	 * Process @FILTER directives: read column metadata for the referenced table,
	 * cache the filter definition and embed db->filter() calls in the generated code.
	 *
	 * @param string $statement Generated code containing @FILTER tokens.
	 *
	 * @return string Statement with filter processing embedded.
	 */
	private function processFilters(string $statement): string {
		if (!preg_match_all($this->config['filterRegex'], $statement, $matches, PREG_SET_ORDER)) {
			return $statement;
		}

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
				$column['n']   = ($column['n'] === 'NO') ? false : true;

				if (isset($column['d'])) {
					$column['d'] = trim($column['d'], '\'');
				}
				$filter[$name] = $column;
			}

			$return      = !empty($match['return']) ? $match['return'] : $match['data'];
			$return      = '$params' . $this->sqlPhpArrayKey($return);
			$key         = '$params' . $this->sqlPhpArrayKey($match['data']);
			$filterName  = '$this->filters[\'' . $this->prefix . $match['columns'] . '\']';

			$filterFunction                                   = '\';' . self::TAB;
			$this->filters[$this->prefix . $match['columns']] = $filter;

			if ($isArray === 'true') {
				$filterFunction .= 'if (isset(' . $key . ') && is_array(' . $key . ')) foreach ( ' . $key . ' as $key => &$filter) ' . $return . '[$key] = $this->db->filter($filter, ' . $filterName . ',' . $addMissingDefaults . ');' . self::TAB . '$sql = \'';
			} else {
				$filterFunction .= $return . '= $this->db->filter(' . $key . ', ' . $filterName . ',' . $addMissingDefaults . ');' . self::TAB . '$sql = \'';
			}

			$statement = str_replace($match[0], $filterFunction, $statement);
		}

		return $statement;
	}

	/**
	 * Parse the parameter declarations block of a stored-procedure definition.
	 *
	 * @param string $params Raw parameter text from the .sqlp file.
	 *
	 * @return list<array{in_out: string, name: string, type: string, length: int, comment?: string}>
	 */
	public function parseParameters(string $params): array {
		$parameters = [];

		if (preg_match_all($this->config['paramRegex'], $params, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$param           = [];
				$param['in_out'] = trim($match[1]);
				$param['name']   = trim($match[2]);

				$param['type'] = $param['length'] = '';

				if (isset($match[3])) {
					$param['type'] = trim($match[3]);
				}

				if (isset($match[4])) {
					$param['length'] = (int)preg_replace('/[^\d]/', '', $match[4]);
				}

				if (isset($match[5])) {
					$param['comment'] = trim($match[5], " \n\r\t\v\x00-");
				}

				$parameters[] = $param;
			}
		}

		return $parameters;
	}

	/**
	 * Resolve `import(…)` directives by replacing them with the contents of the referenced file.
	 *
	 * @param string $sql Raw SQL text.
	 *
	 * @return string SQL with imports inlined.
	 */
	public function processImports(string $sql): string {
		return preg_replace_callback($this->config['importRegex'], function($matches) {
			$file =  $matches[1];
			//absolute path
			if ($file[0] === '/') {
				$path = explode('/', substr($file, 1));
				$app  = $path[0];
				unset($path[0]);

				if ($app === 'plugins') {
					$plugin = $path[1] ?? '';
					unset($path[1]);
				} else {
					$plugin = '';
				}

				$file = implode('/', $path);
				$file = DIR_ROOT . $app . ($plugin ? '/' . $plugin : '') . '/sql/' . DB_ENGINE . '/' . $file;
			} else {//relative path
				$file = DIR_SQL . $matches[1];
			}

			if (file_exists($file)) {
				return file_get_contents($file);
			}

			return '';
		}, $sql);
	}

	/**
	 * Parse a `.sqlp` file and populate the internal method tree.
	 *
	 * @param string      $filename  Path to the `.sqlp` file.
	 * @param string|bool $modelName Optional explicit model name; when false it is derived from the filename.
	 * @param string      $namespace Optional namespace for the generated class.
	 *
	 * @return void
	 */
	public function parseSqlPfile(string $filename, string|bool $modelName = false, string $namespace = ''): void {
		if ($modelName) {
			$this->modelName = $modelName;
		} else {
			//strip .sql/.sqlp extension and camel-case underscored names
			$this->modelName = str_replace('.sql', '', preg_replace_callback(
				'/_(\w)/',
				fn ($m) => ucfirst($m[1]),
				basename($filename)
			));
		}

		$this->namespace = $namespace;
		$this->filename  = $filename;

		$sql = file_get_contents($filename);
		//process imports
		$sql = $this->processImports($sql);

		//remove comments
		$sql        = preg_replace('@(--.*)\s+@', '', $sql);
		$this->tree = [];

		if (preg_match_all($this->config['functionRegex'], $sql, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$method['name']      = trim($match['name'], '`"\'');
				//add slashes only for single quotes
				$method['statement'] = str_replace("'", "\'", trim($match['statement']));
				$method['statement'] = preg_replace('@(--.*)\s+@', '', $method['statement']);

				$method['params'] = $this->parseParameters($match['params']);

				//$this->tree[] = $method;
				//overwrite method if redefined
				$this->tree[$method['name']] = $method;
			}
		}
	}

	/**
	 * Return the parsed method tree.
	 *
	 * @return array<string, array{name: string, statement: string, params: list<array<string, mixed>>}>
	 */
	public function getModel(): array {
		return $this->tree;
	}

	/**
	 * Generate the complete PHP model source code from the parsed tree.
	 *
	 * @return string PHP class source code.
	 */
	public function generateModel(): string {
		$methods = '';

		if ($this->tree) {
			foreach ($this->tree as $i => $method) {
				$methods .= $this->generateMethod($method, $i);
			}
		}

		return $this->template($this->model['model'], [
			'name'       => ucfirst($this->modelName),
			'namespace'  => ucfirst($this->namespace),
			'filename'   => $this->filename,
			'methods'    => $methods,
			'filters'    => var_export($this->filters, true),
			'paramTypes' => var_export($this->paramTypes, true),
		]);
	}

	/**
	 * Generate the code for a single model method (one per stored procedure).
	 *
	 * @param array{name: string, statement: string, params: list<array<string, mixed>>} $method      Parsed method.
	 * @param string|int                                                                 $methodIndex Tree key of the method (its name); compared strictly against the OUT-parameter counter.
	 *
	 * @return string Generated method code.
	 */
	private function generateMethod(array $method, string|int $methodIndex): string {
		$statements = "/*{$this->modelName} - {$method['name']}*/\n\t\t";

		$queries         = explode(';', $method['statement']);
		$method['fetch'] = $this->fetchType('fetch_all');

		//OUT parameters select the fetch style of their corresponding query
		$fetch = [];

		foreach ($method['params'] as $param) {
			if ($param['in_out'] === 'OUT') {
				$fetch[] = $param['name'];
			}
		}

		foreach ($queries as $qIndex => $query) {
			$statement = '';
			$query     = $this->prefixTable(trim($query));

			if (empty($query)) {
				continue;
			}

			$template = $this->model['query'];

			$queryId    = preg_replace('/^' . $this->prefix . '/', '', $this->getTableName($query));
			$arrayKey   = $this->getQueryArrayKey($query);
			$arrayValue = $this->getQueryArrayValue($query);

			$statement .= $this->template(
				$template,
				[
					'statement'   => $this->parseMacros($query, $method['params']),
					'query_id'    => $queryId,
					'array_key'   => $arrayKey,
					'array_value' => $arrayValue,
				]
			);

			$statement = $this->parseEach($statement, $method['params']);
			$statement = $this->expandArrayParams($statement, $method['params']);

			//clean empty sql strings
			$statement = preg_replace('@\$sql \.= \'\s+\n\s*\';@ms', '$sql .= \' \';', $statement);
			$statement = preg_replace('@\s*\$sql \.= \'\';@ms', '', $statement);

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

		//collect typed IN parameters for bind-param
		$paramTypes = [];

		foreach ($method['params'] as $param) {
			if ($param['in_out'] === 'IN' && ($type = $this->paramType($param['type']))) {
				$paramTypes[$param['name']] = $type;
			}
		}

		$this->paramTypes[$method['name']] = $paramTypes;

		$method['param_types'] = '$this->paramTypes[\'' . $method['name'] . '\']';

		$method['fetch'] = $this->fetchType('fetch_all');

		//pick the OUT parameter matching the method index, else the first one
		$fetch = false;
		$o     = 0;

		foreach ($method['params'] as $param) {
			if ($param['in_out'] === 'OUT') {
				if (!$fetch) {
					$fetch = $param['name'];
				}

				if ($o === $methodIndex) {
					$fetch = $param['name'];

					break;
				}
				$o++;
			}
		}

		$method['fetch'] = $this->fetchType($fetch);

		$method['params'] = trim(implode(', ', array_map(
			function($param) {
				if ($param['in_out'] === 'IN') {
					return '$' . $param['name'];
				}
			},
			$method['params']
		)), ', ');

		return $this->template($this->model['methodMultipleTemplate'], $method);
	}

	/**
	 * Expand ARRAY-typed parameters into db->expandArray() calls embedded in the generated code.
	 *
	 * @param string                     $statement Generated statement code.
	 * @param list<array<string, mixed>> $params    Method parameters.
	 *
	 * @return string Statement with array<array-key, mixed> expansions injected.
	 */
	private function expandArrayParams(string $statement, array $params): string {
		foreach ($params as $param) {
			if (in_array(strtolower($param['type']), ['array', 'rows', 'row'], true)) {
				$expandArray = '\';' . self::TAB . ' list($_sql, $_params) = $this->db->expandArray($params[\'' . $param['name'] . '\'], \'' . $param['name'] . '\');'
								 . self::TAB . '$sql .= $_sql;'
								 . self::TAB . 'if (is_array($_params)) $paramTypes = array_merge($paramTypes, $_params);'
								 . self::TAB . '$sql .= \' ';

				$statement = preg_replace('@:' . $param['name'] . '(?!_\.) @', $expandArray, $statement);
			}
		}

		return $statement;
	}
}
