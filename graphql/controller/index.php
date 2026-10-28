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

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Language\Parser;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use function Vvveb\arrayKeysToCamelCase;
use function Vvveb\arrayKeysToUnderscore;
use function Vvveb\camelToUnderscore;
use function Vvveb\controller;
use function Vvveb\isController;
use function Vvveb\isModel;
use function Vvveb\model;
use Vvveb\System\Event;

class PostConnection {
	/** @var mixed */
	public mixed $nodes = null;

	/** @var array<string, mixed> */
	public array $PageInfo = [];
}

#[\AllowDynamicProperties]
class Index extends Base {
	/** @var array<int, array<int, array<string, mixed>>> */
	protected array $stack = [];

	/** @var int Current query index in the stack */
	protected int $queryIndex = -1;

	/** @var int Current stack index */
	protected int $stackIndex = 0;

	/** @var \GraphQL\Type\Schema|null GraphQL schema instance */
	protected mixed $schema = null;

	/** @var array<string, mixed> Current resolved arguments */
	protected array $args = [];

	/** @var bool Whether the current return type is a list */
	protected bool $returnIsList = false;

	/** @var array<string, callable> Custom query resolvers */
	protected array $queries = [];

	/** @var array<string, callable> Custom mutation resolvers */
	protected array $mutations = [];

	/** @var array<string, callable> Custom type resolvers */
	protected array $types = [];

	/** @var array<string, string> Transform class mappings */
	protected array $transforms = [
		'post'    => 'Vvveb\Transform\Post',
		'product' => 'Vvveb\Transform\Product',
	];

	/**
	 * Register custom query resolvers via event trigger.
	 *
	 * @return void
	 */
	public function queries(): void {
		[$this->queries] = Event::trigger(__CLASS__, __FUNCTION__, $this->queries);
	}

	/**
	 * Register custom mutation resolvers via event trigger.
	 *
	 * @return void
	 */
	public function mutations(): void {
		[$this->mutations] = Event::trigger(__CLASS__, __FUNCTION__, $this->mutations);
	}

	/**
	 * Register custom type resolvers via event trigger.
	 *
	 * @return void
	 */
	public function types(): void {
		[$this->types] = Event::trigger(__CLASS__, __FUNCTION__, $this->types);
	}

	/**
	 * Register custom transforms via event trigger.
	 *
	 * @return void
	 */
	public function transforms(): void {
		[$this->transforms] = Event::trigger(__CLASS__, __FUNCTION__, $this->transforms);
	}

	/**
	 * Initialize the GraphQL controller by registering types, queries, mutations, and transforms.
	 *
	 * @return string|null
	 */
	public function init(): ?string {
		$this->types();
		$this->queries();
		$this->mutations();
		$this->transforms();

		return parent::init();
	}

	/**
	 * Placeholder for cascading options.
	 *
	 * @return void
	 */
	public function cascadeOptions(): void {}

	/**
	 * Retrieve model data by type name, optionally as a connection (paginated).
	 *
	 * @param string $typeName
	 * @param bool   $isConnection
	 *
	 * @return array<string, mixed>
	 */
	public function model(string $typeName, bool $isConnection = false): array {
		$data      = [];
		$typeName  = camelToUnderscore($typeName, '_');
		$options   = $this->args + $this->global;
		$limit     = max(1, (int)($options['limit'] ?? 10));
		$start     = max(0, (int)($options['start'] ?? 0));
		$page      = (int)floor($start / $limit) + 1;

		if (!isModel($typeName, 'admin')) {
			return $data;
		}

		$type = model($typeName);

		$transform = null;

		if (isset($this->transforms[$typeName])) {
			$class     = $this->transforms[$typeName];
			$transform = new $class();
		}

		if ($this->returnIsList || $isConnection) {
			$allData   = $type->getAll($options);
			$count     = $allData['count'] ?? 0;
			$data      = $allData[$typeName] ?? [];

			if ($transform) {
				$data = $transform->getAll($data, $options);
			}
		} else {
			$data = $type->get($options);

			if ($transform) {
				$data = $transform->get($data, $options);
			}
		}

		if ($isConnection) {
			$pageInfo = [
				'count'           => $count,
				'page'            => $page,
				'limit'           => $limit,
				'hasNextPage'     => ($page * $limit) < ($count),
				'hasPreviousPage' => $page > 1,
				'startCursor'     => 1,
			];

			$data = ['nodes' => $data, 'pageInfo' => $pageInfo];
		}

		return $data;
	}

	/**
	 * Resolve a controller or model method by type name.
	 *
	 * @param string               $typeName
	 * @param array<string, mixed> $args
	 * @param string               $rootType
	 *
	 * @return array<string, mixed>
	 */
	public function controller(string $typeName, array $args, string $rootType = 'mutation'): array {
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

			if (method_exists($controller, $method)) {
				$data = $controller->$method($args + $this->global);
			} else {
				throw new \Exception('Controller method does not exist!');
			}
		} else {
			$class = strtolower(camelToUnderscore($class, '_'));

			if (isModel($class)) {
				$class = model($class);

				if (method_exists($class, $method)) {
					$data = $class->$method($args + ['site_id' => $this->global['site_id']]);
				} else {
					//no matching model method, return empty and let the
					//caller fall through to type-based model resolution
					return [];
				}
			} else {
				//throw new \Exception('Model does not exist!');
			}
		}

