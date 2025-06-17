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

use function Vvveb\getCurrency;
use function Vvveb\model;
use Vvveb\System\Cart\Currency;
use Vvveb\System\Cart\Tax;
use Vvveb\System\Images;

#[\AllowDynamicProperties]
class Rest extends Base {
	private $method = 'GET';

	private function processResource(&$resource) {
		if (isset($resource['images'])) {
			$resource['images'] = json_decode($resource['images'], 1);

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
			$currentCurrency                = getCurrency();

			$resource['price_tax']           = $tax->addTaxes($resource['price'], $resource['tax_type_id']);
			$resource['price_tax_formatted'] = $currency->format($resource['price_tax']);
			$resource['price_formatted']     = $currency->format($resource['price']);
			$resource['price_currency']      = $currentCurrency;
		}

		return $resource;
	}

	function index() {
		$this->method = $this->request->method;

		$this->resource = $this->request->get['resource'] ?? false;
		$this->id       = $this->request->get['id'] ?? false;
		$this->slug     = $this->request->get['slug'] ?? false;
		$this->options  = $this->request->get ?? [];

		$page                     = $this->request->get['page'] ?? 1;
		$limit                    = $this->request->get['limit'] ?? 10;
		$this->global['start']    = ($page - 1) * $limit;
		$this->global['limit']    = $limit;

		unset($this->options['resource'], $this->options['id']);

		if ($this->resource) {
			$this->resource = rtrim($this->resource, 's');
			$model          = model(rtrim($this->resource, 's')); //singular for model name

			if (! $model) {
				return;
			}
		}

		$output = [];
		$this->response->setType('json');

		switch ($this->method) {
			case 'GET':
				if ($this->id || $this->slug) {
					$options =[];

					if ($this->id) {
						$resource_id           = $this->resource . '_id';
						$options[$resource_id] = $this->id;
					}

					if ($this->slug) {
						$options['slug'] = $this->slug;
					}

					$output      = $model->get($options + $this->options + $this->global);

					if ($output ||
						(isset($output[$this->resource . '_id']) ||
						(isset($output[$this->resource]) ||
						$output[$this->resource]))) {
						if (isset($output[$this->resource])) {
							$output = $output[$this->resource];
						}

						$output      = $this->processResource($output);
					} else {
						return $this->notFound();
					}
				} else {
					$result = $model->getAll($this->options + $this->global);

					if (isset($result[$this->resource . 's'])) {
						$collection = $result[$this->resource . 's'];

						foreach ($collection as $id => &$resource) {
							$output[] = $this->processResource($resource);
						}
					} else {
						return $this->notFound();
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
