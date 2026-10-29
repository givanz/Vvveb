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
use Vvveb\Controller\Crud;
use Vvveb\Sql\Attribute_groupSQL;
use Vvveb\Sql\AttributeSQL;

class AttributeGroup extends Crud {
	protected string $type = 'attribute_group';

	protected string $controller = 'attribute-group';

	protected string $module = 'product';

	/**
	 * Save the attribute group and its associated attributes.
	 *
	 * @return void
	 */
	public function save(): void {
		$this->redirect = false;
		parent::save();

		$delete              = $this->request->post['delete']['attribute_id'] ?? [];
		$attribute           = $this->request->post['attribute'] ?? [];
		$attribute_group     = $this->request->post['attribute_group'] ?? [];
		$edit                = $this->request->get['attribute_group_id'] ?? false;
		$attribute_group_id  = $this->attribute_group_id;
		$new                 = [];

		foreach ($attribute as $index => &$attr) {
			$attr['attribute_group_id'] = $attribute_group_id;
			$attr['sort_order'] ??= 0;

			if (!$attr['attribute_id'] && $attr['name']) {
				unset($attr['attribute_id']);
				$new[] = $attr;
				unset($attribute[$index]);
			}
		}

		if ($attribute_group_id) {
			$attributeGroup = new Attribute_groupSQL();
			$attributeSQL   = new AttributeSQL();

			if ($delete) {
				$attributeSQL->delete(['attribute_id' => $delete] + $this->global);
			}

			if ($attribute) {
				foreach ($attribute as $index => &$attr) {
					$attributeSQL->edit(['attribute' => $attr, 'attribute_id' => $attr['attribute_id']] + $this->global);
				}
			}

			if ($new) {
				foreach ($new as $index => &$attr) {
					$attributeSQL->add(['attribute' => $attr] + $this->global);
				}
			}

			$result = $attributeGroup->edit(['attribute_group_id' => $attribute_group_id, 'attribute_group' => $attribute_group]);

			if ($result && isset($result['attribute_group_content'])) {
				if (!$edit) {
					$this->redirect(['module' => "{$this->module}/{$this->controller}", 'attribute_group_id' => $attribute_group_id]);
				}

				$successMessage             = __('Saved!');
				$this->view->success['get'] = $successMessage;
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		$this->index();
	}

	/**
	 * Display the attribute group edit form with associated attributes.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();
		$attribute_group_id = $this->request->get['attribute_group_id'] ?? false;

		$attributes = [];

		if ($attribute_group_id) {
			$attributeGroup  = new AttributeSQL();
			$attributes	     = $attributeGroup->getAll(['attribute_group_id' => $attribute_group_id, 'limit' => 1000] + $this->global)['attribute'] ?? [];
		}

		$this->view->attributes         = $attributes;
		$this->view->attribute_group_id = $attribute_group_id;
	}
}
