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

use function \Vvveb\arrayKeysToCamelCase;
use function \Vvveb\arrayKeysToUnderscore;
use function \Vvveb\camelToUnderscore;
use function \Vvveb\controller;
use function \Vvveb\isController;
use function \Vvveb\isModel;
use function \Vvveb\model;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use Vvveb\System\Event;

class PostConnection {
	public $nodes;

	public $PageInfo = [];
}

#[\AllowDynamicProperties]
class Index extends Base {
	protected $stack = [];

	protected $queryIndex = -1;

	//protected $currentQuery = -1;
	protected $stackIndex = 0;

	protected $schema = [];

	protected $args = [];

	protected $returnIsList = false;

	protected $queries = [];

	protected $mutations = [];

	protected $types = [];

	protected $transforms = [
		'post'    => 'Vvveb\Transform\Post',
		'product' => 'Vvveb\Transform\Product',
	];

	function queries() {
		list($this->queries) = Event::trigger(__CLASS__, __FUNCTION__, $this->queries);
	}

	function mutations() {
		list($this->mutations) = Event::trigger(__CLASS__, __FUNCTION__, $this->mutations);
	}

	function types() {
		list($this->types) = Event::trigger(__CLASS__, __FUNCTION__, $this->types);
	}

	function transforms() {
		list($this->transforms) = Event::trigger(__CLASS__, __FUNCTION__, $this->transforms);
	}

	function init() {
		$this->types();
		$this->queries();
		$this->mutations();
		$this->transforms();

		return parent::init();
	}

	function cascadeOptions() {
	}

	function model($typeName, $isConnection = false) {
		$page      = 1;
		$limit     = 10;
		$data      = [];
		$typeName  = camelToUnderscore($typeName, '_');
		$options   = $this->args + $this->global;

		if (! isModel($typeName, 'admin')) {
			return $data;
		}

		$type = model($typeName);

		if ($type) {
			$transform = null;

			if (isset($this->transforms[$typeName])) {
				$class     = $this->transforms[$typeName];
				$transform = new $class();
			}

			if ($this->returnIsList || $isConnection) {
				$data = $type->getAll($options);
				$data = $data[$typeName] ?? [];

				if ($transform) {
					error_log(print_r($options, 1));
					$data = $transform->getAll($data, $options);
				}
			} else {
				$data = $type->get($options);

				if ($transform) {
					$data = $transform->get($data, $options);
				}
			}
		} else {
			throw new \Exception('Model does not exist!');
		}

		if ($isConnection) {
			$pageInfo = [
				'count'           => $return['count'] ?? 0,
				'page'            => $page,
				'limit'           => $limit,
				'hasNextPage'     => true,
				'hasPreviousPage' => true,
				'startCursor'     => 1,
			];

			$data = ['nodes' => $data, 'pageInfo' => $pageInfo];
		}

		return $data;
	}

	function controller($typeName, $args, $rootType = 'mutation') {
		//check if has method
		if (preg_match('/^(\w+)([A-Z]\w+)/', $typeName, $matches)) {
			$method = $matches[1];
			$class  = $matches[2];
		} else {
			$method = 'index';
			$class  = $typeName;
		}

		$classFile = $rootType . '/' . $class;
		$className = $rootType . '\\' . $class;

		$data = [];

		if (isController($classFile)) {
			$controller = controller($className);

			if ($controller) {
				if (method_exists($controller, $method)) {
					$data = $controller->$method($args + $this->global);
				} else {
					throw new \Exception('Controller method does not exist!');
				}
			} else {
				throw new \Exception('Controller can\'t be loaded!');
			}
		} else {
			$class = strtolower(camelToUnderscore($class,'_'));

			if (isModel($class)) {
				$class = model($class);

				if ($class) {
					if (method_exists($class, $method)) {
						$data = $class->$method($args + ['site_id' => 1]);
					} else {
						throw new \Exception('Model method does not exist!');
					}
				} else {
					throw new \Exception('Model can\'t be loaded!');
				}
			} else {
				//throw new \Exception('Model does not exist!');
			}
		}

		return $data;
	}

