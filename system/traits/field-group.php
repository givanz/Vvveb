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

namespace Vvveb\System\Traits;

use function Vvveb\__;

trait FieldGroup {
	function renderFields2($fields) {
		$htmlView  = new \Vvveb\System\Core\View();
		$htmlView->setTheme();
		$htmlView->template('field/field-group/renderfields.html');

		$htmlView->fields = $fields;
		$html             = $htmlView->render(true, false, true);

		//return only content inside <body>
		$html = preg_replace('@<!DOCTYPE[^>]+>|</?html>|</?body>@', '', $html);

		return $html;
	}

	function field() {
		$type     = $this->request->post['type'] ?? 'text';
		$field_id = $this->request->post['field_id'];

		$json = $this->renderField($type, $field_id);
		$this->response->setType('json');

		return $json;
	}

	private function renderField($type, $field_id, $input = []) {
		$fields = [];
		$data   = [];
		$json   = [];

		foreach (['settings', 'validation', 'presentation', 'conditionals'] as $section) {
			$input[$section] = $input[$section] ?? $this->request->post['field'][$field_id][$section] ?? [];
		}

		$field      = ucfirst($type);
		$fieldClass = "Vvveb\System\Fields\\$field";

		if (! class_exists($fieldClass)) {
			return;
		}

		$fieldObj             = new $fieldClass();
		$data['settings']     = $fieldObj->getSettings();
		$data['validation']   = $fieldObj->getValidation();
		$data['presentation'] = $fieldObj->getPresentation();

		foreach ($data as $section => $field) {
			foreach ($field as $name => $value) {
				if (! $value) {
					continue;
				}
				/*
				if (isset($this->request->post['field'][$field_id][$section][$name])) {
					$data[$section][$name]['value'] = $this->request->post['field'][$field_id][$section][$name];
				}
				*/
				if (isset($input[$section][$name])) {
					$data[$section][$name]['value'] = $input[$section][$name];
				}

				$data[$section][$name]['id']          = $name;
				$data[$section][$name]['name']        = "field[$field_id][$section][$name]";
				$data[$section][$name]['class']       = ($value['type'] != 'hidden') ? 'row mb-3' : '';
				$data[$section][$name]['label-class'] = 'col-sm-2 col-form-label py-0';
				$data[$section][$name]['input-class'] = 'col-sm-10 col-xl-8 col-xxl-6';

				if ($value['type'] == 'checkbox' && (isset($this->request->post['field'][$field_id][$section][$name]) || isset($input[$section][$name]))) {
					$data[$section][$name]['checked'] = true;
				}
			}
		}

		//check if we have a complex value (json array) or a single value
		if ($fieldClass::valueType) {
			foreach ($fieldClass::valueType as $name) {
				$value[$name] = $data['settings'][$name]['value'] ?? '';
			}
		} else {
			$value = $data['settings']['default']['value'] ?? '';
		}

		$field = [
			'label'        => $data['settings']['label']['value'] ?? __('Label'),
			'instructions' => $data['presentation']['instructions']['value'] ?? __('Instructions'),
			'name'         => 'label',
			//'id'           => 'input-' . $field_id,
			'type'         => $type,
			'label-class'  => 'form-label',
			'placeholder'  => $data['settings']['label']['value'] ?? __('Placeholder'),
			'readonly'     => true,
			'value'        => $value,
			//'required'     => $data['validation']['required']['checked'] ?? '',
		] + $input['presentation'] + $input['settings'];

		foreach ($field as $name => $value) {
			/*
			if (isset($this->request->post['field'][$field_id]['settings'][$name])) {
				$field[$name] = $this->request->post['field'][$field_id]['settings'][$name];
			}*/

			if (isset($input[$section][$name])) {
				$field[$name] = $input[$section][$name];
			}
		}

		$data['field']['input'] = $field;

		$json['settings']     = $this->renderFields2($data['settings']);
		$json['validation']   = $this->renderFields2($data['validation']);
		$json['presentation'] = $this->renderFields2($data['presentation']);
		$json['field']        = $this->renderFields2($data['field']);

		return $json;
	}

	function renderFields() {
		$type   = $this->request->get['type'] ?? '';
		$fields = [];

		if ($type == 'field') {
			$field   = $this->request->get['field'] ?? 'text';
			$section = $this->request->get['section'] ?? 'settings';

			$field      = ucfirst($field);
			$fieldClass = "Vvveb\System\Fields\\$field";

			if (! class_exists($fieldClass)) {
				return;
			}

			$fieldObj = new $fieldClass();

			switch ($section) {
				case 'settings':
					$fields = $fieldObj->getSettings();

					break;

				case 'validation':
					$fields = $fieldObj->getValidation();

				case 'presentation':
					$fields = $fieldObj->getPresentation();

					break;
			}

			foreach ($fields as $fieldName => $field) {
				foreach ($field as $name => $value) {
					if (isset($this->request->post[$fieldName][$name])) {
						$fields[$fieldName][$name] = $this->request->post[$fieldName][$name];
					}
				}
			}
		}

		if ($type == 'input') {
			$field = [
				'label'        => __('Label'),
				'instructions' => __('Instructions'),
				'name'         => 'label',
				'type'         => 'text',
				'class'        => 'form-label',
				'placeholder'  => 'placeholder',
				//'value'        => 'value',
			];

			foreach ($field as $name => $value) {
				if (isset($this->request->post[$name])) {
					$field[$name] = $this->request->post[$name];
				}
			}

			$fields['input'] = $field;
		}

		$this->view->fields = $fields;
	}
}
