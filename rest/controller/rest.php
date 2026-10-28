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

use function Vvveb\isModel;
use function Vvveb\model;

use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Images;

#[\AllowDynamicProperties]
class Rest extends Base {
	/**
	 * @var string HTTP request method
	 */
	private string $method = 'GET';

	/**
	 * Process a single resource: apply image URLs, price formatting, and strip passwords.
	 *
	 * @param array<string, mixed> $resource
	 *
	 * @return array<string, mixed>
	 */
	private function processResource(array &$resource): array {
		if (isset($resource['images'])) {
			$resource['images'] = json_decode($resource['images'], true);

			foreach ($resource['images'] as &$image) {
				$image = Images::image($image, $this->resource, $this->options['image_size'] ?? 'medium');
			}
		}

		if (isset($resource['product_image'])) {
			$resource['images'] = Images::images($resource['product_image'], 'product', $this->options['image_size'] ?? 'medium');
		}

		if (isset($resource['image'])) {
			$resource['image'] = Images::image($resource['image'], $this->resource, $this->options['image_size'] ?? 'medium');
		}

		if (isset($resource['password'])) {
			unset($resource['password']);
		}

		if (isset($resource['price'])) {
			$tax                            = Tax::getInstance();
			$currency                       = Currency::getInstance();
			$currentCurrency                = $this->global['currency'] ?? 'usd';

			$resource['price_tax']           = $tax->addTaxes($resource['price'], $resource['tax_type_id']);
			$resource['price_tax_formatted'] = $currency->format($resource['price_tax']);
			$resource['price_formatted']     = $currency->format($resource['price']);
			$resource['price_currency']      = $currentCurrency;
		}

		return $resource;
	}

	/**
	 * Main REST index action: routes GET/POST/PUT/DELETE to the appropriate model operations.
	 *
	 * @return void
	 */
	public function index(): void {
		//honor the _method override the same way init() does, some servers don't support put/delete
		$this->method = $this->request->get['_method'] ?? $this->request->getMethod();

		$this->resource = $this->request->get['resource'] ?? false;
		$this->id       = $this->request->get['id'] ?? false;
		$this->slug     = $this->request->get['slug'] ?? false;
		$this->options  = $this->request->get ?? [];

		$page                    = $this->request->get['page'] ?? 1;
		$limit                   = $this->request->get['limit'] ?? 10;
		$this->global['start']   = ($page - 1) * $limit;
		$this->global['limit']   = $limit;

		unset($this->options['resource'], $this->options['id']);

		$model = null;

		if ($this->resource) {
			//resolve the model name: clients may pass singular or plural, try both;
			//never use rtrim which eats valid trailing s (ex: address -> addre)
			$candidates = [];

			if (str_ends_with($this->resource, 's')) {
				$candidates[] = substr($this->resource, 0, -1);
			}

			$candidates[] = $this->resource;

			foreach ($candidates as $candidate) {
				if (isModel($candidate)) {
					$model          = model($candidate);
					$this->resource = $candidate;
					break;
				}
			}

			if (!$model) {
				$this->notFound();
			}
		} else {
			$this->notFound();
		}

		$output = [];
		$this->response->setType('json');

		switch ($this->method) {
			case 'GET':
				if ($this->id || $this->slug) {
					$options = [];

					if ($this->id) {
						$resource_id           = $this->resource . '_id';
						$options[$resource_id] = $this->id;
					}

					if ($this->slug) {
						$options['slug'] = $this->slug;
					}

					$output = $model->get($options + $this->options + $this->global);

					if ($output
						&& (isset($output[$this->resource . '_id']) || isset($output[$this->resource]))) {
						if (isset($output[$this->resource])) {
							$output = $output[$this->resource];
						}

						$output      = $this->processResource($output);
					} else {
						$this->notFound();
					}
				} else {
					$result = $model->getAll($this->options + $this->global);

					//models return rows under the table key, accept plural as well
					$collection = null;

					foreach ([$this->resource, $this->resource . 's'] as $collectionKey) {
						if (isset($result[$collectionKey]) && is_array($result[$collectionKey])) {
							$collection = $result[$collectionKey];
							break;
						}
					}

					if ($collection !== null) {
						foreach ($collection as $id => &$resource) {
							$output[] = $this->processResource($resource);
						}
					} else {
						$this->notFound();
					}
				}

				break;

			case 'POST':
				break;

			case 'PUT':
				break;

			case 'DELETE':
				$this->action = 'delete';

				break;
		}

		$this->response->output($output);
	}
}
