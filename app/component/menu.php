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

namespace Vvveb\Component;

use Vvveb\Sql\menuSQL;
use Vvveb\System\Component\ComponentBase;
use Vvveb\System\Event;

class Menu extends ComponentBase {
	public static $defaultOptions = [
		'start'                      => 0, //defaut, override from html
		'limit'                      => 10000,
		'menu_id'                    => null, //unset, set from html
		'slug'                       => null, //unset, set from html
	];

	function results() {
		$options = $this->options;

		//if menu id is set then ignore slug
		if (isset($options['menu_id'])) {
			unset($options['slug']);
		}

		$menuSql               = new menuSQL();
		$results               = $menuSql->getMenus($options);
		$current_category_slug = false;
		//count the number of child menus (subcategories) for each category
		if (isset($results['menus'])) {
			foreach ($results['menus'] as $taxonomy_item_id => &$category) {
				$parent_id = $category['parent_id'] ?? false;

				if ($current_category_slug == $category['slug']) {
					$category['active'] = true;
				} else {
					$category['active'] = false;
				}

				if (! isset($category['children'])) {
					$category['children'] = 0;
				}

				if ($parent_id > 0) {
					$parent = &$results['menus'][$parent_id];

					if (isset($parent['children'])) {
						$parent['children']++;
					} else {
						$parent['children'] = 1;
					}
				}
			}
		}

		list($results) = Event :: trigger(__CLASS__,__FUNCTION__, $results);

		return $results;
	}

	//called by editor on page save for each component on page
	//this method is called from admin app
	static function editorSave($id, $fields, $type = 'menu') {
		$menu              = new menuSQL();
		$menu_item_content = [];

		foreach ($fields as $field) {
			$name  = $field['name'];
			$value = $field['value'];

			if ($name == 'name') {
				$menu_item_content[$name] = strip_tags($value);
			} else {
				if ($name == 'content') {
					$menu_item_content[$name] = $value;
				} else {
					$menu_item[$name] = $value;
				}
			}
		}
		//$menu_item['menu_item_content']['post_id'] = $id;
		$menu_item_content['language_id']      = 1;
		$menu_item_content['content']          = $menu_item_content['content'] ?? '';
		$menu_item['menu_item_content'][]      = $menu_item_content;
		$menu_item['menu_item_id']             = $id;

		if ((isset($menu_item_content['name']) && $menu_item_content['name']) ||
			(isset($menu_item_content['content']) && $menu_item_content['content'])) {
			$result = $menu->editMenuItem(['menu_item' => $menu_item, 'menu_item_id' => $id]);
		}
	}
}
