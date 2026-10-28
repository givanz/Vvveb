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

namespace Vvveb;

/**
 * Instantiate and return a model object by name.
 *
 * @param string $model The model name.
 *
 * @return object The instantiated model object.
 */
function model(string $model): object {
	$modelClass = 'Vvveb\Sql\\' . ucwords($model) . 'SQL';

	return new $modelClass();
}

/**
 * Instantiate and return a controller object by name.
 *
 * @param string $name The controller name.
 *
 * @return object The instantiated controller object.
 */
function controller(string $name): object {
	$controllerClass = 'Vvveb\Controller\\' . ucwords($name);

	return new $controllerClass();
}

/**
 * Check if a controller file exists.
 *
 * @param string $name The controller name.
 * @param string $app  The application directory name.
 *
 * @return bool True if the controller file exists.
 */
function isController(string $name, string $app = APP): bool {
	$file   = DIR_ROOT . $app . DS . 'controller' . DS . strtolower($name) . '.php';

	return file_exists($file);
}

/**
 * Check if a model SQL file exists.
 *
 * @param string $name The model name.
 * @param string $app  The application directory name.
 *
 * @return bool True if the model SQL file exists.
 */
function isModel(string $name, string $app = APP): bool {
	$file   = DIR_ROOT . $app . DS . 'sql' . DS . DB_ENGINE . DS . $name . '.sql';

	return file_exists($file);
}
