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

namespace Vvveb\System\Fields;

use function Vvveb\getFieldTypes;

class Field {
	const valueType = null; //single value, php array for complex json type

	protected static $defaultSettings = [
		'field_id' => [
			'label'        => 'Field id',
			'name'         => 'field_id',
			'type'         => 'hidden',
			'class'        => 'field-label',
		],
		'row' => [
			'name'  => 'row',
			'type'  => 'hidden',
			'value' => 0,
		],
		'sort_order' => [
			'name'  => 'sort_order',
			'type'  => 'hidden',
			'value' => 0,
		],
		'parent_id' => [
			'name'  => 'parent_id',
			'type'  => 'hidden',
			'value' => 0,
		],
		'type' => [
			'label'        => 'Type',
			//'instructions' => '',
			'name'           => 'type',
			'type'           => 'optgroup', /*
			'options'        => [
				'basic' => [
					'text'     => 'Text',
					'textarea' => 'Text Area',
					'number'   => 'Number',
					'range'    => 'Range',
					'email'    => 'Email',
					'url'      => 'Url',
					'password' => 'Password',
				],
				'content' => [
					'image'   => 'Image',
					'file'    => 'File',
					'wysiwyg' => 'Wysiwyg Editor',
					'oembed'  => 'oEmbed',
				],
			],*/
		],
		'label' => [
			'label'        => 'Label',
			'instructions' => 'Name on edit page',
			'name'         => 'label',
			'type'         => 'text',
		],
		'name' => [
			'label'        => 'Name',
			'instructions' => 'Input name',
			'name'         => 'name',
			'type'         => 'text',
		],
		'default' => [
			'label'        => 'Default Value',
			'instructions' => 'Default value for new post',
			'type'         => 'text',
			'name'         => 'default',
		],
		'placeholder' => [
			'label'        => 'Placeholder',
			'instructions' => '',
			'type'         => 'text',
			'name'         => 'placeholder',
		],
	];

	protected static $defaultValidation = [
		'required' => [
			'label'        => 'Required',
			'instructions' => '',
			'type'         => 'checkbox',
			'name'         => 'required',
			'class'        => 'field-required',
			'value'        => 'true',
		], /*
		'minimum' => 'number',
		'maximum' => 'number',*/
	];

	protected static $defaultPresentation = [
		'cols' => [
			'label'        => 'Columns',
			'type'         => 'select',
			'name'         => 'cols',
			'options'      => ['' => 'auto', 'col-md-1' => '1', 'col-md-2' => '2', 'col-md-3' => '3', 'col-md-4' => '4', 'col-md-5' => '5', 'col-md-6' => '6', 'col-md-7' => '7', 'col-md-8' => '8', 'col-md-9' => '9', 'col-md-10' => '10', 'col-md-11' => '11', 'col-md-12' => '12'],
			'rows'         => 5,
		],
		'instructions' => [
			'label'        => 'Instructions',
			'instructions' => 'Instructions for content editors. Shown when submitting data.',
			'type'         => 'textarea',
			'name'         => 'instructions',
			'rows'         => 5,
		],
	];

	function getSettings() {
		return $this->settings;
	}

	function getValidation() {
		return $this->validation;
	}

	function getPresentation() {
		return $this->presentation;
	}

	function extend() {
		//set only once for all fields
		if (! isset(self :: $defaultSettings['type']['options'])) {
			$fieldTypes = getFieldTypes();

			foreach ($fieldTypes as $group => &$fields) {
				foreach ($fields as $field => &$settings) {
					$fields[$field] = $settings['name'];
				}
			}

			self :: $defaultSettings['type']['options'] = $fieldTypes;
		}

		$this->settings     = array_merge(self :: $defaultSettings, $this->settings);
		$this->validation   = array_merge(self :: $defaultValidation, $this->validation);
		$this->presentation = array_merge(self :: $defaultPresentation, $this->presentation);
	}

	function __construct() {
		$this->extend();
	}
}
