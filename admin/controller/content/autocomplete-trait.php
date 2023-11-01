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

namespace Vvveb\Controller\Content;

use \Vvveb\Sql\categorySQL;
use Vvveb\System\Images;

trait AutocompleteTrait {
	function categoriesAutocomplete() {
		$categories = new CategorySQL();

		$results = $categories->getCategories([
			'start'       => 0,
			'limit'       => 10,
			'language_id' => 1,
			'site_id'     => 1,
			'search'      => '%' . trim($this->request->get['text']) . '%',
		]);

		$search = [];

		if (isset($results['categories'])) {
			foreach ($results['categories'] as $category) {
				$search[$category['taxonomy_item_id']] = $category['name'];
			}
		}

		$view         = $this->view;
		$view->noJson = true;

		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function manufacturersAutocomplete() {
		$manufacturers = new \Vvveb\Sql\ManufacturerSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . trim($this->request->get['text']) . '%',
		] + $this->global;

		$results = $manufacturers->getAll($options);

		$search = [];

		foreach ($results['manufacturer'] as $manufacturer) {
			$manufacturer['image']                    = Images::image($manufacturer['image'], 'manufacturer');
			$search[$manufacturer['manufacturer_id']] = '<img width="32" height="32" src="' . $manufacturer['image'] . '"> ' . $manufacturer['name'];
		}

		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function vendorsAutocomplete() {
		$vendors = new \Vvveb\Sql\VendorSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => '%' . trim($this->request->get['text']) . '%',
		] + $this->global;

		$results = $vendors->getAll($options);

		$search = [];

		foreach ($results['vendor'] as $vendor) {
			$vendor['image']               = Images::image($vendor['image'], 'vendor');
			$search[$vendor['vendor_id']]  = '<img width="32" height="32" src="' . $vendor['image'] . '"> ' . $vendor['name'];
		}

		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function productsAutocomplete() {
		$products = new \Vvveb\Sql\ProductSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => trim($this->request->get['text']),
		] + $this->global;

		$results = $products->getAll($options);

		$search = [];

		foreach ($results['products'] as $product) {
			$product['image']                        = Images::image($product['image'], $this->object);
			$search[$product[$this->object . '_id']] = '<img width="32" height="32" src="' . $product['image'] . '"> ' . $product['name'];
		}

		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function adminsAutocomplete() {
		$admins = new \Vvveb\Sql\AdminSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => trim($this->request->get['text']),
		] + $this->global;

		$results = $admins->getAll($options);

		$search = [];

		foreach ($results['admin'] as $admin) {
			$text = '';

			if (isset($admin['avatar'])) {
				$admin['avatar'] = Images::image($admin['avatar'], 'admin');
				$text            =  '<img width="32" height="32" src="' . $admin['avatar'] . '"> ';
			}
			$text .= $admin['username'] . ' (' . $admin['first_name'] . ' ' . $admin['last_name'] . ')';

			$search[$admin['admin_id']] =  $text;
		}
		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function attributesAutocomplete() {
		$attributes = new \Vvveb\Sql\AttributeSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => trim($this->request->get['text']),
		] + $this->global;

		$results = $attributes->getAll($options);

		$search = [];

		foreach ($results['attribute'] as $attribute) {
			$text = '';

			$text .= $attribute['name'] . ' (' . $attribute['group'] . ')';

			$search[$attribute['attribute_id']] =  $text;
		}
		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}	
	
	function optionValuesAutocomplete() {
		$values = new \Vvveb\Sql\Option_valueSQL();

		$options = [
			'option_id'      => trim($this->request->get['option_id']),
		] + $this->global;

		$results = $values->getAll($options);

		$search = [];

		foreach ($results['option_value'] as $value) {
			$text = '';

			$text .= $value['name'];

			$search[$value['option_value_id']] =  $text;
		}
		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}

	function digitalAssetsAutocomplete() {
		$digital_assets = new \Vvveb\Sql\Digital_assetSQL();

		$options = [
			'start'       => 0,
			'limit'       => 10,
			'search'      => trim($this->request->get['text']),
		] + $this->global;

		$results = $digital_assets->getAll($options);

		$search = [];

		foreach ($results['digital_asset'] as $digital_asset) {
			$text = '';

			$text .= $digital_asset['name'] . ' (' . $digital_asset['file'] . ')';

			$search[$digital_asset['digital_asset_id']] =  $text;
		}
		//echo json_encode($search);
		$this->response->setType('json');
		$this->response->output($search);

		return false;
	}
}