	function defaultFieldResolver($objectValue, $args, $context, ResolveInfo $info) {
		$fieldName           = $info->fieldName;

		if (is_null($fieldName)) {
			throw new \Exception('Could not get $fieldName from ResolveInfo');
		}

		if (is_null($info->parentType)) {
			throw new \Exception('Could not get $parentType from ResolveInfo');
		}

		$parentTypeName = $info->parentType->name; //MutationType
		$typeName       = $info->fieldDefinition->config['type']->name ?? '';

		$returnType         = '';
		$this->returnIsList = false;
		$isConnection       = false;
		$this->args         = $args;

		if (is_array($objectValue)) {
			$this->args += $objectValue;
		}

		if (GRAPHQL_CAMELCASE && is_array($this->args)) {
			$this->args = arrayKeysToUnderscore($this->args, '_');
		}

		if ($info->returnType) {
			//method_exists($info->returnType, 'getInnermostType')
			if (is_a($info->returnType, 'GraphQL\Type\Definition\ListOfType')) {
				$returnType         = $info->returnType->getInnermostType()->name;
				$this->returnIsList = true;
			} else {
				$returnType = $info->returnType->name ?? '';
			}
		}
		/*
				error_log('$returnType = ' . $returnType);
				error_log('$objectValue = ' . print_r($objectValue, 1));
				error_log('$fieldName = ' . $fieldName);
				error_log('$parentTypeName = ' . $parentTypeName);
				error_log('$typeName = ' . $typeName);
				error_log('$args = ' . print_r($this->args, 1));
		*/
		$method     = '';
		$type       = '';
		$permission = $fieldName;

		if (preg_match('/^([a-z]+)([A-Z]\w+)/', $fieldName, $matches)) {
			$method     = $matches[1];
			$type       = strtolower(camelToUnderscore($matches[2],'_'));
			$permission = "$type/$method";
		}

		$this->permission($permission);
		$data = [];

		//if previous controller or model call already has the data then don't try to retrive it again
		if (isset($this->stack[$this->queryIndex][$this->stackIndex - 1]['data'][$fieldName])) {
			$data =  $this->stack[$this->queryIndex][$this->stackIndex - 1]['data'][$fieldName];
		}

		//if object already has the data then don't try to retrive it again
		if (isset($objectValue[$fieldName])) {
			$data =  $objectValue[$fieldName];
		}

		if ($parentTypeName == 'RootQueryType') {
			$this->queryIndex++;

			if (isset($this->queries[$type])) {
				$fn   = $this->queries[$type];
				$data = $fn($parentTypeName, $fieldName, $this->args, $this->returnIsList, $isConnection);
			} else {
				$data = $this->controller($fieldName, $this->args, 'query');
			}
		} else {
			if ($parentTypeName == 'MutationType') {
				$data      = [];

				if (isset($this->mutations[$type])) {
					$fn   = $this->mutations[$type];
					$data = $fn($parentTypeName, $fieldName, $this->args, $this->returnIsList, $isConnection);
				} else {
					//crud
					//if (preg_match('/^(get|add|edit|delete)([A-Z]\w+)/', $typeName, $matches)) {
					$data = $this->controller($fieldName, $this->args, 'mutation');
				}
			}
		}

		if (! $data) {
			foreach ([$typeName, $returnType] as $type) {
				if (substr_compare($type, 'Type', -4 ,4) === 0) {
					//$typeName = strtolower(preg_replace('/Type$/', '', $typeName));
					$type = substr($type, 0, -4);

					if (isset($this->types[$type])) {
						$fn   = $this->types[$type];
						$data = $fn($parentTypeName, $typeName, $this->args, $this->returnIsList, $isConnection);
					} else {
						$data = $this->model($type);
					}

					break;
				} else {
					if (substr_compare($type, 'Connection', -10 ,10) === 0) {
						//$typeName = strtolower(preg_replace('/Type$/', '', $typeName));
						$isConnection = true;
						$type         = substr($type, 0, -10);

						if (isset($this->types[$type])) {
							$fn   = $this->types[$type];
							$data = $fn($parentTypeName, $typeName, $this->args, $this->returnIsList, $isConnection);
						} else {
							$data = $this->model($type, true);
						}

						break;
					}
				}
			}
		}

		if (GRAPHQL_CAMELCASE && is_array($data)) {
			$data = arrayKeysToCamelCase($data);
		}

		$this->stack[$this->queryIndex][$this->stackIndex++] = [
			'model'  => $typeName,
			'method' => $method,
			//'args'   => $this->args,
			'data'          => $data,
			'objectValue'   => $objectValue,
		];

		if ($data) {
			if ($isConnection) {
				return $data;
			} else {
				return $data;
			}
		}

		$property = null;

		if (is_array($objectValue) || $objectValue instanceof ArrayAccess) {
			if (isset($objectValue[$fieldName])) {
				$property = $objectValue[$fieldName];
			}
		} elseif (is_object($objectValue)) {
			if (isset($objectValue->{$fieldName})) {
				$property = $objectValue->{$fieldName};
			}
		}
		//error_log(print_r($property, 1));
		return $property instanceof Closure
		? $property($objectValue, $args, $contextValue, $info)
		: $property;
	}

