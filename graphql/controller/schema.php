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

namespace Vvveb\Controller;

use function Vvveb\dashesToCamelCase;
use function Vvveb\humanReadable;
use function Vvveb\siteSettings;
use Vvveb\System\Cache;
use Vvveb\System\Event;
use Vvveb\System\Functions\Plural;
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;

#[\AllowDynamicProperties]
class Schema extends Base {
	private $models = [];

	private $tables = [];

	private $tableNames = [];

	function type($type) {
		$compare = strtolower($type);

		switch ($compare) {
			case 'int':
			case 'tinyint':
			case 'smallint':
			case 'double':
			case 'array':
				$type = 'Int';

				break;

			case 'decimal':
				$type = 'Float';

				break;

			case 'array':
				$type = '[Int]';

				break;

			case 'varchar':
			case 'char':
			case 'text':
			case 'enum':
			case 'date':
			case 'datetime':
			case 'timestamp':
			case 'longtext':
			case 'tinytext':
				$type = 'String';

				break;
		}

		return $type;
	}

	function types() {
		$db = \Vvveb\System\Db::getInstance();

		$tables  = $db->getTableNames(DB_NAME);
		$types   = include(DIR_APP . 'types.php') ?? [];

		foreach ($tables as $table) {
			//skip cart table
			if ($table == 'cart') {
				continue;
			}
			$meta = $db->getColumnsMeta($table, true);
			$cols = [];

			foreach ($meta as $column) {
				$name               = $column['name'] ?? '';
				$col                = [];
				$col['name']        = $name;
				$col['description'] = $column['c'] ?? '';

				//column is id (input) type
				if (substr_compare($name, '_id', -3 ,3) === 0) {
					$type     = 'ID';
					$relation = substr($name,0, -3);

					if ($relation != $table && strpos($table, $relation) === 0) {
						$relationType                           =  dashesToCamelCase($table, '_') . 'Type';
						$typeTable                              = $table;
						$pos                                    = strpos($typeTable, $relation . '_');

						if ($pos === 0) {
							$typeTable = substr_replace($typeTable, '', $pos, strlen($relation . '_'));

							$pos = strpos($typeTable, 'to_');

							if ($pos === 0) {
								$typeTable    = substr_replace($typeTable, '', $pos, 3);
								$relationType =  dashesToCamelCase($typeTable, '_') . 'Type';
							}

							$typeTable = Plural::tablePlural($typeTable);
						}

						if (! isset($types[$relation]['properties'][$typeTable])) {
							$types[$relation]['properties'][$typeTable] = ['name' => $table, 'type' => "[$relationType]"];
						}
					}
				} else {
					if (in_array($name, $tables)) {
						$type = dashesToCamelCase($name, '_') . 'Type'; //. 'Input';
					} else {
						$type = $this->type($column['t'] ?? 'Int');
					}
				}

				$col['type'] = $type;

				$cols[$column['name']] = $col;
			}

			if ($table == 'product_option' || $table == 'product_option_value') {
				$cols += [
					'name' => ['name' => 'name', 'type' => 'string'],
				];
			}

			$data = [
				'name'       => $table,
				'properties' => $cols,
			];

			//$types[$table] = isset($types[$table]) ? array_replace_recursive($types[$table], $data) : $data;
			if (isset($types[$table]['properties'])) {
				$data['properties'] += $types[$table]['properties'];
			}

			$types[$table] = $data;

			//for content table also add name and content columns to relation
			if (substr_compare($table, '_content', -8 ,8) === 0) {
				$relation                                  = substr($table, 0,  -8);
				$types[$relation]['properties']['name']    = ['type' => 'char', 'name' => 'name'];
				$types[$relation]['properties']['content'] = ['type' => 'char', 'name' => 'content'];
				$types[$relation]['properties']['slug']    = ['type' => 'char', 'name' => 'slug'];
			}
		}

		//$types = array_replace($types, include(DIR_APP . 'types.php'));

		list($types) = Event :: trigger(__CLASS__,__FUNCTION__, $types);

		return $types;
	}

	function models() {
		$dirSQL = DIR_ROOT . 'admin' . DS . 'sql' . DS . DB_ENGINE . DS;
		$sqlp   = new Sqlp();
		$models = [];

		$files = glob("$dirSQL*.sql");

		foreach ($files as $file) {
			$sqlp->parseSqlPfile($file);
			$tree  = $sqlp->getModel();
			$model = basename($file, '.sql');
			//skip cart table
			if ($model == 'cart') {
				continue;
			}

			foreach ($tree as $method => $options) {
				$models[$model][$method] = $options['params'];
			}
		}

		list($models) = Event :: trigger(__CLASS__,__FUNCTION__, $models);

		return $models;
	}

