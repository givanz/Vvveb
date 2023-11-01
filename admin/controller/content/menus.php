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

use \Vvveb\Sql\menuSQL;
use function Vvveb\__;

class Menus extends Categories {
	function deleteMenu() {
		$view         = $this->view;
		$menu_id      = $this->request->get['menu_id'] ?? false;
		$menu         = new menuSQL();

		if ($menu_id && ($result = $menu->deleteMenu(['menu_id' => $menu_id]))) {
			if ($result['menu'] > 0) {
				$success = __('Menu deleted!');
			} else {
				$success = __('Menu not found!');
			}
			$view->success[] = $success;
		} else {
			$view->errors = [$menu->error];
		}

		if (defined('CLI')) {
			return;
		}

		return $this->index();
	}

	function delete() {
		$menu_item_id = $this->request->post['menu_item_id'] ?? false;
		$view         = $this->view;
		$menus        = new menuSQL();

		if ($menu_item_id && ($result = $menus->deleteMenuItem(['menu_item_id' => $menu_item_id]))) {
			if ($result['menu_item'] > 0) {
				$success = __('Item deleted!');
			} else {
				$success = __('Item not found!');
			}
			$view->success[] = $success;
			echo $success;
		} else {
			$view->errors = [$menus->error];
			echo $menus->error;
		}

		die();

		return $this->index();
	}

	function reorder() {
		$data       = $this->request->post;
		$menus      = new menuSQL();

		//['menu_items' => $data]
		if ($menus->updateMenuItems($data)) {
			echo __('Items reordered!');
		}

		die();
	}

	function add() {
		$data = $this->request->post;

		$menus  = new menuSQL();

		if (isset($data['menu_item_id']) && $data['menu_item_id']) {
			$results = $menus->editMenuItem(['menu_item' => $data, 'menu_item_id' => $data['menu_item_id']]);

			if ($results) {
				echo __('Item edited!');
			}
		} else {
			$results = $menus->addMenuItem(['menu_item' => $data]);

			if ($results) {
				echo __('Item added!');
			}
		}

		die();

		return;
	}

	function menu() {
		$menuId      = $this->request->get['menu_id'] ?? false;
		$menu_data   = 	$this->request->post['menu_data'] ?? false;
		$view        = $this->view;
		$menus       = new menuSQL();

		$page    = $this->request->get['page'] ?? 1;
		$results = [];

		if ($menuId) {
			$options = [
				'menu_id'            	    => $menuId, //menus
			] + $this->global;

			$results = $menus->getMenuAllLanguages($options);

			foreach ($results['categories'] as &$menu) {
				$langs                 = $menu['languages'] ? json_decode($menu['languages'], true) : [];
				$menu['languages']     = [];

				if ($langs) {
					foreach ($langs as $lang) {
						$menu['languages'][$lang['language_id']] = $lang;
					}

					$menu['name'] = $menu['languages'][$this->global['language_id']]['name'] ?? $langs[0]['name'] ?? '';
				}
			}

			if ($menu_data) {
				$menu_data = $menu_data + $options;
				$return    = $menus->editMenu($menu_data);
				$id        = $return['menu'];

				if (! $id) {
					$view->errors = ['No changes!'];
				} else {
					$view->success[] = __('Menu saved!');
				}
			}

			$results['menu_data'] = $menus->getMenu($options);
		} else {
			if ($menu_data) {
				$menu_data = $menu_data + $this->global;
				$return    = $menus->addMenu(['menu' => $menu_data]);

				$id     = $return['menu'];

				if (! $id) {
					$view->errors = [$menu->error];
				} else {
					$view->success[] = __('Menu saved!');
					$this->redirect(['module'=>'content/menus', 'action' => 'menu', 'menu_id' => $id]);
				}
			}
		}

		$view->set($results);

		//return 'content/menus/menu.html';
	}

	function index() {
		$view        = $this->view;
		$menus       = new menuSQL();

		$options = [
			'limit' => 10000,
		] + $this->global;

		$results = $menus->getMenusList($options);

		if (isset($results['menu'])) {
			foreach ($results['menu'] as &$menu) {
				$url                  = ['module' => 'content/menus', 'action' => 'menu', 'menu_id' => $menu['menu_id']];
				$menu['url']          = \Vvveb\url($url);
				$menu['edit-url']     = $menu['url'];
				$menu['delete-url']   = \Vvveb\url(['action' => 'deleteMenu'] + $url);

				$langs                 = isset($menu['languages']) ? json_decode($menu['languages'], true) : [];
				$menu['languages']     = [];

				if ($langs) {
					foreach ($langs as $lang) {
						$menu['languages'][$lang['language_id']] = $lang;
					}

					$menu['name'] = $langs[0]['name'] ?? '';
				}
			}

			$results['menus'] = $results['menu'];
			unset($results['menu']);
		}
		$view->set($results);
	}
}
