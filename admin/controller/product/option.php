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
use Vvveb\Sql\Option_valueSQL;
use Vvveb\Sql\OptionSQL;
use Vvveb\System\Images;

class Option extends Crud {
	protected string $type = 'option';

	protected string $controller = 'option';

	protected string $module = 'product';

	/**
	 * Save the option and its associated option values.
	 *
	 * @return void
	 */
	public function save(): void {
		$this->redirect = false;
		parent::save();

		$delete       = $this->request->post['delete']['option_value_id'] ?? [];
		$option_value = $this->request->post['option_value'] ?? [];
		$option	      = $this->request->post['option'] ?? [];
		$edit         = $this->request->get['option_id'] ?? false;
		$option_id    = $this->option_id;
		$new          = [];

		foreach ($option_value as $index => &$attr) {
			$attr['option_id']  = $option_id;
			$attr['sort_order'] ??= 0;

			if (!$attr['option_value_id'] && $attr['name']) {
				unset($attr['option_value_id']);
				$new[] = $attr;
				unset($option_value[$index]);
			}
		}

		if ($option_id) {
			$optionSQL         = new OptionSQL();
			$option_valueSQL   = new Option_valueSQL();

			if ($delete) {
				$option_valueSQL->delete(['option_value_id' => $delete] + $this->global);
			}

			if ($option_value) {
				foreach ($option_value as $index => &$attr) {
					$option_valueSQL->edit(['option_value' => $attr, 'option_value_id' => $attr['option_value_id']] + $this->global);
				}
			}

			if ($new) {
				foreach ($new as $index => &$attr) {
					$option_valueSQL->add(['option_value' => $attr] + $this->global);
				}
			}

			$result      = $optionSQL->edit(['option_id' => $option_id, 'option' => $option]);

			if ($result && isset($result['option_content'])) {
				$successMessage         = __('Saved!');
				$this->view->success[]  = $successMessage;

				if (!$edit) {
					$this->redirect(['module' => "{$this->module}/{$this->controller}", 'option_id' => $option_id]);
				}
			} else {
				$this->view->errors[] = __('Error saving!');
			}
		}

		$this->index();
	}

	/**
	 * Display the option edit form with associated option values.
	 *
	 * @return void
	 */
	public function index(): void {
		parent::index();
		$option_id = $this->request->get['option_id'] ?? false;

		$option_values = [];

		if ($option_id) {
			$option_valueGroup  = new Option_valueSQL();
			$option_values	     = $option_valueGroup->getAll(['option_id' => $option_id, 'limit' => 1000] + $this->global)['option_value'] ?? [];

			foreach ($option_values as &$value) {
				$value['image_url'] = Images::image($value['image'], 'product', 'thumb');
			}
		}

		$this->view->type = [
			'text'     => __('Text'),
			'number'   => __('Number'),
			'textarea' => __('Textarea'),
			'email'    => __('Email'),
			'select'   => __('Select'),
			'radio'    => __('Radio'),
			'checkbox' => __('Checkbox'),
			'image'    => __('Image'),
			'date'     => __('Date'),
			'time'     => __('Time'),
			'datetime' => __('Date time'),
			'file'     => __('File'),
		];

		$this->view->option_values         = $option_values;
		$this->view->option_id             = $option_id;
	}
}
