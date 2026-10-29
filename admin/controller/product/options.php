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
use Vvveb\Controller\Listing;
use Vvveb\Sql\Option_valueSQL;
use Vvveb\Sql\OptionSQL;
use function Vvveb\url;

class Options extends Listing {
	/** @var list<string> */
	protected ?array $additionalPermissionCheck = ['product/option/save'];

	protected string $type = 'option';

	protected string $controller = 'option';

	protected string $listController = 'options';

	protected string $list = 'option';

	protected string $module = 'product';

	/**
	 * Duplicate an option and its values.
	 *
	 * @return void
	 */
	public function duplicate(): void {
		$option_id = $this->request->post['option_id'] ?? false;

		$option_values = [];

		if ($option_id) {
			$new_option_id = 1;
			$optionSQL     = new OptionSQL();
			$option        = $optionSQL->get(['option_id' => $option_id] + $this->global) ?? [];
			$option['name'] .= ' [' . __('duplicate') . ']';
			unset($option['option_id'], $option['language_id']);
			$new_option_id = $optionSQL->add(['option' => $option] + $this->global)['option'] ?? null;

			if ($new_option_id) {
				$option_valueSQL    = new Option_valueSQL();
				$option_values	     = $option_valueSQL->getAll(['option_id' => $option_id, 'limit' => 1000] + $this->global)['option_value'] ?? [];

				foreach ($option_values as $index => &$attr) {
					unset($attr['option_value_id']);
					$attr['option_id']  = $new_option_id;
					$option_valueSQL->add(['option_value' => $attr] + $this->global);
				}

				$url = url(['module' => 'product/option', 'option_id' => $new_option_id]);

				$success = ucfirst($this->type) . ' ' . __('duplicated') . '!';
				$success .= sprintf(' <a href="%s">%s</a>', $url, __('Edit') . ' ' . $this->type);
				$this->view->success[] = $success;
			} else {
				$this->view->errors[] = sprintf(__('Error duplicating %s!'), $this->type);
			}
		}

		$this->index();
	}
}