	function schema() {
		$typesText = '';
		$types     = $this->types();

		foreach ($types as $table => $attrs) {
			$properties = '';

			foreach ($attrs['properties'] as $name => $property) {
				if (substr_compare($name, '_id', -3 ,3) === 0) {
					$type = 'ID';
				} else {
					$type = $this->type($property['type'] ?? 'Int');
				}

				if (GRAPHQL_CAMELCASE) {
					$name = lcfirst(dashesToCamelCase($name, '_'));
					$type = dashesToCamelCase($type, '_');
				}

				$properties .= "  $name: $type\n";
			}

			$tableName = dashesToCamelCase($table, '_');
			$typesText .= "type {$tableName}Type {\n$properties}\n\n";
		}

		$models    = $this->models();
		$queries   = [];
		$mutations = [];

		$queriesText     = '';
		$mutationsText   = '';
		$connectionsText = '';

		foreach ($models as $model => $methods) {
			$modelComment   =  "\n\n  # " . humanReadable($model) . "\n";
			$mutationsText .= $modelComment;
			$queriesText .= $modelComment;

			if ($model == 'category' || $model == 'filter' || $model == 'stat') {
				continue;
			}

			foreach ($methods as $method => $params) {
				$methodName = lcfirst(dashesToCamelCase($method . '_' . $model, '_'));
				$parameters = '';

				foreach ($params as $name => $param) {
					if ($param['in_out'] == 'IN') {
						$name = $param['name'];
						$type = $this->type($param['type'] ?? 'Int');

						if (substr_compare($name, '_id', -3 ,3) === 0) {
							if ($param['type'] == 'ARRAY') {
								$type = '[ID]';
							} else {
								$type = 'ID';
							}
						}

						if (GRAPHQL_CAMELCASE) {
							$name = lcfirst(dashesToCamelCase($name, '_'));
							$type = dashesToCamelCase($type, '_');
						}

						$parameters .= "$name: $type, ";
					}
				}

				$parameters = rtrim($parameters, ', ');
				$type       = dashesToCamelCase($model, '_');

				if (strpos($method, 'get') === 0) {
					if ($model == 'category' || $model == 'filter' || $model == 'stat') {
						//$type = 'Taxonomy';
					}

					$return     = $type . 'Type';
					$methodText = "  $methodName($parameters):{$return}\n";

					if (strpos($method, 'getAll') === 0) {
						$return      = "[{$type}Type]";
						$modelPlural = lcfirst(dashesToCamelCase(Plural::tablePlural($model), '_'));
						$methodText  = "  $modelPlural($parameters):{$type}Connection\n";

						$connectionsText .= $modelComment .
						"type {$type}Connection {\n  nodes:[{$type}Type!]!\n  pageInfo:PageInfo!\n}\n";
					}

					if ($method == 'get') {
						$modelName   = lcfirst(dashesToCamelCase($model, '_'));
						$methodText  = "  $modelName($parameters):{$return}\n";
					}

					$queriesText .= $methodText;
				} else {
					$methodText = "  $methodName($parameters):ID\n";

					$mutationsText .= $methodText;
				}
			}
		}

		$cache     = Cache::getInstance();
		$json      = $cache->cache(APP,'graphql-schema',function () {
			$site = siteSettings();
			$data = Sites :: getSiteData();

			$json = [];
			$json['types'] = $this->types();

			return $json;
		}, 259200);

		$text = <<<GQL
type PageInfo {
  count: Int
  page: Int
  limit: Int
  endCursor: String
  hasNextPage: Boolean!
  hasPreviousPage: Boolean!
  startCursor: String
}
		
$connectionsText

$typesText

type RootQueryType {
	
  cart(cartId:ID!):CartType

$queriesText
}

type MutationType {

  createCart:CartType 
  addCart(cartId:ID!, productId:ID!, quantity:Int, options:String, productVariantId:ID, subscriptionPlanId:ID):CartType 
  updateCart(cartId:ID!, key:String!, quantity:Int!, options:String, productVariantId:ID, subscriptionPlanId:ID):CartType 
  removeCart(cartId:ID!, key:[String!]!):CartType 

$mutationsText
}

schema {
  query:RootQueryType
  mutation:MutationType
}
GQL;

		return $text;
	}

	function index() {
		$text = $this->schema();
		$this->response->setType('text');
		$this->response->output($text);
	}
}
