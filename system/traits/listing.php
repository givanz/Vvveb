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
use Vvveb\System\Images;

trait Listing {
	/** @var string Module name (e.g. 'blog', 'shop'). */
	protected string $module = '';

	/** @var string Entity type name used as the primary key suffix. */
	protected string $type = '';

	/** @var string List identifier; defaults to when null. */
	protected string $list = '';

	/** @var string Column name used as primary key; defaults to "{type}_id". */
	protected string $type_id = '';

	/** @var string Column name used as a secondary data identifier. */
	protected string $data_id = '';

	/** @var object|null Model instance. */
	protected ?object $model = null;

	/** @var string Model class name; set by the controller. */
	protected string $modelName = '';

	/** @var bool Whether to apply user_id filter in queries. */
	protected bool $userFilter = false;

	/** @var bool Whether to apply admin_id filter in queries. */
	protected bool $adminFilter = true;

	/** @var array<string, mixed> Additional options merged into every query. */
	protected array $options = [];

	/** @var string Controller name; defaults to when null. */
	protected string $controller = '';

	/** @var string List controller name; defaults to when null. */
	protected string $listController = '';

	/** @var array<string, mixed> Current filter parameters from the request. */
	protected array $filter = [];

	/** @var string Slug from the request. */
	protected string $slug = '';

	/**
	 * Initialise the listing controller.
	 *
	 * @return mixed Parent init result.
	 */
	public function init(): ?string {
		return parent::init();
	}

	/**
	 * Delete one or more entities by primary key.
	 *
	 * Reads the type ID from the request, calls the model delete method,
	 * and displays a success or error message via the view.
	 *
	 * @return array<string, mixed> The listing data after deletion.
	 */
	public function delete(): void {
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

			if (empty($this->modelName)) {
				$this->modelName = $type;
			}

			$model               = model($this->modelName);
			$options             = [$type_id => $data_id] + $this->global;

			if (!$this->adminFilter) {
				unset($options['admin_id']);
			}

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

		unset($this->request->get[$type_id]);

		$this->index();
	}

	/**
	 * Fetch and return a paginated list of entities.
	 *
	 * Applies pagination, filters, and optional image URL resolution from
	 * the model results.
	 *
	 * @return array<string, mixed> Results array<array-key, mixed> keyed by entity type, plus query options.
	 */
	public function index(): array {
		$type           = $this->type;
		$this->filter   = $this->request->get['filter'] ?? [];

		if (empty($this->modelName)) {
			$this->modelName = $type;
		}

		$model = model($this->modelName);

		$page   = max((int)($this->request->get['page'] ?? 1), 1);
		$limit  = (int)($this->request->get['limit'] ?? 10);
		$start  = ($page - 1) * $limit;

		$options = [
			'start' => $start,
			'limit' => $limit,
		] + $this->global + $this->filter + $this->request->get;

		if (!$this->userFilter) {
			unset($options['user_id']);
		}

		if (!$this->adminFilter) {
			unset($options['admin_id']);
		}

		if ($this->data_id && ($id = $this->request->get[$this->data_id] ?? false)) {
			$options[$this->data_id] = $id;
		}

		$results = $model->getAll($this->options + $options);

		if (isset($results[$type])) {
			foreach ($results[$type] as $id => &$row) {
				if (isset($row['images'])) {
					$row['images'] = json_decode($row['images'], true);

					if (is_array($row['images'])) {
						foreach ($row['images'] as &$image) {
							$image = Images::image($image, $type);
						}
					}
				} else {
					if (isset($row['image'])) {
						$row['image'] = Images::image($row['image'], $type);
					}
				}
			}
		}
		$results[$type] = array_values($results[$type] ?? []);

		$options['page'] = $page;

		return $results + $options;
	}
}