		return $data;
	}

	/**
	 * Default field resolver for GraphQL queries and mutations.
	 *
	 * @param mixed                $objectValue
	 * @param array<string, mixed> $args
	 * @param mixed                $context
	 * @param ResolveInfo          $info
	 *
	 * @return mixed
	 */
	public function defaultFieldResolver(mixed $objectValue, array $args, mixed $context, ResolveInfo $info): mixed {
		$fieldName = $info->fieldName;

		$parentTypeName = $info->parentType->name; //MutationType
		$typeName       = $info->fieldDefinition->config['type']->name ?? '';

		$returnType         = '';
		$this->returnIsList = false;
		$isConnection       = false;
		$this->args         = $args;

		if (is_array($objectValue)) {
			$this->args += $objectValue;
		}

		if (GRAPHQL_CAMELCASE) {
			$this->args = arrayKeysToUnderscore($this->args, '_');
		}

		//method_exists($info->returnType, 'getInnermostType')
		if (is_a($info->returnType, 'GraphQL\Type\Definition\ListOfType')) {
			$returnType         = $info->returnType->getInnermostType()->name;
			$this->returnIsList = true;
		} else {
			$returnType = $info->returnType->name ?? '';
		}

		$method     = '';
		$type       = '';
		$permission = $fieldName;

		if (preg_match('/^([a-z]+)([A-Z]\w+)/', $fieldName, $matches)) {
			$method     = $matches[1];
			$type       = strtolower(camelToUnderscore($matches[2], '_'));
			$permission = "$type/$method";
		}

		$this->permission($permission);
		$data = [];

		//if previous controller or model call already has the data then don't try to retrieve it again
		if (isset($this->stack[$this->queryIndex][$this->stackIndex - 1]['data'][$fieldName])) {
			$data =  $this->stack[$this->queryIndex][$this->stackIndex - 1]['data'][$fieldName];
		}

		//if object already has the data then don't try to retrieve it again
		if (isset($objectValue[$fieldName])) {
			$data =  $objectValue[$fieldName];
		}

		if ($parentTypeName === 'RootQueryType') {
			$this->queryIndex++;

			if (isset($this->queries[$type])) {
				$fn   = $this->queries[$type];
				$data = $fn($parentTypeName, $fieldName, $this->args, $this->returnIsList, $isConnection);
			} else {
				$data = $this->controller($fieldName, $this->args, 'query');
			}
		} else {
			if ($parentTypeName === 'MutationType') {
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

		if (!$data && $parentTypeName !== 'MutationType') {
			//mutations that return no data must stay empty and never
			//fall through to an unrelated model read
			foreach ([$typeName, $returnType] as $type) {
				if (substr_compare($type, 'Type', -4, 4) === 0) {
					$type = substr($type, 0, -4);
					//$typeName = strtolower(preg_replace('/Type$/', '', $typeName));

					if (isset($this->types[$type])) {
						$fn   = $this->types[$type];
						$data = $fn($parentTypeName, $typeName, $this->args, $this->returnIsList, $isConnection);
					} else {
						$data = $this->model($type);
					}

					break;
				} else {
					if (substr_compare($type, 'Connection', -10, 10) === 0) {
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
			'data'        => $data,
			'objectValue' => $objectValue,
		];

		if ($data) {
			return $data;
		}

		$property = null;

		if (is_array($objectValue) || $objectValue instanceof \ArrayAccess) {
			if (isset($objectValue[$fieldName])) {
				$property = $objectValue[$fieldName];
			}
		} elseif (is_object($objectValue)) {
			if (isset($objectValue->{$fieldName})) {
				$property = $objectValue->{$fieldName};
			}
		}

		//error_log(print_r($property, 1));
		return $property instanceof \Closure
		? $property($objectValue, $args, $context, $info)
		: $property;
	}

	/**
	 * Main GraphQL index action: parse and execute the GraphQL query.
	 *
	 * @return array<string, mixed>
	 */
	public function index(): array {
		$rawInput = file_get_contents('php://input');

		$typeConfigDecorator = function(array $typeConfig, mixed $typeDefinitionNode): array {
			// ... add missing options to $typeConfig based on type $name
			return $typeConfig;
		};

		$cacheFilename = DIR_STORAGE . 'model/cached_schema.php';

		//regenerate at least weely so schema and model changes are picked up
		$regenerate = true;

		if (file_exists($cacheFilename)) {
			$cached     = require $cacheFilename;
			$regenerate = (($cached['generated'] ?? 0) + 604800) < time();

			if (!$regenerate) {
				$document = AST::fromArray($cached['schema']); // fromArray() is a lazy operation as well
			}
		}

		if ($regenerate) {
			$schemaController = new Schema();
			$schema           = $schemaController->schema(); //file_get_contents(DIR_APP . 'schema.gql');
			$document         = Parser::parse($schema);
			$exported         = var_export(['generated' => time(), 'schema' => AST::toArray($document)], true);
			//write to a temp file and rename, so concurrent requests never read a half written schema
			$tmp = $cacheFilename . '.' . getmypid() . '.tmp';
			file_put_contents($tmp, "<?php\nreturn " . $exported . ";\n");
			rename($tmp, $cacheFilename);
		}

		$input          = json_decode($rawInput, true);
		$query          = $input['query'] ?? '';
		$variableValues = $input['variables'] ?? null;

		try {
			$this->schema = BuildSchema::build($document, $typeConfigDecorator);

			$result    = GraphQL::executeQuery($this->schema, $query, null, null, $variableValues, null, [$this, 'defaultFieldResolver']);
			$flags     = 0;

			if (DEBUG) {
				$flags = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
			}
			$output    = $result->toArray($flags);
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
