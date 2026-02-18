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

use function Vvveb\__;
use function Vvveb\sanitizeHTML;
use Vvveb\Sql\menuSQL;
use Vvveb\System\Sites;
use function Vvveb\url;

class Menus extends Categories {
	use SitesTrait;

	function duplicate() {
		$menu_id    = $this->request->post['menu_id'] ?? false;

		if ($menu_id) {
			$this->menus  = new menuSQL();
			$data         = $this->menus->get(['menu_id' => $menu_id]);
			$old_id       = $data['menu_id'];

			unset($data['menu_id']);
			$id = rand(1, 1000);

			$data['name'] .= ' [' . __('duplicate') . ']';
			//$data['slug'] .= '-' . __('duplicate') . "-$old_id-$id";

			if (isset($data['menu_to_site'])) {
				foreach ($data['menu_to_site'] as &$item) {
					$site_id[] = $item['site_id'];
				}
			}

			if ($data) {
				$result = $this->menus->addMenu([
					'menu'             => $data,
					'site_id'          => $site_id,
				]);

				if ($result && isset($result['menu'])) {
					$menu_id = $result['menu'];
					$items         = $this->menus->getMenuAllLanguages(['menu_id' => $old_id])['categories'] ?? [];

					$parentId = [];
					foreach ($items as &$item) {
						$old_menu_item_id = $item['menu_item_id'];
						$item['menu_id'] = $menu_id;
						$item['parent_id'] = $parentId[$item['parent_id']] ?? 0;
						$item['menu_item_content'] = json_decode($item['languages'], true);
						unset($item['menu_item_id']);
						$res = $this->menus->addMenuItem(['menu_item' => $item]);
						$menu_item_id = $res['menu_item'];
						$parentId[$old_menu_item_id] = $menu_item_id;
					}

					$url     = url(['module' => 'content/menus', 'action' => 'menu', 'menu_id' => $menu_id]);

					$type = __('menu');
					$success = ucfirst($type) . ' ' . __('duplicated') . '!';
					$success .= sprintf(' <a href="%s">%s</a>', $url, __('Edit') . ' ' . $type);
					$this->view->success['get'] = $success;
				} else {
					$this->view->errors[] = sprintf(__('Error duplicating %s!'), $type);
				}
			}
		}

		return $this->index();
	}

	function deleteMenu() {
		$view         = $this->view;
		$menu_id      = $this->request->post['menu_id'] ?? $this->request->get['menu_id'] ?? false;
		$menu         = new menuSQL();

		if (is_numeric($menu_id)) {
			$menu_id = [$menu_id];
		}

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

		if ($menu_item_id) {
			if (is_numeric($menu_item_id)) {
				$menu_item_id = [$menu_item_id];
			}

			try {
				//will fail on mysql 5
				$result = $menus->deleteMenuItemRecursive(['menu_item_id' => $menu_item_id]);
			} catch (\Exception $ex) {
				//older mysql versions don't have recursive CTE
				$result = $menus->deleteMenuItem(['menu_item_id' => $menu_item_id]);
			}

			if ($result['menu_item'] > 0) {
				$success = __('Item deleted!');
			} else {
				$success = __('Item not found!');
			}
			$view->success[] = $success;
			echo $success;
		} else {
			$view->errors = [$menus->error ?? ''];
			echo $menus->error;
		}

		die(0);

		return $this->index();
	}

	function reorder() {
		$data       = $this->request->post;
		$menus      = new menuSQL();

		//['menu_items' => $data]
		if ($menus->updateMenuItems($data)) {
			echo __('Items reordered!');
		}

		die(0);
	}

