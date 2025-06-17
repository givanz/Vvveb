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
use Vvveb\System\Event;
use Vvveb\System\Functions\Plural;
use Vvveb\System\Sites;
use Vvveb\System\Sqlp\Sqlp;
use function Vvveb\url;

#[\AllowDynamicProperties]
class Index extends Base {
	function type($type) {
		$type = strtolower($type);

		switch ($type) {
			case 'int':
			case 'tinyint':
				$type = 'integer';

				break;

			case 'varchar':
			case 'char':
			case 'datetime':
			case 'longtext':
				$type = 'string';

				break;
		}

		return $type;
	}

	function paths() {
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
		$db           = \Vvveb\System\Db::getInstance();
		$tables       = $db->getTableNames(DB_NAME);
		$routesNew    = [];

		foreach ($tables as $table) {
			$cols = [];
			$meta = $db->getColumnsMeta($table, true);

			foreach ($meta as $col) {
				$cols[$col['name']] = $col;
			}
			$this->tables[$table] = $cols;

			if (! in_array($table, $routeSchemas)) {
				if (file_exists($dirSQL . $table . '.sql')) {
					$plural                                              = Plural::tablePlural($table);
					$routes['/rest/' . $plural]                          = ['module' => 'default', 'methods' => ['get', 'post'], 'schema' => $table];
					$routes['/rest/' . $plural . '/#' . $table . '_id#'] = ['module' => 'default/crud/index', 'methods' => ['get', 'post', 'put', 'patch', 'delete'], 'schema' => $table];
				}
			}
		}
		//echo var_export($routesNew, true);

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

				//$responses
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

					if ($method == 'get' && ! isset($methods['delete'])) {
						$action = 'getAll';
					}

					if (($tree && isset($tree[$action])) &&
						($method != 'get' || ($method == 'get' && ! $getParams))) {
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
									$encoding[$name]['explode'] = true;
									$encoding[$name]['style']   = 'deepObject';
									//$encoding[$name]['contentType']   = 'application/x-www-form-urlencoded';
									$params[$name]['type']         = 'object';
									$params[$name]['style']        = 'deepObject';
									$params[$name]['explode']      = true;
									//$params[$name]['contentEncoding']      = 'text/plain';

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

										if ($col['d'] === NULL || $col['d'] == 'NULL') {
											$properties[$col['name']]['required'] = true;
										} else {
											$properties[$col['name']]['required'] = false;
										}
									}

									if (in_array($name,['post_content', 'product_content'])) {
										$params[$name]['type']                = 'array';
										$params[$name]['items']['type']       = 'object';
										$params[$name]['items']['explode']    = true;
										$params[$name]['items']['style']      = 'deepObject';
										$params[$name]['items']['properties'] = $properties;
										$encoding[$name]['items']['explode']  = true;
										$encoding[$name]['items']['style']    = 'deepObject';
									//$encoding[$name]['items']['type']   = 'object';
										//$encoding[$name]['type']   = 'array';
									} else {
										$params[$name]['properties'] = $properties;
									}
								}

								if ($params[$name]['type'] == 'array' && substr_compare($name, '_id', -3 ,3) === 0) {
									$params[$name]['items']['type'] = 'integer';
									//$params[$name]['items']['explode'] = true;
									$params[$name]['style']               = 'deepObject';
									$params[$name]['explode']             = true;
									$encoding[$name]['items']['explode']  = true;
									$encoding[$name]['items']['style']    = 'deepObject';
									$encoding[$name]['explode']           = true;
									$encoding[$name]['style']             = 'deepObject';
									//$encoding[$name]['items']['style']    = 'deepObject';
								}
							} else {
								$param['name']           = $p['name'];
								$param['in']             = 'query';
								$param['required']       = 'false';
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

										if ($col['d'] === NULL || $col['d'] == 'NULL') {
											$properties[$col['name']]['required'] = true;
										} else {
											$properties[$col['name']]['required'] = false;
										}
									}

									//$param['name'] .= '[]';

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

								if (! isset($getParams[$param['name']])) {
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
										'type'        => 'object',
										'properties'  => $params,
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

		list($paths) = Event :: trigger(__CLASS__,__FUNCTION__, $paths);

		return $paths;
		$this->response->setType('json');
		$this->response->output($paths);
	}

	function schemas() {
		$db = \Vvveb\System\Db::getInstance();

		$tables  = $db->getTableNames(DB_NAME);
		$schemas = [];

		foreach ($tables as $table) {
			$meta = $db->getColumnsMeta($table, true);
			$cols = [];

			foreach ($meta as $column) {
				$col                = [];
				$col['name']        = $column['name'] ?? '';
				$col['description'] = $column['c'] ?? '';
				$col['type']        = $this->type($column['t'] ?? 'integer');

				switch ($col['type']) {
						case 'datetime':
							$col['format'] = 'date-time';
					}

				if (isset($column['c']) && $column['c'] == 'YES') {
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

		//var_export($schemas);
		list($schemas) = Event :: trigger(__CLASS__,__FUNCTION__, $schemas);

		return $schemas;
		$this->response->setType('json');
		$this->response->output($schemas);
	}

	public static function getControllerList($app = APP) {
		$files = [];
		$path  = [DIR_ROOT . $app . '/controller/*', DIR_PLUGINS . '*/' . $app . '/controller/*'];

		while (count($path) > 0) {
			$next = array_shift($path);
			//echo $next . "\n";
			foreach (glob("$next/*.json") as $file) {
				if (is_dir($file)) {
					$path[] = $file;
				}

				if (is_file($file)) {
					$files[] = $file;
				}
			}
		}

		list($files) = Event :: trigger(__CLASS__,__FUNCTION__, $files);

		return $files;
	}

	function renderJson() {
		$htmlView  = new View();
		//$htmlView  = clone $this->view;
		$htmlView->setTheme();
		$htmlView->set(['seo' => []]);
		$htmlView->template('openapi.json');
		$xml   = $htmlView->render(true, false, true);

		if ($xml) {
			$sxml  = \simplexml_load_string($xml, null, LIBXML_NOCDATA | LIBXML_DTDATTR);
			$array = reconstructJson($sxml, true);

			return $array;
			$json  = json_encode($array, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
			echo $json;
		}
	}

	function api() {
		$cache     = Cache::getInstance();
		$json      = $cache->cache(APP,'openapi',function () {
			$site = siteSettings();
			$data = Sites :: getSiteData();

			$json                          = $this->renderJson();
			$json['paths']                 = $this->paths();
			$json['components']['schemas'] = $this->schemas();

			$site = siteSettings(SITE_ID, $this->global['language_id']);
			$urlParams  = ['host' => $data['host'], 'scheme' => isSecure() ? 'https' : 'http'];
			$url = url('/',$urlParams);

			$json['info']['title'] = $site['description']['title'];
			$json['info']['contact']['email'] = $site['contact-email'];
			$json['info']['contact']['url'] = $url;
			$json['servers'][0]['url'] = url('',$urlParams);

			return $json;
		}, 259200);

		$this->response->setType('json');
		$this->response->output($json);
	}

	function index() {
		return $this->api();
	}
}
