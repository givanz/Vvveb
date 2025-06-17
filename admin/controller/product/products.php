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

namespace Vvveb\Controller\Product;

use function Vvveb\__;
use Vvveb\Controller\Base;
use function Vvveb\humanReadable;
use Vvveb\Sql\ProductSQL;
use Vvveb\System\Core\View;
use Vvveb\System\Images;
use Vvveb\System\User\Admin;
use function Vvveb\url;

class Products extends Base {
	protected $type = 'product';

	//check for other modules permission like post and editor to enable links like save/delete etc
	protected $additionalPermissionCheck = ['product/product/save'];

	function init() {
		if (isset($this->request->get['type'])) {
			$this->type = $this->request->get['type'];
		}

		return parent::init();
	}

	function duplicate() {
		$product_id    = $this->request->product['product_id'] ?? $this->request->get['product_id'] ?? false;

		if ($product_id) {
			$this->products   = new ProductSQL();
			$data             = $this->products->get(['product_id' => $product_id, 'type' => $this->type]);

			unset($data['product_id']);
			$id = rand(1, 1000);

			foreach ($data['product_content'] as &$content) {
				unset($content['product_id']);
				$content['name'] .= ' [' . __('duplicate') . ']';
				$content['slug'] .= '-' . __('duplicate') . "-$id";
			}

			if (isset($data['product_to_taxonomy_item'])) {
				foreach ($data['product_to_taxonomy_item'] as &$item) {
					$taxonomy_item_id[] = $item['taxonomy_item_id'];
				}
			}

			if (isset($data['product_to_site'])) {
				foreach ($data['product_to_site'] as &$item) {
					$site_id[] = $item['site_id'];
				}
			}

			if ($data) {
				$result = $this->products->add([
					'product'          => $data,
					'product_content'  => $data['product_content'],
					'taxonomy_item_id' => $taxonomy_item_id ?? [],
					'site_id'          => $site_id,
				]);

				if ($result && isset($result['product'])) {
					$product_id = $result['product'];

					if ($data['product_image']) {
						$product_image = [];

						foreach ($data['product_image'] as $image) {
							$product_image[] = $image['image'];
						}
						$images = $this->products->productImage(['product_image' => $product_image, 'product_id' => $product_id]);
					}

					$url = url(['module' => 'product/product', 'product_id' => $product_id, 'type' => $this->type]);

					$success = ucfirst($this->type) . __(' duplicated!');
					$success .= sprintf(' <a href="%s">%s</a>', $url, __('Edit') . " {$this->type}");
					$this->view->success[] = $success;
					$this->session->set('success', $success);
					$this->redirect(['module' => 'product/products'], [], false);
				} else {
					$this->view->errors[] = sprintf(__('Error duplicating %s!'),  $this->type);
				}
			}
		}

		return $this->index();
	}

	function delete() {
		$product_id    = $this->request->post['product_id'] ?? $this->request->get['product_id'] ?? false;

		if ($product_id) {
			if (is_numeric($product_id)) {
				$product_id = [$product_id];
			}

			$products = new ProductSQL();
			$options  = ['product_id' => $product_id] + $this->global;
			$result   = $products->delete($options);

			if ($result && isset($result['product'])) {
				$this->view->success[] = __('Product(s) deleted!');
			} else {
				$this->view->errors[] = __('Error deleting product!');
			}
		}

		return $this->index();
	}

	function index() {
		$view     = View :: getInstance();
		$products = new ProductSQL();

		$this->filter = array_filter($this->request->get['filter'] ?? []);

		if (isset($this->filter['vendor_id'])) {
			$this->filter['vendor_id'] = [$this->filter['vendor_id']];
		}

		if (isset($this->filter['manufacturer_id'])) {
			$this->filter['manufacturer_id'] = [$this->filter['manufacturer_id']];
		}

		$options = [
			'type'        => $this->type,
		] + $this->global;

		if (Admin::hasCapability('view_other_products')) {
			unset($options['admin_id']);
		} else {
			$options['admin_id'] = $this->global['admin_id'];
		}

		$options += $this->filter;

		$results = $products->getAll($options);

		$defaultTemplate = "product/{$this->type}.html";

		if ($results && isset($results['product'])) {
			foreach ($results['product'] as $id => &$product) {
				if (isset($product['images'])) {
					$product['images'] = json_decode($product['images'], 1);

					foreach ($product['images'] as &$image) {
						$image = Images::image($image, 'product');
					}
				} else {
					if (isset($product['image'])) {
						$product['image'] = Images::image($product['image'], 'product');
					}
				}

				if (! $product['name'] && ($product['language_id'] != $this->global['default_language_id'])) {
					$product['name'] = '[' . __('No translation') . ']';
				}

				$adminPath                = \Vvveb\adminPath();
				$url                      = ['module' => 'product/product', 'product_id' => $product['product_id'], 'type' => $product['type']];
				$template                 = (isset($product['template']) && $product['template']) ? $product['template'] : $defaultTemplate;
				$product['url']           = url($url);
				$product['edit-url']      = url(['module' => 'product/product', 'product_id' => $product['product_id'], 'type' => $product['type']]);
				$product['delete-url']    = url(['module' => 'product/products', 'action' => 'delete'] + $url + ['product_id[]' => $product['product_id']]);
				$product['duplicate-url'] = url(['module' => 'product/products', 'action' => 'duplicate'] + $url + ['product_id' => $product['product_id']]);
				$product['view-url']      = url('product/product/index', $product + $url + ['host' => $this->global['host']]);
				$relativeUrl              = url('product/product/index', $product + $url);
				$product['design-url']    = url(['module' => 'editor/editor', 'name' => urlencode($product['name'] ?? ''), 'url' => $relativeUrl, 'template' => $template, 'host' => $this->global['host'] . $adminPath], false);
			}
		}

		$view->set($results);
		$view->status           = [0 => 'Disabled', 1 => 'Enabled'];
		$view->filter           = $this->filter;
		$view->type             = $this->type;
		$view->limit            = $options['limit'];
		$view->type             = $this->type;
		$view->addUrl           = url(['module' => 'product/product', 'type' => $this->type]);
		$view->type_name        = humanReadable(__($this->type));
		$view->type_name_plural = humanReadable(__($view->type . 's'));
	}
}
