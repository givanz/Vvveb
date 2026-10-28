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

use function Vvveb\config;
use function Vvveb\isSecure;
use function Vvveb\reconstructJson;
use function Vvveb\siteSettings;
use Vvveb\System\Cache;
use Vvveb\System\Core\View;
use Vvveb\System\Db;
use Vvveb\System\Event;
use Vvveb\System\Functions\Plural;
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;
use function Vvveb\url;

#[\AllowDynamicProperties]
class Index extends Base {
	/**
	 * Map a database column type to an OpenAPI schema type.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function type(string $type): string {
		//strip length and modifiers so raw database types map correctly:
		//"varchar(191)" -> "varchar", "int unsigned" -> "int"
		$type = strtolower($type);
		$type = preg_replace('/[\s(].*$/', '', $type);

		switch ($type) {
			case 'int':
			case 'integer':
			case 'tinyint':
			case 'smallint':
			case 'smallinteger':
			case 'mediumint':
			case 'bigint':
			case 'serial':
			case 'bigserial':
				$type = 'integer';

				break;

			case 'decimal':
			case 'float':
			case 'double':
			case 'real':
				$type = 'number';

				break;

			case 'boolean':
			case 'bool':
				$type = 'boolean';

				break;

			case 'array':
				$type = 'array';

				break;

			case 'varchar':
			case 'char':
			case 'text':
			case 'tinytext':
			case 'longtext':
			case 'enum':
			case 'date':
			case 'datetime':
			case 'timestamp':
			case 'json':
				$type = 'string';

				break;

			default:
				//unknown types fall back to string so one exotic column
				//can never poison the generated specification
				$type = 'string';

				break;
		}

		return $type;
	}

	/**
	 * Build OpenAPI paths from database tables and route configurations.
	 *
	 * @return array<string, mixed>
	 */
	public function paths(): array {
		$routes = config('rest-routes');
		$paths  = [];
		$sqlp   = new Sqlp();
		$dirSQL = DIR_ROOT . 'admin' . DS . 'sql' . DS . DB_ENGINE . DS;

		$routeSchemas = [];

		foreach ($routes as $route => $options) {
			if (isset($options['schema'])) {
				$routeSchemas[$options['schema']] = $options['schema'];
			}
		}

		$routeSchemas = array_values($routeSchemas);

		$this->tables = [];
		$db           = Db::getInstance();
		$tables       = $db->getTableNames(DB_NAME);
		$routesNew    = [];

		foreach ($tables as $table) {
			$cols = [];
			$meta = $db->getColumnsMeta($table, true);

			foreach ($meta as $col) {
				$cols[$col['name']] = $col;
			}
			$this->tables[$table] = $cols;

			if (!in_array($table, $routeSchemas)) {
				if (file_exists($dirSQL . $table . '.sql')) {
					$plural                                              = Plural::tablePlural($table);
					$routes['/rest/' . $plural]                          = ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => $table];
					$routes['/rest/' . $plural . '/#' . $table . '_id#'] = ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => $table];
				}
			}
		}

		foreach ($routes as $route => $options) {
			$route   = str_replace(['/rest', '/#', '#'], ['', '/{', '}'], $route);
			$data    = [];
			$methods = $options['methods'] ?? ['get'];
			$actions = ['get' => 'get', 'post' => 'add', 'patch' => 'edit', 'put' => 'edit', 'delete' => 'delete'];

			foreach ($methods as $method) {
				$getParams = [];

				if (preg_match_all('/#([a-zA-Z]\w+)#/', $route, $matches)) {
					foreach ($matches[1] as $match) {
						$getParams[$match] = ['name' => $match, 'in' => 'path', 'schema' => ['type' => 'integer']];
					}
				}

				if (preg_match_all('/{(\w+)}/', $route, $matches)) {
					foreach ($matches[1] as $match) {
						$getParams[$match] = ['name' => $match, 'in' => 'path', 'schema' => ['type' => 'string']];
					}
				}

				$meth              = ['responses' => []];
				$ok                = [
					'description' => 'OK',
				];

				$params   = [];
				$encoding = [];

				if (isset($options['schema'])) {
					$ok['content']  = [
						'application/json' => [
							'schema' => [
								'$ref' => '#/components/schemas/' . $options['schema'],
							],
						],
					];

					$sqlp->parseSqlPfile($dirSQL . $options['schema'] . '.sql');
					$tree = $sqlp->getModel();

					$action = $actions[$method];

					if ($method == 'get' && !isset($methods['delete'])) {
						$action = 'getAll';
					}

					if (($tree && isset($tree[$action])) &&
						($method != 'get' || ($method == 'get' && !$getParams))) {
						foreach ($tree[$action]['params'] as $p) {
							if ($p['in_out'] == 'OUT') {
								continue;
							}

							$param  = [];

							if ($method == 'post') {
								$name                  = $p['name'];

								if ($name == $options['schema'] . '_id') {
									continue;
								}

								$params[$name]['type'] = $this->type($p['type'] ?? 'integer');

								if ($params[$name]['type'] == 'array' && isset($this->tables[$name])) {
									$encoding[$name]['explode']    = true;
									$encoding[$name]['style']      = 'deepObject';
									$params[$name]['type']         = 'object';
									$params[$name]['style']        = 'deepObject';
									$params[$name]['explode']      = true;

									$properties = [];

									foreach ($this->tables[$name] as $col) {
										if ($col['name'] == $options['schema'] . '_id') {
											continue;
										}

										//skip auto increment
										if ((isset($col['e']) && $col['e'] == 'auto_increment')) {
											continue;
										}
										$properties[$col['name']] = ['type' => $this->type($col['t'])];

										if ($col['d'] === null || $col['d'] == 'NULL') {
											$properties[$col['name']]['required'] = true;
										} else {
											$properties[$col['name']]['required'] = false;
										}
									}

									if (in_array($name, ['post_content', 'product_content'])) {
										$params[$name]['type']                = 'array';
										$params[$name]['items']['type']       = 'object';
										$params[$name]['items']['explode']    = true;
										$params[$name]['items']['style']      = 'deepObject';
										$params[$name]['items']['properties'] = $properties;
										$encoding[$name]['items']['explode']  = true;
										$encoding[$name]['items']['style']    = 'deepObject';
									} else {
										$params[$name]['properties'] = $properties;
									}
								}

								if ($params[$name]['type'] == 'array' && substr_compare($name, '_id', -3, 3) === 0) {
									$params[$name]['items']['type']       = 'integer';
									$params[$name]['style']               = 'deepObject';
									$params[$name]['explode']             = true;
									$encoding[$name]['items']['explode']  = true;
									$encoding[$name]['items']['style']    = 'deepObject';
									$encoding[$name]['explode']           = true;
									$encoding[$name]['style']             = 'deepObject';
								}
							} else {
								$param['name']           = $p['name'];
								$param['in']             = 'query';
								$param['required']       = false;
								$param['description']    = $p['comment'] ?? '';
								$param['schema']['type'] = $this->type($p['type'] ?? 'integer');

								if ($param['schema']['type'] == 'array' && isset($this->tables[$param['name']])) {
									$param['schema']['$ref'] = '#/components/schemas/' . $param['name'];

									$properties              = [];
									$param['explode']        = true;
									$param['style']          = 'deepObject';
									$param['schema']['type'] = 'object';

									foreach ($this->tables[$param['name']] as $col) {
										$properties[$col['name']] = ['type' => $this->type($col['t'])];

										if ($col['d'] === null || $col['d'] == 'NULL') {
											$properties[$col['name']]['required'] = true;
										} else {
											$properties[$col['name']]['required'] = false;
										}
									}

									if ($param['name'] == 'post_content') {
										$param['schema']['type']                = 'array';
										$param['schema']['items']['type']       = 'object';
										$param['schema']['items']['explode']    = true;
										$param['schema']['items']['style']      = 'deepObject';
										$param['schema']['items']['properties'] = $properties;
									} else {
										$param['schema']['properties'] = $properties;
									}
								}

								if (!isset($getParams[$param['name']])) {
									$getParams[$param['name']] = $param;
								}
							}
						}
					}

					if ($method == 'post') {
						$meth['requestBody']  = [
							'content' => [
								'application/x-www-form-urlencoded' => [
									'encoding' => $encoding,
									'schema'   => [
										'type'       => 'object',
										'properties' => $params,
									],
								],
							],
						];
					}

					if ($getParams) {
						$meth['parameters'] = array_values($getParams);
					}
				}

				$meth['responses']['200'] = $ok;

				$data[$method] = $meth;
			}

			$paths[$route] = $data;
		}

		[$paths] = Event :: trigger(__CLASS__, __FUNCTION__, $paths);

		return $paths;
	}

	/**
	 * Build OpenAPI schemas from database table metadata.
	 *
	 * @return array<string, mixed>
	 */
	public function schemas(): array {
		$db = \Vvveb\System\Db::getInstance();

		$tables  = $db->getTableNames(DB_NAME);
		$schemas = [];

		foreach ($tables as $table) {
			$meta = $db->getColumnsMeta($table, true);
			$cols = [];

			foreach ($meta as $column) {
				$rawType            = strtolower($column['t'] ?? 'integer');
				$col                = [];
				$col['name']        = $column['name'] ?? '';
				$col['description'] = $column['c'] ?? '';
				$col['type']        = $this->type($rawType);

				if ($rawType === 'datetime' || $rawType === 'timestamp' || $rawType === 'date') {
					$col['format'] = 'date-time';
				}

				//n holds IS_NULLABLE on mysqli/pgsql ('YES'/'NO') and NOT NULL flag on sqlite (1/0)
				$nullable = $column['n'] ?? null;

				if ($nullable === 'YES' || $nullable === 0 || $nullable === '0') {
					$col['type'] = [$col['type'], 'null'];
				}

				$cols[$column['name']] = $col;
			}

			$data = [
				'title'      => $table,
				'type'       => 'object',
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'properties' => $cols,
			];

			$schemas[$table] = $data;
		}

		[$schemas] = Event :: trigger(__CLASS__, __FUNCTION__, $schemas);

		return $schemas;
	}

	/**
	 * Get a list of all controller JSON files for the REST app.
	 *
	 * @param string $app
	 *
	 * @return array<int, string>
	 */
	public static function getControllerList(string $app = APP): array {
		$files = [];
		$path  = [DIR_ROOT . $app . '/controller/*', DIR_PLUGINS . '*/' . $app . '/controller/*'];

		while (count($path) > 0) {
			$next = array_shift($path);

			foreach (glob("$next/*.json") ?: [] as $file) {
				if (is_dir($file)) {
					$path[] = $file;
				}

				if (is_file($file)) {
					$files[] = $file;
				}
			}
		}

		[$files] = Event :: trigger(__CLASS__, __FUNCTION__, $files);

		return $files;
	}

	/**
	 * Render the OpenAPI JSON template and return it as an array.
	 *
	 * @return array<string, mixed>
	 */
	public function renderJson(): array {
		$htmlView  = new View();
		$htmlView->setTheme();
		$htmlView->template('openapi.json');
		$xml   = $htmlView->render(true, false, true);

		if ($xml) {
			$sxml  = \simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_DTDATTR);

			return reconstructJson($sxml, true);
		}

		return [];
	}

	/**
	 * Generate and output the full OpenAPI specification.
	 *
	 * @return void
	 */
	public function api(): void {
		$cache     = Cache::getInstance();
		$json      = $cache->cache(APP, 'openapi', function() {
			$site = siteSettings();
			$data = Sites :: getSiteData();

			$json                          = $this->renderJson();
			$json['paths']                 = $this->paths();
			$json['components']['schemas'] = $this->schemas();

			$site       = siteSettings(SITE_ID, $this->global['language_id']);
			$urlParams  = ['host' => $data['host'], 'scheme' => isSecure() ? 'https' : 'http'];
			$url        = url('/', $urlParams);

			$json['info']['title']            = $site['description']['title'];
			$json['info']['contact']['email'] = $site['contact-email'];
			$json['info']['contact']['url']   = $url;
			$json['servers'][0]['url']        = url('', $urlParams);

			return $json;
		}, 259200);

		$this->response->setType('json');
		$this->response->output($json);
	}

	/**
	 * Main index action - delegates to api().
	 *
	 * @return void
	 */
	public function index(): void {
		$this->api();
	}
}
