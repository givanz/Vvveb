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

namespace Vvveb\System\Traits;

use function Vvveb\__;
use function Vvveb\humanReadable;
use function Vvveb\model;
use function Vvveb\sanitizeHTML;
use Vvveb\System\Images;

trait Crud {
	/** @var string Module name (e.g. 'blog', 'shop'). */
	protected string $module = '';

	/** @var string Entity type name used as the primary key suffix. */
	protected string $type = '';

	/** @var string Controller name; defaults to when null. */
	protected string $controller = '';

	/** @var string|bool Redirect target after save. true = auto-redirect, false = no redirect, string = custom URL. */
	protected string|bool $redirect = true;

	/** @var string Current entity primary key value. */
	protected string $data_id = '';

	/** @var string Column name used as primary key; defaults to "{type}_id". */
	protected string $type_id = '';

	/** @var string Slug from the request. */
	protected string $slug = '';

	/** @var object|null Model instance. */
	protected ?object $model = null;

	/** @var array<string, mixed> Additional options merged into every query. */
	protected array $options = [];

	/** @var array<string, mixed> Request data being processed. */
	protected array $data = [];

	/** @var bool Whether to apply user_id filter in queries. */
	protected bool $userFilter = false;

	/** @var bool Whether to apply admin_id filter in queries. */
	protected bool $adminFilter = true;

	/** @var string Model class name; set by the controller. */
	protected string $modelName = '';

	/** @var bool When true, the full POST array<array-key, mixed> is used instead of->post[$type]. */
	protected bool $fullPost = false;

	/**
	 * Delete one or more entities by primary key.
	 *
	 * Reads the type ID from the request, calls the model delete method,
	 * and displays a success or error message via the view.
	 *
	 * @return array<string, mixed> The index data after deletion.
	 */
	public function delete(): array {
		if (APP === 'admin') {
			$this->checkCsrf();
		}

		$type    = $this->type;
		$type_id = $this->type_id ?: "{$type}_id";

		if (APP === 'admin') {
			$data_id = $this->request->post[$type_id];
		} else {
			$data_id = $this->request->post[$type_id] ?? $this->request->get[$type_id] ?? false;
		}

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
					$this->view->success[] = sprintf(__('%s(s) deleted!'), humanReadable($name));
				} else {
					$this->view->info[] = __('No rows affected!');
				}
			} else {
				$this->view->errors[] = sprintf(__('Error deleting %s!'), humanReadable($name));
			}
		}

		return $this->index();
	}

	/**
	 * Save (create or update) an entity from submitted POST data.
	 *
	 * Determines whether to insert or update based on the presence of a
	 * primary key value in the request. Handles timestamps, sanitisation,
	 * and optional redirect after save.
	 *
	 * @return array<string, mixed> The model result or index data.
	 */
	public function save(): array {
		$type        = $this->type;
		$module      = $this->module;
		$controller  = $this->controller ?: $type;
		$result      = [];

		$type_id       = $this->type_id ?: "{$type}_id";
		$this->data_id = $this->request->get[$type_id] ?? false;

		if ($this->fullPost) {
			$this->data = $this->request->post ?? [];
		} else {
			$this->data = $this->request->post[$type] ?? [];
		}

		if ($this->data) {
			if (empty($this->modelName)) {
				$this->modelName = $type;
			}

			$this->model = model($this->modelName);

			if (!$this->data_id) {
				$this->data['created_at'] ??= date('Y-m-d H:i:s');
			}
			$this->data['updated_at'] ??= date('Y-m-d H:i:s');

			if ($this->fullPost) {
				$options = $this->data + $this->global;
			} else {
				$options = [$type => $this->data] + $this->global;
			}

			foreach (['content', 'name', 'excerpt'] as $field) {
				if (isset($this->data[$field])) {
					$this->data[$field] = sanitizeHTML($this->data[$field]);
				}
			}

			if ($this->data_id) {
				$options[$type_id] = $this->data_id;
				$this->$type_id    = $this->data_id;
				$result            = $this->model->edit($options);
			} else {
				$result         = $this->model->add($options);
				$this->$type_id = $result[$type] ?? false;
			}

			if ($result && isset($result[$type])) {
				$successMessage             = humanReadable(__($type)) . __(' saved!');
				$this->view->success['get'] = $successMessage;
				$this->session->set('success', ['get' => $successMessage]);

				if (!$this->data_id && $this->redirect) {
					if ($this->redirect === true) {
						$this->redirect(['module' => "$module/$controller", $type_id => $result[$type], 'success' => $successMessage]);
					} else {
						$this->redirect($this->redirect . "?$type_id={$result[$type]}&success=$successMessage");
					}
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		if ($this->redirect) {
			$this->index();
		}

		return $result;
	}

	/**
	 * Load a single entity or an empty data set for the edit/create form.
	 *
	 * When a primary key or slug is present in the request, the model is
	 * queried for the corresponding record. Otherwise an empty array is
	 * returned so the view can render a "create new" form.
	 *
	 * @return array<string, mixed> The entity data array<array-key, mixed>.
	 */
	protected function index(): array {
		$type             = $this->type;
		$type_id          = $this->type_id ?: "{$type}_id";
		$this->data_id    = $this->request->get[$type_id] ?? false;
		$this->slug       = $this->request->get['slug'] ?? '';
		$this->data       = [];

		if ($this->data_id || $this->slug) {
			if (empty($this->modelName)) {
				$this->modelName = $type;
			}

			$this->model = model($this->modelName);

			if ($this->data_id) {
				$this->options[$type_id] = $this->data_id;
			}

			if ($this->slug) {
				$this->options['slug'] = $this->slug;
			}

			$this->options += $this->global + $this->request->get;

			if (!$this->userFilter) {
				unset($this->options['user_id']);
			}

			if (!$this->adminFilter) {
				unset($this->options['admin_id']);
			}

			$result = $this->model->get($this->options);

			if ($result) {
				$this->data = $result;

				if (isset($this->data['image'])) {
					$this->data['image_url'] = Images::image($this->data['image'], $type);
				}
			}
		}

		return $this->data;
	}
}
