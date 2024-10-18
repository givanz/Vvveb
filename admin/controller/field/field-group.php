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

namespace Vvveb\Controller\Field;

use function Vvveb\__;
use Vvveb\Controller\Crud;
use Vvveb\Sql\CountrySQL;
use Vvveb\Sql\Field_GroupSQL;
use Vvveb\Sql\FieldSQL;

class FieldGroup extends Crud {
	protected $type = 'field_group';

	protected $controller = 'field-group';

	protected $module = 'field';

	function save() {/*
		$field          = $this->request->post['field'] ?? [];
		$field_group_id = $this->request->get['field_group_id'] ?? false;

		if ($field_group_id) {
			$fieldGroup = new Field_GroupSQL();
			$result     = $fieldGroup->addFields(['field_group_id' => $field_group_id, 'field_to_field_group' => $field]);

			if ($result && isset($result['field_to_field_group'])) {
				$successMessage        = __('Field saved!');
				$this->view->success[] = $successMessage;
				$this->view->errors    = [];
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}*/

		$this->request->post[$this->type]['status'] = 1;

		parent::save();
	}

	function fields() {
		$country_id  = $this->request->get['country_id'] ?? false;
		$fields      = [];

		if ($country_id) {
			$field                  = new FieldSQL();
			$options                = $this->global;
			$options['status'] 	    = 1;
			$options['country_id']  = $country_id;
			unset($options['limit']);
			$fields	               = $field->getAll($options)['field'] ?? [];
		}

		$this->response->setType('json');
		$this->response->output($fields);
		//return [];
	}

	function index() {
		$field_group_id             = $this->request->get['field_group_id'] ?? false;
		$this->view->field_group_id = $field_group_id;

		parent::index();

		return;
		$field_group_id = $this->request->get['field_group_id'] ?? false;

		$fields = [];

		if ($field_group_id) {
			$fieldGroup  = new Field_GroupSQL();
			$fields	     = $fieldGroup->getFields(['field_group_id' => $field_group_id])['fields'] ?? [];
		}
		$this->view->fields         = $fields;
		$this->view->field_group_id = $field_group_id;

		$countryModel      = new CountrySQL();
		$options           = $this->global;
		$options['status'] = 1;
		unset($options['limit']);
		$country	 = $countryModel->getAll($options);

		$this->view->countries = $country['country'] ?? [];

		$admin_path              = \Vvveb\adminPath();
		$controllerPath          = $admin_path . 'index.php?module=localization/field-group';
		$this->view->fieldsUrl   = "$controllerPath&action=fields";
	}
}