	function save() {
		$data = $this->request->post;

		$menus  = new menuSQL();

		if (isset($data['item_id']) && ! is_numeric($data['item_id'])) {
			unset($data['item_id']);
		}

		if (isset($data['menu_item_content'])) {
			foreach ($data['menu_item_content'] as &$lang) {
				$lang['content'] = sanitizeHTML($lang['content']);

				// if autocomplete set text as default name for languages
				if (isset($data['item_id_text'])) {
					$lang['name'] = $data['item_id_text'];
				}
			}
		}

		$response     = [];
		$success      = true;
		$menu_item_id = false;

		if (isset($data['menu_item_id']) && $data['menu_item_id']) {
			$results      = $menus->editMenuItem(['menu_item' => $data, 'menu_item_id' => $data['menu_item_id']]);
			$menu_item_id = $data['menu_item_id'];

			if ($results) {
				$message = __('Item saved!');
			} else {
				$message =  __('Error!');
				$success = false;
			}
		} else {
			$results = $menus->addMenuItem(['menu_item' => $data]);

			if ($results) {
				$menu_item_id = $results['menu_item'];
				$message      =  __('Item added!');
			} else {
				$message =  __('Error!');
				$success = false;
			}
		}

		$response += ['success' => $success, 'message' => $message, 'menu_item_id' => $menu_item_id];

		$this->response->setType('json');
		$this->response->output($response);
	}

	function menu() {
		$menuId      = $this->request->get['menu_id'] ?? false;
		$menu_data   = 	$this->request->post['menu_data'] ?? false;
		$site_id     = $this->request->post['site'] ?? []; //[$this->global['site_id']];
		$view        = $this->view;
		$menus       = new menuSQL();

		$page    = $this->request->get['page'] ?? 1;
		$results = [];

		if ($menuId) {
			$options = [
				'menu_id' => $menuId,
				'limit'   => 100000,
			] + $this->global;

			$results = $menus->getMenuAllLanguages($options);

			foreach ($results['categories'] as &$menu) {
				$langs                 = $menu['languages'] ? json_decode($menu['languages'], true) : [];
				$menu['languages']     = [];

				if ($langs) {
					foreach ($langs as $lang) {
						$menu['languages'][$lang['language_id']] = $lang;
					}

					$menu['name']    = $menu['languages'][$this->global['language_id']]['name'] ?? $langs[0]['name'] ?? '';
					$menu['content'] = $menu['languages'][$this->global['language_id']]['content'] ?? $langs[0]['content'] ?? '';
				}
			}

			if ($menu_data) {
				$menu_data = $menu_data + ['site_id' => $site_id] + $options;

				$return    = $menus->editMenu($menu_data);
				$id        = $return['menu'];
				$menu_site = $return['menu_to_site'];

				if (! $id && ! $menu_site) {
					$view->errors = ['No changes!'];
				} else {
					$view->success[] = __('Menu saved!');
				}
			}

			unset($options['site_id']);
			$results['menu_data'] = $menus->get($options);
		} else {
			if ($menu_data) {
				$menu_data = $menu_data + $this->global;
				$return    = $menus->addMenu(['menu' => $menu_data, 'site_id' => $site_id]);

				$id     = $return['menu'];

				if (! $id) {
					$view->errors = [$menu->error];
				} else {
					$success         = __('Menu saved!');
					$view->success[] = $success;
					$this->redirect(['module'=>'content/menus', 'action' => 'menu', 'menu_id' => $id, 'success' => $success]);
					$menu_data['menu_id'] = $id;
				}
			}

			$results['menu_data'] = $menu_data;
		}

		$view->set($results);
		$view->menu_id = $menuId;
		$sites         = $results['menu_data']['menu_to_site'] ?? [];

		if ($sites) {
			$sites = array_keys($sites);
		} else {
			if (! $menuId) {
				$sites[] = $this->global['site_id'];
			}
		}

		$view->sitesList = $this->sites($sites);
		$admin_path      = \Vvveb\adminPath();
		$controllerPath  = $admin_path . 'index.php?module=media/media';
		$view->scanUrl   = "$controllerPath&action=scan";
		$view->uploadUrl = "$controllerPath&action=upload";
		$view->linkUrl   = $admin_path . 'index.php?module=content/post&action=urlAutocomplete';
		$theme           = Sites::getTheme($this->global['site_id']) ?? 'default';
		$view->themeCss  = PUBLIC_PATH . "themes/$theme/css/admin-post-editor.css";
	}

	function index() {
		$view        = $this->view;
		$menus       = new menuSQL();

		$options = [
			'limit' => 10000,
		] + $this->global;

		$results = $menus->getAll($options);

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
