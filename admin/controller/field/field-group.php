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
use function Vvveb\fieldTypeClass;
use function Vvveb\getFieldTypes;
use function Vvveb\importHtml;
use function Vvveb\postTypes;
use Vvveb\Sql\Field_GroupSQL;
use Vvveb\Sql\FieldSQL;
use Vvveb\System\Event;
use Vvveb\System\Field as Fields;
use Vvveb\System\Traits\FieldGroup as FieldGroupTrait;

class FieldGroup extends Crud {
	use FieldGroupTrait;

	protected $type = 'field_group';

	protected $controller = 'field-group';

	protected $module = 'field';

	function setFieldDefault(&$field) {
		$fieldClass = fieldTypeClass($field['type']);

		if ($fieldClass && $fieldClass::valueType) {
			$field['default'] = [];

			foreach ($fieldClass::valueType as $key) {
				$field['default'][$key] = $field['settings'][$key] ?? '';
			}

			$field['default'] = json_encode($field['default']);
		}
	}

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
		$this->redirect                             = false;
		parent::save();

		$delete          	  = $this->request->post['delete']['field_id'] ?? [];
		$field              = $this->request->post['field'] ?? [];
		$field_group	       = $this->request->post['field_group'] ?? [];
		$field_group_id     = $this->request->get['field_group_id'] ?? false;
		$edit               = $this->request->get['field_group_id'] ?? false;
		$field_group_id     = $this->field_group_id;
		$new                = [];
		unset($field_group['conditionals']);
		//var_dump($field);
		foreach ($field as $index => &$attr) {
			$attr['field_group_id']       = $field_group_id;
			$attr['sort_order']           = $attr['sort_order'] ?? 0;

			if ($index === '#') {
				unset($field[$index]);

				continue;
			}

			$attr['name']       = $attr['name'] ?? $attr['settings']['name'] ?? '(none)';
			$attr['label']      = $attr['label'] ?? $attr['settings']['label'] ?? '(none)';
			$attr['default']    = $attr['label'] ?? $attr['settings']['default'] ?? '';
			$attr['type']       = $attr['settings']['type'] ?? 'text';
			$attr['field_id']   = $attr['settings']['field_id'] ?? $attr['field_id'] ?? null;
			$attr['sort_order'] = $attr['settings']['sort_order'] ?? $attr['sort_order'] ?? 0;
			$attr['row']        = $attr['settings']['row'] ?? $attr['row'] ?? 0;

			if (! $attr['name']) {
				unset($field[$index]);

				continue;
			}

			$this->setFieldDefault($attr);

			foreach (['settings', 'validation', 'presentation', 'conditionals'] as $section) {
				$attr[$section] = $attr[$section] ?? [];
				$attr[$section] = array_filter($attr[$section]);
				$attr[$section] = json_encode($attr[$section]);
			}

			if (! ($attr['field_id'] ?? false) && $attr['name']) {
				unset($attr['field_id']);
				$new[] = $attr;
				unset($field[$index]);
			}
		}

		if ($field_group_id) {
			$fieldGroup = new Field_GroupSQL();
			$fieldSql   = new FieldSQL();

			if ($delete) {
				$fieldSql->delete(['field_id' => $delete] + $this->global);
			}

			if ($field) {
				foreach ($field as $index => &$attr) {
					$fieldSql->edit(['field' => $attr, 'field_id' => $attr['field_id']] + $this->global);
				}
			}

			if ($new) {
				foreach ($new as $index => &$attr) {
					$fieldSql->add(['field' => $attr] + $this->global);
				}
			}

			$result = $fieldGroup->edit(['field_group_id' => $field_group_id, 'field_group' => $field_group]);

			if ($result && isset($result['field_group_content'])) {
				if (! $edit) {
					$this->redirect(['module' => "{$this->module}/{$this->controller}", 'field_group_id' => $field_group_id]);
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		$this->index();
	}

	function fields() {
		$fields      = [];

		if ($country_id) {
			$field                  = new FieldSQL();
			$options                = $this->global;
			$options['status'] 	    = 1;
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

		//return;
		$field_group_id = $this->request->get['field_group_id'] ?? false;

		$fields = [];

		if ($field_group_id) {
			$field       = new FieldSQL();
			$fields	     = $field->getAll(['field_group_id' => [$field_group_id], 'limit' => 1000] + $this->global);

			if ($fields['count'] > 0) {
				foreach ($fields['field'] as &$field) {
					$input['settings']               = json_decode($field['settings'], true);
					$input['validation']             = json_decode($field['validation'], true);
					$input['presentation']           = json_decode($field['presentation'], true);
					$input['cols']                   = $input['presentation']['cols'] ?? 'col';
					$input['settings']['field_id']   = $field['field_id'];
					$input['settings']['name']       = $input['settings']['name'] ?? '(none)';
					$input['settings']['label']      = $input['settings']['label'] ?? '(none)';
					$input['settings']['sort_order'] = $field['sort_order'];
					$input['settings']['row']        = $field['row'];
					$input['row']                    = $input['settings']['row'] ?? 0;

					$render                    = $this->renderField($field['type'], $field['field_id'], $input + $field);
					$field['settings-tab']     = $render['settings'];
					$field['validation-tab']   = $render['validation'];
					$field['presentation-tab'] = $render['presentation'];
					$field['field']            = $render['field'];
					$field                     = $input + $field;
				}
			}
		}

		$this->view->fieldTypes   = getFieldTypes();
		$this->view->postTypes    = postTypes('post');
		$this->view->productTypes = postTypes('product');
		$this->view->fields       = $fields['field'] ?? [];

		$this->view->count      = $fields['count'] ?? 0;
	}
}
