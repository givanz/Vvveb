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
use Vvveb\Sql\Option_ValueSQL;
use Vvveb\Sql\OptionSQL;
use Vvveb\System\Images;

class Option extends Crud {
	protected $type = 'option';

	protected $controller = 'option_value';

	protected $module = 'product';

	function save() {
		$delete		     = $this->request->post['delete']['option_value_id'] ?? [];
		$option_value = $this->request->post['option_value'] ?? [];
		$option	      = $this->request->post['option'] ?? [];
		$option_id    = $this->request->get['option_id'] ?? false;
		$new          = [];

		foreach ($option_value as $index => &$attr) {
			$attr['option_id']  = $option_id;
			$attr['sort_order'] = $attr['sort_order'] ?? 0;

			if (! $attr['option_value_id'] && $attr['name']) {
				unset($attr['option_value_id']);
				$new[] = $attr;
				unset($option_value[$index]);
			}
		}

		if ($option_id) {
			$optionSql         = new OptionSQL();
			$option_valueSql   = new Option_ValueSQL();

			if ($delete) {
				$option_valueSql->delete(['option_value_id' => $delete] + $this->global);
			}

			if ($option_value) {
				foreach ($option_value as $index => &$attr) {
					$option_valueSql->edit(['option_value' => $attr, 'option_value_id' => $attr['option_value_id']] + $this->global);
				}
			}

			if ($new) {
				foreach ($new as $index => &$attr) {
					$option_valueSql->add(['option_value' => $attr] + $this->global);
				}
			}

			$result      = $optionSql->edit(['option_id' => $option_id, 'option' => $option]);

			if ($result && isset($result['option_content'])) {
				//$successMessage        = __('Saved!');
				//$this->view->success[] = $successMessage;
				//$this->view->errors    = [];
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		parent::save();
	}

	function index() {
		parent::index();
		$option_id = $this->request->get['option_id'] ?? false;

		$option_values = [];

		if ($option_id) {
			$option_valueGroup  = new Option_ValueSQL();
			$option_values	     = $option_valueGroup->getAll(['option_id' => $option_id] + $this->global)['option_value'] ?? [];

			foreach ($option_values as &$value) {
				$value['image_url'] = Images::image($value['image'], 'product', 'thumb');
			}
		}

		$this->view->type = [
			'text'    => 'Text',
			'number'  => 'Number',
			'textarea'=> 'Textarea',
			'email'	  => 'Email',
			'select'  => 'Select',
			'radio'	  => 'Radio',
			'checkbox'=> 'Checkbox',
			'select'  => 'Select',
			'image'   => 'Image',
			'date'    => 'Date',
			'time'    => 'Time',
			'datetime'=> 'Date time',
			'file'    => 'File',
		];

		$this->view->option_values         = $option_values;
		$this->view->option_id             = $option_id;
	}
}