	function index() {
		$rawInput = file_get_contents('php://input');

		$typeConfigDecorator = function (array $typeConfig, $typeDefinitionNode) : array {
			$name = $typeConfig['name'];
			//error_log($name . ' $typeConfigDecorator');
			if ($name == 'ObjectType') {
				//error_log(print_r($typeDefinitionNode, 1));

/*
$typeDefinitionNode = new ObjectType([
	'name' => 'PostConnection',
	'fields' => [
		'nodes' => [
			'type' => Type::listOf('PostType'),
			'resolve' => fn () => $data['nodes']
		],
		'pageInfo' => [
			'type' => 'PageInfo',
			'resolve' => fn () => $data['pageInfo']
		]
	]
]);			
 */
			}
			// ... add missing options to $typeConfig based on type $name
			return $typeConfig;
		};

		$cacheFilename = DIR_STORAGE . 'model' . DS . 'cached_schema.php';

		if (! file_exists($cacheFilename)) {
			$schemaController = new Schema();
			$schema           = $schemaController->schema(); //file_get_contents(DIR_APP . 'schema.gql');
			$document         = Parser::parse($schema);
			file_put_contents($cacheFilename, "<?php\nreturn " . var_export(AST::toArray($document), true) . ";\n");
		} else {
			$document = AST::fromArray(require $cacheFilename); // fromArray() is a lazy operation as well
		}

		//$typeConfigDecorator = function () {};
		//$schema = BuildSchema::build($document);//, $typeConfigDecorator);
		$this->schema = BuildSchema::build($document, $typeConfigDecorator);

		$input          = json_decode($rawInput, true);
		$query          = $input['query'] ?? '';
		$variableValues = $input['variables'] ?? null;

		try {
			$result    = GraphQL::executeQuery($this->schema, $query, null, null, $variableValues, null, [$this, 'defaultFieldResolver'] /*'Vvveb\Controller\defaultFieldResolver'*/);
			$flags     = 0;

			if (DEBUG) {
				$flags = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
			}
			$output    = $result->toArray($flags);

			//error_log(print_r($this->stack, 1));
		} catch (\Exception $e) {
			$message = $e->getMessage();

			if (DEBUG) {
				$message .= ' - ' . $e->getFile();
				$message .= ':' . $e->getLine();
				$message .= "\n" . $e->getTraceAsString();
			}

			$output = [
				'errors' => [
					[
						'message' => $message,
					],
				],
			];
		}

		return $output;
	}
}
