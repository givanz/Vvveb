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

use function Vvveb\__;
use function Vvveb\model;
use Vvveb\System\Traits\Crud as CrudTrait;

class Crud extends Base {
	use CrudTrait {
		CrudTrait::delete as remove;
		CrudTrait::index as get;
	}

	//protected bool $fullPost = true;

	public function __construct() {
		$this->fullPost = true;
	}

	/**
	 * Validate the incoming request data against the model.
	 *
	 * @param string $method
	 * @param bool   $ignoreMissing
	 *
	 * @return array<int, string>|true Array of error messages or true on success
	 */
	public function validate(string $method, bool $ignoreMissing = false): array|true {
		$model = model($this->type);
		$data  = $this->request->post ?? [];

		$errors = $model->validate($data, $method, $this->type, $ignoreMissing);

		if ($errors) {
			return $errors;
		} else {
			return true;
		}
	}

	/**
	 * Retrieve a single resource by ID.
	 *
	 * @return array<string, mixed>
	 */
	public function index(): array {
		$data = $this->get();

		if (!$data || (!isset($data[$this->type . '_id']))) {
			return $this->notFound(sprintf(__('%s not found!'), ucfirst($this->type)));
		}

		return $data;
	}

	/**
	 * Create a new resource.
	 *
	 * @return array<string, mixed>
	 */
	public function post(): array {
		if (($errors = $this->validate('add')) === true) {
			$this->redirect = false;

			return $this->save();
		} else {
			$this->notFound(['errors' => $errors], 500);
		}
	}

	/**
	 * Update an existing resource via PATCH.
	 *
	 * @return array<string, mixed>
	 */
	public function patch(): array {
		if (($errors = $this->validate('edit')) === true) {
			$this->redirect = false;

			return $this->save();
		} else {
			$this->notFound(['errors' => $errors], 500);
		}
	}

	/**
	 * Upsert a resource (create or update).
	 *
	 * @return array<string, mixed>
	 */
	public function put(): array {
		//if id update else create
		if (($errors = $this->validate('edit', true)) === true) {
			return $this->save();
		} else {
			$this->notFound(['errors' => $errors], 500);
		}
	}

	/**
	 * Delete a resource by ID.
	 *
	 * @return array<string, mixed>
	 */
	public function delete(): array {
		$type    = $this->type;
		$type_id = $this->type_id ?: "{$type}_id";

		$data_id    = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;
		$response   = ['deleted' => false];

		if ($data_id) {
			if (is_numeric($data_id)) {
				$data_id = [$data_id];
			}

			$modelName = $this->modelName ?: $type;

			$model               = model($modelName);
			$options             = [$type_id => $data_id] + $this->global;
			$result              = $model->delete($options);
			$name                = ucfirst(__($type));

			if ($result && isset($result[$type])) {
				if ($result[$type]) {
					$response['deleted'] = true;
				} else {
					$response['message'] = 'No rows affected!';

					return $this->notFound($response, 410);
				}
			} else {
				$response['message'] = sprintf('Error deleting %s!', $name);

				return $this->notFound($response, 500);
			}
		}

		return $response;
	}
}
